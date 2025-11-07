<?php
require_once __DIR__ . '/../config/db.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function human_size($bytes){
  $u=['B','KB','MB','GB']; $i=0; while($bytes>=1024 && $i<count($u)-1){ $bytes/=1024; $i++; }
  return round($bytes,1).' '.$u[$i];
}

$area = $_GET['area'] ?? 'S1';
$validAreas = ['S1','S2','S3','S4'];
if(!in_array($area,$validAreas)) $area='S1';

$projectBase = realpath(__DIR__ . '/..');
$root = realpath($projectBase . '/storage/listas_control/' . $area);
if(!$root){ http_response_code(404); echo "Carpeta no encontrada"; exit; }

/* Recurre subcarpetas y arma filas SOLO de XLSX */
$rows = [];
$rii = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST
);
foreach ($rii as $f) {
  if (!$f->isFile()) continue;
  $ext = strtolower($f->getExtension());
  if ($ext !== 'xlsx') continue; // <<< IMPORTANTÍSIMO: ignoramos PDF y otros
  $abs = $f->getPathname();
  $rel = substr($abs, strlen($projectBase)+1); // relativo al proyecto
  $sub = substr($abs, strlen($root)+1);
  $loc = dirname($sub);
  if ($loc === '.') $loc = '';
  $rows[] = [
    'ext'  => strtoupper($ext),
    'name' => $f->getFilename(),
    'rel'  => str_replace('\\','/',$rel),
    'loc'  => ($loc? $loc.'/' : ''),
    'size' => $f->getSize(),
    'mtime'=> $f->getMTime(),
  ];
}

/* Orden por carpeta y nombre */
usort($rows, function($a,$b){
  return strcasecmp($a['loc'].$a['name'], $b['loc'].$b['name']);
});

/* UI */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Documentos por área (<?= e($area) ?>)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{
    background:#0f1117; color:#e9eef5; font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;
    padding:16px;
  }
  .btn-acc{background:#16a34a; border:none; color:#fff; border-radius:12px; font-weight:800; padding:.45rem .9rem}
  .btn-acc:hover{background:#22c55e}
  .btn-acc-sm{font-size:.85rem; padding:.32rem .56rem; border-radius:10px}

  .tabs .btn{border-radius:10px; margin-right:.4rem}
  .tabs .btn.active{background:#16a34a; border-color:#16a34a}

  .table-wrap{background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:16px; padding:8px}
  table{width:100%; border-collapse:collapse}
  th,td{padding:10px; border-bottom:1px solid rgba(255,255,255,.12)}
  thead th{background:#11151d; position:sticky; top:0; z-index:2; text-transform:uppercase; letter-spacing:.04em; font-weight:800}
  .badge-ext{display:inline-block; min-width:44px; text-align:center; padding:.2rem .5rem; border-radius:8px; background:#0b5; font-weight:800}
  .group-row{background:#0f2018; font-weight:700}
  .cell-trunc{white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:520px}
</style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0" style="font-weight:800">Documentos</h1>
    <a class="btn btn-acc btn-acc-sm" href="index.php">Volver al dashboard</a>
  </div>

  <div class="tabs mb-3">
    <?php foreach($validAreas as $a): ?>
      <a class="btn btn-outline-light <?= $area===$a?'active':'' ?>" href="?area=<?= e($a) ?>"><?= e($a) ?></a>
    <?php endforeach; ?>
  </div>

  <h5 class="mb-2">Área <?= e($area) ?></h5>

  <div class="table-wrap">
    <div style="overflow:auto; max-height:78vh;">
      <table>
        <thead>
          <tr>
            <th style="width:70px">Ext</th>
            <th>Archivo</th>
            <th>Ubicación</th>
            <th style="width:120px">Tamaño</th>
            <th style="width:160px">Modificado</th>
            <th style="width:120px">Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $currentGroup = null;
          foreach ($rows as $r):
            if ($r['loc'] !== $currentGroup) {
              $currentGroup = $r['loc'];
              echo '<tr class="group-row"><td colspan="6">'.e($currentGroup===''?'(raíz)/':$currentGroup).'</td></tr>';
            }
            $qs = 'p='.rawurlencode($r['rel']);
        ?>
          <tr>
            <td><span class="badge-ext">XLSX</span></td>
            <td class="cell-trunc" title="<?= e($r['name']) ?>"><?= e($r['name']) ?></td>
            <td class="cell-trunc"><?= e($r['loc']) ?></td>
            <td><?= e(human_size($r['size'])) ?></td>
            <td><?= e(date('d/m/Y H:i', $r['mtime'])) ?></td>
            <td>
              <a class="btn btn-acc btn-acc-sm" href="ver_tabla.php?<?= $qs ?>">Ver tabla</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?>
          <tr><td colspan="6" class="text-muted">No se encontraron archivos XLSX en esta área.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
