<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

$rol = (int)($_SESSION['user_rol'] ?? 0);
// Admin (2) y Analista (3) pueden desactivar
if (!isset($_SESSION['user_id']) || !in_array($rol, [2,3], true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
  exit;
}

$pdo = Database::getConnection();

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);

$id = (int)($in['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

try {
  $stmt = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = ?");
  $stmt->execute([$id]);
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error al desactivar']);
}
