<?php
/* public/ver_tabla.php — Tema 602 con paginación + toolbar compacta
   Agregado: Formato “Formulario” en menú “Formato de tabla”, con columna “Dato”
   para filas especiales (Predio en litigio, Estado actual, Predio, Dimensiones,
   Tiempo estipulado, Organismo que lo suscribió), además de Sí/No/Obs/Evidencia.
*/
require_once __DIR__ . '/../config/db.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function starts_with($h,$n){ return substr($h,0,strlen($n)) === $n; }
function norm($s){ return preg_replace('/\s+/u','',mb_strtoupper(trim((string)$s),'UTF-8')); }

/* ===== Rutas de assets para usar el mismo fondo que index.php ===== */
$PUBLIC_URL = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'])), '/');
$APP_URL    = rtrim(str_replace('\\','/', dirname($PUBLIC_URL)), '/');
$ASSETS_URL = ($APP_URL === '' ? '' : $APP_URL) . '/assets';
$IMG_BG     = $ASSETS_URL . '/img/fondo.png';

/* ===== Parámetros ===== */
$rel = $_GET['p'] ?? '';
$sheetIdx = isset($_GET['s']) ? max(0,(int)$_GET['s']) : 0;
$debugShowColor = isset($_GET['showcolor']);

/* Paginación */
$allowedPP = [10,20,30,50,100];
$perPage = (int)($_GET['pp'] ?? 20);
if(!in_array($perPage,$allowedPP,true)) $perPage = 20;
$page = max(1,(int)($_GET['page'] ?? 1));

/* ===== Paths base ===== */
$projectBase = realpath(__DIR__ . '/..');
$abs         = realpath($projectBase . '/' . $rel);
if(!$projectBase || !$abs || !is_file($abs)){
  http_response_code(400); echo "Ruta inválida"; exit;
}

/* ===== Rutas permitidas ===== */
$roots = [
  'listas_control'            => realpath($projectBase.'/storage/listas_control'),
  'ultima_inspeccion'         => realpath($projectBase.'/storage/ultima_inspeccion'),
  'visitas_de_estado_mayor'   => realpath($projectBase.'/storage/visitas_de_estado_mayor'),
];
$inScope = null;
foreach($roots as $slug=>$root){ if($root && starts_with($abs, $root)){ $inScope=$slug; break; } }
if(!$inScope){ http_response_code(400); echo "Ruta fuera de las carpetas permitidas"; exit; }

$scopeMeta = [
  'listas_control' => ['label' => 'Lista de control', 'list_url' => 'lista_de_control.php', 'dash_scope' => 'lista_de_control'],
  'ultima_inspeccion' => ['label' => 'Última inspección', 'list_url' => 'ultima_inspeccion.php', 'dash_scope' => 'ultima_inspeccion'],
  'visitas_de_estado_mayor' => ['label' => 'Visitas de Estado Mayor', 'list_url' => 'visitas_de_estado_mayor.php', 'dash_scope' => 'visitas_de_estado_mayor'],
];
$SCOPE = $scopeMeta[$inScope];

/* Mostrar “Carácter” solo en última inspección */
$showCaracter = ($inScope === 'ultima_inspeccion');

/* ===== Preferencias por archivo (modo nro + formato de tabla) ===== */
$pdo->exec("CREATE TABLE IF NOT EXISTS xlsx_prefs (
  file_rel VARCHAR(512) PRIMARY KEY,
  mode_num_is ENUM('title','item') NOT NULL DEFAULT 'item',
  table_fmt ENUM('classic','form') NOT NULL DEFAULT 'classic',
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* En caso de que la tabla exista sin la columna table_fmt, intentamos agregarla (idempotente) */
try { $pdo->exec("ALTER TABLE xlsx_prefs ADD COLUMN table_fmt ENUM('classic','form') NOT NULL DEFAULT 'classic'"); } catch(Throwable $e){ /* ignore */ }

if(isset($_GET['setmode'])){
  $modeSet = $_GET['setmode']==='item' ? 'item' : 'title';
  $up = $pdo->prepare("INSERT INTO xlsx_prefs(file_rel,mode_num_is,updated_at)
                       VALUES(?,?,NOW())
                       ON DUPLICATE KEY UPDATE mode_num_is=VALUES(mode_num_is), updated_at=NOW()");
  $up->execute([$rel,$modeSet]);
  $qs = 'p='.rawurlencode($rel).'&s='.$sheetIdx.($debugShowColor?'&showcolor=1':'')."&pp=$perPage&page=$page";
  if(isset($_GET['fmt'])) $qs .= '&fmt='.rawurlencode($_GET['fmt']);
  header("Location: ver_tabla.php?".$qs); exit;
}
if(isset($_GET['setfmt'])){
  $fmtSet = ($_GET['setfmt']==='form') ? 'form' : 'classic';
  $up = $pdo->prepare("INSERT INTO xlsx_prefs(file_rel,table_fmt,updated_at)
                       VALUES(?,?,NOW())
                       ON DUPLICATE KEY UPDATE table_fmt=VALUES(table_fmt), updated_at=NOW()");
  $up->execute([$rel,$fmtSet]);
  $qs = 'p='.rawurlencode($rel).'&s='.$sheetIdx.($debugShowColor?'&showcolor=1':'')."&pp=$perPage&page=$page";
  header("Location: ver_tabla.php?".$qs); exit;
}

$stM = $pdo->prepare("SELECT mode_num_is, table_fmt FROM xlsx_prefs WHERE file_rel=?");
$stM->execute([$rel]); $pref = $stM->fetch(PDO::FETCH_ASSOC) ?: ['mode_num_is'=>'item','table_fmt'=>'classic'];
$mode = $pref['mode_num_is'] ?? 'item';
$tableFmt = $pref['table_fmt'] ?? 'classic';

/* ===== PhpSpreadsheet ===== */
$autoload = $projectBase . '/vendor/autoload.php';
$ssAvail = is_file($autoload);
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
if($ext==='xlsx' && !$ssAvail){ $errFatal = "No encuentro PhpSpreadsheet (vendor/autoload.php). Instálalo con Composer."; }

/* ===== Lectores ===== */
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

if(!isset($errFatal) && $ext==='xlsx'){
  require_once $autoload;
  function coord($c,$r){ return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c).$r; }
  function cell_text($cell){
    if(!$cell) return '';
    $v = $cell->getValue();
    if($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText){ return trim((string)$v->getPlainText()); }
    $calc = $cell->getCalculatedValue();
    if(is_scalar($calc) && $calc!=='') return trim((string)$calc);
    return trim((string)$cell->getFormattedValue());
  }
  function sheet_used_bounds($sh){
    $maxR=$sh->getHighestRow();
    $maxC=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sh->getHighestColumn());
    $minR=$maxR; $minC=$maxC; $found=false;
    for($r=1;$r<=$maxR;$r++){
      for($c=1;$c<=$maxC;$c++){
        if(trim(cell_text($sh->getCell(coord($c,$r))))!==''){ $found=true; if($r<$minR)$minR=$r; if($c<$minC)$minC=$c; }
      }
    }
    if(!$found) return [1,1,0,0];
    while($maxR>=$minR){
      $empty=true; for($c=$minC;$c<=$maxC;$c++){ if(trim(cell_text($sh->getCell(coord($c,$maxR))))!==''){ $empty=false; break; } }
      if($empty) $maxR--; else break;
    }
    while($maxC>=$minC){
      $empty=true; for($r=$minR;$r<=$maxR;$r++){ if(trim(cell_text($sh->getCell(coord($maxC,$r))))!==''){ $empty=false; break; } }
      if($empty) $maxC--; else break;
    }
    return [$minR,$minC,$maxR,$maxC];
  }
  function cell_has_fill($sh,$c,$r){
    $style = $sh->getStyle(coord($c,$r)); if(!$style) return false;
    $fill = $style->getFill(); if(!$fill) return false;
    if($fill->getFillType() !== \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID) return false;
    $rgb = strtoupper($fill->getStartColor()->getRGB() ?: '');
    return !($rgb==='' || $rgb==='FFFFFF');
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
          $ri=$r-$minR; $ci=$c-$minC; if(!isset($grid[$ri][$ci]) || $grid[$ri][$ci]==='') $grid[$ri][$ci]=$val;
        }
      }
    }
  }
  function read_xlsx_all($file,$sheetIdx=0,&$sheetNames=[],&$err=null){
    try{
      $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(); $reader->setReadDataOnly(false);
      $ss=$reader->load($file);
      $sheetNames=[]; $count=$ss->getSheetCount(); for($i=0;$i<$count;$i++){ $sheetNames[]=$ss->getSheet($i)->getTitle(); }
      if($sheetIdx>=$count) $sheetIdx=0;
      $sh=$ss->getSheet($sheetIdx);
      [$minR,$minC,$maxR,$maxC]=sheet_used_bounds($sh);
      if($maxR<$minR||$maxC<$minC) return [[],[],[], null];
      $rows=[]; $rowFill=[];
      for($r=$minR;$r<=$maxR;$r++){
        $line=[]; $hasFill=false;
        for($c=1;$c<=$maxC;$c++){
          $line[] = cell_text($sh->getCell(coord($c,$r)));
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

/* ===== Encabezados y recorte a 2/3 columnas ===== */
$headers=[];
if($rows){
  $first=$rows[0]; $non=0; foreach($first as $v){ if(trim((string)$v)!=='') $non++; }
  if(count($first)>=3 && $non>=ceil(count($first)/2)){
    $headers=array_map(fn($v)=>$v===''?'—':$v,$first);
    array_shift($rows); if($rowFill) array_shift($rowFill);
  }
}
if(!$headers){
  $headers = ['Obs Nro', 'Observaciones'];
  if($showCaracter) $headers[] = 'Carácter';
}else{
  if(isset($headers[1])) $headers[1] = 'Observaciones';
  if($showCaracter){ $headers[2] = 'Carácter'; } else { $headers = array_slice($headers, 0, 2); }
}
$MAX_TEXT_COLS = $showCaracter ? 3 : 2;
$headers = array_slice(array_pad(array_values($headers), $MAX_TEXT_COLS, ''), 0, $MAX_TEXT_COLS);
foreach ($rows as $i => $r) {
  $rows[$i] = array_slice(array_pad(array_values($r), $MAX_TEXT_COLS, ''), 0, $MAX_TEXT_COLS);
}

/* Filas-título */
function is_title_row(array $r, string $mode){
  $a=trim((string)($r[0]??'')); $b=trim((string)($r[1]??'')); $hasDigit = ($a!=='' && preg_match('/\d/',$a)===1);
  if($mode==='title' && $hasDigit) return true;
  if($mode==='item'  && $hasDigit) return false;
  $othersEmpty=true; for($i=2;$i<count($r);$i++){ if(trim((string)$r[$i])!==''){ $othersEmpty=false; break; } }
  if($a==='' && $b!=='' && $othersEmpty) return true;
  if($b!=='' && mb_strtoupper($b,'UTF-8')===$b && mb_strlen($b,'UTF-8')<=120) return true;
  return false;
}

/* ===== Tablas de datos ===== */
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

/* Nueva tabla para valores de “Dato” en Formulario */
$pdo->exec("CREATE TABLE IF NOT EXISTS checklist_form (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_rel VARCHAR(512) NOT NULL,
  row_idx INT NOT NULL,
  field_key VARCHAR(128) NOT NULL,
  field_value TEXT NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_file_row_field (file_rel,row_idx,field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$sel = $pdo->prepare("SELECT row_idx, estado, observacion, evidencia_path FROM checklist WHERE file_rel=?");
$sel->execute([$rel]); $prefill=[]; foreach($sel as $r){ $prefill[(int)$r['row_idx']]=$r; }

$selF = $pdo->prepare("SELECT row_idx, field_key, field_value FROM checklist_form WHERE file_rel=?");
$selF->execute([$rel]); $prefForm=[];
foreach($selF as $r){ $i=(int)$r['row_idx']; $prefForm[$i][$r['field_key']] = $r['field_value']; }

/* PDF vecino */
$pdf_abs = preg_replace('/\.(xlsx|csv)$/i', '.pdf', $abs);
$pdf_rel = preg_replace('/\.(xlsx|csv)$/i', '.pdf', $rel);
$pdf_exists = is_file($pdf_abs);

/* Area/labels */
$area = strtoupper($SCOPE['label']);
if ($inScope==='listas_control' && preg_match('#/storage/listas_control/(S1|S2|S3|S4)/#', $rel, $m)) { $area = $m[1]; }

/* Última actualización */
$stUpd = $pdo->prepare("SELECT MAX(updated_at) FROM checklist WHERE file_rel=?");
$stUpd->execute([$rel]); $lastUpd = $stUpd->fetchColumn();

/* Severidad por “Carácter” */
function row_severity_class($caracter){
  $n = norm($caracter);
  if($n==='INMEDIATA') return 'sev-immediate';
  if($n==='PROGRAMADA' || $n==='PROGRAMA' || $n==='PROGRAMAS') return 'sev-program';
  return '';
}

/* ===== Detección de filas “formulario” ===== */
$formLabelsMap = [
  'PREDIOENLITIGIO'             => 'predio_litigio',
  'ESTADOACTUAL'                 => 'estado_actual',
  'PREDIO'                       => 'predio',
  'DIMENSIONES'                  => 'dimensiones',
  'TIEMPOESTIPULADO'            => 'tiempo',
  'ORGANISMOQUELOSUSCRIBIO'     => 'organismo',
  'ORGANISMOQUELOSUSCRIBIÓ'     => 'organismo', // por si trae tilde
];

function detect_form_key(array $row, array $map){
  // buscamos en las primeras 2-3 celdas
  for($i=0;$i<min(3,count($row));$i++){
    $n = norm((string)$row[$i]);
    if($n==='') continue;
    if(isset($map[$n])) return $map[$n];
  }
  return null;
}

/* ===== Construcción de listado visible + paginación ===== */
$visible = [];
$rowIndex=1; $lastSection=null;
foreach($rows as $i=>$r){
  if(!$debugShowColor && !empty($rowFill[$i])) continue; // ocultar coloreadas
  if(is_title_row($r,$mode)){
    $lastSection=['section'=>true,'title'=>trim(($r[0]??'').' '.($r[1]??''))];
    $rowIndex++; continue;
  }
  $sevClass = $showCaracter ? row_severity_class($r[2] ?? '') : '';
  $saved = $prefill[$rowIndex] ?? ['estado'=>'','observacion'=>'','evidencia_path'=>''];
  if($lastSection){ $visible[]=$lastSection; $lastSection=null; }

  $fk = ($tableFmt==='form') ? detect_form_key($r, $formLabelsMap) : null;
  $formVal = '';
  if($fk && isset($prefForm[$rowIndex][$fk])) $formVal = (string)$prefForm[$rowIndex][$fk];

  $visible[] = [
    'section'=>false,
    'row_idx'=>$rowIndex,
    'cols'=>$r,
    'sevClass'=>$sevClass,
    'estado'=>$saved['estado'] ?? '',
    'observacion'=>$saved['observacion'] ?? '',
    'ev'=>$saved['evidencia_path'] ?? '',
    'form_key'=>$fk,
    'form_val'=>$formVal,
  ];
  $rowIndex++;
}

/* Estadísticas (solo datos) */
$cSi=$cNo=$cNull=0;
foreach($visible as $v){ if(!empty($v['section'])) continue; $sv=$v['estado']??''; if($sv==='si') $cSi++; elseif($sv==='no') $cNo++; else $cNull++; }
$mostradas = $cSi+$cNo+$cNull;
$pct = $mostradas ? round($cSi*100.0/$mostradas,1) : 0.0;

/* Paginación */
$totalItems = 0; foreach($visible as $v){ if(empty($v['section'])) $totalItems++; }
$totalPages = max(1, (int)ceil($totalItems / $perPage));
if($page > $totalPages) $page = $totalPages;

$startItem = ($page-1)*$perPage + 1;
$endItem   = min($totalItems, $page*$perPage);

$render = [];
$counter=0; $pendingSection=null;
for($i=0;$i<count($visible);$i++){
  $v = $visible[$i];
  if(!empty($v['section'])){ $pendingSection=$v; continue; }
  $counter++;
  if($counter < $startItem || $counter > $endItem) { $pendingSection=null; continue; }
  if($pendingSection){ $render[]=$pendingSection; $pendingSection=null; }
  $render[] = $v;
}

/* Helper QS base (arrastra formato) */
function base_qs($rel,$sheetIdx,$debugShowColor,$perPage,$fmt){
  return 'p='.rawurlencode($rel).'&s='.(int)$sheetIdx.($debugShowColor?'&showcolor=1':'').'&pp='.(int)$perPage.'&fmt='.rawurlencode($fmt);
}
$baseQS = base_qs($rel,$sheetIdx,$debugShowColor,$perPage,$tableFmt);

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= e(basename($abs)) ?> — <?= e($area) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme-602.css">
<style>
:root{ --sevRedRGB:239,68,68; --sevYellowRGB:245,158,11; --sevAlpha:0.35; }

/* ====== Fondo y tipografía igual a index.php ====== */
body{
  background: url("<?= e($IMG_BG) ?>") no-repeat center center fixed;
  background-size: cover;
  background-attachment: fixed;
  background-color:#0f1117; color:#e9eef5; margin:0; padding:0;
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,sans-serif;
}

.page{ padding:16px 16px 24px }
.box{ background:rgba(20,24,33,.75); border:1px solid rgba(255,255,255,.14); border-radius:16px; padding:12px; backdrop-filter:blur(6px) }

/* ===== Toolbar compacta ===== */
.toolbar { gap: .6rem; flex-wrap: wrap; }
.toolbar .left, .toolbar .right { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

/* Chips informativos */
.badge-area {
  background: #0e1525; border: 1px solid rgba(255,255,255,.15);
  color: #d9e2ef; padding: .22rem .55rem; border-radius: 999px; font-weight: 800; font-size: .78rem;
}

/* Botones “pill” */
.btnx {
  --padY: .42rem; --padX: .7rem; --radius: 10px; --border: rgba(255,255,255,.18);
  display: inline-flex; align-items: center; gap: .45rem;
  padding: var(--padY) var(--padX); border-radius: var(--radius);
  font-weight: 800; font-size: .86rem; line-height: 1;
  border: 1px solid var(--border); background: #0f1520; color: #e7ecf4; text-decoration: none;
}
.btnx:hover { background: #141c2b; color: #f3f7fb; border-color: rgba(255,255,255,.28); }
.btnx--accent { background: #16a34a; color: #04110a; border-color: #13853e; }
.btnx--accent:hover { background: #22c55e; border-color: #1ea152; color: #031007; }
.btnx--muted  { background: #0b111a; border-color: rgba(255,255,255,.15); }

/* Dropdown oscuro compacto */
.dropdown-menu-dark { --bs-dropdown-bg: #0e1525; --bs-dropdown-color: #e7ecf4; }
.dropdown-item { font-weight: 700; font-size: .88rem; }
.dropdown-header { color:#9fb3c8; font-weight:800; }

/* Selectores compactos */
.form-compact { display:flex; align-items:center; gap:.35rem; }
.form-compact label { font-size:.78rem; color:#cbd5e1; }
.form-compact .form-select { padding:.25rem .5rem; height: 32px; border-radius: 8px; font-size:.86rem; }

/* ====== Tabla ====== */
table{ width:100%; border-collapse:separate; border-spacing:0; }
thead th{
  position:sticky; top:0; z-index:3;
  background:#11151d; color:#e7ecf4;
  border-bottom:1px solid rgba(255,255,255,.2);
  padding:12px; text-transform:uppercase; letter-spacing:.04em; font-weight:800;
}
tbody td{ padding:12px; border-bottom:1px solid rgba(255,255,255,.10); vertical-align:top; color:#d5deea; }
tbody tr:nth-child(even){ background:rgba(255,255,255,.02) }
tbody tr:hover{ background:rgba(255,255,255,.05) }
.section{ background:linear-gradient(90deg, rgba(34,197,94,.18), rgba(34,197,94,.10)); color:#eaf7ee; font-weight:800; }

/* Anchos */
@media (min-width: 1100px){
  thead th:nth-last-child(3), tbody td:nth-last-child(3){ width:160px }  /* Estado / Dato (según formato) */
  thead th:nth-last-child(2), tbody td:nth-last-child(2){ width:360px }  /* Observación */
  thead th:nth-last-child(1), tbody td:nth-last-child(1){ width:340px }  /* Evidencia */
}

/* Inputs */
select, input[type="text"]{
  width:100%; background:#0f1520; color:#e6edf7; border:1px solid rgba(255,255,255,.22); border-radius:10px; padding:.45rem .55rem;
}
input[type="file"]{
  background:#0f1520; color:#e6edf7; border:1px solid rgba(255,255,255,.22); border-radius:10px; padding:.35rem .55rem;
}

/* Overlay */
#overlay{ position:fixed; inset:0; background:rgba(0,0,0,.55); display:none; align-items:center; justify-content:center; z-index:9999; }
#overlay.show{ display:flex; }
.spinner{ width:72px; height:72px; border-radius:50%; border:6px solid rgba(255,255,255,.2); border-top-color:#22c55e; animation: spin 1s linear infinite; }
@keyframes spin { to{ transform: rotate(360deg); } }

/* Severidad (“Carácter”) */
.sev-immediate td{ background: rgba(var(--sevRedRGB), var(--sevAlpha)) !important; border-left: 4px solid rgb(var(--sevRedRGB)); }
.sev-program  td{ background: rgba(var(--sevYellowRGB), var(--sevAlpha)) !important; border-left: 4px solid rgb(var(--sevYellowRGB)); }

/* Marca */
.brand-hero .brand-title{ color:#f3f7fb } .brand-hero .brand-sub{ color:#b9c6d8 }
</style>
</head>
<body>

<div class="page-bg"></div><span class="mesh"></span><span class="mesh mesh--left"></span>

<header class="brand-hero">
  <div class="hero-inner container-fluid">
    <img class="brand-logo" src="assets/img/escudo602sinfondo.png" alt="Escudo 602">
    <div>
      <div class="brand-title">Batallón de Comunicaciones 602</div>
      <div class="brand-sub">“Hogar de las Comunicaciones Fijas del Ejército”</div>
    </div>
    <div class="brand-year"><?= date('Y') ?></div>
  </div>
</header>

<div class="page container-fluid">

  <!-- Toolbar superior -->
  <div class="d-flex align-items-center justify-content-between toolbar mb-3">
    <div class="left">
      <a class="btnx btnx--muted" href="<?= e($SCOPE['list_url']) ?>" title="Volver al listado">📁 Volver</a>
      <a class="btnx btnx--muted" href="index.php?scope=<?= e($SCOPE['dash_scope']) ?>" title="Ir al Dashboard">🏠 Dashboard</a>

      <span class="badge-area">Origen: <b><?= e($SCOPE['label']) ?></b></span>
      <span class="badge-area text-truncate" title="<?= e($rel) ?>">Archivo: <b><?= e(basename($rel)) ?></b></span>
      <?php if ($lastUpd): ?>
        <span class="badge-area">Última actualización: <b><?= e(date('d/m/Y H:i', strtotime($lastUpd))) ?></b></span>
      <?php endif; ?>

      <?php if (!empty($sheetNames) && count($sheetNames)>1): ?>
        <form method="get" class="form-compact">
          <input type="hidden" name="p" value="<?= e($rel) ?>">
          <?php if($debugShowColor): ?><input type="hidden" name="showcolor" value="1"><?php endif; ?>
          <input type="hidden" name="pp" value="<?= (int)$perPage ?>">
          <input type="hidden" name="page" value="<?= (int)$page ?>">
          <label>Hoja:</label>
          <select name="s" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach($sheetNames as $i=>$nm): ?>
              <option value="<?= (int)$i ?>" <?= $i===$sheetIdx?'selected':'' ?>><?= e($nm) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>
    </div>

    <div class="right">
      <!-- Botón único “Formato de tabla” -->
      <div class="dropdown">
        <button class="btnx dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          🧩 Formato de tabla
        </button>
        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
          <li class="dropdown-header">Interpretación del Nro</li>
          <li><a class="dropdown-item" href="ver_tabla.php?<?= $baseQS ?>&setmode=title&page=<?= (int)$page ?>">Nro como TÍTULO</a></li>
          <li><a class="dropdown-item" href="ver_tabla.php?<?= $baseQS ?>&setmode=item&page=<?= (int)$page ?>">Nro como ÍTEM</a></li>
          <li><hr class="dropdown-divider"></li>
          <li class="dropdown-header">Filas coloreadas</li>
          <?php if(!$debugShowColor): ?>
            <li><a class="dropdown-item" href="ver_tabla.php?<?= $baseQS ?>&page=<?= (int)$page ?>&showcolor=1">Ver color (debug)</a></li>
          <?php else: ?>
            <li><a class="dropdown-item" href="ver_tabla.php?<?= base_qs($rel,$sheetIdx,false,$perPage,$tableFmt) ?>&page=<?= (int)$page ?>">Ocultar color</a></li>
          <?php endif; ?>
          <li><hr class="dropdown-divider"></li>
          <li class="dropdown-header">Formato</li>
          <li><a class="dropdown-item<?= $tableFmt==='classic'?' active':'' ?>" href="ver_tabla.php?<?= base_qs($rel,$sheetIdx,$debugShowColor,$perPage,'classic') ?>&setfmt=classic&page=<?= (int)$page ?>">Clásico</a></li>
          <li><a class="dropdown-item<?= $tableFmt==='form'?' active':'' ?>" href="ver_tabla.php?<?= base_qs($rel,$sheetIdx,$debugShowColor,$perPage,'form') ?>&setfmt=form&page=<?= (int)$page ?>">Formulario (Predio/Dimensiones/...)</a></li>
        </ul>
      </div>

      <?php if($pdf_exists): ?>
        <a class="btnx" target="_blank" href="../<?= e($pdf_rel) ?>" title="Abrir PDF adyacente">📄 PDF</a>
      <?php else: ?>
        <span class="btnx" style="opacity:.5; pointer-events:none;" title="No se encontró PDF">📄 PDF</span>
      <?php endif; ?>

      <button form="bulkForm" class="btnx btnx--accent" title="Guardar cambios de esta página">💾 Guardar</button>
    </div>
  </div>

  <!-- Controles de items por página -->
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
    <form method="get" class="form-compact">
      <input type="hidden" name="p" value="<?= e($rel) ?>">
      <input type="hidden" name="s" value="<?= (int)$sheetIdx ?>">
      <?php if($debugShowColor): ?><input type="hidden" name="showcolor" value="1"><?php endif; ?>
      <?php if($tableFmt): ?><input type="hidden" name="fmt" value="<?= e($tableFmt) ?>"><?php endif; ?>
      <label>Ítems por página:</label>
      <select name="pp" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach($allowedPP as $pp): ?><option value="<?= $pp ?>" <?= $pp===$perPage?'selected':'' ?>><?= $pp ?></option><?php endforeach; ?>
      </select>
      <input type="hidden" name="page" value="1">
    </form>
  </div>

  <!-- Paginador -->
  <?php if($totalPages>1): ?>
    <nav class="mb-2">
      <ul class="pagination pagination-sm">
        <li class="page-item <?= $page<=1?'disabled':'' ?>">
          <a class="page-link" href="ver_tabla.php?<?= $baseQS ?>&page=<?= max(1,$page-1) ?>">«</a>
        </li>
        <?php
          $win=2;
          $from=max(1,$page-$win); $to=min($totalPages,$page+$win);
          if($from>1){ echo '<li class="page-item"><a class="page-link" href="ver_tabla.php?'.$baseQS.'&page=1">1</a></li>'; if($from>2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
          for($p=$from;$p<=$to;$p++){
            $act = $p===$page?' active':'';
            echo '<li class="page-item'.$act.'"><a class="page-link" href="ver_tabla.php?'.$baseQS.'&page='.$p.'">'.$p.'</a></li>';
          }
          if($to<$totalPages){ if($to<$totalPages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; echo '<li class="page-item"><a class="page-link" href="ver_tabla.php?'.$baseQS.'&page='.$totalPages.'">'.$totalPages.'</a></li>'; }
        ?>
        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
          <a class="page-link" href="ver_tabla.php?<?= $baseQS ?>&page=<?= min($totalPages,$page+1) ?>">»</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>

  <!-- Overlay bloqueo -->
  <div id="overlay" aria-hidden="true"><div class="spinner" role="status" aria-label="Guardando..."></div></div>

  <form id="bulkForm" action="save_check_bulk.php" method="post" enctype="multipart/form-data" class="box">
    <input type="hidden" name="file_rel" value="<?= e($rel) ?>">
    <input type="hidden" name="sheet" value="<?= (int)$sheetIdx ?>">
    <input type="hidden" name="showcolor" value="<?= $debugShowColor?'1':'0' ?>">
    <input type="hidden" name="pp" value="<?= (int)$perPage ?>">
    <input type="hidden" name="page" value="<?= (int)$page ?>">
    <input type="hidden" name="fmt" value="<?= e($tableFmt) ?>">

    <div style="overflow:auto; max-height:72vh;">
      <table>
        <thead>
          <tr>
            <?php foreach($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
            <?php if($tableFmt==='form'): ?><th>Dato</th><?php endif; ?>
            <th>Estado</th>
            <th>Observación</th>
            <th>Evidencia</th>
          </tr>
        </thead>
        <tbody>
        <?php
          if(empty($render)){
            $extraCols = ($tableFmt==='form') ? 4 : 3;
            echo '<tr><td colspan="'.(count($headers)+$extraCols).'" class="text-muted">No hay filas para esta página.</td></tr>';
          } else {
            foreach($render as $v){
              if(!empty($v['section'])){
                $title = trim($v['title'] ?? ''); if($title==='') $title='—';
                $extraCols = ($tableFmt==='form') ? 4 : 3;
                echo '<tr class="section"><td colspan="'.count($headers).'">'.e($title).'</td><td colspan="'.$extraCols.'"></td></tr>';
                continue;
              }
              $r = $v['cols'];
              $rowIdx=(int)$v['row_idx'];
              $est=$v['estado']; $obs=$v['observacion']; $ev=$v['ev'];
              $sevClass=$v['sevClass'];
              $fk = $v['form_key'] ?? null;
              $fv = $v['form_val'] ?? '';

              echo '<tr'.($sevClass ? ' class="'.$sevClass.'"' : '').'>';
              foreach($r as $vv){ echo '<td>'.e($vv).'</td>'; }

              // Columna “Dato” (solo en formato Formulario)
              if($tableFmt==='form'){
                echo '<td>';
                if($fk){
                  echo '<input type="hidden" name="form_key['.$rowIdx.']" value="'.e($fk).'">';
                  echo '<input class="form-control form-control-sm" type="text" name="form_val['.$rowIdx.']" value="'.e($fv).'" placeholder="Completar…">';
                }else{
                  echo '<span class="text-muted">—</span>';
                }
                echo '</td>';
              }

              // Estado
              echo '<td><select name="estado['.$rowIdx.']" class="form-select form-select-sm">';
              echo '<option value="" '.($est===''?'selected':'').'>—</option>';
              echo '<option value="si" '.($est==='si'?'selected':'').'>Sí</option>';
              echo '<option value="no" '.($est==='no'?'selected':'').'>No</option>';
              echo '</select></td>';

              // Observación
              echo '<td><input class="form-control form-control-sm" type="text" name="observacion['.$rowIdx.']" value="'.e($obs).'" placeholder="Escribir..."></td>';

              // Evidencia
              echo '<td><div class="d-flex align-items-center gap-2 flex-wrap">';
              echo   '<input class="form-control form-control-sm" type="file" name="evidencia['.$rowIdx.']" accept=".jpg,.jpeg,.png,.pdf,.webp">';
              if ($ev) {
                $qsBack = $baseQS.'&page='.(int)$page;
                echo '<a class="btn btn-sm btn-outline-info" href="../'.e($ev).'" target="_blank">Ver</a>';
                echo '<a class="btn btn-sm btn-outline-danger" href="delete_evidencia.php?row='.$rowIdx.'&'.$qsBack.'" onclick="return confirm(\'¿Eliminar la evidencia de la fila '.$rowIdx.'?\')">Eliminar</a>';
              } else {
                echo '<span class="text-muted">Sin arc…nados</span>';
              }
              echo '</div></td>';

              echo '</tr>';
            }
          }
        ?>
        </tbody>
      </table>
    </div>
  </form>

</div>

<script>
  (function(){
    const form = document.getElementById('bulkForm');
    const overlay = document.getElementById('overlay');
    if(!form || !overlay) return;
    form.addEventListener('submit', function(){
      overlay.classList.add('show');
      overlay.setAttribute('aria-hidden','false');
      document.body.setAttribute('aria-busy','true');
      const toDisable = document.querySelectorAll('button, a.btnx');
      toDisable.forEach(el => { if ('disabled' in el) el.disabled = true; });
    });
  })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
