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
$adminArea = trim($_SESSION['user_area'] ?? ($_SESSION['area'] ?? ''));

$taskId = (int)($_POST['task_id'] ?? 0);
$newAnalystId = (int)($_POST['new_assigned_to_user_id'] ?? 0);

if ($taskId <= 0 || $newAnalystId <= 0) {
  $_SESSION['flash_err'] = 'Datos inválidos.'; 
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}

// verificar tarea es del admin
$stmt = $pdo->prepare("SELECT id, assigned_to_user_id, status FROM tasks WHERE id=? AND created_by_admin_id=? LIMIT 1");
$stmt->execute([$taskId, $adminId]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$t) {
  $_SESSION['flash_err'] = 'Tarea no válida.'; 
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}

// validar nuevo analista sea rol 3 del área
$stmtA = $pdo->prepare("SELECT id FROM users WHERE id=? AND rol=3 AND area=? LIMIT 1");
$stmtA->execute([$newAnalystId, $adminArea]);
if (!$stmtA->fetchColumn()) {
  $_SESSION['flash_err'] = 'Analista inválido o fuera de tu área.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}

$oldAnalystId = (int)$t['assigned_to_user_id'];

// reasignar y resetear flow
$pdo->prepare("
  UPDATE tasks
  SET assigned_to_user_id = ?,
      status = 'ASIGNADA',
      acknowledged_at = NULL,
      ack_at = NULL,
      finished_at = NULL,
      validated_at = NULL,
      canceled_at = NULL,
      updated_at = NOW()
  WHERE id = ? AND created_by_admin_id = ?
  LIMIT 1
")->execute([$newAnalystId, $taskId, $adminId]);

// evento (nota incluye el analista anterior para que el polling lo detecte)
$note = "Reasignada: #{$oldAnalystId} -> #{$newAnalystId}";
logTaskEvent(
  $pdo,
  $taskId,
  $adminId,
  'REASSIGNED',
  $note,
  ['assigned_to_user_id'=>$oldAnalystId, 'status'=>$t['status']],
  ['assigned_to_user_id'=>$newAnalystId, 'status'=>'ASIGNADA']
);

$_SESSION['flash_ok'] = 'Tarea reasignada.';
header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
exit;
