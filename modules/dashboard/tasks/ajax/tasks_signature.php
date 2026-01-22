<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No auth']);
  exit;
}

$pdo = Database::getConnection();
$uid = (int)$_SESSION['user_id'];
$rol = currentRole();

$taskId = (int)($_GET['task_id'] ?? 0);        // opcional: firma de 1 tarea
$sinceEventId = (int)($_GET['since_event_id'] ?? 0); // para notificaciones

try {
  if (!in_array($rol, [2,3], true)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No role']);
    exit;
  }

  // =============== ADMIN (rol 2) ==================
  if ($rol === 2) {

    // firma de 1 tarea
    if ($taskId > 0) {
      $stmt = $pdo->prepare("SELECT id,status,due_at,created_at,assigned_to_user_id,priority_id,updated_at FROM tasks WHERE id=? AND created_by_admin_id=? LIMIT 1");
      $stmt->execute([$taskId, $uid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
      echo json_encode(['ok'=>true,'signature'=>sha1(json_encode($row)), 'events'=>[], 'max_event_id'=>$sinceEventId]);
      exit;
    }

    // firma del listado admin
    $stmt = $pdo->prepare("
      SELECT id,status,due_at,created_at,assigned_to_user_id,priority_id,updated_at
      FROM tasks
      WHERE created_by_admin_id=?
      ORDER BY created_at DESC
      LIMIT 200
    ");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $signature = sha1(json_encode($rows));

    // eventos recientes para admin (reasignó/canceló/algo)
    $stmtE = $pdo->prepare("
      SELECT id, task_id, event_type, note, created_at
      FROM task_events
      WHERE actor_user_id = ?
        AND event_type IN ('REASSIGNED','CANCELED')
        AND id > ?
      ORDER BY id ASC
      LIMIT 20
    ");
    $stmtE->execute([$uid, $sinceEventId]);
    $events = $stmtE->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $maxId = $sinceEventId;
    foreach ($events as $e) { $maxId = max($maxId, (int)$e['id']); }

    echo json_encode(['ok'=>true,'signature'=>$signature,'events'=>$events,'max_event_id'=>$maxId], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // =============== ANALISTA (rol 3) ==================
  if ($taskId > 0) {
    $stmt = $pdo->prepare("SELECT id,status,due_at,created_at,updated_at FROM tasks WHERE id=? AND assigned_to_user_id=? LIMIT 1");
    $stmt->execute([$taskId, $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['ok'=>true,'signature'=>sha1(json_encode($row)), 'events'=>[], 'max_event_id'=>$sinceEventId]);
    exit;
  }

  // firma del listado analista (solo lo asignado actualmente)
  $stmt = $pdo->prepare("
    SELECT id,status,due_at,created_at,created_by_admin_id,priority_id,updated_at
    FROM tasks
    WHERE assigned_to_user_id=?
    ORDER BY created_at DESC
    LIMIT 200
  ");
  $stmt->execute([$uid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $signature = sha1(json_encode($rows));

$likeAssignedTo = '%"assigned_to_user_id":' . $uid . '%';
$likeAssignee   = '%"assignee_id":' . $uid . '%';
$likeHash       = '%#' . $uid . '%';

  $stmtE = $pdo->prepare("
    SELECT id, task_id, event_type, note, created_at
    FROM task_events
    WHERE event_type IN ('REASSIGNED','CANCELED')
      AND id > ?
      AND (
  old_value LIKE ? OR old_value LIKE ? OR note LIKE ?
)

    ORDER BY id ASC
    LIMIT 20
  ");
$stmtE->execute([$sinceEventId, $likeAssignedTo, $likeAssignee, $likeHash]);
  $events = $stmtE->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $maxId = $sinceEventId;
  foreach ($events as $e) { $maxId = max($maxId, (int)$e['id']); }

echo json_encode([
  'ok' => true,
  'signature' => $signature,
  'events' => $events,
  'max_event_id' => (int)$maxId
], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Server error']);
}
