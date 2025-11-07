<?php
require_once __DIR__ . '/../config/db.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$rel = $_GET['p'] ?? '';
$sheetIdx = isset($_GET['s']) ? max(0,(int)$_GET['s']) : 0;
$debugShowColor = isset($_GET['showcolor']);

$projectBase = realpath(__DIR__ . '/..');
$scanRoot    = realpath($projectBase . '/storage/listas_control');
$abs         = realpath($projectBase . '/' . $rel);

$ok = $scanRoot && $abs && is_file($abs) && (substr($abs,0,strlen($scanRoot)) === $scanRoot);
if(!$ok){ http_response_code(400); echo "Ruta inválida"; exit; }

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
if(!in_array($ext, ['xlsx','csv'])){ http_response_code(400); echo "Solo XLSX/CSV"; exit; }

/* Preferencia nro → título/ítem (por defecto: item) */
$pdo->exec("CREATE TABLE IF NOT EXISTS xlsx_prefs (
  file_rel VARCHAR(512) PRIMARY KEY,
  mode_num_is ENUM('title','item') NOT NULL DEFAULT 'item',
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if(isset($_GET['setmode'])){
  $mode = $_GET['setmode']==='item' ? 'item' : 'title';
  $up = $pdo->prepare("INSERT INTO xlsx_prefs(file_rel,mode_num_is,updated_at) VALUES(?,?,NOW())
                       ON DUPLICATE KEY UPDATE mode_num_is=VALUES(mode_num_is), updated_at=NOW()");
  $up->execute([$rel,$mode]);
  $qs = 'p='.rawurlencode($rel).'&s='.$sheetIdx.($debugShowColor?'&showcolor=1':'');
  header("Location: ver_tabla.php?".$qs); exit;
}
$stM = $pdo->prepare("SELECT mode_num_is FROM xlsx_prefs WHERE file_rel=?");
$stM->execute([$rel]); $mode = $stM->fetchColumn() ?: 'item';

/* ====== PDF hermano (mismo nombre, misma carpeta) ====== */
function url_path($path){
  return implode('/', array_map('rawurlencode', explode('/', $path)));
}
$pdfAbs    = preg_replace('/\.(xlsx|csv)$/i', '.pdf', $abs);
$pdfRel    = preg_replace('/\.(xlsx|csv)$/i', '.pdf', $rel);
$pdfExists = is_file($pdfAbs);
$pdfHref   = $pdfExists ? ('../' . url_path($pdfRel)) : '';

/* Autoload PhpSpreadsheet */
$autoload = $projectBase . '/vendor/autoload.php';
$ssAvail = is_file($autoload);
if($ext==='xlsx' && !$ssAvail){
  $errFatal = "No encuentro PhpSpreadsheet (vendor/autoload.php). Instálalo con Composer.";
}

/* ===== Helpers ===== */
function read_csv_all($file){
  $rows=[]; $fh=@fopen($file,'r'); if(!$fh) return [$rows, ['CSV'], [], null];
  $first=fgets($fh); if($first===false){ fclose($fh); return [$rows, ['CSV'], [], null]; }
  $sep=(substr_count($first,';')>substr_count($first,','))?';':',';
  rewind($fh);
  while(($d=fgetcsv($fh,0,$sep))!==false){ $rows[] = array_map(fn($v)=>trim((string)$v),$d); }
  fclose($fh);
  $meta = array_fill(0, max(0,count($rows)-1), false);
  return [$rows, ['CSV'], $meta, null];
}

/* ===== XLSX ===== */
if(!isset($errFatal) && $ext==='xlsx'){
  require_once $autoload;

  function coord($c,$r){
    return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c).$r;
  }
  function cell_text($cell){
    if(!$cell) return '';
    $v = $cell->getValue();
    if($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText){
      return trim((string)$v->getPlainText());
    }
    $calc = $cell->getCalculatedValue();
    if(is_scalar($calc) && $calc!=='') return trim((string)$calc);
    $fmt = $cell->getFormattedValue();
    return trim((string)$fmt);
  }
  function sheet_used_bounds($sh){
    $maxR=$sh->getHighestRow();
    $maxC=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sh->getHighestColumn());
    $minR=$maxR; $minC=$maxC; $found=false;
    for($r=1;$r<=$maxR;$r++){
      for($c=1;$c<=$maxC;$c++){
        $cell = $sh->getCell(coord($c,$r));
        $txt = cell_text($cell);
        if(trim($txt)!==''){ $found=true; if($r<$minR)$minR=$r; if($c<$minC)$minC=$c; }
      }
    }
    if(!$found) return [1,1,0,0];
    while($maxR>=$minR){
      $empty=true;
      for($c=$minC;$c<=$maxC;$c++){
        if(trim(cell_text($sh->getCell(coord($c,$maxR))))!==''){ $empty=false; break; }
      }
      if($empty) $maxR--; else break;
    }
    while($maxC>=$minC){
      $empty=true;
      for($r=$minR;$r<=$maxR;$r++){
        if(trim(cell_text($sh->getCell(coord($maxC,$r))))!==''){ $empty=false; break; }
      }
      if($empty) $maxC--; else break;
    }
    return [$minR,$minC,$maxR,$maxC];
  }
  function cell_has_fill($sh,$c,$r){
    $style = $sh->getStyle(coord($c,$r));
    if(!$style) return false;
    $fill = $style->getFill();
    if(!$fill) return false;
    if($fill->getFillType() !== \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID) return false;
    $rgb = strtoupper($fill->getStartColor()->getRGB() ?: '');
    if($rgb==='' || $rgb==='FFFFFF') return false;
    return true;
  }
  function expand_merged_values($sh,&$grid,$minR,$minC){
    foreach($sh->getMergeCells() as $range){
      [$tl,$br]=explode(':',$range);
      $tlC=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace('/\d+/','',$tl));
      $tlR=(int)preg_replace('/\D+/','',$tl);
      $brC=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace('/\d+/','',$br));
      $brR=(int)preg_replace('/\D+/','',$br);
      $val=cell_text($sh->getCell($tl));
      for($r=$tlR;$r<=$brR;$r++){
        for($c=$tlC;$c<=$brC;$c++){
          $ri=$r-$minR; $ci=$c-$minC;
          if(!isset($grid[$ri][$ci]) || $grid[$ri][$ci]==='') $grid[$ri][$ci]=$val;
        }
      }
    }
  }
  function read_xlsx_all($file,$sheetIdx=0,&$sheetNames=[],&$err=null){
    try{
      $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $reader->setReadDataOnly(false);
      $ss=$reader->load($file);
      $sheetNames=[]; $count=$ss->getSheetCount();
      for($i=0;$i<$count;$i++){ $sheetNames[]=$ss->getSheet($i)->getTitle(); }
      if($sheetIdx>=$count) $sheetIdx=0;
      $sh=$ss->getSheet($sheetIdx);

      [$minR,$minC,$maxR,$maxC]=sheet_used_bounds($sh);
      if($maxR<$minR||$maxC<$minC) return [[],[]];

      $rows=[]; $rowFill=[];
      for($r=$minR;$r<=$maxR;$r++){
        $line=[]; $hasFill=false;
        for($c=$minC;$c<=$maxC;$c++){
          $cell = $sh->getCell(coord($c,$r));
          $txt  = cell_text($cell);
          $line[] = $txt;
          if(!$hasFill && cell_has_fill($sh,$c,$r)) $hasFill=true;
        }
        $rows[]=$line; $rowFill[]=$hasFill;
      }
      expand_merged_values($sh,$rows,$minR,$minC);
      return [$rows,$rowFill,$sheetNames,null];
    }catch(Throwable $e){ $err=$e->getMessage(); return [[],[],[], $e->getMessage()]; }
  }
}

/* = CARGA = */
$rows=[]; $rowFill=[]; $err=null; $sheetNames=['Hoja'];
if(isset($errFatal)){ $rows=[]; $rowFill=[]; $err=$errFatal; }
else{
  if($ext==='csv'){ [$rows,$sheetNames,$rowFill,$err]=read_csv_all($abs); $sheetIdx=0; }
  else{ [$rows,$rowFill,$sheetNames,$err]=read_xlsx_all($abs,$sheetIdx,$sheetNames,$err); }
}

/* Encabezados */
$headers=[];
if($rows){
  $first=$rows[0]; $non=0; foreach($first as $v){ if(trim((string)$v)!=='') $non++; }
  if(count($first)>=3 && $non>=ceil(count($first)/2)){
    $headers=array_map(fn($v)=>$v===''?'—':$v,$first);
    array_shift($rows); if($rowFill) array_shift($rowFill);
  }
}
$cols = $rows ? max(array_map('count',$rows)) : count($headers);
if(!$headers){
  for($i=1;$i<=$cols;$i++){
    $headers[] = ($i==2 ? 'Inspecciones' : 'Col'.$i);
  }
}

/* === Mostrar solo 2 columnas de contenido (Col1 + Col2 “Inspecciones”) === */
$MAX_TEXT_COLS = 2;
$headers = array_values($headers);
$headers = array_pad($headers, $MAX_TEXT_COLS, '');
$headers = array_slice($headers, 0, $MAX_TEXT_COLS);
if (isset($headers[1])) $headers[1] = 'Inspecciones';
foreach ($rows as $i => $r) {
  $r = array_values($r);
  $r = array_pad($r, $MAX_TEXT_COLS, '');
  $rows[$i] = array_slice($r, 0, $MAX_TEXT_COLS);
}

function is_title_row(array $r, string $mode){
  $a=trim((string)($r[0]??'')); $b=trim((string)($r[1]??''));
  $hasDigit = ($a!=='' && preg_match('/\d/',$a)===1);
  if($mode==='title' && $hasDigit) return true;
  if($mode==='item'  && $hasDigit) return false;
  $othersEmpty=true; for($i=2;$i<count($r);$i++){ if(trim((string)$r[$i])!==''){ $othersEmpty=false; break; } }
  if($a==='' && $b!=='' && $othersEmpty) return true;
  if($b!=='' && mb_strtoupper($b,'UTF-8')===$b && mb_strlen($b,'UTF-8')<=120) return true;
  return false;
}

/* Prefill respuestas */
$pdo->exec("CREATE TABLE IF NOT EXISTS checklist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_rel VARCHAR(512) NOT NULL,
  row_idx INT NOT NULL,
  estado ENUM('si','no') NULL,
  observacion TEXT NULL,
  evidencia_path VARCHAR(512) NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_file_row (file_rel,row_idx)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$sel = $pdo->prepare("SELECT row_idx, estado, observacion, evidencia_path FROM checklist WHERE file_rel=?");
$sel->execute([$rel]); $prefill=[]; foreach($sel as $r){ $prefill[(int)$r['row_idx']]=$r; }

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= e(basename($abs)) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  /* Paleta oscura + acento verde */
  body{
    background: radial-gradient(1200px 600px at 10% -10%, #151922 0, transparent 40%), #0f1117;
    color:#e9eef5;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;
    margin:0; padding:16px;
  }
  .box{background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:16px; padding:12px; backdrop-filter:blur(6px)}
  .btn-acc{background:#16a34a;border:none;color:#fff;border-radius:12px;font-weight:800;padding:.45rem .9rem}
  .btn-acc:hover{background:#22c55e}
  .btn-acc-sm{font-size:.85rem;padding:.32rem .56rem;border-radius:10px}
  .toolbar{gap:.5rem;flex-wrap:wrap}

  /* ==== Tabla: que el contenido mande ===== */
  table{ width:100%; border-collapse:collapse; table-layout:auto; }
  th,td{
    padding:10px; border-bottom:1px solid rgba(255,255,255,.12); vertical-align:top;
    white-space:normal;
    overflow-wrap:anywhere;
  }

  /* Encabezado fijo */
  thead th{
    position:sticky; top:0; z-index:5;
    background:#11151d; color:#e9eef5;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    font-weight:800; letter-spacing:.04em; text-transform:uppercase;
  }

  /* Columna # */
  thead th:nth-child(1), tbody td:nth-child(1){ width:56px }

  /* Estado / Obs / Evidencia (ancho controlado) */
  thead th:nth-last-child(3), tbody td:nth-last-child(3){ width:160px } /* Estado */
  thead th:nth-last-child(2), tbody td:nth-last-child(2){ width:340px } /* Observación */
  thead th:nth-last-child(1), tbody td:nth-last-child(1){ width:280px } /* Evidencia */

  /* Columnas de texto */
  tbody td:not(:first-child):not(:nth-last-child(3)):not(:nth-last-child(2)):not(:last-child){
    min-width:360px;
  }

  .section{background:rgba(34,197,94,.10); font-weight:800}
  .muted{color:#cfd6ff99}
  .alert-lite{background:rgba(252,165,165,.15); border:1px solid rgba(248,113,113,.35); color:#ffdede; border-radius:12px}

  select, input[type="text"]{
    width:100%; background:rgba(255,255,255,.10); color:#fff;
    border:1px solid rgba(255,255,255,.18); border-radius:8px; padding:.35rem .5rem
  }
  input[type="file"]{ width:100% }
</style>
</head>
<body>

<div class="mb-2 d-flex align-items-center toolbar">
  <a class="btn btn-acc btn-acc-sm" href="documentos.php">← Volver</a>

  <span class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
    <?= e($rel) ?>
  </span>

  <span class="ms-2">Modo números: <b><?= $mode==='item'?'Ítem':'Título' ?></b></span>

  <a class="btn btn-acc btn-acc-sm" href="ver_tabla.php?p=<?= rawurlencode($rel) ?>&s=<?= (int)$sheetIdx ?>&setmode=title<?= $debugShowColor?'&showcolor=1':'' ?>">Nro como TÍTULO</a>
  <a class="btn btn-acc btn-acc-sm" href="ver_tabla.php?p=<?= rawurlencode($rel) ?>&s=<?= (int)$sheetIdx ?>&setmode=item<?= $debugShowColor?'&showcolor=1':'' ?>">Nro como ÍTEM</a>

  <?php if(!$debugShowColor): ?>
    <a class="btn btn-acc btn-acc-sm" href="ver_tabla.php?p=<?= rawurlencode($rel) ?>&s=<?= (int)$sheetIdx ?>&showcolor=1">Ver con color (debug)</a>
  <?php else: ?>
    <a class="btn btn-acc btn-acc-sm" href="ver_tabla.php?p=<?= rawurlencode($rel) ?>&s=<?= (int)$sheetIdx ?>">Ocultar color</a>
  <?php endif; ?>

  <?php if ($pdfExists): ?>
    <a class="btn btn-acc btn-acc-sm" target="_blank" href="<?= e($pdfHref) ?>">Abrir PDF</a>
  <?php else: ?>
    <button class="btn btn-acc btn-acc-sm" title="Colocá el PDF con el mismo nombre al lado del XLSX" disabled>PDF no disponible</button>
  <?php endif; ?>

  <div class="ms-auto">
    <form action="save_check_bulk.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="file_rel" value="<?= e($rel) ?>">
      <input type="hidden" name="sheet" value="<?= (int)$sheetIdx ?>">
      <input type="hidden" name="showcolor" value="<?= $debugShowColor?'1':'0' ?>">
      <button class="btn btn-acc btn-acc-sm">Guardar todo</button>
    </form>
  </div>
</div>

<?php if(!empty($err)): ?>
  <div class="alert-lite p-3 mb-3">
    <div class="fw-bold">No pude leer el archivo:</div>
    <div><?= e($err) ?></div>
  </div>
<?php endif; ?>

<?php
  $totalFilas = count($rows);
  $coloreadas = 0; foreach($rowFill as $f){ if($f) $coloreadas++; }
  if($debugShowColor){
    echo '<div class="mb-2 muted">Filas totales: '.$totalFilas.' · Filas con color: '.$coloreadas.' · (mostrando todas)</div>';
  } else {
    echo '<div class="mb-2 muted">Filas totales: '.$totalFilas.' · Ocultando '.$coloreadas.' con color</div>';
  }
?>

<form action="save_check_bulk.php" method="post" enctype="multipart/form-data" class="box">
  <input type="hidden" name="file_rel" value="<?= e($rel) ?>">
  <input type="hidden" name="sheet" value="<?= (int)$sheetIdx ?>">
  <input type="hidden" name="showcolor" value="<?= $debugShowColor?'1':'0' ?>">

  <div style="overflow:auto; max-height:78vh;">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <?php foreach($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
          <th>Estado</th>
          <th>Observación</th>
          <th>Evidencia</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $rowIndex=1;
        foreach($rows as $i=>$r){
          if(!$debugShowColor && !empty($rowFill[$i])) continue;

          while(count($r)<count($headers)) $r[]='';
          if(is_title_row($r,$mode)){
            $title=trim(($r[0]??'').' '.($r[1]??''));
            if($title==='') $title=trim((string)($r[1]??''));
            echo '<tr class="section"><td>'.$rowIndex.'</td><td colspan="'.count($headers).'">'.e($title).'</td><td colspan="3"></td></tr>';
            $rowIndex++; continue;
          }

          $saved = $prefill[$rowIndex] ?? null;
          $est = $saved['estado'] ?? '';
          $obs = $saved['observacion'] ?? '';
          $ev  = $saved['evidencia_path'] ?? '';

          echo '<tr>';
          echo '<td>'.$rowIndex.'</td>';
          foreach($r as $v){ echo '<td>'.e($v).'</td>'; }
          echo '<td><select name="estado['.$rowIndex.']"><option value="" '.($est===''?'selected':'').'>—</option><option value="si" '.($est==='si'?'selected':'').'>Sí</option><option value="no" '.($est==='no'?'selected':'').'>No</option></select></td>';
          echo '<td><input type="text" name="observacion['.$rowIndex.']" value="'.e($obs).'" placeholder="Escribir..."></td>';
          echo '<td><input type="file" name="evidencia['.$rowIndex.']" accept=".jpg,.jpeg,.png,.pdf,.webp">';
          if($ev){ echo ' <a href="../'.e($ev).'" target="_blank">Ver</a>'; }
          echo '</td>';
          echo '</tr>';
          $rowIndex++;
        }

        if($rowIndex===1){
          echo '<tr><td colspan="'.(count($headers)+3).'" class="muted">No se encontraron filas para mostrar. Probá “Ver con color (debug)” o revisá la hoja.</td></tr>';
        }
      ?>
      </tbody>
    </table>
  </div>

  <div class="mt-2 text-end">
    <button class="btn btn-acc btn-acc-sm" type="submit">Guardar todo</button>
  </div>
</form>
</body>
</html>
