<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

$userArea = trim((string)($_SESSION['user_area'] ?? ''));
$rol = (int)($_SESSION['user_rol'] ?? 0);

try {
  $stmt = $pdo->query("
    SELECT id, title, body, level, target_area, starts_at, ends_at, created_at, created_by_area
    FROM announcements
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 20
  ");
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // Agrega permiso por item (misma lógica que tu PHP)
  foreach ($items as &$a) {
    $createdByArea = trim((string)($a['created_by_area'] ?? ''));
    $canDisable = ($rol === 2) || (strcasecmp($createdByArea, $userArea) === 0);
    $a['can_disable'] = $canDisable ? 1 : 0;
  }
  unset($a);

  $sigBase = array_map(fn($a) => [
    (int)$a['id'],
    (string)($a['title'] ?? ''),
    (string)($a['body'] ?? ''),
    (string)($a['level'] ?? ''),
    (string)($a['target_area'] ?? ''),
    (string)($a['starts_at'] ?? ''),
    (string)($a['ends_at'] ?? ''),
    (string)($a['created_at'] ?? ''),
    (string)($a['created_by_area'] ?? ''),
    (int)($a['can_disable'] ?? 0),
  ], $items);

  echo json_encode([
    'ok' => true,
    'items' => $items,
    'signature' => sha1(json_encode($sigBase, JSON_UNESCAPED_UNICODE)),
    'count' => count($items),
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error servidor'], JSON_UNESCAPED_UNICODE);
}
