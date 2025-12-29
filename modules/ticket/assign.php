<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$ticketId  = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$analystId = (int)$_SESSION['user_id'];
$analystName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));

if ($ticketId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Ticket inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

try {
  $pdo->beginTransaction();

  // asigna solo si sigue abierto y sin asignar
  $stmt = $pdo->prepare("
    UPDATE tickets
    SET asignado_a       = :analyst,
        estado           = 'en_proceso',
        fecha_asignacion = NOW()
    WHERE id = :id
      AND (asignado_a IS NULL OR asignado_a = 0)
      AND estado = 'abierto'
  ");
  $stmt->execute([':analyst' => $analystId, ':id' => $ticketId]);

  if ($stmt->rowCount() === 0) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'El ticket ya fue asignado o cambió de estado.'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // trae el ticket completo + label
  $stmtT = $pdo->prepare("
    SELECT
      t.id,
      t.fecha_envio,
      t.nombre AS usuario,
      t.problema AS problema_raw,
      COALESCE(cp.label, t.problema) AS problema_label,
      t.prioridad,
      t.estado,
      t.user_id
    FROM tickets t
    LEFT JOIN catalog_problems cp ON cp.code = t.problema
    WHERE t.id = :id
    LIMIT 1
  ");
  $stmtT->execute([':id' => $ticketId]);
  $ticket = $stmtT->fetch(PDO::FETCH_ASSOC);

  // notificación al usuario
  if (!empty($ticket['user_id'])) {
    $msg = sprintf(
      'Tu ticket #%d será atendido por %s.',
      $ticketId,
      $analystName !== '' ? $analystName : 'un analista'
    );

    // OJO: ajustado a created_at + is_read (como tu update_status.php)
    $stmtN = $pdo->prepare("
      INSERT INTO ticket_notifications (ticket_id, user_id, mensaje, created_at, is_read)
      VALUES (:ticket_id, :user_id, :mensaje, NOW(), 0)
    ");
    $stmtN->execute([
      ':ticket_id' => $ticketId,
      ':user_id'   => (int)$ticket['user_id'],
      ':mensaje'   => $msg
    ]);
  }

  $pdo->commit();

  echo json_encode([
    'ok' => true,
    'msg' => 'Ticket asignado correctamente.',
    'ticket_id' => $ticketId,
    'analyst' => $analystName,
    'ticket' => [
      'id' => (int)($ticket['id'] ?? $ticketId),
      'fecha_envio' => $ticket['fecha_envio'] ?? '',
      'usuario' => $ticket['usuario'] ?? '',
      'problema_raw' => $ticket['problema_raw'] ?? '',
      'problema_label' => $ticket['problema_label'] ?? ($ticket['problema_raw'] ?? ''),
      'prioridad' => $ticket['prioridad'] ?? 'media',
      'estado' => $ticket['estado'] ?? 'en_proceso'
    ]
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('assign.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Error interno al asignar el ticket.'], JSON_UNESCAPED_UNICODE);
  exit;
}
