<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}

$rol = currentRole();
if (!in_array($rol, [2,3], true)) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/' . ($rol === 2 ? 'admin.php' : 'analyst.php'));
  exit;
}

$pdo    = Database::getConnection();
$userId = (int)$_SESSION['user_id'];

$taskId = (int)($_POST['task_id'] ?? 0);
$notes  = trim((string)($_POST['notes'] ?? ''));

// límite sano (ajústalo)
if (mb_strlen($notes) > 3000) {
  $notes = mb_substr($notes, 0, 3000);
}

if ($taskId <= 0) {
  $_SESSION['flash_err'] = 'Tarea inválida.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId);
  exit;
}

// validar permisos sobre la tarea
$stmt = $pdo->prepare("
  SELECT created_by_admin_id, assigned_to_user_id
  FROM tasks
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$taskId]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
  $_SESSION['flash_err'] = 'Tarea no encontrada.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/' . ($rol === 2 ? 'admin.php' : 'analyst.php'));
  exit;
}

if ($rol === 2 && (int)$t['created_by_admin_id'] !== $userId) {
  $_SESSION['flash_err'] = 'No autorizado.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}
if ($rol === 3 && (int)$t['assigned_to_user_id'] !== $userId) {
  $_SESSION['flash_err'] = 'No autorizado.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}

$upd = $pdo->prepare("UPDATE tasks SET notes = ? WHERE id = ? LIMIT 1");
$upd->execute([$notes === '' ? null : $notes, $taskId]);

$_SESSION['flash_ok'] = 'Observaciones guardadas.';
header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId);
exit;
