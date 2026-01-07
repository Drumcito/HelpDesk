<?php
// /HelpDesk_EQF/modules/dashboard/tasks/upload_evidence.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';
require_once __DIR__ . '/helpers/TaskFiles.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 1) {
    header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit;
}

$pdo = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];

$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId <= 0) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit; }

// valida que es su tarea y que está EN_PROCESO (tú puedes permitir FINALIZADA también si quieres)
$stmt = $pdo->prepare("SELECT status FROM tasks WHERE id=? AND assigned_to_user_id=?");
$stmt->execute([$taskId, $analystId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['status'] !== 'EN_PROCESO') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId); exit;
}

try {
    if (empty($_FILES['evidence_files']['name'][0])) {
        header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId); exit;
    }

    $baseDir = __DIR__ . '/../../../uploads/tasks/evidence/';
    $allowed = ['pdf','png','jpg','jpeg','webp','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar'];
    $saved = saveUploadedFiles($_FILES['evidence_files'], $baseDir, $allowed);

    $stmtF = $pdo->prepare("
        INSERT INTO task_files (task_id, uploaded_by_user_id, file_type, original_name, stored_name, mime, size_bytes)
        VALUES (?, ?, 'EVIDENCE', ?, ?, ?, ?)
    ");

    foreach ($saved as $s) {
        $stmtF->execute([$taskId, $analystId, $s['original_name'], $s['stored_name'], $s['mime'], $s['size_bytes']]);
        logTaskEvent($pdo, $taskId, $analystId, 'EVIDENCE_ADDED', 'Evidencia agregada', null, ['file' => $s['original_name']]);
    }

    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId);
    exit;

} catch (Throwable $e) {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/view.php?id=' . $taskId);
    exit;
}
