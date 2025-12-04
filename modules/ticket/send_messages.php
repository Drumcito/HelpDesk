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

// Usuario final no puede marcar interno
if ($rol === 4) {
    $isInternal = 0;
}

$hasFile = isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] !== UPLOAD_ERR_NO_FILE;

if ($ticketId <= 0 || ($mensaje === '' && !$hasFile)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

$pdo = Database::getConnection();

try {
    // Validar ticket / permisos
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

    $senderRole = match ($rol) {
        3       => 'analista',
        4       => 'usuario',
        1       => 'sa',
        2       => 'admin',
        default => 'usuario'
    };

    $pdo->beginTransaction();

    // 1) Insertamos mensaje
    $stmtIns = $pdo->prepare("
        INSERT INTO ticket_messages (ticket_id, sender_id, sender_role, mensaje, is_internal)
        VALUES (:ticket_id, :sender_id, :sender_role, :mensaje, :is_internal)
    ");
    $stmtIns->execute([
        ':ticket_id'   => $ticketId,
        ':sender_id'   => $userId,
        ':sender_role' => $senderRole,
        ':mensaje'     => $mensaje !== '' ? $mensaje : '[Archivo adjunto]',
        ':is_internal' => $isInternal
    ]);

    $msgId = (int)$pdo->lastInsertId();

    // 2) Si hay archivo, lo guardamos
    $fileInfo = null;

    if ($hasFile && isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/ticket_messages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $origName  = $_FILES['adjunto']['name'];
        $tmpName   = $_FILES['adjunto']['tmp_name'];
        $mimeType  = $_FILES['adjunto']['type'] ?? null;
        $ext       = pathinfo($origName, PATHINFO_EXTENSION);

        $safeExt   = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $newName   = 't' . $ticketId . '_m' . $msgId . '_' . uniqid() . ($safeExt ? '.' . $safeExt : '');
        $destPath  = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $relPath = 'uploads/ticket_messages/' . $newName;

            $stmtFile = $pdo->prepare("
                INSERT INTO ticket_message_files (message_id, ticket_id, file_name, file_path, file_type)
                VALUES (:message_id, :ticket_id, :file_name, :file_path, :file_type)
            ");
            $stmtFile->execute([
                ':message_id' => $msgId,
                ':ticket_id'  => $ticketId,
                ':file_name'  => $origName,
                ':file_path'  => $relPath,
                ':file_type'  => $mimeType
            ]);

            $fileInfo = [
                'name' => $origName,
                'path' => $relPath,
                'type' => $mimeType
            ];
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok'    => true,
        'id'    => $msgId,
        'file'  => $fileInfo
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error en send_message: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
