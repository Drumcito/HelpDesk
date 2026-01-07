<?php
// /HelpDesk_EQF/modules/dashboard/tasks/view.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
$rol = (int)($_SESSION['user_rol'] ?? 0);

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/' . ($rol===2?'admin.php':'analyst.php')); exit; }

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// task
$stmt = $pdo->prepare("
  SELECT t.*,
         cp.name AS priority_name,
         CONCAT(ad.user_name,' ',ad.user_last) AS admin_name,
         CONCAT(an.user_name,' ',an.user_last) AS analyst_name
  FROM tasks t
  JOIN catalog_priority cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  JOIN users an ON an.id = t.assigned_to_user_id
  WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/' . ($rol===2?'admin.php':'analyst.php')); exit; }

// permisos: admin puede ver si la creó; analista si se la asignaron
if ($rol === 2 && (int)$task['created_by_admin_id'] !== $userId) {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit;
}
if ($rol === 1 && (int)$task['assigned_to_user_id'] !== $userId) {
    header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit;
}

// files
$stmtF = $pdo->prepare("
  SELECT * FROM task_files
  WHERE task_id = ? AND is_deleted = 0
  ORDER BY created_at DESC
");
$stmtF->execute([$taskId]);
$files = $stmtF->fetchAll(PDO::FETCH_ASSOC);

$adminFiles = array_values(array_filter($files, fn($x) => $x['file_type']==='ADMIN_ATTACHMENT'));
$evidenceFiles = array_values(array_filter($files, fn($x) => $x['file_type']==='EVIDENCE'));

// events
$stmtE = $pdo->prepare("
  SELECT e.*, CONCAT(u.user_name,' ',u.user_last) AS actor_name
  FROM task_events e
  JOIN users u ON u.id = e.actor_user_id
  WHERE e.task_id = ?
  ORDER BY e.created_at DESC
");
$stmtE->execute([$taskId]);
$events = $stmtE->fetchAll(PDO::FETCH_ASSOC);

// download base (ajusta si tú sirves archivos distinto)
function filePublicUrl(array $f): string {
    $base = ($f['file_type']==='ADMIN_ATTACHMENT') ? '/HelpDesk_EQF/uploads/tasks/admin/' : '/HelpDesk_EQF/uploads/tasks/evidence/';
    return $base . rawurlencode($f['stored_name']);
}
?>

<div class="user-container">

  <div class="user-info-card">
    <h2 style="margin:0 0 8px 0;"><?php echo h($task['title']); ?></h2>
    <div style="opacity:.85; font-size:14px;">
      Admin: <b><?php echo h($task['admin_name']); ?></b> ·
      Analista: <b><?php echo h($task['analyst_name']); ?></b><br>
      Prioridad: <b><?php echo h($task['priority_name']); ?></b> ·
      Entrega: <b><?php echo h($task['due_at']); ?></b> ·
      Estado: <b><?php echo h($task['status']); ?></b>
    </div>

    <p style="margin:12px 0 0 0; opacity:.9;"><?php echo nl2br(h($task['description'])); ?></p>
  </div>

  <div class="user-grid" style="display:grid; gap:16px; grid-template-columns: 1fr;">

    <div class="user-info-card">
      <h3 style="margin:0 0 10px 0;">Adjuntos del admin</h3>
      <?php if (empty($adminFiles)): ?>
        <p style="opacity:.8;margin:0;">Sin adjuntos.</p>
      <?php else: ?>
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($adminFiles as $f): ?>
            <li style="margin:6px 0;">
              <a href="<?php echo h(filePublicUrl($f)); ?>" target="_blank" rel="noopener">
                <?php echo h($f['original_name']); ?>
              </a>
              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/delete_file.php" style="display:inline;">
                <input type="hidden" name="file_id" value="<?php echo (int)$f['id']; ?>">
                <button class="user-btn user-btn-ghost" type="submit" style="padding:6px 10px;">Borrar</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="user-info-card">
      <h3 style="margin:0 0 10px 0;">Evidencias del analista</h3>
      <?php if (empty($evidenceFiles)): ?>
        <p style="opacity:.8;margin:0;">Sin evidencias.</p>
      <?php else: ?>
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($evidenceFiles as $f): ?>
            <li style="margin:6px 0;">
              <a href="<?php echo h(filePublicUrl($f)); ?>" target="_blank" rel="noopener">
                <?php echo h($f['original_name']); ?>
              </a>
              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/delete_file.php" style="display:inline;">
                <input type="hidden" name="file_id" value="<?php echo (int)$f['id']; ?>">
                <button class="user-btn user-btn-ghost" type="submit" style="padding:6px 10px;">Borrar</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="user-info-card">
      <h3 style="margin:0 0 10px 0;">Auditoría (timeline)</h3>
      <?php if (empty($events)): ?>
        <p style="opacity:.8;margin:0;">Sin eventos.</p>
      <?php else: ?>
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($events as $e): ?>
            <li style="margin:8px 0;">
              <b><?php echo h($e['event_type']); ?></b>
              <span style="opacity:.8;">· <?php echo h($e['actor_name']); ?> · <?php echo h($e['created_at']); ?></span>
              <?php if (!empty($e['note'])): ?>
                <div style="opacity:.85;"><?php echo h($e['note']); ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

  </div>

</div>
