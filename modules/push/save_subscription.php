<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
  exit;
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
$area = trim((string)($_SESSION['user_area'] ?? ''));

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Payload inválido']);
  exit;
}

$endpoint = (string)$data['endpoint'];
$p256dh = (string)$data['keys']['p256dh'];
$auth = (string)$data['keys']['auth'];
$ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

try {
  $stmt = $pdo->prepare("
    INSERT INTO push_subscriptions (user_id, area, endpoint, p256dh, auth, user_agent)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      area = VALUES(area),
      p256dh = VALUES(p256dh),
      auth = VALUES(auth),
      user_agent = VALUES(user_agent),
      updated_at = CURRENT_TIMESTAMP
  ");
  $stmt->execute([$userId, $area, $endpoint, $p256dh, $auth, $ua]);

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Error al guardar']);
}
