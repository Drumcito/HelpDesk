<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
  http_response_code(403);
  echo json_encode(['ok'=>false], JSON_UNESCAPED_UNICODE);
  exit;
}

$userId = (int)$_SESSION['user_id'];
$pdo = Database::getConnection();

try {
  $stmt = $pdo->prepare("
    SELECT id
    FROM tickets
    WHERE asignado_a = ?
      AND estado IN ('abierto','en_proceso')
    ORDER BY fecha_envio DESC
    LIMIT 20
  ");
  $stmt->execute([$userId]);
  $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (!$ids) {
    echo json_encode(['ok'=>true,'items'=>[]], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $in = implode(',', array_fill(0, count($ids), '?'));

  $sql = "
    SELECT 
      t.id AS ticket_id,
      COUNT(m.id) AS unread_count
    FROM tickets t
    LEFT JOIN ticket_reads r
      ON r.ticket_id = t.id AND r.user_id = ?
    LEFT JOIN ticket_messages m
      ON m.ticket_id = t.id
     AND m.id > COALESCE(r.last_read_message_id, 0)
    WHERE t.id IN ($in)
    GROUP BY t.id
  ";

  $params = array_merge([$userId], $ids);
  $q = $pdo->prepare($sql);
  $q->execute($params);
  $rows = $q->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok'=>true,
    'items'=>array_map(fn($r)=>[
      'ticket_id'=>(int)$r['ticket_id'],
      'unread_count'=>(int)$r['unread_count'],
    ], $rows)
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  error_log('staff_unread.php: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false], JSON_UNESCAPED_UNICODE);
  exit;
}
