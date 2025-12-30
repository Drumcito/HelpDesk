<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$userId = (int)$_SESSION['user_id'];

try {
  $pdo = Database::getConnection();

  $stmt = $pdo->prepare("
    SELECT 
  t.id,
  t.fecha_envio,
  t.estado,
  t.asignado_a,
  CONCAT(COALESCE(u.name,''),' ',COALESCE(u.last_name,'')) AS atendido_por,
  f.token AS feedback_token
FROM tickets t
LEFT JOIN users u ON u.id = t.asignado_a
LEFT JOIN ticket_feedback f
  ON f.ticket_id = t.id
 AND f.answered_at IS NULL
WHERE t.user_id = :uid
  AND t.area = 'TI'
  AND (
        t.estado IN ('abierto','en_proceso')
     OR (t.estado='cerrado' AND f.id IS NOT NULL)
  )
ORDER BY t.fecha_envio DESC
LIMIT 10

  ");
  $stmt->execute([':uid' => $userId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'msg' => 'Error interno',
    'debug' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
