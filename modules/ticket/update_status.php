<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';
require_once __DIR__ . '/../../config/audit.php';

header('Content-Type: application/json; charset=utf-8');

/* ===========================
   VALIDACIONES BÁSICAS
=========================== */
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

// SA (1), Admin (2), Analista (3)
if (!in_array($rol, [1, 2, 3], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin permisos']);
    exit;
}

$ticketId  = (int)($_POST['ticket_id'] ?? 0);
$newStatus = trim((string)($_POST['estado'] ?? ''));

if ($ticketId <= 0 || $newStatus === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

$pdo = Database::getConnection();

try {
    /* ===========================
       VALIDAR ESTADO (CATÁLOGO)
    =========================== */
    $st = $pdo->prepare("
        SELECT code, label
        FROM catalog_status
        WHERE active = 1 AND code = ?
        LIMIT 1
    ");
    $st->execute([$newStatus]);
    $statusRow = $st->fetch(PDO::FETCH_ASSOC);

    if (!$statusRow) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Estado no permitido']);
        exit;
    }

    $pdo->beginTransaction();

    /* ===========================
       OBTENER TICKET (LOCK)
    =========================== */
    $stmt = $pdo->prepare("
        SELECT id, estado, asignado_a, user_id
        FROM tickets
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado']);
        exit;
    }

$oldStatus = trim((string)($ticket['estado'] ?? ''));

if ($oldStatus === '') {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode([
        'ok' => false,
        'msg' => 'El ticket tiene un estado inválido en BD (vacío).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$assignedTo   = (int)($ticket['asignado_a'] ?? 0);
$ticketUserId = (int)($ticket['user_id'] ?? 0);


    /* ===========================
       PERMISOS ANALISTA
    =========================== */
    if ($rol === 3 && $assignedTo !== $userId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No puedes modificar este ticket']);
        exit;
    }

    /* ===========================
       TRANSICIONES PERMITIDAS
    =========================== */
    $allowedTransitions = [
        'abierto'    => ['en_proceso'],
        'en_proceso' => ['soporte', 'cerrado', 'abierto'],
        'soporte'    => ['en_proceso', 'cerrado', 'abierto'],
        'cerrado'    => [],
    ];

    if (
        !isset($allowedTransitions[$oldStatus]) ||
        !in_array($newStatus, $allowedTransitions[$oldStatus], true)
    ) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'msg' => "Transición no permitida: {$oldStatus} → {$newStatus}"
        ]);
        exit;
    }

    /* ===========================
       UPDATE DEL TICKET
    =========================== */
    if ($newStatus === 'cerrado') {
        $sql = "
            UPDATE tickets
            SET estado = 'cerrado',
                fecha_resolucion = NOW()
            WHERE id = ?
        ";
        $params = [$ticketId];
    }
    elseif ($newStatus === 'abierto' && in_array($oldStatus, ['en_proceso', 'soporte'], true)) {
        // Reabrir y liberar
        $sql = "
            UPDATE tickets
            SET estado = 'abierto',
                asignado_a = NULL,
                fecha_asignacion = NULL,
                fecha_resolucion = NULL
            WHERE id = ?
        ";
        $params = [$ticketId];
    }
    else {
        // en_proceso / soporte
        $sql = "
            UPDATE tickets
            SET estado = ?,
                fecha_resolucion = NULL
            WHERE id = ?
        ";
        $params = [$newStatus, $ticketId];
    }

    $upd = $pdo->prepare($sql);
    $upd->execute($params);

    /* ===========================
       AUDITORÍA (NO BLOQUEA)
    =========================== */
    try {
        audit_log($pdo, 'TICKET_STATUS_CHANGE', 'tickets', $ticketId, [
            'from' => $oldStatus,
            'to'   => $newStatus
        ]);
    } catch (Throwable $e) {
        error_log('Audit error: ' . $e->getMessage());
    }

    /* ===========================
       CREAR ENCUESTA (SI CIERRA)
    =========================== */
    if ($newStatus === 'cerrado') {
        $check = $pdo->prepare("
            SELECT id FROM ticket_feedback WHERE ticket_id = ? LIMIT 1
        ");
        $check->execute([$ticketId]);

        if (!$check->fetch()) {
            $token = bin2hex(random_bytes(32));

            $fb = $pdo->prepare("
                INSERT INTO ticket_feedback
                (ticket_id, user_id, token, q1_attention, q2_resolved, q3_time)
                VALUES (?, ?, ?, 0, 0, 0)
            ");
            $fb->execute([$ticketId, $ticketUserId, $token]);
        }
    }

    /* ===========================
       NOTIFICACIÓN USUARIO
    =========================== */
    try {
        $msg = "El estatus de tu ticket #{$ticketId} cambió a: {$statusRow['label']}";
        $n = $pdo->prepare("
            INSERT INTO ticket_notifications
            (ticket_id, user_id, mensaje, created_at, is_read)
            VALUES (?, ?, ?, NOW(), 0)
        ");
        $n->execute([$ticketId, $ticketUserId, $msg]);
    } catch (Throwable $e) {
        error_log('Notif error: ' . $e->getMessage());
    }

    $pdo->commit();

$released = (
    $newStatus === 'abierto'
    && in_array($oldStatus, ['en_proceso','soporte'], true)
) ? 1 : 0;

$incomingTicket = null;

if ($released === 1) {
    // Re-consulta para poder devolver los datos a la UI (tabla entrantes)
    $q = $pdo->prepare("
        SELECT
            t.id,
            t.fecha_envio,
            t.nombre,
            t.descripcion,
            t.prioridad,
            COALESCE(cp1.label, cp2.label, t.problema) AS problema_label
        FROM tickets t
        LEFT JOIN catalog_problems cp1 ON cp1.id = t.problema
        LEFT JOIN catalog_problems cp2 ON cp2.code = t.problema
        WHERE t.id = ?
        LIMIT 1
    ");
    $q->execute([$ticketId]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $incomingTicket = [
            'id'          => (int)$row['id'],
            'fecha'       => (string)$row['fecha_envio'],
            'usuario'     => (string)$row['nombre'],
            'problema'    => (string)$row['problema_label'],
            'prioridad'   => (string)$row['prioridad'],
            'descripcion' => (string)$row['descripcion'],
        ];
    }
}

echo json_encode([
    'ok'            => true,
    'estado'        => $newStatus,
    'estado_label'  => $statusRow['label'],
    'released'      => $released,
    'incoming_ticket' => $incomingTicket
], JSON_UNESCAPED_UNICODE);
exit;



} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('update_status.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno al actualizar el estatus']);
    exit;
}
