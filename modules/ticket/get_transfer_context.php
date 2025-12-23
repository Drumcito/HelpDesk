<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId   = (int)($_SESSION['user_id'] ?? 0);
$userRol  = (int)($_SESSION['user_rol'] ?? 0);
$userArea = (string)($_SESSION['user_area'] ?? '');

$ticketId = (int)($_GET['ticket_id'] ?? 0);
if ($ticketId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ticket_id inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    // ---------------------------------------------------------
    // VALIDACIÓN DE ACCESO (por rol)
    // ---------------------------------------------------------
    // 1 = SA (todo)
    // 2 = Admin (solo tickets de su área)
    // 3 = Analista (solo tickets de su área)
    // 4 = Usuario (solo sus tickets)
    if ($userRol === 4) {
        $stmtOwn = $pdo->prepare("SELECT id FROM tickets WHERE id = :tid AND user_id = :uid LIMIT 1");
        $stmtOwn->execute([':tid' => $ticketId, ':uid' => $userId]);
        if (!$stmtOwn->fetchColumn()) {
            echo json_encode(['ok' => false, 'error' => 'Sin permiso'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } elseif ($userRol === 2 || $userRol === 3) {
        // Admin/Analista -> restringimos por área (y la existencia)
        $stmtArea = $pdo->prepare("SELECT id FROM tickets WHERE id = :tid AND area = :area LIMIT 1");
        $stmtArea->execute([':tid' => $ticketId, ':area' => $userArea]);
        if (!$stmtArea->fetchColumn()) {
            echo json_encode(['ok' => false, 'error' => 'Sin permiso'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        // SA u otros -> solo existencia
        $stmtExists = $pdo->prepare("SELECT id FROM tickets WHERE id = :tid LIMIT 1");
        $stmtExists->execute([':tid' => $ticketId]);
        if (!$stmtExists->fetchColumn()) {
            echo json_encode(['ok' => false, 'error' => 'Ticket no existe'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ---------------------------------------------------------
    // ÚLTIMA TRANSFERENCIA
    // ---------------------------------------------------------
    $stmtTr = $pdo->prepare("
        SELECT id, ticket_id, from_area, to_area, admin_id, motivo, created_at
        FROM ticket_transfers
        WHERE ticket_id = :tid
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtTr->execute([':tid' => $ticketId]);
    $transfer = $stmtTr->fetch(PDO::FETCH_ASSOC);

    if (!$transfer) {
        echo json_encode(['ok' => true, 'has_transfer' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $transferId = (int)$transfer['id'];

    // ---------------------------------------------------------
    // MENSAJES DEL TRASPASO (CHAT BLOQUEADO)
    // ---------------------------------------------------------
    $stmtMsgs = $pdo->prepare("
        SELECT sender_role, sender_name, message, created_at
        FROM ticket_transfer_messages
        WHERE transfer_id = :trid
        ORDER BY created_at ASC
    ");
    $stmtMsgs->execute([':trid' => $transferId]);
    $msgs = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------------
    // ARCHIVOS DEL TRASPASO
    // ---------------------------------------------------------
    $stmtFiles = $pdo->prepare("
        SELECT file_name, file_path, mime_type, created_at
        FROM ticket_transfer_files
        WHERE transfer_id = :trid
        ORDER BY created_at ASC
    ");
    $stmtFiles->execute([':trid' => $transferId]);
    $files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'has_transfer' => true,
        'transfer' => $transfer,
        'messages' => $msgs,
        'files' => $files
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    // IMPORTANTE: no exponer error crudo al front (solo log)
    error_log('[get_transfer_context.php] ' . $e->getMessage());

    echo json_encode([
        'ok' => false,
        'error' => 'Error interno'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
