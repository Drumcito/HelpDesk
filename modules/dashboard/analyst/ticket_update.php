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

$ticketId = (int)($_POST['id'] ?? 0);
$nuevoEstado = $_POST['estado'] ?? '';

$estadosPermitidos = ['abierto', 'en_proceso', 'cerrado'];

if ($ticketId <= 0 || !in_array($nuevoEstado, $estadosPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$analistaId = (int)$_SESSION['user_id'];

try {
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

    $estadoActual = $ticket['estado'];
    $asignadoA = (int)($ticket['asignado_a'] ?? 0);

    /* ===============================
       ABIERTO → EN_PROCESO (Aceptar)
    =============================== */
    if ($estadoActual === 'abierto' && $nuevoEstado === 'en_proceso') {

        // Si ya fue asignado a otro analista
        if ($asignadoA !== 0 && $asignadoA !== $analistaId) {
            $pdo->rollBack();
            echo json_encode([
                'ok' => false,
                'msg' => 'Este ticket ya fue aceptado por otro analista'
            ], JSON_UNESCAPED_UNICODE);
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
            echo json_encode([
                'ok' => false,
                'msg' => 'No se pudo aceptar el ticket'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /* ===============================
       EN_PROCESO → CERRADO
    =============================== */
    if ($estadoActual === 'en_proceso' && $nuevoEstado === 'cerrado') {

        // Solo el analista asignado puede cerrar
        if ($asignadoA !== $analistaId) {
            $pdo->rollBack();
            echo json_encode([
                'ok' => false,
                'msg' => 'No puedes cerrar un ticket que no te pertenece'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $upd = $pdo->prepare("
            UPDATE tickets
            SET estado = 'cerrado',
                fecha_resolucion = NOW()
            WHERE id = ?
              AND estado = 'en_proceso'
        ");
        $upd->execute([$ticketId]);

        createTicketFeedback($pdo, $ticketId, (int)$ticket['user_id']);

    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'estado' => $nuevoEstado
    ], JSON_UNESCAPED_UNICODE);

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
    $check = $pdo->prepare("
        SELECT id FROM ticket_feedback WHERE ticket_id = ?
    ");
    $check->execute([$ticketId]);

    if ($check->fetch()) {
        return; // ya existe, no duplicar
    }

    $token = bin2hex(random_bytes(32)); // 64 chars

    $insert = $pdo->prepare("
        INSERT INTO ticket_feedback (ticket_id, user_id, token)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$ticketId, $userId]);

    // Aquí puedes mandar notificación o correo si quieres
}
