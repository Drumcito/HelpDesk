<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$rol    = (int)($_SESSION['user_rol'] ?? 0);

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$lastId   = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Ticket inválido']);
    exit;
}

$pdo = Database::getConnection();

try {
    // Validar que el usuario tenga acceso al ticket
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

    $ticketUserId    = (int)$ticket['user_id'];
    $ticketAnalystId = (int)($ticket['asignado_a'] ?? 0);

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
        echo json_encode(['ok' => false, 'msg' => 'Sin permisos para ver mensajes de este ticket']);
        exit;
    }

    // Usuario final (rol 4) NO ve mensajes internos
    $hideInternal = ($rol === 4) ? 1 : 0;

    $sql = "
        SELECT id, sender_id, sender_role, mensaje, is_internal, created_at
        FROM ticket_messages
        WHERE ticket_id = :ticket_id
          AND id > :last_id
    ";

    if ($hideInternal) {
        $sql .= " AND is_internal = 0 ";
    }

    $sql .= " ORDER BY id ASC";

    $stmtMsg = $pdo->prepare($sql);
    $stmtMsg->execute([
        ':ticket_id' => $ticketId,
        ':last_id'   => $lastId
    ]);

    $rows = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok'       => true,
        'messages' => $rows
    ]);
    exit;

} catch (Exception $e) {
    error_log('Error en get_messages: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
