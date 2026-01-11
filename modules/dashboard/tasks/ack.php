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
  $stmtPrev = $pdo->prepare("
    SELECT status
    FROM tasks
    WHERE id=? AND assigned_to_user_id=?
    LIMIT 1
  ");
  $stmtPrev->execute([$taskId, $analystId]);
  $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC) ?: [];

  $stmt = $pdo->prepare("
    UPDATE tasks
    SET status = 'EN_PROCESO',
        acknowledged_at = COALESCE(acknowledged_at, NOW())
    WHERE id = ?
      AND assigned_to_user_id = ?
      AND status = 'ASIGNADA'
    LIMIT 1
  ");
  $stmt->execute([$taskId, $analystId]);

  if ($stmt->rowCount() <= 0) {
    throw new RuntimeException('No se pudo marcar como EN PROCESO (quizá ya fue tomada o no es tuya).');
  }

  logTaskEvent(
    $pdo,
    $taskId,
    $analystId,
    'ACKNOWLEDGED',
    'Analista marcó como EN PROCESO',
    ['status' => ($prev['status'] ?? null)],
    ['status' => 'EN_PROCESO']
  );

  $pdo->commit();
  $_SESSION['flash_ok'] = 'Tarea marcada como EN PROCESO';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  $_SESSION['flash_err'] = $e->getMessage(); // mejor que "Error al marcar..." para debug
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}
