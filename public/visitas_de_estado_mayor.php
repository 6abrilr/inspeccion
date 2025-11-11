<?php
// public/visitas_de_estado_mayor.php — Listado de XLSX dentro de /storage/visitas_de_estado_mayor
require_once __DIR__ . '/../config/db.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function human_size($bytes){
  $u=['B','KB','MB','GB']; $i=0; while($bytes>=1024 && $i<count($u)-1){ $bytes/=1024; $i++; }
  return round($bytes,1).' '.$u[$i];
}

/* ===== Config ===== */
const BASE_PREFIX = 'visitas_de_estado_mayor';
$projectBase = realpath(__DIR__ . '/..');
$baseDir     = realpath($projectBase . '/storage/' . BASE_PREFIX);
if(!$baseDir){ http_response_code(404); echo "No existe /storage/".BASE_PREFIX; exit; }

/* Subcarpeta (primer nivel) opcional: por ejemplo Años/Meses */
$sub = $_GET['sub'] ?? '';
$sub = trim(str_replace(['..','\\'], ['','/'], $sub), '/');
$root = $baseDir;
if ($sub !== '') {
  $try = realpath($baseDir . '/' . $sub);
  if ($try && str_starts_with($try, $baseDir)) $root = $try;
}

/* Tabs de primer nivel */
$tabs = ['' => '(Todos)'];
$it = new DirectoryIterator($baseDir);
foreach ($it as $entry) {
  if($entry->isDot()) continue;
  if($entry->isDir()) $tabs[$entry->getFilename()] = $entry->getFilename();
}

/* ===== Recorrer archivos XLSX ===== */
$rows=[];
$rii = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST
);
foreach($rii as $f){
  if(!$f->isFile()) continue;
  if(strtolower($f->getExtension())!=='xlsx') continue;
  $abs = $f->getPathname();
  $rel = str_replace('\\','/', substr($abs, strlen($projectBase)+1)); // relativo al proyecto
  $subPath = str_replace('\\','/', substr($abs, strlen($root)+1));
  $loc = dirname($subPath);
  if($loc === '.' || $loc === DIRECTORY_SEPARATOR) $loc = '';
  $rows[] = [
    'ext'=>'XLSX','name'=>$f->getFilename(),'rel'=>$rel,
    'loc'=>($sub ? $sub.'/' : '').($loc && $loc!=='/' ? $loc.'/' : ''),
    'size'=>$f->getSize(),'mtime'=>$f->getMTime(),
  ];
}
usort($rows, fn($a,$b)=>strcasecmp($a['loc'].$a['name'], $b['loc'].$b['name']));

/* % por archivo en checklist */
$stTot = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE file_rel = ?");
$stOk  = $pdo->prepare("SELECT COUNT(*) FROM checklist WHERE file_rel = ? AND estado='si'");
function pct_class($p){ return $p>=90?'ok':($p>=75?'warn':'bad'); }

/* ===== Rutas de assets como en index.php ===== */
$PUBLIC_URL = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'])), '/');
$APP_URL    = rtrim(str_replace('\\','/', dirname($PUBLIC_URL)), '/');
$ASSETS_URL = ($APP_URL === '' ? '' : $APP_URL) . '/assets';
$IMG_BG     = $ASSETS_URL . '/img/fondo.png';
$ESCUDO     = $ASSETS_URL . '/img/escudo_bcom602.png';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Batallón de Comunicaciones 602 – Visitas de Estado Mayor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme-602.css">
<style>
  /* === mismo fondo y formato de tabla que lista_de_control === */
  body{
    background: url("<?= e($IMG_BG) ?>") no-repeat center center fixed;
    background-size: cover;
    background-attachment: fixed;
    background-color:#0f1117; color:#e9eef5;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; margin:0; padding:0;
  }
  .page-wrap{padding:20px;}
  .container-fluid{max-width:1600px;margin:auto;}
  .table-wrap{
    background:rgba(15,17,23,.88); border:1px solid rgba(255,255,255,.12);
    border-radius:16px; padding:8px; backdrop-filter: blur(6px);
    box-shadow:0 10px 24px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.05);
  }
  table{width:100%;border-collapse:collapse;}
  th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.12);vertical-align:middle;}
  thead th{background:#11151d;text-transform:uppercase;letter-spacing:.04em;font-weight:800;position:sticky;top:0;z-index:2;}
  .group-row{background:#0f2018;font-weight:700;}
  .cell-trunc{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px;}
  .badge-ext{display:inline-block;min-width:44px;text-align:center;padding:.2rem .5rem;border-radius:8px;background:#0b5;font-weight:800;}
  .prog{display:flex;align-items:center;gap:.6rem;}
  .track{flex:1;height:12px;background:#1b222c;border-radius:999px;overflow:hidden;border:1px solid #2a3140;}
  .fill{height:100%;background:linear-gradient(90deg,#1cd259,#15a34a);}
  .b-pill{padding:.25rem .55rem;border-radius:999px;font-weight:800;font-size:.8rem;border:1px solid;}
  .b-ok{background:#052e1b;color:#22c55e;border-color:#14532d;}
  .b-warn{background:#2a1a00;color:#fbbf24;border-color:#b45309;}
  .b-bad{background:#2a1113;color:#fca5a5;border-color:#7f1d1d;}
  .pct{width:62px;text-align:right;color:#bfe8cb;font-weight:800;}
  .btn-ed{background:#0ea5e9;border:none;color:#001018;font-weight:800;border-radius:10px;padding:.32rem .56rem;}
  .btn-ed:hover{background:#38bdf8;}
  .btn-acc{background:#16a34a;border:none;color:#fff;border-radius:12px;font-weight:800;padding:.45rem .9rem;}
  .btn-acc-sm{font-size:.85rem;padding:.32rem .56rem;border-radius:10px;}
  .tabs .btn{border-radius:10px;margin-right:.4rem;}
  .tabs .btn.active{background:#16a34a;border-color:#16a34a;}
</style>
</head>
<body>

  <header class="brand-hero">
    <div class="hero-inner container-fluid">
      <img class="brand-logo" src="<?= e($ESCUDO) ?>" alt="Escudo 602">
      <div>
        <div class="brand-title">Batallón de Comunicaciones 602</div>
        <div class="brand-sub">“Hogar de las Comunicaciones Fijas del Ejército”</div>
      </div>
      <div class="brand-year"><?= date('Y') ?></div>
    </div>
  </header>

  <div class="page-wrap container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="tabs">
        <?php foreach($tabs as $k=>$label): ?>
          <a class="btn btn-outline-light <?= $sub===$k?'active':'' ?>" href="?sub=<?= e(urlencode($k)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
      <a class="btn btn-acc btn-acc-sm" href="index.php?scope=visitas_de_estado_mayor">Volver al dashboard</a>
    </div>

    <div class="table-wrap shadow-lg">
      <div style="overflow:auto;max-height:80vh;">
        <table>
          <thead>
            <tr>
              <th style="width:68px">EXT</th>
              <th>ARCHIVO</th>
              <th>UBICACIÓN</th>
              <th style="width:220px">PROGRESO</th>
              <th style="width:120px" class="text-end">TAMAÑO</th>
              <th style="width:160px">MODIFICADO</th>
              <th style="width:130px">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $grp=null;
            foreach($rows as $r):
              if($r['loc']!==$grp){
                $grp=$r['loc'];
                echo '<tr class="group-row"><td colspan="7">'.e($grp===''?'(Raíz)/':$grp).'</td></tr>';
              }
              $fileRel=$r['rel'];
              $stTot->execute([$fileRel]); $tot=(int)$stTot->fetchColumn();
              $stOk->execute([$fileRel]);  $ok =(int)$stOk->fetchColumn();
              $pct=$tot?round($ok*100.0/$tot,1):0;
              $cls=pct_class($pct);
              $badge = $cls==='ok'?'b-ok':($cls==='warn'?'b-warn':'b-bad');
              $qs='p='.rawurlencode($r['rel']);
          ?>
            <tr>
              <td><span class="badge-ext"><?= e($r['ext']) ?></span></td>
              <td class="cell-trunc" title="<?= e($r['name']) ?>"><?= e($r['name']) ?></td>
              <td class="cell-trunc"><?= e($r['loc']) ?></td>
              <td>
                <div class="prog">
                  <div class="track"><div class="fill" style="width:<?= $pct ?>%"></div></div>
                  <span class="b-pill <?= $badge ?> pct"><?= $pct ?>%</span>
                </div>
              </td>
              <td class="text-end"><?= e(human_size($r['size'])) ?></td>
              <td><?= e(date('d/m/Y H:i',$r['mtime'])) ?></td>
              <td class="d-flex gap-2">
                <a class="btn btn-ed btn-acc-sm" href="ver_tabla.php?<?= $qs ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; if(empty($rows)): ?>
            <tr><td colspan="7" class="text-muted">No se encontraron archivos XLSX.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</body>
</html>
