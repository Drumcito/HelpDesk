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
      t.email,
      t.prioridad,
      t.problema AS problema_raw,
      COALESCE(cp1.label, cp2.label, t.problema) AS problema_label,

      CONCAT(COALESCE(u.name,''),' ',COALESCE(u.last_name,'')) AS atendido_por,
      f.token AS feedback_token

    FROM tickets t
    LEFT JOIN users u ON u.id = t.asignado_a
    LEFT JOIN catalog_problems cp1 ON cp1.id = t.problema
    LEFT JOIN catalog_problems cp2 ON cp2.code = t.problema
    LEFT JOIN ticket_feedback f
      ON f.ticket_id = t.id
     AND f.answered_at IS NULL

    WHERE t.user_id = :uid
      AND t.area = 'TI'
      AND (
            t.estado IN ('abierto','en_proceso','soporte')
         OR (t.estado = 'cerrado' AND f.id IS NOT NULL)
      )
    ORDER BY t.fecha_envio DESC
    LIMIT 10
  ");
  $stmt->execute([':uid' => $userId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // signature para polling inteligente
  $sig = md5(json_encode(array_map(fn($x)=>[
    $x['id'] ?? null,
    $x['estado'] ?? null,
    $x['asignado_a'] ?? null,
    $x['atendido_por'] ?? null,
    $x['prioridad'] ?? null,
    $x['problema_label'] ?? null,
    $x['feedback_token'] ?? null,
  ], $items), JSON_UNESCAPED_UNICODE));

  echo json_encode(['ok' => true, 'items' => $items, 'signature' => $sig], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'msg' => 'Error interno',
    'debug' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
