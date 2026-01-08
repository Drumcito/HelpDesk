<?php
// /HelpDesk_EQF/modules/dashboard/tasks/finish.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}
function isAnalystRole(int $r): bool {
  return in_array($r, [1, 3], true);
}

if (!isset($_SESSION['user_id']) || !isAnalystRole(currentRole())) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}

$pdo = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];
$taskId = (int)($_POST['task_id'] ?? 0);

if ($taskId <= 0) {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}

$stmt = $pdo->prepare("SELECT status FROM tasks WHERE id=? AND assigned_to_user_id=? LIMIT 1");
$stmt->execute([$taskId, $analystId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || ($row['status'] ?? '') !== 'EN_PROCESO') {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}

$pdo->prepare("
  UPDATE tasks
  SET status='FINALIZADA',
      finished_at=NOW()
  WHERE id=? AND assigned_to_user_id=?
  LIMIT 1
")->execute([$taskId, $analystId]);

logTaskEvent(
  $pdo,
  $taskId,
  $analystId,
  'FINISHED',
  'Analista finalizó tarea',
  ['status' => 'EN_PROCESO'],
  ['status' => 'FINALIZADA']
);

$_SESSION['flash_ok'] = 'Tarea finalizada.';
header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
exit;
