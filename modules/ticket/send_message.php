<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$rol    = (int)($_SESSION['user_rol'] ?? 0);

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$mensaje  = trim($_POST['mensaje'] ?? '');
$isInternal = isset($_POST['interno']) ? (int)$_POST['interno'] : 0;

// Evitar que usuarios marquen interno
if ($rol === 4) {
    $isInternal = 0;
}

if ($ticketId <= 0 || $mensaje === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

$pdo = Database::getConnection();

try {
    // Obtenemos info del ticket para validar permisos
    $stmt = $pdo->prepare("
        SELECT id, user_id, asignado_a
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

    $ticketUserId   = (int)$ticket['user_id'];
    $ticketAnalystId= (int)($ticket['asignado_a'] ?? 0);

    // Permisos:
    // - Usuario (rol 4) solo su propio ticket
    // - Analista (rol 3) solo si está asignado a él
    // - SA (1) o Admin (2) podrían mandar a cualquiera (por si quieres)
    $allowed = false;

    if ($rol === 4 && $userId === $ticketUserId) {
        $allowed = true;
    } elseif ($rol === 3 && $userId === $ticketAnalystId) {
        $allowed = true;
    } elseif (in_array($rol, [1, 2], true)) {
        $allowed = true;
    }

    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Sin permisos para escribir en este ticket']);
        exit;
    }

    // Rol de texto
    $senderRole = match ($rol) {
        3       => 'analista',
        4       => 'usuario',
        1       => 'sa',
        2       => 'admin',
        default => 'usuario'
    };

    $stmtIns = $pdo->prepare("
        INSERT INTO ticket_messages (ticket_id, sender_id, sender_role, mensaje, is_internal)
        VALUES (:ticket_id, :sender_id, :sender_role, :mensaje, :is_internal)
    ");
    $stmtIns->execute([
        ':ticket_id'   => $ticketId,
        ':sender_id'   => $userId,
        ':sender_role' => $senderRole,
        ':mensaje'     => $mensaje,
        ':is_internal' => $isInternal
    ]);

    $msgId = (int)$pdo->lastInsertId();
    $createdAt = date('Y-m-d H:i:s');

    echo json_encode([
        'ok'    => true,
        'id'    => $msgId,
        'at'    => $createdAt,
        'role'  => $senderRole
    ]);
    exit;

} catch (Exception $e) {
    error_log('Error en send_message: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
