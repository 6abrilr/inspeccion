<?php
// public/index.php — Dashboard con 3 bloques: Global, Sistema activo, Críticos (chips por área/subcarpeta)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/ui.php';

if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/* ===== Scopes ===== */
$SCOPES = [
  'ultima_inspeccion' => [
    'label'  => 'Última inspección - IGE',
    'route'  => 'ultima_inspeccion.php',
    'prefix' => 'ultima_inspeccion',
  ],
  'lista_de_control' => [
    'label'  => 'Lista de control - IGE',
    'route'  => 'lista_de_control.php',
    'prefix' => 'listas_control',
  ],
  'visitas_de_estado_mayor' => [
    'label'  => 'Visitas de Estado Mayor - IGE',
    'route'  => 'visitas_de_estado_mayor.php',
    'prefix' => 'visitas_de_estado_mayor',
  ],
];
$order = ['ultima_inspeccion','lista_de_control','visitas_de_estado_mayor'];

$scope = $_GET['scope'] ?? 'ultima_inspeccion';
if (!isset($SCOPES[$scope])) $scope = 'ultima_inspeccion';
$ACTIVE = $SCOPES[$scope];
$PREFIX = $ACTIVE['prefix'];

/* ===== Rutas de assets ===== */
$PUBLIC_URL = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'])), '/');
$APP_URL    = rtrim(str_replace('\\','/', dirname($PUBLIC_URL)), '/');
$ASSETS_URL = ($APP_URL === '' ? '' : $APP_URL) . '/assets';

$IMG_BG = $ASSETS_URL . '/img/fondo.png';
$ESCUDO = $ASSETS_URL . '/img/escudo_bcom602.png';

/* ===== URL de documentos (según scope) ===== */
if ($scope === 'lista_de_control') {
  $DOC_URL = $PUBLIC_URL . '/' . $ACTIVE['route'] . '?area=S1';
} else {
  $DOC_URL = $PUBLIC_URL . '/' . $ACTIVE['route'];
}

/* ===== Helpers KPI ===== */
function kpi_from_like(PDO $pdo, string $like): array {
  $qTot = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE file_rel LIKE ?");
  $qTot->execute([$like]);
  $tot = (int)($qTot->fetchColumn() ?: 0);

  $st = $pdo->prepare("
    SELECT
      SUM(estado='si')     AS si,
      SUM(estado='no')     AS no,
      SUM(estado IS NULL)  AS nr
    FROM checklist
    WHERE file_rel LIKE ?
  ");
  $st->execute([$like]);
  $r  = $st->fetch(PDO::FETCH_ASSOC) ?: ['si'=>0,'no'=>0,'nr'=>0];
  $si = (int)$r['si']; $no=(int)$r['no']; $nr=(int)$r['nr'];

  $cumplidos = $si;
  $pend      = max(0, $tot - $cumplidos);
  $porc      = $tot ? round($cumplidos * 100.0 / $tot, 1) : 0.0;
  return compact('tot','cumplidos','pend','porc','si','no','nr');
}

/* Global (todos los sistemas) */
function kpi_global_all(PDO $pdo): array {
  $likes = [
    "storage/ultima_inspeccion/%",
    "storage/listas_control/%",
    "storage/visitas_de_estado_mayor/%",
  ];
  $tot=$cum=0;
  foreach($likes as $lk){
    $k = kpi_from_like($pdo, $lk);
    $tot += $k['tot']; $cum += $k['cumplidos'];
  }
  $pend = max(0, $tot-$cum);
  $porc = $tot ? round($cum*100.0/$tot,1) : 0.0;
  return ['tot'=>$tot,'cumplidos'=>$cum,'pend'=>$pend,'porc'=>$porc];
}

/* Sistema activo */
$g_all    = kpi_global_all($pdo);
$g_scope  = kpi_from_like($pdo, "storage/{$PREFIX}/%");
$tot_controles = $g_scope['tot'];
$tot_cumplidos = $g_scope['cumplidos'];
$tot_pend      = $g_scope['pend'];
$porc_scope    = $g_scope['porc'];

/* ===== Cumplimiento por área (sólo aplica a Listas) ===== */
function kpi_por_area(PDO $pdo, string $prefix): array {
  // Siempre devuelve S1..S4 aunque haya 0 filas, para que no falten S2/S4
  $base = ['S1'=>['controles'=>0,'cumplidos'=>0,'pendientes'=>0,'porc'=>0.0],
           'S2'=>['controles'=>0,'cumplidos'=>0,'pendientes'=>0,'porc'=>0.0],
           'S3'=>['controles'=>0,'cumplidos'=>0,'pendientes'=>0,'porc'=>0.0],
           'S4'=>['controles'=>0,'cumplidos'=>0,'pendientes'=>0,'porc'=>0.0]];
  $sql = "
    SELECT
      CASE
        WHEN file_rel LIKE 'storage/{$prefix}/S1/%' THEN 'S1'
        WHEN file_rel LIKE 'storage/{$prefix}/S2/%' THEN 'S2'
        WHEN file_rel LIKE 'storage/{$prefix}/S3/%' THEN 'S3'
        WHEN file_rel LIKE 'storage/{$prefix}/S4/%' THEN 'S4'
      END AS area,
      COUNT(*) total,
      SUM(estado='si') si
    FROM checklist
    WHERE file_rel LIKE 'storage/{$prefix}/%'
    GROUP BY area
  ";
  foreach ($pdo->query($sql) as $r){
    $a = $r['area'];
    if (!$a) continue;
    $t = (int)$r['total']; $si=(int)$r['si'];
    $base[$a] = [
      'controles'=>$t,
      'cumplidos'=>$si,
      'pendientes'=>max(0,$t-$si),
      'porc'=>$t?round($si*100.0/$t,1):0.0
    ];
  }
  return $base;
}

/* ===== Chips de “Tareas críticas” por área/subcarpeta =====
   - En Listas => S1..S4 con % y link a lista_de_control.php?area=SX
   - En Última/Visitas => subcarpetas reales (token 3 del path) con % y link a su página (?sub=...)
*/
function chips_criticos(PDO $pdo, string $scope, array $SCOPES): array {
  $prefix = $SCOPES[$scope]['prefix'];
  if ($scope === 'lista_de_control') {
    $stats = kpi_por_area($pdo, $prefix);
    $out = [];
    foreach(['S1','S2','S3','S4'] as $a){
      $p = $stats[$a]['porc'];
      $out[] = [
        'label' => $a,
        'pct'   => $p,
        'count' => $stats[$a]['pendientes'],
        'href'  => 'lista_de_control.php?area='.$a
      ];
    }
    return $out;
  }

  // Última inspección o Visitas => token 3 del path
  $rows = [];
  $sql = "
    SELECT
      SUBSTRING_INDEX(SUBSTRING_INDEX(file_rel,'/',3), '/', -1) AS sub,
      COUNT(*) total,
      SUM(estado='si') si
    FROM checklist
    WHERE file_rel LIKE 'storage/{$prefix}/%'
    GROUP BY sub
    ORDER BY sub
  ";
  foreach($pdo->query($sql) as $r){
    $tot = (int)$r['total']; $si=(int)$r['si'];
    $pct = $tot ? round($si*100.0/$tot,1) : 0.0;
    $sub = (string)$r['sub'];
    $href = $SCOPES[$scope]['route'].'?sub='.rawurlencode($sub);
    $rows[] = ['label'=>$sub, 'pct'=>$pct, 'count'=>max(0,$tot-$si), 'href'=>$href];
  }
  return $rows;
}

$chips = chips_criticos($pdo, $scope, $SCOPES);

/* ===== Datos para barras por área (si estás en Listas) ===== */
$uses_areas = ($scope === 'lista_de_control');
$table_rows = [];
if ($uses_areas) {
  $areas_stats = kpi_por_area($pdo, 'listas_control');
  foreach (['S1','S2','S3','S4'] as $ax) {
    $st = $areas_stats[$ax];
    $table_rows[] = [
      'label'      => $ax,
      'controles'  => $st['controles'],
      'cumplidos'  => $st['cumplidos'],
      'pendientes' => $st['pendientes'],
      'porc'       => $st['porc'],
      'href'       => $SCOPES['lista_de_control']['route'].'?area='.$ax
    ];
  }
} else {
  // Única barra para subcarpeta genérica del sistema activo
  $label = ($scope === 'ultima_inspeccion') ? 'Observaciones' : 'Visitas';
  $like = "storage/{$PREFIX}/%";
  $k = kpi_from_like($pdo, $like);
  $table_rows[] = [
    'label'      => $label,
    'controles'  => $k['tot'],
    'cumplidos'  => $k['cumplidos'],
    'pendientes' => $k['pend'],
    'porc'       => $k['porc'],
    'href'       => $SCOPES[$scope]['route']
  ];
}

ui_header('PRESENTACION IGE', ['container'=>'xl', 'show_brand'=>false]);
?>
<link rel="stylesheet" href="./assets/css/theme-602.css">
<style>
  body{
    background: url("<?= e($IMG_BG) ?>") no-repeat center center fixed;
    background-size: cover;
    background-attachment: fixed;
    background-color:#0f1117; color:#e9eef5; margin:0; padding:0;
    font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,sans-serif;
  }
  :root{ --ok:#16a34a; --warn:#f59e0b; --bad:#ef4444; --mut:#9aa4b2; }
  .grid{ display:grid; gap:14px; grid-template-columns: repeat(12, 1fr); }
  .panel{
    background: rgba(15,17,23,.88); border:1px solid rgba(255,255,255,.1);
    border-radius:16px; padding:14px; backdrop-filter: blur(8px);
    box-shadow:0 10px 24px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.05);
    transition: transform .06s ease, box-shadow .06s ease;
  }
  .panel.link{ cursor:pointer; }
  .panel.link:hover{ transform: translateY(-1px); box-shadow:0 14px 28px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.06); }

  .title{ font-weight:800; font-size:1.05rem; margin:0 0 .4rem 0 }
  .muted{ color:var(--mut) }

  .tabs{ display:flex; gap:10px; flex-wrap:wrap; margin:12px 0 18px }
  .tab{
    border:1px solid rgba(255,255,255,.18);
    background:rgba(15,17,23,.65);
    color:#e9eef5;
    padding:.6rem 1.2rem;
    border-radius:999px;
    font-weight:900;
    font-size:1rem;
    text-decoration:none;
    letter-spacing:.01em;
  }
  .tab:hover{ background:rgba(255,255,255,.10) }
  .tab.active{ background:#16a34a; border-color:#16a34a; color:#08140c; }

  .gauge{ --p:0; width:220px; height:220px; border-radius:999px;
    background: radial-gradient(closest-side,#0e1116 76%,transparent 0 99.9%,#0e1116 0),
               conic-gradient(var(--ok) calc(var(--p)*1%), #3a3f4a 0);
    display:grid; place-items:center; margin:auto;
    box-shadow: inset 0 0 0 10px #0f141c, 0 0 0 1px #2b3140, 0 10px 30px rgba(0,0,0,.35);
  }
  .gauge>.v{ font-weight:900; font-size:2.6rem; letter-spacing:.02em }

  .gauge-sm{ --p:0; width:180px; height:180px; border-radius:999px;
    background: radial-gradient(closest-side,#0e1116 72%,transparent 0 99.9%,#0e1116 0),
               conic-gradient(var(--ok) calc(var(--p)*1%), #3a3f4a 0);
    display:grid; place-items:center; margin:auto;
    box-shadow: inset 0 0 0 8px #0f141c, 0 0 0 1px #2b3140;
  }
  .gauge-sm .v{ font-weight:900; font-size:2.0rem }

  .chip{ display:inline-flex; align-items:center; gap:.5rem; padding:.45rem .7rem;
    border:1px solid rgba(255,255,255,.18); border-radius:999px; background:#0f1722; margin:.22rem;
    text-decoration:none; color:#e9eef5; font-weight:800; }
  .chip .pct{ opacity:.8; font-weight:800 }
  .chip:hover{ background:#152033 }

  .bar{ display:flex; align-items:center; gap:.6rem; }
  .bar .label{ width:200px; color:#e6f4ea; font-weight:700 }
  .bar .track{ flex:1; height:12px; background:#1b222c; border-radius:999px; overflow:hidden; border:1px solid #2a3140 }
  .bar .fill{ height:100%; background:linear-gradient(90deg,#1cd259,#15a34a) }
  .bar .pct{ width:60px; text-align:right; color:#bfe8cb; font-weight:800 }

  table.tbl{ width:100%; border-collapse:collapse }
  .tbl th,.tbl td{ padding:.6rem .7rem; border-bottom:1px solid rgba(255,255,255,.08) }
  .tbl th{ font-weight:800; color:#d7e7dc }

  .btn-acc{ background:#16a34a; color:#fff; border:none; border-radius:12px; font-weight:800; padding:.45rem .9rem }
  .btn-acc:hover{ background:#22c55e }
</style>

<header class="brand-hero">
  <div class="hero-inner container">
    <img class="brand-logo" src="<?= e($ESCUDO) ?>" alt="Escudo 602">
    <div>
      <div class="brand-title">Batallón de Comunicaciones 602</div>
      <div class="brand-sub">“Hogar de las Comunicaciones Fijas del Ejército”</div>
    </div>
  </div>
</header>

<div class="container">
  <nav class="tabs" aria-label="Filtro">
    <?php foreach ($order as $key):
      $active = $key===$scope ? 'active' : '';
      $url = 'index.php?scope='.urlencode($key); ?>
      <a class="tab <?= $active ?>" href="<?= e($url) ?>"><?= e($SCOPES[$key]['label']) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<div class="container">
  <div class="grid">

    <!-- 1) Cumplimiento Global — TODOS LOS SISTEMAS -->
    <div class="panel" style="grid-column: span 4;">
      <h3 class="title">Cumplimiento Global — Todos los sistemas</h3>
      <div class="gauge" style="--p: <?= (float)$g_all['porc'] ?>;"><div class="v"><?= (int)$g_all['porc'] ?>%</div></div>
      <div class="mt-3 text-center muted">
        Controles: <b><?= $g_all['tot'] ?></b> · Cumplidos: <b><?= $g_all['cumplidos'] ?></b> · Pendientes: <b><?= $g_all['pend'] ?></b>
      </div>
    </div>

    <!-- 2) Cumplimiento del SISTEMA ACTIVO (sólo 1 gauge) -->
    <div class="panel link" data-href="<?= e($DOC_URL) ?>" style="grid-column: span 5;">
      <h3 class="title">Cumplimiento — <?= e($ACTIVE['label']) ?> (activo)</h3>
      <div class="gauge-sm" style="--p: <?= (float)$porc_scope ?>;"><div class="v"><?= (float)$porc_scope ?>%</div></div>
      <div class="mt-3 text-center muted">
        Controles: <b><?= $tot_controles ?></b> · Cumplidos: <b><?= $tot_cumplidos ?></b> · Pendientes: <b><?= $tot_pend ?></b>
      </div>
    </div>

    <!-- 3) “Tareas críticas”: chips por Área/Subcarpeta (link a ingresar/editar) -->
    <div class="panel" style="grid-column: span 3;">
      <h3 class="title">Tareas Críticas — <?= e($ACTIVE['label']) ?></h3>
      <?php if (empty($chips)): ?>
        <div class="muted">No hay datos para este sistema.</div>
      <?php else: ?>
        <div>
          <?php foreach($chips as $c): ?>
            <a class="chip" href="<?= e($c['href']) ?>" title="Pend: <?= (int)$c['count'] ?>">
              <span><?= e($c['label'] ?: '—') ?></span>
              <span class="pct">(<?= (float)$c['pct'] ?>%)</span>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="mt-3">
          <a class="btn-acc btn btn-sm" href="<?= e($DOC_URL) ?>">Ingresar / Editar</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Barras por área / única barra según scope -->
    <div class="panel link" data-href="<?= e($DOC_URL) ?>" style="grid-column: span 7;">
      <h3 class="title">Cumplimiento por Área</h3>
      <?php if ($uses_areas): ?>
        <div class="d-flex flex-column gap-2">
          <?php
            $aliases=['S1'=>'Personal (S-1)','S2'=>'Inteligencia (S-2)','S3'=>'Operaciones (S-3)','S4'=>'Material (S-4)'];
            foreach($table_rows as $r):
              $pct = $r['porc'];
              $sig = ($pct>=90?'#16a34a':($pct>=75?'#f59e0b':'#ef4444'));
          ?>
          <div class="bar">
            <div class="label"><?= e($aliases[$r['label']] ?? $r['label']) ?></div>
            <div class="track"><div class="fill" style="width:<?= (float)$pct ?>%"></div></div>
            <div class="pct" style="color:<?= $sig ?>"><?= $pct ?>%</div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?php $r=$table_rows[0] ?? ['label'=>'Observaciones','porc'=>0]; $pct=$r['porc']; ?>
        <div class="d-flex flex-column gap-2">
          <div class="bar">
            <div class="label"><?= e($r['label']) ?></div>
            <div class="track"><div class="fill" style="width:<?= (float)$pct ?>%"></div></div>
            <div class="pct"><?= $pct ?>%</div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tabla resumen inferior -->
    <div class="panel" style="grid-column: span 12;">
      <h3 class="title">Porcentaje de tareas realizadas — <?= e($ACTIVE['label']) ?></h3>
      <div class="table-responsive">
        <table class="tbl">
          <thead>
            <tr>
              <th><?= $uses_areas ? 'Área' : 'Documento' ?></th>
              <th class="text-end">Controles</th>
              <th class="text-end">Cumplidos</th>
              <th class="text-end">Pendientes</th>
              <th style="width:300px">Progreso</th>
              <th class="text-end">%</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($table_rows as $r): $pct=$r['porc']; ?>
            <tr>
              <td><b><?= e($r['label']) ?></b></td>
              <td class="text-end"><?= (int)$r['controles'] ?></td>
              <td class="text-end"><?= (int)$r['cumplidos'] ?></td>
              <td class="text-end"><?= (int)$r['pendientes'] ?></td>
              <td>
                <div class="bar">
                  <div class="track"><div class="fill" style="width:<?= (float)$pct ?>%"></div></div>
                  <div class="pct"><?= $pct ?>%</div>
                </div>
              </td>
              <td class="text-end"><b><?= $pct ?>%</b></td>
              <td class="text-center">
                <a class="btn-acc btn btn-sm" href="<?= e($r['href']) ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
            <tr>
              <td><b>Total</b></td>
              <td class="text-end"><?= $tot_controles ?></td>
              <td class="text-end"><?= $tot_cumplidos ?></td>
              <td class="text-end"><?= $tot_pend ?></td>
              <td>
                <div class="bar">
                  <div class="track"><div class="fill" style="width:<?= (float)$porc_scope ?>%"></div></div>
                  <div class="pct"><?= $porc_scope ?>%</div>
                </div>
              </td>
              <td class="text-end"><b><?= $porc_scope ?>%</b></td>
              <td class="text-center">
                <a class="btn-acc btn btn-sm" href="<?= e($DOC_URL) ?>">Editar</a>
              </td>
            </tr>
          </tbody>
        </table>
       </div>
    </div>

  </div>
</div>

<script>
  // Click en paneles superiores para ir a documentos
  document.querySelectorAll('.panel.link').forEach(el=>{
    el.addEventListener('click', ()=>{
      const href = el.getAttribute('data-href');
      if (href) window.location.href = href;
    });
  });
</script>

<?php ui_footer(); ?>
