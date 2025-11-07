<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/ui.php';

if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/* KPIs globales */
$tot_controles = (int)$pdo->query("
  SELECT COUNT(*) c
  FROM items i
  JOIN documentos d ON d.id = i.documento_id
  JOIN areas a ON a.id = d.area_id
")->fetch()['c'];

$tot_cumplidos = (int)$pdo->query("
  SELECT COUNT(DISTINCT r.item_id) c
  FROM respuestas r
  WHERE r.estado = 'si'
")->fetch()['c'];

$tot_pend = max(0, $tot_controles - $tot_cumplidos);
$porc = $tot_controles ? round($tot_cumplidos * 100.0 / $tot_controles, 1) : 0;

/* URLs Grafana (ejemplo) */
$grafana_global = 'http://localhost:3000/d/xxxxx/inspeccion?orgId=1&viewPanel=1&kiosk';
$grafana_s1     = 'http://localhost:3000/d/xxxxx/inspeccion?orgId=1&viewPanel=2&kiosk';
$grafana_s2     = 'http://localhost:3000/d/xxxxx/inspeccion?orgId=1&viewPanel=3&kiosk';
$grafana_s3     = 'http://localhost:3000/d/xxxxx/inspeccion?orgId=1&viewPanel=4&kiosk';
$grafana_s4     = 'http://localhost:3000/d/xxxxx/inspeccion?orgId=1&viewPanel=5&kiosk';

ui_header('Dashboard de Inspección', ['container'=>'xl', 'show_brand'=>true]);
?>
  <style>
    /* === Nueva paleta (grafito + esmeralda) === */
    body{ background: radial-gradient(1200px 600px at 10% -10%, #151922 0, transparent 40%) , #0f1117 !important; color:#e9eef5; }
    .app-card{ background: rgba(255,255,255,.06); border-radius:16px; backdrop-filter: blur(6px); border:1px solid rgba(255,255,255,.08); }
    .btn-acc{ background:#16a34a; color:#fff; border:none; border-radius:12px; font-weight:700; padding:.5rem .9rem }
    .btn-acc:hover{ background:#22c55e }
    .badge-ok{ background:#052e1b; color:#22c55e; border:1px solid #14532d; border-radius:10px; }
    .badge-warn{ background:#2a1a00; color:#fbbf24; border:1px solid #b45309; border-radius:10px; }
    iframe{ background:#0b0e13; border-radius:12px }
  </style>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="m-0" style="font-weight:900;">Dashboard de Inspección</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-acc" href="documentos.php">Ver documentos por área</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="app-card p-3">
        <div class="small text-uppercase" style="letter-spacing:.08em;opacity:.8">Total controles</div>
        <h2 class="m-0"><?= $tot_controles ?></h2>
        <div class="small" style="opacity:.7">Ítems de checklist</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="app-card p-3">
        <div class="small text-uppercase" style="letter-spacing:.08em;opacity:.8">Cumplidos</div>
        <h2 class="m-0"><?= $tot_cumplidos ?></h2>
        <div class="small" style="opacity:.7">Con al menos una respuesta “sí”</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="app-card p-3">
        <div class="small text-uppercase" style="letter-spacing:.08em;opacity:.8">Pendientes</div>
        <h2 class="m-0"><?= $tot_pend ?></h2>
        <div class="small" style="opacity:.7">Sin respuesta “sí”</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="app-card p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="small text-uppercase" style="letter-spacing:.08em;opacity:.8">Porcentaje global</div>
            <h2 class="m-0"><?= $porc ?>%</h2>
          </div>
          <span class="p-2 <?= $porc>=80?'badge-ok':'badge-warn' ?>">
            <?= $porc>=80?'Objetivo':'A mejorar' ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Paneles Grafana -->
  <h2 class="mt-4" style="font-weight:800;">Paneles Grafana</h2>
  <div class="row g-3">
    <div class="col-12 col-xl-6">
      <div class="app-card p-2">
        <h5 class="px-2 pt-2 m-0">Global</h5>
        <iframe class="w-100" style="height:420px;border:0" src="<?= e($grafana_global) ?>"></iframe>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-6">
      <div class="app-card p-2">
        <h5 class="px-2 pt-2 m-0">S1</h5>
        <iframe class="w-100" style="height:420px;border:0" src="<?= e($grafana_s1) ?>"></iframe>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-6">
      <div class="app-card p-2">
        <h5 class="px-2 pt-2 m-0">S2</h5>
        <iframe class="w-100" style="height:420px;border:0" src="<?= e($grafana_s2) ?>"></iframe>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-6">
      <div class="app-card p-2">
        <h5 class="px-2 pt-2 m-0">S3</h5>
        <iframe class="w-100" style="height:420px;border:0" src="<?= e($grafana_s3) ?>"></iframe>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-6">
      <div class="app-card p-2">
        <h5 class="px-2 pt-2 m-0">S4</h5>
        <iframe class="w-100" style="height:420px;border:0" src="<?= e($grafana_s4) ?>"></iframe>
      </div>
    </div>
  </div>

<?php ui_footer(); ?>
