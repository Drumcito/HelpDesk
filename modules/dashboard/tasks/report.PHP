<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) { header('Location: /HelpDesk_EQF/auth/login.php'); exit; }

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}
$rol = currentRole();
if (!in_array($rol, [2,3], true)) { header('Location: /HelpDesk_EQF/auth/login.php'); exit; }

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
// --- Detectar si existe acknowledged_at en tasks
function colExists(PDO $pdo, string $table, string $col): bool {
  static $cache = [];
  $key = $table . '.' . $col;
  if (isset($cache[$key])) return $cache[$key];

  $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = ?
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
  ");
  $stmt->execute([$db, $table, $col]);
  $cache[$key] = ((int)$stmt->fetchColumn() > 0);
  return $cache[$key];
}

$hasAck = colExists($pdo, 'tasks', 'acknowledged_at');

// si NO existe, usamos created_at para el cálculo
$timeStartExpr = $hasAck ? "COALESCE(t.acknowledged_at, t.created_at)" : "t.created_at";
$ackSelect     = $hasAck ? ", t.acknowledged_at" : "";

$stmt = $pdo->prepare("
  SELECT
    t.*,
    cp.label AS priority_name,
    CONCAT(ad.name,' ',ad.last_name) AS admin_name,
    CONCAT(an.name,' ',an.last_name) AS analyst_name
    $ackSelect,
    TIMESTAMPDIFF(SECOND, $timeStartExpr, t.finished_at) AS elapsed_sec
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  JOIN users an ON an.id = t.assigned_to_user_id
  WHERE t.id = ?
  LIMIT 1
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmtSeconds(?int $sec): string {
  if (!$sec || $sec < 0) return '—';
  $h = intdiv($sec, 3600);
  $m = intdiv($sec % 3600, 60);
  $s = $sec % 60;
  return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/'.($rol===2?'admin.php':'analyst.php')); exit; }

$stmt = $pdo->prepare("
  SELECT
    t.*,
    cp.label AS priority_name,
    CONCAT(ad.name,' ',ad.last_name) AS admin_name,
    CONCAT(an.name,' ',an.last_name) AS analyst_name,
    TIMESTAMPDIFF(SECOND, COALESCE(t.acknowledged_at, t.created_at), t.finished_at) AS elapsed_sec
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  JOIN users an ON an.id = t.assigned_to_user_id
  WHERE t.id = ?
  LIMIT 1
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/'.($rol===2?'admin.php':'analyst.php')); exit; }

// permisos
if ($rol === 2 && (int)$task['created_by_admin_id'] !== $userId) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php'); exit; }
if ($rol === 3 && (int)$task['assigned_to_user_id'] !== $userId) { header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php'); exit; }

$stmtF = $pdo->prepare("
  SELECT *
  FROM task_files
  WHERE task_id = ? AND is_deleted = 0
  ORDER BY created_at DESC
");
$stmtF->execute([$taskId]);
$files = $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];

$adminFiles = array_values(array_filter($files, fn($x)=>($x['file_type']??'')==='ADMIN_ATTACHMENT'));
$evidFiles  = array_values(array_filter($files, fn($x)=>($x['file_type']??'')==='EVIDENCE'));

function fileUrl(array $f): string {
  $base = (($f['file_type'] ?? '') === 'ADMIN_ATTACHMENT')
    ? '/HelpDesk_EQF/uploads/tasks/admin/'
    : '/HelpDesk_EQF/uploads/tasks/evidence/';
  return $base . rawurlencode((string)($f['stored_name'] ?? ''));
}

include __DIR__ . '/../../../template/header.php';
?>
<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
<style>
@media print{
  .no-print{ display:none !important; }
  .user-body{ background:#fff !important; }
}
</style>
<?php
include __DIR__ . '/../../../template/sidebar.php';
?>

<main class="user-main">
  <section class="user-main-inner">

    <header class="user-main-header no-print">
      <div>
        <p class="login-brand"><span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span></p>
        <p class="user-main-subtitle">Reporte de tarea · #<?php echo (int)$task['id']; ?></p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn-primary" style="text-decoration:none;"
           href="/HelpDesk_EQF/modules/dashboard/tasks/<?php echo ($rol===2?'admin.php':'analyst.php'); ?>">Volver</a>
        <button class="btn-secondary" onclick="window.print()">Imprimir</button>
      </div>
    </header>

    <div class="user-info-card">
      <h2 style="margin:0 0 10px;"><?php echo h($task['title']); ?></h2>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:14px;">
        <div><b>Creada por:</b> <?php echo h($task['admin_name']); ?></div>
        <div><b>Asignada a:</b> <?php echo h($task['analyst_name']); ?></div>

        <div><b>Prioridad:</b> <?php echo h($task['priority_name']); ?></div>
        <div><b>Estado:</b> <?php echo h($task['status']); ?></div>

        <div><b>Creación:</b> <?php echo h($task['created_at']); ?></div>
        <div><b>Entrega:</b> <?php echo h($task['due_at']); ?></div>

<div><b>Enterado:</b> <?php echo h($task['acknowledged_at'] ?? '—'); ?></div>
        <div><b>Finalizada:</b> <?php echo h($task['finished_at'] ?? '—'); ?></div>

        <div style="grid-column:1 / -1;"><b>Tiempo total:</b> <?php echo h(fmtSeconds((int)($task['elapsed_sec'] ?? 0))); ?></div>
      </div>

      <div style="margin-top:12px;">
        <b>Descripción:</b>
        <div style="margin-top:6px; opacity:.95;"><?php echo nl2br(h($task['description'])); ?></div>
      </div>
    </div>

    <div class="panel-grid-2" style="margin-top:14px;">
      <div class="user-info-card">
        <h2 style="margin:0 0 10px;">Adjuntos del admin (<?php echo count($adminFiles); ?>)</h2>
        <?php if (!$adminFiles): ?>
          <p style="margin:0; opacity:.8;">Sin adjuntos.</p>
        <?php else: ?>
          <ul style="margin:0; padding-left:18px;">
            <?php foreach($adminFiles as $f): ?>
              <li style="margin:6px 0;">
                <a href="<?php echo h(fileUrl($f)); ?>" target="_blank" rel="noopener"><?php echo h($f['original_name']); ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="user-info-card">
        <h2 style="margin:0 0 10px;">Evidencias del analista (<?php echo count($evidFiles); ?>)</h2>
        <?php if (!$evidFiles): ?>
          <p style="margin:0; opacity:.8;">Sin evidencias.</p>
        <?php else: ?>
          <ul style="margin:0; padding-left:18px;">
            <?php foreach($evidFiles as $f): ?>
              <li style="margin:6px 0;">
                <a href="<?php echo h(fileUrl($f)); ?>" target="_blank" rel="noopener"><?php echo h($f['original_name']); ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

  </section>
</main>
<script src="/HelpDesk_EQF/assets/js/script.js?v=<?php echo time(); ?>"></script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
