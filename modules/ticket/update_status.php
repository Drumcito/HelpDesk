<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';
require_once __DIR__ . '/../../config/audit.php';

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Debe haber sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$rol    = (int)($_SESSION['user_rol'] ?? 0);

// Solo SA, Admin o Analista pueden cambiar estado
if (!in_array($rol, [1, 2, 3], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin permisos para modificar tickets']);
    exit;
}

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$estado   = trim($_POST['estado'] ?? '');

// Estados permitidos
$allowedStatus = ['abierto', 'en_proceso', 'resuelto', 'cerrado'];

if ($ticketId <= 0 || !in_array($estado, $allowedStatus, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

$pdo = Database::getConnection();

try {
    // 1) Validar ticket
    $stmt = $pdo->prepare("
        SELECT id, asignado_a, user_id, area
        FROM tickets
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado']);
        exit;
    }

    // 2) Reglas de permisos: el analista solo puede tocar sus tickets
    if ($rol === 3 && (int)$ticket['asignado_a'] !== $userId) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No puedes modificar este ticket']);
        exit;
    }

    // 3) Actualizar estado (y fecha_resolucion si aplica)
    if (in_array($estado, ['resuelto', 'cerrado'], true)) {
        $sqlUpdate = "
            UPDATE tickets
            SET estado = :estado,
                fecha_resolucion = NOW()
            WHERE id = :id
        ";
    } else {
        $sqlUpdate = "
            UPDATE tickets
            SET estado = :estado,
                fecha_resolucion = NULL
            WHERE id = :id
        ";
    }

    $stmtUp = $pdo->prepare($sqlUpdate);
    $stmtUp->execute([
        ':estado' => $estado,
        ':id'     => $ticketId
    ]);
audit_log($pdo, 'TICKET_STATUS_CHANGE', 'tickets', $ticketId, [
  'from' => $oldStatus,
  'to'   => $newStatus
]);

    // 4) Label bonito
    $estadoLabel = match ($estado) {
        'abierto'     => 'Abierto',
        'en_proceso'  => 'En proceso',
        'resuelto'    => 'Resuelto',
        'cerrado'     => 'Cerrado',
        default       => $estado
    };

    // 5) Intentamos crear notificación al usuario, pero SIN romper si algo falla
    try {
        $stmtNotif = $pdo->prepare("
            INSERT INTO ticket_notifications (ticket_id, user_id, mensaje, created_at, is_read)
            VALUES (:ticket_id, :user_id, :mensaje, NOW(), 0)
        ");
        $mensajeNotif = "El estatus de tu ticket #{$ticketId} cambió a: {$estadoLabel}";

        $stmtNotif->execute([
            ':ticket_id' => $ticketId,
            ':user_id'   => (int)$ticket['user_id'],
            ':mensaje'   => $mensajeNotif
        ]);
    } catch (Exception $eNotif) {
        // Solo lo registramos, pero NO frenamos la respuesta
        error_log('Error insertando notificación de estado: ' . $eNotif->getMessage());
    }

    echo json_encode([
        'ok'           => true,
        'estado'       => $estado,
        'estado_label' => $estadoLabel
    ]);
    exit;

} catch (Exception $e) {
    error_log('Error en update_status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
