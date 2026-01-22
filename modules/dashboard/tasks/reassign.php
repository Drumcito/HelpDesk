<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';

$rol = (int)($_SESSION['user_rol'] ?? ($_SESSION['rol'] ?? 0));
if (!isset($_SESSION['user_id']) || $rol !== 2) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}

$pdo = Database::getConnection();
$adminId = (int)$_SESSION['user_id'];

$taskId = (int)($_POST['task_id'] ?? 0);
$newAid = (int)($_POST['new_assigned_to_user_id'] ?? 0);

if ($taskId <= 0 || $newAid <= 0) {
  $_SESSION['flash_err'] = 'Datos inválidos.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}

// valida que la tarea sea del admin
$stmt = $pdo->prepare("SELECT id FROM tasks WHERE id=? AND created_by_admin_id=? LIMIT 1");
$stmt->execute([$taskId, $adminId]);
if (!$stmt->fetchColumn()) {
  $_SESSION['flash_err'] = 'No autorizado.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}

$pdo->beginTransaction();
try {
  // 1) obtener assignees activos (para notificar)
  $stmtOld = $pdo->prepare("
    SELECT id, analyst_id, status
    FROM task_assignees
    WHERE task_id=?
      AND status IN ('ASIGNADA','EN_PROCESO')
  ");
  $stmtOld->execute([$taskId]);
  $olds = $stmtOld->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // 2) retirarlos
$pdo->prepare("UPDATE tasks SET assigned_to_user_id=?, status='ASIGNADA' WHERE id=? LIMIT 1")
    ->execute([$newAid, $taskId]);

// ===== NOTIFICACIONES (REASSIGN) =====
$link = "/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=" . (int)$taskId;

// consigue título de tarea (para mensaje)
$stmtT = $pdo->prepare("SELECT title FROM tasks WHERE id=? LIMIT 1");
$stmtT->execute([$taskId]);
$taskTitle = (string)($stmtT->fetchColumn() ?: '');

// prepara inserción
$stmtN = $pdo->prepare("
  INSERT INTO notifications (user_ide, type, title, body, link, is_read, created_at)
  VALUES (?, 'task_reassigned', ?, ?, ?, 0, NOW())
");

// notificar a los analistas anteriores (retirados)
foreach ($olds as $o) {
  $oldUid = (int)($o['analyst_id'] ?? 0);
  if ($oldUid > 0 && $oldUid !== $newAid) {
    $stmtN->execute([
      $oldUid,
      "Tarea reasignada (#{$taskId})",
      "Te retiraron la tarea: " . ($taskTitle ?: 'Sin título'),
      $link
    ]);
  }
}

// notificar al nuevo analista
$stmtN->execute([
  (int)$newAid,
  "Tarea reasignada (#{$taskId})",
  "Se te asignó la tarea: " . ($taskTitle ?: 'Sin título'),
  $link
]);

  // 3) crear o reactivar assignee para nuevo analista
  // si ya existe RETIRADA/CANCELADA/FINALIZADA, crea uno nuevo (más limpio)
  $stmtIns = $pdo->prepare("
    INSERT INTO task_assignees (task_id, analyst_id, status, delivered_at)
    VALUES (?, ?, 'ASIGNADA', NOW())
  ");
  $stmtIns->execute([$taskId, $newAid]);

  // 4) eventos
  foreach ($olds as $o) {
    logTaskEvent(
      $pdo,
      $taskId,
      $adminId,
      'REASSIGNED',
      'Tarea reasignada (retirada al analista)',
      ['assignee_id' => (int)$o['id'], 'analyst_id' => (int)$o['analyst_id'], 'status' => (string)$o['status']],
      ['status' => 'RETIRADA', 'new_analyst_id' => $newAid]
    );
  }

  logTaskEvent(
    $pdo,
    $taskId,
    $adminId,
    'REASSIGNED',
    'Tarea reasignada a nuevo analista',
    null,
    ['new_analyst_id' => $newAid]
  );

  $pdo->commit();
  $_SESSION['flash_ok'] = 'Tarea reasignada.';
} catch(Throwable $e){
  $pdo->rollBack();
  $_SESSION['flash_err'] = 'Error al reasignar.';
}

header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
exit;
