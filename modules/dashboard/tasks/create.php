<?php
// /HelpDesk_EQF/modules/dashboard/tasks/create.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/helpers/TaskEvents.php';
require_once __DIR__ . '/helpers/TaskFiles.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
    exit;
}

$pdo = Database::getConnection();

$adminId = (int)$_SESSION['user_id'];
$adminArea = trim($_SESSION['user_area'] ?? '');

$assignedTo = (int)($_POST['assigned_to_user_id'] ?? 0);
$priorityId = (int)($_POST['priority_id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$desc = trim((string)($_POST['description'] ?? ''));
$dueRaw = trim((string)($_POST['due_at'] ?? ''));

if ($assignedTo <= 0 || $priorityId <= 0 || $title === '' || $desc === '' || $dueRaw === '') {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
    exit;
}

// valida analista del área
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id=? AND user_rol=1 AND user_area=?");
$stmtCheck->execute([$assignedTo, $adminArea]);
if (!$stmtCheck->fetch()) {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
    exit;
}

// datetime-local -> "YYYY-MM-DDTHH:MM"
$dueAt = str_replace('T', ' ', $dueRaw) . ':00';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO tasks (created_by_admin_id, assigned_to_user_id, title, description, priority_id, due_at, status)
        VALUES (?, ?, ?, ?, ?, ?, 'ASIGNADA')
    ");
    $stmt->execute([$adminId, $assignedTo, $title, $desc, $priorityId, $dueAt]);

    $taskId = (int)$pdo->lastInsertId();

    logTaskEvent($pdo, $taskId, $adminId, 'CREATED', 'Tarea creada');
    logTaskEvent($pdo, $taskId, $adminId, 'ASSIGNED', 'Tarea asignada al analista', null, ['assigned_to' => $assignedTo]);

    // adjuntos admin (opcional)
    if (!empty($_FILES['admin_files']['name'][0])) {
        $baseDir = __DIR__ . '/../../../uploads/tasks/admin/';
        $allowed = ['pdf','png','jpg','jpeg','webp','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar'];
        $saved = saveUploadedFiles($_FILES['admin_files'], $baseDir, $allowed);

        $stmtF = $pdo->prepare("
            INSERT INTO task_files (task_id, uploaded_by_user_id, file_type, original_name, stored_name, mime, size_bytes)
            VALUES (?, ?, 'ADMIN_ATTACHMENT', ?, ?, ?, ?)
        ");

        foreach ($saved as $s) {
            $stmtF->execute([$taskId, $adminId, $s['original_name'], $s['stored_name'], $s['mime'], $s['size_bytes']]);
            logTaskEvent($pdo, $taskId, $adminId, 'ADMIN_FILE_ADDED', 'Adjunto agregado', null, ['file' => $s['original_name']]);
        }
    }

    $pdo->commit();
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
    exit;
}
