<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false], JSON_UNESCAPED_UNICODE);
  exit;
}

$userId = (int)$_SESSION['user_id'];
$pdo = Database::getConnection();

try {
  // los mismos tickets que enseñas en user.php (ajusta si tu query difiere)
  $stmt = $pdo->prepare("
    SELECT t.id
    FROM tickets t
    WHERE t.user_id = ?
      AND t.estado IN ('abierto','en_proceso','cerrado','resuelto')
    ORDER BY t.fecha_envio DESC
    LIMIT 10
  ");
  $stmt->execute([$userId]);
  $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (!$ids) {
    echo json_encode(['ok'=>true,'items'=>[]], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $in = implode(',', array_fill(0, count($ids), '?'));

  // unread = mensajes NO internos con id > last_read_message_id
  $sql = "
    SELECT 
      t.id AS ticket_id,
      COUNT(m.id) AS unread_count
    FROM tickets t
    LEFT JOIN ticket_reads r
      ON r.ticket_id = t.id AND r.user_id = ?
    LEFT JOIN ticket_messages m
      ON m.ticket_id = t.id
     AND m.is_internal = 0
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
  error_log('user_unread.php: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false], JSON_UNESCAPED_UNICODE);
  exit;
}
