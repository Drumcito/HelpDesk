<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

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
    // Validar acceso al ticket
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

//  Dueño del ticket puede ver mensajes (sea usuario final o analista)
if ($userId === $ticketUserId) {
    $allowed = true;

//  Analista asignado puede ver mensajes
} elseif ($rol === 3 && $userId === $ticketAnalystId) {
    $allowed = true;

//  SA / Admin
} elseif (in_array($rol, [1, 2], true)) {
    $allowed = true;
}


    if (!$allowed) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Sin permisos para ver mensajes de este ticket']);
        exit;
    }

    // Usuario final (rol 4) NO ve mensajes internos
    $hideInternal = ($rol === 4);
// ---------------------------------------------------------
// Evitar duplicado: si el ticket fue transferido, ocultar mensajes
// anteriores a la última transferencia (ya se muestran en Historial transferido)
// ---------------------------------------------------------
$sinceTransferAt = null;

$stmtLastTr = $pdo->prepare("
    SELECT created_at
    FROM ticket_transfers
    WHERE ticket_id = :tid
    ORDER BY created_at DESC
    LIMIT 1
");
$stmtLastTr->execute([':tid' => $ticketId]);
$sinceTransferAt = $stmtLastTr->fetchColumn();
if (!$sinceTransferAt) $sinceTransferAt = null;

    $sql = "
        SELECT 
            m.id,
            m.sender_id,
            m.sender_role,
            COALESCE(u.name,'') AS sender_name,
            m.mensaje,
            m.is_internal,
            m.created_at,
            f.file_name,
            f.file_path,
            f.file_type
        FROM ticket_messages m
        LEFT JOIN users u
            ON u.id = m.sender_id
        LEFT JOIN ticket_message_files f
               ON f.message_id = m.id
        WHERE m.ticket_id = :ticket_id
          AND m.id > :last_id
    ";

if ($sinceTransferAt) {
    $sql .= " AND m.created_at >= :since_transfer ";
}


    if ($hideInternal) {
        $sql .= " AND (m.is_internal = 0 OR m.is_internal IS NULL) ";
    }

    $sql .= " ORDER BY m.id ASC";

    $stmtMsg = $pdo->prepare($sql);

$params = [
    ':ticket_id' => $ticketId,
    ':last_id'   => $lastId
];

if ($sinceTransferAt) {
    $params[':since_transfer'] = $sinceTransferAt;
}

$stmtMsg->execute($params);


    $rows = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        if (!empty($r['file_path'])) {
            $r['file_url'] = '/HelpDesk_EQF/' . ltrim($r['file_path'], '/');
        } else {
            $r['file_url'] = null;
        }
    }
    unset($r);

    echo json_encode([
        'ok'       => true,
        'messages' => $rows
    ]);
    exit;

} catch (Throwable $e) {
    error_log('Error en get_messages: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
