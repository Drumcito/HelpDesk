<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']);
  exit;
}

$pdo = Database::getConnection();

$userRol  = (int)($_SESSION['user_rol'] ?? 0);
$userArea = trim((string)($_SESSION['user_area'] ?? ''));

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) $in = [];

$id = (int)($in['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

try {
  // Traer anuncio
  $stmt = $pdo->prepare("SELECT id, created_by_area, is_active FROM announcements WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $a = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$a) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'No existe el anuncio']);
    exit;
  }

  if ((int)$a['is_active'] !== 1) {
    echo json_encode(['ok'=>true,'msg'=>'Ya estaba desactivado']);
    exit;
  }

  $createdByArea = trim((string)($a['created_by_area'] ?? ''));

  $can = false;
  if ($userRol === 2) $can = true;
  if (!$can && $createdByArea !== '' && $userArea !== '' && strcasecmp($createdByArea, $userArea) === 0) $can = true;

  if (!$can) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado para desactivar (solo Admin o el área creadora).']);
    exit;
  }

  $upd = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = ? AND is_active = 1");
  $upd->execute([$id]);

  echo json_encode(['ok'=>true]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error al desactivar']);
}
