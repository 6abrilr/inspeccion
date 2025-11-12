<?php
// public/index.php — Dashboard con filtro (Última / Visitas / Listas) y rutas separadas
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

/* ===== Rutas de assets (resuelve correctamente desde /public) ===== */
$PUBLIC_URL = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'])), '/');
$APP_URL    = rtrim(str_replace('\\','/', dirname($PUBLIC_URL)), '/');
$ASSETS_URL = ($APP_URL === '' ? '' : $APP_URL) . '/assets';

$IMG_BG = $ASSETS_URL . '/img/fondo.png';
$ESCUDO = $ASSETS_URL . '/img/escudo_bcom602.png';

/* ===== URL de documentos (según scope) ===== */
if ($scope === 'lista_de_control') {
  $DOC_URL = $PUBLIC_URL . '/' . $ACTIVE['route'] . '?area=S1';
} else {
  // Última inspección y Visitas no usan áreas S1..S4
  $DOC_URL = $PUBLIC_URL . '/' . $ACTIVE['route'];
}

/* ===== Helpers KPI ===== */
function kpi_global(PDO $pdo, string $prefix): array {
  $like = "storage/{$prefix}/%";
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

function kpi_por_area(PDO $pdo, string $prefix): array {
  $out = []; $areas = ['S1','S2','S3','S4'];
  foreach ($areas as $ax) {
    $like = "storage/{$prefix}/{$ax}/%";
    $q1 = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE file_rel LIKE ?");
    $q2 = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE estado='si' AND file_rel LIKE ?");
    $q1->execute([$like]); $q2->execute([$like]);
    $cnt = (int)($q1->fetchColumn() ?: 0);
    $cum = (int)($q2->fetchColumn() ?: 0);
    $out[$ax] = [
      'controles'=>$cnt,
      'cumplidos'=>$cum,
      'pendientes'=>max(0,$cnt-$cum),
      'porc'=>$cnt?round($cum*100.0/$cnt,1):0.0
    ];
  }
  return $out;
}

/* Variante “single” (sin S1..S4) para Última inspección y Visitas */
function kpi_single(PDO $pdo, string $prefix, string $label): array {
  $like = "storage/{$prefix}/%";
  $q1 = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE file_rel LIKE ?");
  $q2 = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE estado='si' AND file_rel LIKE ?");
  $q1->execute([$like]); $q2->execute([$like]);
  $cnt = (int)($q1->fetchColumn() ?: 0);
  $cum = (int)($q2->fetchColumn() ?: 0);
  return [
    'rows' => [[
      'label'      => $label,
      'controles'  => $cnt,
      'cumplidos'  => $cum,
      'pendientes' => max(0,$cnt-$cum),
      'porc'       => $cnt?round($cum*100.0/$cnt,1):0.0
    ]]
  ];
}

/* ===== Cálculos ===== */
$g = kpi_global($pdo, $PREFIX);
$tot_controles = $g['tot'];
$tot_cumplidos = $g['cumplidos'];
$tot_pend      = $g['pend'];
$porc          = $g['porc'];

/* Data rows para la tabla inferior (depende del scope) */
$table_rows = [];
$uses_areas = ($scope === 'lista_de_control');

if ($uses_areas) {
  $areas_stats = kpi_por_area($pdo, $PREFIX);
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
  // Única fila: “Observaciones” para Última inspección, y una genérica para Visitas
  $label = ($scope === 'ultima_inspeccion') ? 'Observaciones' : 'Visitas';
  $single = kpi_single($pdo, $PREFIX, $label);
  foreach ($single['rows'] as $r) {
    $table_rows[] = [
      'label'      => $r['label'],
      'controles'  => $r['controles'],
      'cumplidos'  => $r['cumplidos'],
      'pendientes' => $r['pendientes'],
      'porc'       => $r['porc'],
      'href'       => $SCOPES[$scope]['route'] // sin ?area
    ];
  }
}

/* ===== Grafana (embed opcional) ===== */
$grafana_line = 'http://localhost:3000/d/adfnc82/porcentaje-de-tareas-realizadas?orgId=1&from=now-30d&to=now&viewPanel=panel-1';
$grafana_pie  = 'http://localhost:3000/d/adfnc82/porcentaje-de-tareas-realizadas?orgId=1&from=now-30d&to=now&viewPanel=panel-3';

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

  /* Tabs filtro — MÁS GRANDES */
  .tabs{ display:flex; gap:10px; flex-wrap:wrap; margin:12px 0 18px }
  .tab{
    border:1px solid rgba(255,255,255,.18);
    background:rgba(15,17,23,.65);
    color:#e9eef5;
    padding:.6rem 1.2rem;              /* <-- más grande */
    border-radius:999px;
    font-weight:900;
    font-size:1rem;                     /* <-- más grande */
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
  .g-cap{ text-align:center; margin-top:.5rem; font-weight:700; color:#d6ffe1 }

  .bar{ display:flex; align-items:center; gap:.6rem; }
  .bar .label{ width:200px; color:#e6f4ea; font-weight:700 }
  .bar .track{ flex:1; height:12px; background:#1b222c; border-radius:999px; overflow:hidden; border:1px solid #2a3140 }
  .bar .fill{ height:100%; background:linear-gradient(90deg,#1cd259,#15a34a) }
  .bar .pct{ width:60px; text-align:right; color:#bfe8cb; font-weight:800 }
  .sig{ width:10px; height:10px; border-radius:999px }
  .sig.ok{ background:#16a34a } .sig.warn{ background:#f59e0b } .sig.bad{ background:#ef4444 }

  table.tbl{ width:100%; border-collapse:collapse }
  .tbl th,.tbl td{ padding:.6rem .7rem; border-bottom:1px solid rgba(255,255,255,.08) }
  .tbl th{ font-weight:800; color:#d7e7dc }

  iframe{ background:#0b0e13; border-radius:12px; width:100%; height:380px; border:0 }
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
    <div class="brand-year"><?= date('Y') ?></div>
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

    <div class="panel link" data-href="<?= e($DOC_URL) ?>" style="grid-column: span 4;">
      <h3 class="title">Cumplimiento Global — <?= e($ACTIVE['label']) ?></h3>
      <div class="gauge" style="--p: <?= (float)$porc ?>;"><div class="v"><?= (int)$porc ?>%</div></div>
      <div class="g-cap">Objetivo ≥ 90%</div>
      <div class="mt-3 text-center muted">
        Controles: <b><?= $tot_controles ?></b> · Cumplidos: <b><?= $tot_cumplidos ?></b> · Pendientes: <b><?= $tot_pend ?></b>
      </div>
    </div>

    <div class="panel link" data-href="<?= e($DOC_URL) ?>" style="grid-column: span 5;">
      <h3 class="title">Cumplimiento por Área</h3>
      <?php if ($uses_areas): ?>
        <div class="d-flex flex-column gap-2">
          <?php
            $aliases=['S1'=>'Personal (S-1)','S2'=>'Inteligencia (S-2)','S3'=>'Operaciones (S-3)','S4'=>'Material (S-4)'];
            foreach($table_rows as $r):
              $pct = $r['porc'];
              $sig = ($pct>=90?'ok':($pct>=75?'warn':'bad'));
          ?>
          <div class="bar">
            <div class="label"><?= e($aliases[$r['label']] ?? $r['label']) ?></div>
            <div class="sig <?= $sig ?>"></div>
            <div class="track"><div class="fill" style="width:<?= (float)$pct ?>%"></div></div>
            <div class="pct"><?= $pct ?>%</div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3 muted" style="font-size:.9rem">
          <span class="sig ok"></span> OK (≥90%) &nbsp; <span class="sig warn"></span> Atención (75–89%) &nbsp; <span class="sig bad"></span> Crítico (&lt;75%)
        </div>
      <?php else: ?>
        <!-- Cuando no hay S1..S4, mostramos una única barra (p.ej., “Observaciones”) -->
        <?php $r=$table_rows[0] ?? ['label'=>'Observaciones','porc'=>0]; $pct=$r['porc']; ?>
        <div class="d-flex flex-column gap-2">
          <div class="bar">
            <div class="label"><?= e($r['label']) ?></div>
            <div class="sig <?= ($pct>=90?'ok':($pct>=75?'warn':'bad')) ?>"></div>
            <div class="track"><div class="fill" style="width:<?= (float)$pct ?>%"></div></div>
            <div class="pct"><?= $pct ?>%</div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel" style="grid-column: span 3;">
      <h3 class="title">Temas Críticos</h3>
      <div class="d-flex flex-column gap-2">
        <div style="background:#2a1113; border-radius:8px; padding:.5rem .7rem;">⚠️ Falta de documentación en S-4</div>
        <div style="background:#2a1a00; border-radius:8px; padding:.5rem .7rem;">📅 Vencimiento de licencias (S-1)</div>
        <div style="background:#052e1b; border-radius:8px; padding:.5rem .7rem;">✅ Capacitación S-2 completada</div>
      </div>
      <div class="mt-3">
        <a class="btn-acc btn btn-sm" href="<?= e($DOC_URL) ?>">Ver documentos</a>
      </div>
    </div>

    <div class="panel" style="grid-column: span 7;">
      <h3 class="title">Tendencia Histórica — <?= e($ACTIVE['label']) ?></h3>
      <iframe src="<?= e($grafana_line) ?>"></iframe>
    </div>

    <div class="panel" style="grid-column: span 5;">
      <h3 class="title">Distribución por Categoría</h3>
      <iframe src="<?= e($grafana_pie) ?>"></iframe>
    </div>

    <!-- === TABLA DE ABAJO: fuente de progreso + Acciones (Editar) === -->
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
                  <div class="track"><div class="fill" style="width:<?= (float)$porc ?>%"></div></div>
                  <div class="pct"><?= $porc ?>%</div>
                </div>
              </td>
              <td class="text-end"><b><?= $porc ?>%</b></td>
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
