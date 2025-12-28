<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$ticketId  = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$analystId = (int)$_SESSION['user_id'];
$analystName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Ticket inválido']);
    exit;
}

$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();

    // 1) Asignar solo si sigue abierto y sin analista
    $stmt = $pdo->prepare("
        UPDATE tickets
        SET asignado_a       = :analyst,
            estado           = 'en_proceso',
            fecha_asignacion = NOW()
        WHERE id = :id
          AND (asignado_a IS NULL OR asignado_a = 0)
          AND estado = 'abierto'
    ");
    $stmt->execute([
        ':analyst' => $analystId,
        ':id'      => $ticketId
    ]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode([
            'ok'  => false,
            'msg' => 'El ticket ya fue asignado por otro analista o cambió de estado.'
        ]);
        exit;
    }

    // 2) Leer ticket actualizado (para responder al front)
    $stmtTicket = $pdo->prepare("
        SELECT id, user_id, sap, nombre, area, email, problema, prioridad, descripcion,
               fecha_envio, estado, asignado_a, fecha_asignacion
        FROM tickets
        WHERE id = :id
        LIMIT 1
    ");
    $stmtTicket->execute([':id' => $ticketId]);
    $ticketRow = $stmtTicket->fetch(PDO::FETCH_ASSOC);

    if (!$ticketRow) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado']);
        exit;
    }

    $userIdTicket = (int)$ticketRow['user_id'];

    // 3) Notificación a usuario
    $mensaje = sprintf(
        'Tu ticket #%d será atendido por %s.',
        $ticketId,
        $analystName !== '' ? $analystName : 'un analista'
    );

    // Ajusta campos según tu tabla real:
    // - tú aquí usas "leido"
    $stmtNotif = $pdo->prepare("
        INSERT INTO ticket_notifications (ticket_id, user_id, mensaje, leido)
        VALUES (:ticket_id, :user_id, :mensaje, 0)
    ");
    $stmtNotif->execute([
        ':ticket_id' => $ticketId,
        ':user_id'   => $userIdTicket,
        ':mensaje'   => $mensaje
    ]);

    $pdo->commit();

    echo json_encode([
        'ok'      => true,
        'msg'     => 'Ticket asignado correctamente.',
        'ticket'  => [
            'id'               => (int)$ticketRow['id'],
            'problema_raw'     => (string)$ticketRow['problema'],
            'prioridad'        => (string)$ticketRow['prioridad'],
            'estado'           => (string)$ticketRow['estado'], // en_proceso
            'fecha_envio'      => (string)$ticketRow['fecha_envio'],
            'fecha_asignacion' => (string)$ticketRow['fecha_asignacion'],
            'usuario'          => (string)$ticketRow['nombre'],
            'sap'              => (string)$ticketRow['sap'],
            'area'             => (string)$ticketRow['area'],
        ],
        'analyst' => $analystName
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error asignando ticket: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno al asignar el ticket.']);
    exit;
}
