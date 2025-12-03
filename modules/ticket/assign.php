<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Solo analistas (rol = 3)
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
    // IMPORTANTE: usamos transacción para que la asignación + notificación
    // se manejen de forma consistente.
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
        // Nada se actualizó → alguien más lo tomó o cambió de estado
        $pdo->rollBack();
        echo json_encode([
            'ok'  => false,
            'msg' => 'El ticket ya fue asignado por otro analista o cambió de estado.'
        ]);
        exit;
    }

    // 2) Obtener datos básicos del ticket para poder notificar al usuario
    $stmtTicket = $pdo->prepare("
        SELECT user_id, nombre
        FROM tickets
        WHERE id = :id
        LIMIT 1
    ");
    $stmtTicket->execute([':id' => $ticketId]);
    $ticketRow = $stmtTicket->fetch(PDO::FETCH_ASSOC);

    $userIdTicket = $ticketRow['user_id'] ?? null;

    // 3) Insertar notificación para el usuario (si tenemos user_id)
    if ($userIdTicket) {
        $mensaje = sprintf(
            'Tu ticket #%d será atendido por %s.',
            $ticketId,
            $analystName !== '' ? $analystName : 'un analista'
        );

        // Tabla sugerida: ticket_notifications
        $stmtNotif = $pdo->prepare("
            INSERT INTO ticket_notifications (ticket_id, user_id, mensaje, leido)
            VALUES (:ticket_id, :user_id, :mensaje, 0)
        ");
        $stmtNotif->execute([
            ':ticket_id' => $ticketId,
            ':user_id'   => $userIdTicket,
            ':mensaje'   => $mensaje
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok'      => true,
        'msg'     => 'Ticket asignado correctamente.',
        'ticket_id' => $ticketId,
        'analyst' => $analystName
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error asignando ticket: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno al asignar el ticket.']);
    exit;
}
