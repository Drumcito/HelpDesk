<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

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

$adminId   = (int)($_SESSION['user_id'] ?? 0);
$adminArea = trim($_SESSION['user_area'] ?? ($_SESSION['area'] ?? ''));

$assignedTo = (int)($_POST['assigned_to_user_id'] ?? 0);
$dueAt      = trim((string)($_POST['due_at'] ?? ''));
$title      = trim((string)($_POST['title'] ?? ''));
$priorityId = (int)($_POST['priority_id'] ?? 0);
$desc       = trim((string)($_POST['description'] ?? ''));

if ($assignedTo <= 0 || $priorityId <= 0 || $dueAt === '' || $title === '' || $desc === '') {
  $_SESSION['flash_err'] = 'Completa todos los campos obligatorios.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}

// datetime-local -> "Y-m-d H:i:s"
$dueAtDB = str_replace('T', ' ', $dueAt);
if (strlen($dueAtDB) === 16) $dueAtDB .= ':00';

// ✅ validar que el analista exista y sea de MI área
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND rol = 3 AND area = ? LIMIT 1");
$stmtCheck->execute([$assignedTo, $adminArea]);
if (!$stmtCheck->fetchColumn()) {
  $_SESSION['flash_err'] = 'El analista seleccionado no es válido o no pertenece a tu área.';
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}

// ✅ crear task
$stmt = $pdo->prepare("
  INSERT INTO tasks
    (created_by_admin_id, assigned_to_user_id, priority_id, title, description, due_at, status, created_at)
  VALUES
    (?, ?, ?, ?, ?, ?, 'ASIGNADA', NOW())
");
$stmt->execute([
  $adminId,
  $assignedTo,
  $priorityId,
  $title,
  $desc,
  $dueAtDB
]);


$taskId = (int)$pdo->lastInsertId();

// (opcional) subir adjuntos admin_files[]
$uploadBase = __DIR__ . '/../../../uploads/tasks/';
if (!is_dir($uploadBase)) @mkdir($uploadBase, 0775, true);

if (!empty($_FILES['admin_files']) && is_array($_FILES['admin_files']['name'])) {
  $count = count($_FILES['admin_files']['name']);

  for ($i=0; $i<$count; $i++) {
    if (empty($_FILES['admin_files']['name'][$i])) continue;
    if ((int)$_FILES['admin_files']['error'][$i] !== UPLOAD_ERR_OK) continue;

    $orig = (string)$_FILES['admin_files']['name'][$i];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $safe = uniqid('task_admin_', true) . ($ext ? ('.'.$ext) : '');
    $dest = $uploadBase . $safe;

    if (move_uploaded_file($_FILES['admin_files']['tmp_name'][$i], $dest)) {
      // guarda en tabla task_files si existe (si no existe, esto lo puedes comentar)
      try {
        $stmtF = $pdo->prepare("
          INSERT INTO task_files (task_id, uploaded_by_user_id, role, file_path, original_name, created_at)
          VALUES (?, ?, 'ADMIN', ?, ?, NOW())
        ");
        $stmtF->execute([$taskId, $adminId, 'uploads/tasks/'.$safe, $orig]);
      } catch (Throwable $e) {
        // si aún no tienes task_files, no truena el flujo
      }
    }
  }
}

$_SESSION['flash_ok'] = 'Tarea creada y asignada ';
header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
exit;
