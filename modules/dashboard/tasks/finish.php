<?php
// /HelpDesk_EQF/modules/dashboard/tasks/finish.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit;
}

$pdo = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];
$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId <= 0) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit; }

$stmt = $pdo->prepare("SELECT status FROM tasks WHERE id=? AND assigned_to_user_id=?");
$stmt->execute([$taskId, $analystId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['status'] !== 'EN_PROCESO') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit;
}

$pdo->prepare("UPDATE tasks SET status='FINALIZADA', finished_at=NOW() WHERE id=?")->execute([$taskId]);

logTaskEvent($pdo, $taskId, $analystId, 'FINISHED', 'Analista finalizó tarea', ['status'=>'EN_PROCESO'], ['status'=>'FINALIZADA']);

header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
exit;
