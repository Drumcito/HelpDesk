<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit('No autenticado');
}

$pdo = Database::getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('ID inválido'); }

// audiencia
$userAreaRaw = trim((string)($_SESSION['user_area'] ?? ''));
$audience = (stripos($userAreaRaw, 'sucursal') !== false) ? 'SUCURSAL' : 'CORPORATIVO';

// opcional TI/SAP/MKT
$userAreaUpper = strtoupper($userAreaRaw);
$extraVisibility = in_array($userAreaUpper, ['TI','SAP','MKT'], true) ? $userAreaUpper : null;

$allowed = ['ALL', $audience];
if ($extraVisibility) $allowed[] = $extraVisibility;

$stmt = $pdo->prepare("
  SELECT stored_name, original_name, mime_type, visibility
  FROM documents
  WHERE id = :id
  LIMIT 1
");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) { http_response_code(404); exit('No encontrado'); }

$vis = strtoupper(trim((string)($doc['visibility'] ?? '')));
if (!in_array($vis, $allowed, true)) { http_response_code(403); exit('Sin permiso'); }

$stored = trim((string)($doc['stored_name'] ?? ''));
if ($stored === '') { http_response_code(404); exit('Archivo no disponible'); }

// ✅ directorio real de uploads/docs
$docsDir = realpath(__DIR__ . '/../../../uploads/docs');
if (!$docsDir || !is_dir($docsDir)) {
  http_response_code(500);
  exit('Directorio docs no disponible');
}

$full = realpath($docsDir . DIRECTORY_SEPARATOR . $stored);

if (!$full || strpos($full, $docsDir) !== 0 || !is_file($full)) {
  http_response_code(404);
  exit('Archivo no encontrado');
}

$mime = trim((string)($doc['mime_type'] ?? ''));
if ($mime === '') $mime = 'application/octet-stream';

$downloadName = trim((string)($doc['original_name'] ?? ''));
if ($downloadName === '') $downloadName = basename($full);

// Descargar
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
