<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
  exit;
}

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
  exit;
}

$adminId   = (int)$_SESSION['user_id'];
$adminArea = trim($_SESSION['user_area'] ?? '');

$ticketId  = (int)($_POST['ticket_id'] ?? 0);
$toAnalyst = (int)($_POST['to_analyst_id'] ?? 0);
$motivo    = trim($_POST['motivo'] ?? '');

if ($ticketId <= 0 || $toAnalyst <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Datos inválidos']);
  exit;
}

try {
  $pdo = Database::getConnection();
  $pdo->beginTransaction();

  // 1) ticket existe y es de mi área
  $stT = $pdo->prepare("SELECT id, area, estado, asignado_a, problema FROM tickets WHERE id=:id LIMIT 1");
  $stT->execute([':id'=>$ticketId]);
  $t = $stT->fetch(PDO::FETCH_ASSOC);

  if (!$t) {
    $pdo->rollBack();
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'Ticket no encontrado']);
    exit;
  }

  if ((string)$t['area'] !== $adminArea) {
    $pdo->rollBack();
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Ese ticket no es de tu área']);
    exit;
  }

  // opcional: no permitir asignar cerrados
  if (in_array($t['estado'], ['cerrado'], true)) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'No puedes asignar un ticket cerrado']);
    exit;
  }

  $fromAnalyst = (int)($t['asignado_a'] ?? 0);

  // 2) validar analista destino (rol=3 y del área)
  $stA = $pdo->prepare("SELECT id, name, last_name FROM users WHERE id=:id AND rol=3 AND area=:area LIMIT 1");
  $stA->execute([':id'=>$toAnalyst, ':area'=>$adminArea]);
  $a = $stA->fetch(PDO::FETCH_ASSOC);

  if (!$a) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Analista inválido o no pertenece a tu área']);
    exit;
  }

  // 3) actualizar ticket
  $stUp = $pdo->prepare("
    UPDATE tickets
    SET asignado_a = :aid,
        fecha_asignacion = COALESCE(fecha_asignacion, NOW())
    WHERE id = :tid
    LIMIT 1
  ");
  $stUp->execute([':aid'=>$toAnalyst, ':tid'=>$ticketId]);

  // 4) Log (ticket_assignments_log)
$stmtLog = $pdo->prepare("
    INSERT INTO ticket_assignments_log
        (ticket_id, from_analyst_id, to_analyst_id, admin_id, motivo, created_at)
    VALUES
        (:tid, :from_id, :to_id, :admin_id, :motivo, NOW())
");
$stmtLog->execute([
    ':tid'      => $ticketId,
    ':from_id'  => ($fromAnalyst > 0 ? $fromAnalyst : null),
    ':to_id'    => $analystId,
    ':admin_id' => $userId,
    ':motivo'   => ($motivo !== '' ? mb_substr($motivo, 0, 255) : null),
]);


  // 5) notificación al analista destino
  $title = ($fromAnalyst > 0) ? "Ticket reasignado #{$ticketId}" : "Nuevo ticket asignado #{$ticketId}";
  $body  = "Problema: " . (string)$t['problema'] . ($motivo ? " | Motivo: {$motivo}" : "");
  $link  = "/HelpDesk_EQF/modules/dashboard/analyst/analyst.php?open_ticket=" . $ticketId;

  $stN = $pdo->prepare("
    INSERT INTO notifications (user_id, type, title, body, link, is_read)
    VALUES (:uid, :type, :title, :body, :link, 0)
  ");
  $stN->execute([
    ':uid'   => $toAnalyst,
    ':type'  => 'ticket_assigned',
    ':title' => $title,
    ':body'  => mb_substr($body, 0, 255),
    ':link'  => $link
  ]);

  $pdo->commit();

  echo json_encode([
    'ok' => true,
    'msg'=> ($fromAnalyst > 0 ? 'Ticket reasignado' : 'Ticket asignado'),
    'from_analyst_id' => $fromAnalyst,
    'to_analyst_id'   => $toAnalyst,
    'to_name'         => trim(($a['name'] ?? '') . ' ' . ($a['last_name'] ?? '')),
  ]);
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('admin_assign_ticket error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno']);
  exit;
}
