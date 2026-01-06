<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Solo Analista
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

$ticketId     = (int)($_POST['id'] ?? 0);
$nuevoEstado  = trim((string)($_POST['estado'] ?? ''));

if ($ticketId <= 0 || $nuevoEstado === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$analistaId = (int)$_SESSION['user_id'];

try {
    //  Validar estado contra catálogo (evita des-sync)
    $stmtAllowed = $pdo->prepare("
        SELECT 1
        FROM catalog_status
        WHERE activo = 1 AND code = ?
        LIMIT 1
    ");
    $stmtAllowed->execute([$nuevoEstado]);

    if (!$stmtAllowed->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Estado no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    // Bloqueo del ticket
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
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $estadoActual = (string)($ticket['estado'] ?? '');
    $asignadoA    = (int)($ticket['asignado_a'] ?? 0);
    $usuarioId    = (int)($ticket['user_id'] ?? 0);

    // Helper: ticket "sin asignar" (NULL o 0)
    $isUnassigned = ($asignadoA === 0);

    //  Reglas de transición permitidas
    $allowedTransitions = [
        'abierto'    => ['en_proceso'],
        'en_proceso' => ['soporte', 'cerrado', 'abierto'],
        'soporte'    => ['en_proceso', 'cerrado', 'abierto'],
        'cerrado'    => [], // no se reabre desde aquí (si quieres reabrir luego, lo agregamos)
    ];

    // Si el estado actual no está en el mapa, lo tratamos como no permitido
    if (!isset($allowedTransitions[$estadoActual]) || !in_array($nuevoEstado, $allowedTransitions[$estadoActual], true)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'msg' => "Transición no permitida: {$estadoActual} → {$nuevoEstado}"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ===============================
       ABIERTO → EN_PROCESO (Aceptar)
    =============================== */
    if ($estadoActual === 'abierto' && $nuevoEstado === 'en_proceso') {

        // Si ya fue asignado a otro analista
        if (!$isUnassigned && $asignadoA !== $analistaId) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Este ticket ya fue aceptado por otro analista'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $upd = $pdo->prepare("
            UPDATE tickets
            SET estado = 'en_proceso',
                asignado_a = ?,
                fecha_asignacion = NOW(),
                fecha_primera_respuesta = IFNULL(fecha_primera_respuesta, NOW())
            WHERE id = ?
              AND estado = 'abierto'
              AND (asignado_a IS NULL OR asignado_a = 0)
        ");
        $upd->execute([$analistaId, $ticketId]);

        if ($upd->rowCount() === 0) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'No se pudo aceptar el ticket'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /* ===============================
       EN_PROCESO/SOPORTE → SOPORTE/EN_PROCESO
       (solo el asignado puede mover)
    =============================== */
    if (
        in_array($estadoActual, ['en_proceso','soporte'], true)
        && in_array($nuevoEstado, ['en_proceso','soporte'], true)
    ) {
        if ($asignadoA !== $analistaId) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'No puedes cambiar el estado de un ticket que no te pertenece'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $upd = $pdo->prepare("
            UPDATE tickets
            SET estado = ?
            WHERE id = ?
        ");
        $upd->execute([$nuevoEstado, $ticketId]);
    }

    /* ===============================
       EN_PROCESO/SOPORTE → ABIERTO (LIBERAR)
       - vuelve a entrantes
       - se desasigna
    =============================== */
    if (in_array($estadoActual, ['en_proceso','soporte'], true) && $nuevoEstado === 'abierto') {

        if ($asignadoA !== $analistaId) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'No puedes liberar un ticket que no te pertenece'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $upd = $pdo->prepare("
            UPDATE tickets
            SET estado = 'abierto',
                asignado_a = NULL,
                fecha_asignacion = NULL
            WHERE id = ?
        ");
        $upd->execute([$ticketId]);
    }

    /* ===============================
       EN_PROCESO/SOPORTE → CERRADO
    =============================== */
    if (in_array($estadoActual, ['en_proceso','soporte'], true) && $nuevoEstado === 'cerrado') {

        if ($asignadoA !== $analistaId) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'No puedes cerrar un ticket que no te pertenece'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $upd = $pdo->prepare("
            UPDATE tickets
            SET estado = 'cerrado',
                fecha_resolucion = NOW()
            WHERE id = ?
        ");
        $upd->execute([$ticketId]);

        createTicketFeedback($pdo, $ticketId, $usuarioId);
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'estado' => $nuevoEstado], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error interno',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function createTicketFeedback(PDO $pdo, int $ticketId, int $userId): void {
    // Verifica que no exista ya
    $check = $pdo->prepare("SELECT id FROM ticket_feedback WHERE ticket_id = ? LIMIT 1");
    $check->execute([$ticketId]);

    if ($check->fetch()) return;

    $token = bin2hex(random_bytes(32)); // 64 chars

    $insert = $pdo->prepare("
        INSERT INTO ticket_feedback (ticket_id, user_id, token)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$ticketId, $userId, $token]);
}
