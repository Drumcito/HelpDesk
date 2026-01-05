<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false]);
  exit;
}

$pdo = Database::getConnection();

$rawArea = trim((string)($_SESSION['user_area'] ?? ''));
$audience = (stripos($rawArea, 'sucursal') !== false) ? 'Sucursal' : 'Corporativo';

$stmt = $pdo->prepare("
  SELECT id, title, body, level, target_area, starts_at, ends_at, created_at
  FROM announcements
  WHERE target_area = ?
    AND is_active = 1
  ORDER BY created_at DESC
  LIMIT 10
");
$stmt->execute([$audience]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
  'ok' => true,
  'audience' => $audience,
  'items' => $items
]);
