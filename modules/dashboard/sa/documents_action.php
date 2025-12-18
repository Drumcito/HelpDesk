<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();

$action = $_POST['action'] ?? '';

$visAllowed = ['ALL','TI','SAP','MKT','SUCURSAL','CORPORATIVO'];

$uploadDir = __DIR__ . '/../../../uploads/docs/';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}

function redirectOk($msg) {
  header('Location: /HelpDesk_EQF/modules/dashboard/sa/documents.php?ok=' . urlencode($msg));
  exit;
}
function redirectErr($msg) {
  header('Location: /HelpDesk_EQF/modules/dashboard/sa/documents.php?err=' . urlencode($msg));
  exit;
}

if ($action === 'upload') {

  $display = trim($_POST['display_name'] ?? '');
  $visibility = strtoupper(trim($_POST['visibility'] ?? 'ALL'));

  if ($display === '') redirectErr('Nombre visible requerido.');
  if (!in_array($visibility, $visAllowed, true)) redirectErr('Visibilidad inválida.');

  if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name'])) {
    redirectErr('Selecciona un archivo.');
  }

  $f = $_FILES['file'];
  if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    redirectErr('Error al subir archivo (código ' . (int)$f['error'] . ').');
  }

  $size = (int)($f['size'] ?? 0);
  if ($size <= 0) redirectErr('Archivo vacío.');
  if ($size > 20 * 1024 * 1024) redirectErr('Máximo 20 MB.');

  $original = basename((string)($f['name'] ?? 'archivo'));
  $mime = (string)($f['type'] ?? 'application/octet-stream');

  // nombre único guardado
  $ext = pathinfo($original, PATHINFO_EXTENSION);
  $stored = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');

  $dest = $uploadDir . $stored;

  if (!move_uploaded_file($f['tmp_name'], $dest)) {
    redirectErr('No se pudo guardar el archivo en el servidor.');
  }

  try {
    $st = $pdo->prepare("
      INSERT INTO documents
        (display_name, stored_name, original_name, mime_type, size_bytes, visibility, uploaded_by)
      VALUES
        (?, ?, ?, ?, ?, ?, ?)
    ");
    $st->execute([
      $display,
      $stored,
      $original,
      $mime,
      $size,
      $visibility,
      (int)$_SESSION['user_id']
    ]);
  } catch (Throwable $e) {
    // rollback manual: borrar archivo si falló BD
    @unlink($dest);
    redirectErr('No se pudo registrar el documento en BD.');
  }

  redirectOk('Documento subido correctamente.');
}

if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) redirectErr('ID inválido.');

  try {
    $st = $pdo->prepare("SELECT stored_name FROM documents WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) redirectErr('Documento no encontrado.');

    $stored = (string)$row['stored_name'];

    $stD = $pdo->prepare("DELETE FROM documents WHERE id=?");
    $stD->execute([$id]);

    $path = $uploadDir . $stored;
    if (is_file($path)) @unlink($path);

  } catch (Throwable $e) {
    redirectErr('No se pudo eliminar.');
  }

  redirectOk('Documento eliminado.');
}

redirectErr('Acción no válida.');
