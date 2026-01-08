<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

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

// Cambia a EN_PROCESO solo si era ASIGNADA
$stmt = $pdo->prepare("
  UPDATE tasks
  SET status = 'EN_PROCESO',
      acknowledged_at = COALESCE(acknowledged_at, NOW())
  WHERE id = ? AND assigned_to_user_id = ? AND status = 'ASIGNADA'
  LIMIT 1
");

$stmt->execute([$taskId, $analystId]);

$_SESSION['flash_ok'] = 'Tarea marcada como EN PROCESO';
header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
exit;
