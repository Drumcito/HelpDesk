<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$userId = (int)$_SESSION['user_id'];
$rol    = (int)($_SESSION['user_rol'] ?? 0);
$ticketId = (int)($_POST['ticket_id'] ?? 0);

if ($ticketId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Ticket inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

try {
  // seguridad mínima: solo puede marcar leído si es su ticket (usuario) o si es staff (1/2/3)
  if ($rol === 4) { // si tu user normal es 4 (ajusta si aplica)
    $st = $pdo->prepare("SELECT user_id FROM tickets WHERE id=? LIMIT 1");
    $st->execute([$ticketId]);
    $owner = (int)$st->fetchColumn();
    if ($owner !== $userId) {
      http_response_code(403);
      echo json_encode(['ok'=>false,'msg'=>'Sin permisos'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  } else {
    if (!in_array($rol, [1,2,3], true)) {
      http_response_code(403);
      echo json_encode(['ok'=>false,'msg'=>'Sin permisos'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  // último mensaje visible para ese rol:
  // - usuario NO debe “leer” internos
  if ($rol === 4) {
    $q = $pdo->prepare("SELECT COALESCE(MAX(id),0) FROM ticket_messages WHERE ticket_id=? AND is_internal=0");
    $q->execute([$ticketId]);
  } else {
    $q = $pdo->prepare("SELECT COALESCE(MAX(id),0) FROM ticket_messages WHERE ticket_id=?");
    $q->execute([$ticketId]);
  }
  $lastId = (int)$q->fetchColumn();

  $up = $pdo->prepare("
    INSERT INTO ticket_reads (ticket_id, user_id, last_read_message_id)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE last_read_message_id = VALUES(last_read_message_id)
  ");
  $up->execute([$ticketId, $userId, $lastId]);

  echo json_encode(['ok'=>true,'last_read_message_id'=>$lastId], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  error_log('mark_read.php: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno'], JSON_UNESCAPED_UNICODE);
  exit;
}
