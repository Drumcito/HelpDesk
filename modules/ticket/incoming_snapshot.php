<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$area = (string)($_SESSION['user_area'] ?? '');
$idsRaw = trim($_POST['ids'] ?? '');
if ($idsRaw === '') {
  echo json_encode(['ok' => true, 'available_ids' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

$ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw)), fn($x) => $x > 0));
if (!$ids) {
  echo json_encode(['ok' => true, 'available_ids' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

try {
  $in = implode(',', array_fill(0, count($ids), '?'));

  $sql = "
    SELECT id
    FROM tickets
    WHERE area = ?
      AND estado = 'abierto'
      AND (asignado_a IS NULL OR asignado_a = 0)
      AND id IN ($in)
  ";

  $params = array_merge([$area], $ids);

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $available = $stmt->fetchAll(PDO::FETCH_COLUMN);

  echo json_encode([
    'ok' => true,
    'available_ids' => array_map('intval', $available)
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  error_log('incoming_snapshot error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
  exit;
}
