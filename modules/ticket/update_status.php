<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';
require_once __DIR__ . '/../../config/audit.php';

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Debe haber sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$rol    = (int)($_SESSION['user_rol'] ?? 0);

// Solo SA, Admin o Analista pueden cambiar estado
if (!in_array($rol, [1, 2, 3], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin permisos para modificar tickets'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$estado   = trim((string)($_POST['estado'] ?? ''));

// Estados permitidos
$allowedStatus = ['abierto', 'en_proceso', 'resuelto', 'cerrado'];

if ($ticketId <= 0 || !in_array($estado, $allowedStatus, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();

    // 1) Traer ticket + estado actual (con bloqueo)
    $stmt = $pdo->prepare("
        SELECT id, estado, asignado_a, user_id, area
        FROM tickets
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([':id' => $ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $oldStatus = (string)$ticket['estado'];
    $newStatus = $estado;

    // 2) Reglas de permisos: el analista solo puede tocar sus tickets
    if ($rol === 3 && (int)$ticket['asignado_a'] !== $userId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No puedes modificar este ticket'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3) Actualizar estado (y fecha_resolucion si aplica)
    if (in_array($newStatus, ['resuelto', 'cerrado'], true)) {
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
        ':estado' => $newStatus,
        ':id'     => $ticketId
    ]);

    // 4) Audit (NO romper si falla)
    try {
        audit_log($pdo, 'TICKET_STATUS_CHANGE', 'tickets', $ticketId, [
            'from' => $oldStatus,
            'to'   => $newStatus
        ]);
    } catch (Throwable $eAudit) {
        error_log('Audit error: ' . $eAudit->getMessage());
    }

    // 5) Si se cerró → crear encuesta (ticket_feedback) SIN duplicar
    if ($newStatus === 'cerrado') {
        $token = bin2hex(random_bytes(32)); // 64 chars

        $stmtFb = $pdo->prepare("
            INSERT INTO ticket_feedback (ticket_id, user_id, token, q1_attention, q2_resolved, q3_time)
            VALUES (:ticket_id, :user_id, :token, 0, 0, 0)
            ON DUPLICATE KEY UPDATE token = token
        ");
        $stmtFb->execute([
            ':ticket_id' => $ticketId,
            ':user_id'   => (int)$ticket['user_id'],
            ':token'     => $token
        ]);
    }

    // 6) Label bonito
    $estadoLabel = match ($newStatus) {
        'abierto'     => 'Abierto',
        'en_proceso'  => 'En proceso',
        'resuelto'    => 'Resuelto',
        'cerrado'     => 'Cerrado',
        default       => $newStatus
    };

    // 7) Notificación al usuario (sin romper si falla)
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
    } catch (Throwable $eNotif) {
        error_log('Error insertando notificación de estado: ' . $eNotif->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'ok'           => true,
        'estado'       => $newStatus,
        'estado_label' => $estadoLabel
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error en update_status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno al actualizar el estatus.'], JSON_UNESCAPED_UNICODE);
    exit;
}
