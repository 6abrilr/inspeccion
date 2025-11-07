<?php
require_once __DIR__ . '/../config/db.php';

function nvl($v){ return isset($v)?trim((string)$v):''; }
function safe_name($s){ return preg_replace('/[^A-Za-z0-9_\-\.]/','_', $s); }
function rel_path(){ return str_replace(['\\','//'],'/','storage/evidencias'); }

$file_rel = nvl($_POST['file_rel'] ?? '');
$sheet    = (int)($_POST['sheet'] ?? 0);
$showc    = nvl($_POST['showcolor'] ?? '');

if ($file_rel === '') {
  http_response_code(400);
  exit('Falta file_rel');
}

/* Tabla (si no existe) */
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

$sel = $pdo->prepare("SELECT evidencia_path FROM checklist WHERE file_rel=? AND row_idx=?");
$ins = $pdo->prepare("INSERT INTO checklist(file_rel,row_idx,estado,observacion,evidencia_path,updated_at) VALUES(?,?,?,?,?,NOW())");
$upd = $pdo->prepare("UPDATE checklist SET estado=?, observacion=?, evidencia_path=?, updated_at=NOW() WHERE file_rel=? AND row_idx=?");

/* Carpeta de evidencias */
$base = realpath(__DIR__ . '/..');
$evDirAbs = $base . '/storage/evidencias';
if(!is_dir($evDirAbs)) mkdir($evDirAbs, 0777, true);

$estados = $_POST['estado'] ?? [];
$observ  = $_POST['observacion'] ?? [];
$files   = $_FILES['evidencia'] ?? null;

/* Transacción por consistencia */
$pdo->beginTransaction();

try {
  foreach($estados as $idxStr => $estado){
    $idx = (int)$idxStr; 
    if($idx<=0) continue;

    $obs = nvl($observ[$idxStr] ?? '');

    $sel->execute([$file_rel,$idx]); 
    $prev = $sel->fetchColumn();      // false si no hay fila
    $evPath = $prev ?: null;

    /* Evidencia (si subieron) */
    if($files 
       && isset($files['name'][$idxStr]) 
       && isset($files['error'][$idxStr]) 
       && $files['error'][$idxStr]===UPLOAD_ERR_OK)
    {
      $tmp  = $files['tmp_name'][$idxStr];
      $name = $files['name'][$idxStr];
      $ext  = strtolower(pathinfo($name,PATHINFO_EXTENSION));

      if(in_array($ext,['jpg','jpeg','png','pdf','webp'])){
        $fname='ev_'.md5($file_rel.'|'.$idx.'|'.microtime(true)).'.'.$ext;
        $dest=$evDirAbs.'/'.$fname;
        if(@move_uploaded_file($tmp,$dest)){
          $evPath = rel_path().'/'.$fname; // storage/evidencias/...
        }
      }
    }

    /* Insert/Update */
    if($prev===false){
      $ins->execute([$file_rel,$idx,$estado?:null,$obs?:null,$evPath]);
    }else{
      $upd->execute([$estado?:null,$obs?:null,$evPath,$file_rel,$idx]);
    }
  }

  $pdo->commit();

  /* Al terminar OK → volvemos al dashboard */
  header('Location: index.php?saved=1');
  exit;

} catch(Throwable $e){
  $pdo->rollBack();
  http_response_code(500);
  echo "Error al guardar: ".$e->getMessage();
  exit;
}
