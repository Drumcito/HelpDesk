<?php
// /HelpDesk_EQF/modules/dashboard/tasks/delete_file.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit;
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
$rol = (int)($_SESSION['user_rol'] ?? 0);

$fileId = (int)($_POST['file_id'] ?? 0);
if ($fileId <= 0) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit; }

$stmt = $pdo->prepare("
  SELECT f.*, t.created_by_admin_id, t.assigned_to_user_id
  FROM task_files f
  JOIN tasks t ON t.id = f.task_id
  WHERE f.id = ? AND f.is_deleted = 0
");
$stmt->execute([$fileId]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$f) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit; }

$taskId = (int)$f['task_id'];

$allowed = false;

if ($rol === 2) {
    // admin: solo si la tarea es suya
    $allowed = ((int)$f['created_by_admin_id'] === $userId);
} elseif ($rol === 1) {
    // analista: solo si es evidencia y el uploader es él
    $allowed = ($f['file_type'] === 'EVIDENCE' && (int)$f['uploaded_by_user_id'] === $userId);
}

if (!$allowed) {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId); exit;
}

$pdo->prepare("
  UPDATE task_files
  SET is_deleted=1, deleted_at=NOW(), deleted_by_user_id=?
  WHERE id=?
")->execute([$userId, $fileId]);

logTaskEvent($pdo, $taskId, $userId, 'FILE_DELETED', 'Archivo eliminado', null, ['file_id'=>$fileId, 'original'=>$f['original_name']]);

header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId);
exit;
