<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}

if (!isset($_SESSION['user_id']) || currentRole() !== 2) {
  header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}

$pdo = Database::getConnection();
$adminId = (int)$_SESSION['user_id'];
$taskId = (int)($_POST['task_id'] ?? 0);

if ($taskId <= 0) {
  $_SESSION['flash_err'] = 'Tarea inválida.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}

$stmt = $pdo->prepare("SELECT id, assigned_to_user_id, status FROM tasks WHERE id=? AND created_by_admin_id=? LIMIT 1");
$stmt->execute([$taskId, $adminId]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$t) {
  $_SESSION['flash_err'] = 'Tarea no válida.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}

$oldAnalystId = (int)$t['assigned_to_user_id'];

$pdo->prepare("
  UPDATE tasks
  SET status='CANCELADA',
      canceled_at=NOW(),
      updated_at=NOW()
  WHERE id=? AND created_by_admin_id=?
  LIMIT 1
")->execute([$taskId, $adminId]);
// ===== NOTIFICACIÓN (CANCEL) al analista afectado =====
if ($oldAnalystId > 0) {
  $link = "/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=" . (int)$taskId;

  // título de tarea
  $stmtT = $pdo->prepare("SELECT title FROM tasks WHERE id=? LIMIT 1");
  $stmtT->execute([$taskId]);
  $taskTitle = (string)($stmtT->fetchColumn() ?: '');

  $stmtN = $pdo->prepare("
    INSERT INTO notifications (user_ide, type, title, body, link, is_read, created_at)
    VALUES (?, 'task_canceled', ?, ?, ?, 0, NOW())
  ");
  $stmtN->execute([
    (int)$oldAnalystId,
    "Tarea cancelada (#{$taskId})",
    "Cancelaron la tarea: " . ($taskTitle ?: 'Sin título'),
    $link
  ]);
}

$note = "Cancelada (afectó a analista #{$oldAnalystId})";
logTaskEvent(
  $pdo,
  $taskId,
  $adminId,
  'CANCELED',
  $note,
  ['status'=>$t['status']],
  ['status'=>'CANCELADA']
);

$_SESSION['flash_ok'] = 'Tarea cancelada.';
header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
exit;
