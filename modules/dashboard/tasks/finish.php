<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';

$rol = (int)($_SESSION['user_rol'] ?? ($_SESSION['rol'] ?? 0));
if (!isset($_SESSION['user_id']) || $rol !== 3) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}

$pdo = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];
$taskId    = (int)($_POST['task_id'] ?? 0);

if ($taskId <= 0) {
  $_SESSION['flash_err'] = 'Tarea inválida.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}

$pdo->beginTransaction();
try {
  $stmt = $pdo->prepare("
    SELECT status, finished_at
    FROM tasks
    WHERE id = ?
      AND assigned_to_user_id = ?
    LIMIT 1
  ");
  $stmt->execute([$taskId, $analystId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row || ($row['status'] ?? '') !== 'EN_PROCESO') {
    throw new RuntimeException('No puedes finalizar esta tarea.');
  }

  $now = date('Y-m-d H:i:s');

  $upd = $pdo->prepare("
    UPDATE tasks
    SET status = 'FINALIZADA',
        finished_at = ?
    WHERE id = ?
      AND assigned_to_user_id = ?
      AND status = 'EN_PROCESO'
    LIMIT 1
  ");
  $upd->execute([$now, $taskId, $analystId]);

  if ($upd->rowCount() <= 0) {
    throw new RuntimeException('No se pudo finalizar la tarea.');
  }

  logTaskEvent(
    $pdo,
    $taskId,
    $analystId,
    'FINISHED',
    'Analista finalizó tarea',
    ['status' => ($row['status'] ?? null), 'finished_at' => ($row['finished_at'] ?? null)],
    ['status' => 'FINALIZADA', 'finished_at' => $now]
  );

  $pdo->commit();
  $_SESSION['flash_ok'] = 'Tarea finalizada.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  $_SESSION['flash_err'] = $e->getMessage();
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}
