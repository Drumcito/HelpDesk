<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Solo analistas
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ticketId  = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$analystId = (int)$_SESSION['user_id'];
$analystName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$area = (string)($_SESSION['user_area'] ?? '');

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Ticket inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

try {
    // 0) Verificar que el ticket sea de tu área y esté disponible (evita tomar de otras áreas)
    $stmtCheck = $pdo->prepare("
        SELECT id, area
        FROM tickets
        WHERE id = :id
        LIMIT 1
    ");
    $stmtCheck->execute([':id' => $ticketId]);
    $rowCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$rowCheck) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ((string)$rowCheck['area'] !== $area) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No puedes asignar tickets de otra área'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1) Asignar (sin depender de notificación)
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE tickets
        SET asignado_a       = :analyst,
            estado           = 'en_proceso',
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
        $pdo->rollBack();
        echo json_encode([
            'ok'  => false,
            'msg' => 'El ticket ya fue asignado por otro analista o cambió de estado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
// ===== NOTIFICACIÓN AL USUARIO: ticket asignado a analista =====

$stmtT = $pdo->prepare("
  SELECT id, user_id
  FROM tickets
  WHERE id = ?
  LIMIT 1
");
$stmtT->execute([(int)$ticketId]);
$tk = $stmtT->fetch(PDO::FETCH_ASSOC);

$userTicketId = (int)($tk['user_id'] ?? 0);
if ($userTicketId > 0) {

  $stmtA = $pdo->prepare("SELECT CONCAT(name,' ',last_name) AS full_name FROM users WHERE id=? LIMIT 1");
  $stmtA->execute([(int)$analystId]);
  $analystName = (string)($stmtA->fetchColumn() ?: 'Analista');

  $link = "/HelpDesk_EQF/modules/dashboard/user/user.php?open_ticket=" . (int)$ticketId;

  $stmtN = $pdo->prepare("
    INSERT INTO notifications (user_ide, type, title, body, link, is_read, created_at)
    VALUES (?, 'ticket_assigned', ?, ?, ?, 0, NOW())
  ");
  $stmtN->execute([
    $userTicketId,
    "Ticket #{$ticketId} asignado",
    "Tu ticket será atendido por: {$analystName}",
    $link
  ]);
}

    // 2) Traer datos completos para devolver al frontend
    $stmtTicket = $pdo->prepare("
        SELECT
            t.id,
            t.fecha_envio,
            t.nombre,
            t.prioridad,
            t.problema AS problema_raw,
            COALESCE(cp.label, t.problema) AS problema_label,
            t.user_id
        FROM tickets t
        LEFT JOIN catalog_problems cp
               ON cp.code = t.problema
        WHERE t.id = :id
        LIMIT 1
    ");
    $stmtTicket->execute([':id' => $ticketId]);
    $ticketRow = $stmtTicket->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    // 3) Notificar (NO rompe la asignación si falla)
    //    Si tu tabla no existe o está distinta, aquí ya no te tumba el flujo.
    try {
        if (!empty($ticketRow['user_id'])) {
            $mensaje = sprintf(
                'Tu ticket #%d será atendido por %s.',
                $ticketId,
                $analystName !== '' ? $analystName : 'un analista'
            );

            $stmtNotif = $pdo->prepare("
                INSERT INTO ticket_notifications (ticket_id, user_id, mensaje, leido)
                VALUES (:ticket_id, :user_id, :mensaje, 0)
            ");
            $stmtNotif->execute([
                ':ticket_id' => $ticketId,
                ':user_id'   => (int)$ticketRow['user_id'],
                ':mensaje'   => $mensaje
            ]);
        }
    } catch (Throwable $eNotif) {
        // Solo log, no interrumpas
        error_log('assign.php notif error: ' . $eNotif->getMessage());
    }

    echo json_encode([
        'ok'      => true,
        'msg'     => 'Ticket asignado correctamente.',
        'ticket'  => [
            'id'            => (int)($ticketRow['id'] ?? $ticketId),
            'fecha_envio'   => (string)($ticketRow['fecha_envio'] ?? ''),
            'usuario'       => (string)($ticketRow['nombre'] ?? ''),
            'prioridad'     => (string)($ticketRow['prioridad'] ?? 'media'),
            'problema_raw'  => (string)($ticketRow['problema_raw'] ?? ''),
            'problema_label'=> (string)($ticketRow['problema_label'] ?? ($ticketRow['problema_raw'] ?? '')),
        ],
        'analyst' => $analystName
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error asignando ticket (assign.php): ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno al asignar el ticket.'], JSON_UNESCAPED_UNICODE);
    exit;
}
