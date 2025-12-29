<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Auth
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);
$userArea = (string)($_SESSION['user_area'] ?? '');

if (!in_array($rol, [1,2,3], true)) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'Sin permisos'], JSON_UNESCAPED_UNICODE);
  exit;
}

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if ($ticketId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Ticket inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

try {
  // Ticket + label
  $stmt = $pdo->prepare("
    SELECT
      t.id, t.user_id, t.asignado_a, t.area,
      t.sap, t.nombre, t.email,
      t.problema AS problema_raw,
      COALESCE(cp.label, t.problema) AS problema_label,
      t.descripcion, t.fecha_envio, t.estado, t.prioridad
    FROM tickets t
    LEFT JOIN catalog_problems cp ON cp.code = t.problema
    WHERE t.id = :id
    LIMIT 1
  ");
  $stmt->execute([':id' => $ticketId]);
  $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$ticket) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Permisos (ajústalo a tu regla)
  if ($rol === 3) {
    // analista: o está asignado a él, o al menos es su área
    $isAssigned = ((int)($ticket['asignado_a'] ?? 0) === $userId);
    $sameArea = ((string)($ticket['area'] ?? '') === $userArea);

    if (!$isAssigned && !$sameArea) {
      http_response_code(403);
      echo json_encode(['ok' => false, 'msg' => 'No puedes ver este ticket'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  // Adjuntos (tu tabla: ticket_attachments)
  $stmtA = $pdo->prepare("
    SELECT
      id,
      nombre_archivo AS file_name,
      ruta_archivo   AS file_path,
      tipo           AS file_type,
      peso           AS file_size,
      subido_en      AS created_at
    FROM ticket_attachments
    WHERE ticket_id = :id
    ORDER BY id ASC
  ");
  $stmtA->execute([':id' => $ticketId]);
  $attachments = $stmtA->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'ticket' => $ticket,
    'attachments' => $attachments
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  error_log('ticket_view.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
  exit;
}
