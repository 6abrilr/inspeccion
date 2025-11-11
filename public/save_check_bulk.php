<?php
// public/save_check_bulk.php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=utf-8');

// ===== Helpers =====
function bad($msg, $http=400){
  http_response_code($http);
  echo "<pre style='color:#f00;background:#111;padding:12px;border-radius:8px'>".$msg."</pre>";
  exit;
}
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ===== Entrada obligatoria =====
$file_rel   = $_POST['file_rel'] ?? '';
$sheet      = isset($_POST['sheet']) ? (int)$_POST['sheet'] : 0;
$showcolor  = isset($_POST['showcolor']) ? (string)$_POST['showcolor'] : '0';
$ESTADOS    = $_POST['estado']      ?? [];  // [row_idx => 'si'|'no'|'' ]
$OBS        = $_POST['observacion'] ?? [];  // [row_idx => texto]
$EVID       = $_FILES['evidencia']  ?? null; // input multiple (índices por row_idx)

if ($file_rel === '') bad("Falta parámetro file_rel");

// ===== Normalizar arrays de inputs =====
if (!is_array($ESTADOS))    $ESTADOS = [];
if (!is_array($OBS))        $OBS     = [];

// ===== Preparar subida de archivos =====
$projectBase = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$eviRoot     = $projectBase . '/storage/evidencias';
$yy          = date('Y'); $mm = date('m');
$targetDir   = $eviRoot . "/$yy/$mm";
if (!is_dir($targetDir)) {
  @mkdir($targetDir, 0775, true);
}

// ===== DB =====
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

// ===== Subir evidencias (si hay) mapeando por índice =====
$subidas = []; // [row_idx => relative_path]
if ($EVID && isset($EVID['name']) && is_array($EVID['name'])) {
  foreach ($EVID['name'] as $rowIdx => $name) {
    if ($name === '' || !isset($EVID['tmp_name'][$rowIdx])) continue;
    $tmp    = $EVID['tmp_name'][$rowIdx];
    $err    = (int)$EVID['error'][$rowIdx];
    $type   = (string)($EVID['type'][$rowIdx] ?? '');
    $size   = (int)($EVID['size'][$rowIdx] ?? 0);
    if ($err !== UPLOAD_ERR_OK) continue;

    // Validaciones básicas
    // Extensiones permitidas
    $allowed = ['jpg','jpeg','png','pdf','webp'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) continue;

    // Nombre final único
    $base = preg_replace('/[^a-zA-Z0-9_\-\.]/','_', pathinfo($name, PATHINFO_FILENAME));
    $destName = $base . '__' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $destAbs  = $targetDir . '/' . $destName;

    if (@move_uploaded_file($tmp, $destAbs)) {
      // ruta relativa desde /public (subís un nivel para salir de /public)
      $rel = 'storage/evidencias/' . $yy . '/' . $mm . '/' . $destName;
      $subidas[(int)$rowIdx] = $rel;
      // permisos “amables” si el FS lo permite
      @chmod($destAbs, 0644);
    }
  }
}

// ===== Guardado en bloque =====
$ins = $pdo->prepare("
  INSERT INTO checklist (file_rel, row_idx, estado, observacion, evidencia_path, updated_at)
  VALUES (?,?,?,?,?,NOW())
  ON DUPLICATE KEY UPDATE
    estado=VALUES(estado),
    observacion=VALUES(observacion),
    evidencia_path=COALESCE(VALUES(evidencia_path), evidencia_path),
    updated_at=NOW()
");

$pdo->beginTransaction();

$cont = 0;
$rowsAfectados = [];
// Tomamos la unión de todos los índices que llegaron (estado/obs/ev)
$idxs = array_unique(array_map('intval', array_merge(array_keys($ESTADOS), array_keys($OBS), array_keys($subidas))));
sort($idxs);

foreach ($idxs as $idx) {
  $estado = $ESTADOS[$idx] ?? '';
  $estado = ($estado === '' ? null : ($estado === 'si' ? 'si' : ($estado === 'no' ? 'no' : null)));

  $observ = trim((string)($OBS[$idx] ?? ''));
  $evRel  = $subidas[$idx] ?? null; // si no subieron en este request, queda null para no sobreescribir

  $ins->execute([$file_rel, $idx, $estado, $observ, $evRel]);
  $cont++;
  $rowsAfectados[] = $idx;
}

$pdo->commit();

// ===== DEBUG opcional =====
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
  echo "<pre style='background:#0b1020;color:#e6f0ff;padding:12px;border-radius:10px'>";
  echo "Guardado OK\n";
  echo "file_rel: ".e($file_rel)."\n";
  echo "sheet: $sheet   showcolor: $showcolor\n";
  echo "Filas afectadas: $cont\n";
  echo "Indices: ".e(json_encode($rowsAfectados))."\n";
  echo "</pre>";
  echo '<a href="ver_tabla.php?p='.rawurlencode($file_rel).'&s='.(int)$sheet.'&showcolor='.urlencode($showcolor).'" style="color:#9ef">Volver</a>';
  exit;
}

// ===== Redirigir de vuelta =====
$qs = 'p='.rawurlencode($file_rel).'&s='.(int)$sheet.'&showcolor='.urlencode($showcolor);
header('Location: ver_tabla.php?'.$qs);
exit;
