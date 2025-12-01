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

$ticketId   = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$analystId  = (int)$_SESSION['user_id'];

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Ticket inválido']);
    exit;
}

$pdo = Database::getConnection();

try {
    // Solo asignar si el ticket sigue sin asignar
    $stmt = $pdo->prepare("
        UPDATE tickets
        SET asignado_a = :analyst,
            estado = 'en_proceso',
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
        echo json_encode([
            'ok'  => false,
            'msg' => 'El ticket ya fue asignado por otro analista o cambió de estado.'
        ]);
        exit;
    }

    echo json_encode(['ok' => true, 'msg' => 'Ticket asignado correctamente.']);
    exit;

} catch (Exception $e) {
    error_log('Error asignando ticket: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno al asignar el ticket.']);
    exit;
}
