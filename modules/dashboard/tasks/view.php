<?php
// /HelpDesk_EQF/modules/dashboard/tasks/view.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo    = Database::getConnection();
$userId = (int)$_SESSION['user_id'];

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}
$rol = currentRole();
function prioClass(string $label): string {
  $x = mb_strtolower(trim($label));
  if (in_array($x, ['baja','low'], true)) return 'task-priority-low';
  if (in_array($x, ['media','medium'], true)) return 'task-priority-med';
  if (in_array($x, ['alta','high'], true)) return 'task-priority-high';
  return 'task-priority-med';
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- Detectar columnas reales en users (para soportar user_name vs name, etc)
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

$firstCol = colExists($pdo, 'users', 'user_name') ? 'user_name' : (colExists($pdo, 'users', 'name') ? 'name' : 'nombre');
$lastCol  = colExists($pdo, 'users', 'user_last') ? 'user_last' : (colExists($pdo, 'users', 'last_name') ? 'last_name' : 'apellido');

// --- Task id
$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/' . ($rol === 2 ? 'admin.php' : 'analyst.php'));
  exit;
}

// --- Traer task (prioridad = label, como tu catalog_priorities)
$sqlTask = "
  SELECT t.*,
         cp.label AS priority_name,
         CONCAT(ad.`$firstCol`,' ',ad.`$lastCol`) AS admin_name,
         CONCAT(an.`$firstCol`,' ',an.`$lastCol`) AS analyst_name
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  JOIN users an ON an.id = t.assigned_to_user_id
  WHERE t.id = ?
  LIMIT 1
";
$stmt = $pdo->prepare($sqlTask);
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/' . ($rol === 2 ? 'admin.php' : 'analyst.php'));
  exit;
}

// --- Permisos
if ($rol === 2 && (int)$task['created_by_admin_id'] !== $userId) {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/admin.php');
  exit;
}
if ($rol === 3 && (int)$task['assigned_to_user_id'] !== $userId) {
  header('Location: /HelpDesk_EQF/modules/dashboard/tasks/analyst.php');
  exit;
}
if (!in_array($rol, [2,3], true)) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

// --- Files
$stmtF = $pdo->prepare("
  SELECT *
  FROM task_files
  WHERE task_id = ? AND is_deleted = 0
  ORDER BY created_at DESC
");
$stmtF->execute([$taskId]);
$files = $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];

$adminFiles    = array_values(array_filter($files, fn($x) => ($x['file_type'] ?? '') === 'ADMIN_ATTACHMENT'));
$evidenceFiles = array_values(array_filter($files, fn($x) => ($x['file_type'] ?? '') === 'EVIDENCE'));


function filePublicUrl(array $f): string {
  $type = $f['file_type'] ?? '';
  $base = ($type === 'ADMIN_ATTACHMENT')
    ? '/HelpDesk_EQF/uploads/tasks/admin/'
    : '/HelpDesk_EQF/uploads/tasks/evidence/';
  return $base . rawurlencode((string)($f['stored_name'] ?? ''));
}
function statusPillClass(string $s): string {
  $s = strtoupper(trim($s));
  return match($s){
    'ASIGNADA'   => 'task-status-pill task-status-assigned',
    'EN_PROCESO' => 'task-status-pill task-status-progress',
    'FINALIZADA' => 'task-status-pill task-status-done',
    default      => 'task-status-pill task-status-assigned',
  };
}
function statusLabel(string $s): string {
  $s = strtoupper(trim($s));
  return match($s){
    'ASIGNADA'   => 'Asignada',
    'EN_PROCESO' => 'En proceso',
    'FINALIZADA' => 'Finalizada',
    default      => $s ?: '—',
  };
}

function canDeleteFile(int $rol, array $f): bool {
  $type = $f['file_type'] ?? '';
  if ($rol === 2) return $type === 'ADMIN_ATTACHMENT';
  if ($rol === 3) return $type === 'EVIDENCE';
  return false;
}


include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de tarea | HelpDesk EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

</head>

<body class="user-body">
<main class="user-main">
  <section class="user-main-inner">

    <header class="user-main-header">
      <div>
        <p class="login-brand">
          <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
        </p>
        <p class="user-main-subtitle">Detalle de tarea · #<?php echo (int)$task['id']; ?></p>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn-primary" style="text-decoration:none;"
           href="/HelpDesk_EQF/modules/dashboard/tasks/<?php echo ($rol === 2 ? 'admin.php' : 'analyst.php'); ?>">
          Volver
        </a>
      </div>
    </header>

    <section class="user-main-content">

      <div class="user-info-card">
        <h2 style="margin:0 0 8px 0;"><?php echo h($task['title']); ?></h2>

          <div style="opacity:.92; font-size:14px; line-height:1.45;">
  <div class="ticket-row">
    <div class="ticket-label">Admin</div>
    <div class="ticket-value" style="text-align:left;"><b><?php echo h($task['admin_name']); ?></b></div>
  </div>

  <div class="ticket-row">
    <div class="ticket-label">Analista</div>
    <div class="ticket-value" style="text-align:left;"><b><?php echo h($task['analyst_name']); ?></b></div>
  </div>

  <div class="ticket-row">
    <div class="ticket-label">Prioridad</div>
    <div class="ticket-value" style="text-align:left;">
      <span class="task-priority-pill <?php echo prioClass($task['priority_name'] ?? ''); ?>">
        <?php echo h($task['priority_name'] ?? '—'); ?>
      </span>
    </div>
  </div>

  <div class="ticket-row">
    <div class="ticket-label">Entrega</div>
    <div class="ticket-value" style="text-align:left;"><b><?php echo h($task['due_at']); ?></b></div>
  </div>

  <div class="ticket-row">
    <div class="ticket-label">Estado</div>
<span class="<?php echo statusPillClass($task['status'] ?? ''); ?>">
  <?php echo h(statusLabel($task['status'] ?? '')); ?>
</span>
  </div>
</div>


        <div style="margin-top:12px; opacity:.95;">
          <?php echo nl2br(h($task['description'])); ?>
        </div>
      </div>

      <div class="panel-grid-2" style="margin-top:14px;">

        <div class="user-info-card">
          <h2 style="margin-bottom:10px;">Adjuntos del admin</h2>
          <?php if (empty($adminFiles)): ?>
            <p style="opacity:.8;margin:0;">Sin adjuntos.</p>
          <?php else: ?>
            <ul style="margin:0; padding-left:18px;">
              <?php foreach ($adminFiles as $f): ?>
                <li style="margin:8px 0;">
                  <a href="<?php echo h(filePublicUrl($f)); ?>" target="_blank" rel="noopener">
                    <?php echo h($f['original_name']); ?>
                  </a>

                  <?php if (canDeleteFile($rol, $f)): ?>
                    <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/delete_file.php" style="display:inline;">
                      <input type="hidden" name="file_id" value="<?php echo (int)$f['id']; ?>">
                      <button class="eraser-red" type="submit" style="height:34px; width:auto; padding:0 14px; margin-left:8px;">
                        Borrar
                      </button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

        <div class="user-info-card">
          <h2 style="margin-bottom:10px;">Evidencias del analista</h2>
          <?php if (empty($evidenceFiles)): ?>
            <p style="opacity:.8;margin:0;">Sin evidencias.</p>
          <?php else: ?>
            <ul style="margin:0; padding-left:18px;">
              <?php foreach ($evidenceFiles as $f): ?>
                <li style="margin:8px 0;">
                  <a href="<?php echo h(filePublicUrl($f)); ?>" target="_blank" rel="noopener">
                    <?php echo h($f['original_name']); ?>
                  </a>

                  <?php if (canDeleteFile($rol, $f)): ?>
                    <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/delete_file.php" style="display:inline;">
                      <input type="hidden" name="file_id" value="<?php echo (int)$f['id']; ?>">
                      <button class="eraser-red" type="submit" style="height:34px; width:auto; padding:0 14px; margin-left:8px;">
                        Borrar
                      </button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

      </div>

      

    </section>
  </section>
</main>

<?php include __DIR__ . '/../../../template/footer.php'; ?>

<script>
(function(){
  const taskId = <?php echo (int)$task['id']; ?>;
  let lastSig = '';

  async function poll(){
    try{
      const r = await fetch('/HelpDesk_EQF/modules/dashboard/tasks/ajax/tasks_signature.php?task_id=' + taskId, {cache:'no-store'});
      const j = await r.json();
      if(!j.ok) return;

      if(!lastSig){
        lastSig = j.signature || '';
        return;
      }

      if((j.signature || '') !== lastSig){
        location.reload();
      }
    }catch(e){}
  }

  poll();
  setInterval(poll, 4000);
})();
</script>

</body>
</html>
