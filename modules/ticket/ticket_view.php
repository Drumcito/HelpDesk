<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
  exit;
}

$role = (int)($_SESSION['user_rol'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if ($ticketId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'ticket_id inválido']);
  exit;
}

$pdo = Database::getConnection();

try {
  // Leer ticket
  $stmt = $pdo->prepare("
    SELECT id, user_id, sap, nombre, area, email, problema, prioridad, descripcion,
           fecha_envio, estado, asignado_a, fecha_asignacion, fecha_resolucion
    FROM tickets
    WHERE id = :id
    LIMIT 1
  ");
  $stmt->execute([':id' => $ticketId]);
  $t = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$t) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado']);
    exit;
  }

  // Permisos:
  // - Usuario (rol 4? el tuyo) solo ve si es suyo
  // - Analista (rol 3) solo si está asignado a él O si es de su área y está abierto (para previsualizar)
  // - Admin/SA ven todo
  if ($role === 3) {
    $myArea = (string)($_SESSION['user_area'] ?? '');
    $assignedTo = (int)($t['asignado_a'] ?? 0);

    $can =
      ($assignedTo === $userId) ||
      ((string)$t['area'] === $myArea && (string)$t['estado'] === 'abierto' && ($assignedTo === 0));

    if (!$can) {
      http_response_code(403);
      echo json_encode(['ok' => false, 'msg' => 'No puedes ver este ticket']);
      exit;
    }
  } elseif ($role !== 1 && $role !== 2) {
    // asumo "usuario" = cualquier rol diferente de 1/2/3
    if ((int)$t['user_id'] !== $userId) {
      http_response_code(403);
      echo json_encode(['ok' => false, 'msg' => 'No puedes ver este ticket']);
      exit;
    }
  }

  // Adjuntos del ticket (del usuario al crear)
  $stmtA = $pdo->prepare("
    SELECT id, ticket_id, file_name, file_path, mime_type, created_at
    FROM ticket_attachments
    WHERE ticket_id = :id
    ORDER BY id ASC
  ");
  $stmtA->execute([':id' => $ticketId]);
  $attachments = $stmtA->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'ticket' => [
      'id' => (int)$t['id'],
      'user_id' => (int)$t['user_id'],
      'sap' => (string)$t['sap'],
      'nombre' => (string)$t['nombre'],
      'email' => (string)$t['email'],
      'area' => (string)$t['area'],
      'problema' => (string)$t['problema'],
      'prioridad' => (string)$t['prioridad'],
      'descripcion' => (string)$t['descripcion'],
      'fecha_envio' => (string)$t['fecha_envio'],
      'estado' => (string)$t['estado'],
      'asignado_a' => (int)($t['asignado_a'] ?? 0),
      'fecha_asignacion' => (string)($t['fecha_asignacion'] ?? ''),
      'fecha_resolucion' => (string)($t['fecha_resolucion'] ?? ''),
    ],
    'attachments' => array_map(function($a){
      return [
        'id' => (int)$a['id'],
        'file_name' => (string)$a['file_name'],
        'file_path' => (string)$a['file_path'],
        'mime_type' => (string)($a['mime_type'] ?? ''),
        'created_at' => (string)($a['created_at'] ?? ''),
      ];
    }, $attachments)
  ]);
  exit;

} catch (Exception $e) {
  error_log('ticket_view.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Error interno']);
  exit;
}
