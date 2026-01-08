<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

$rol = (int)($_SESSION['user_rol'] ?? ($_SESSION['rol'] ?? 0));
if (!isset($_SESSION['user_id']) || $rol !== 2) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();

$adminId   = (int)($_SESSION['user_id'] ?? 0);
$adminArea = trim($_SESSION['user_area'] ?? ($_SESSION['area'] ?? ''));

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function statusLabel(string $s): string {
  $s = strtoupper(trim($s));
  return match($s){
    'ASIGNADA'   => 'Asignada',
    'EN_PROCESO' => 'En proceso',
    'FINALIZADA' => 'Finalizada',
    default      => $s ?: '—',
  };
}

// prioridades
$priorities = $pdo->query("
  SELECT id, label
  FROM catalog_priorities
  WHERE active = 1
  ORDER BY sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// analistas del área (rol=3)
$stmtA = $pdo->prepare("
  SELECT id, CONCAT(name,' ',last_name) AS full_name
  FROM users
  WHERE rol = 3 AND area = ?
  ORDER BY last_name, name
");
$stmtA->execute([$adminArea]);
$analysts = $stmtA->fetchAll(PDO::FETCH_ASSOC);

// tareas creadas por este admin
$stmtT = $pdo->prepare("
  SELECT t.*,
         cp.label AS priority_name,
         CONCAT(u.name,' ',u.last_name) AS analyst_name
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users u ON u.id = t.assigned_to_user_id
  WHERE t.created_by_admin_id = ?
  ORDER BY t.created_at DESC
");
$stmtT->execute([$adminId]);
$tasks = $stmtT->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tareas (Admin) | HelpDesk EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body class="user-body">
<main class="user-main">
  <section class="user-main-inner">

    <header class="user-main-header">
      <div>
        <p class="login-brand">
          <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
        </p>
        <p class="user-main-subtitle">Tareas (Admin) — Área <?php echo h($adminArea); ?></p>
      </div>

      <button type="button" class="btn-primary" onclick="openTaskModal()">+ Crear tarea</button>
    </header>

    <section class="user-main-content">

      <?php if (empty($tasks)): ?>
        <div class="user-info-card">
          <h2>Mis tareas creadas</h2>
          <p style="margin:0; opacity:.85;">Aún no has creado tareas.</p>
        </div>
      <?php else: ?>
        <div class="tickets-grid">
          <?php foreach ($tasks as $t): ?>
            <article class="ticket-card">
              <div class="ticket-card__top">
                <div class="ticket-id"><?php echo h($t['title']); ?></div>
                <div class="ticket-date">Entrega: <?php echo h($t['due_at']); ?></div>
              </div>

              <div class="ticket-card__body">
                <div class="ticket-row">
                  <div class="ticket-label">Asignada a</div>
                  <div class="ticket-value"><?php echo h($t['analyst_name']); ?></div>
                </div>

                <div class="ticket-row">
                  <div class="ticket-label">Prioridad</div>
                  <div class="ticket-value"><?php echo h($t['priority_name']); ?></div>
                </div>

                <div class="ticket-row">
                  <div class="ticket-label">Estado</div>
                  <div class="ticket-value"><?php echo h(statusLabel($t['status'] ?? '')); ?></div>
                </div>

                <div class="ticket-desc"><?php echo h($t['description']); ?></div>
              </div>

              <div class="ticket-card__actions" style="align-items:center;">
                <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=<?php echo (int)$t['id']; ?>">Ver detalle</a>

                <form method="POST"
                      action="/HelpDesk_EQF/modules/dashboard/tasks/upload_admin_files.php"
                      enctype="multipart/form-data"
                      style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:0;">
                  <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                  <input type="file" name="admin_files[]" multiple>
                  <button class="btn-secondary" type="submit" style="width:auto; padding:0 16px;">Adjuntar</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </section>
  </section>
</main>

<!-- MODAL -->
<div class="user-modal-backdrop" id="taskModal">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Crear tarea</h2>
      <button type="button" class="user-modal-close" onclick="closeTaskModal()">×</button>
    </header>

    <p class="user-modal-description">Asigna una tarea a un analista y define fecha/hora máxima de entrega.</p>

    <form method="POST"
          action="/HelpDesk_EQF/modules/dashboard/tasks/create.php"
          enctype="multipart/form-data"
          class="user-modal-form"
          id="taskForm">

      <div class="form-group">
        <label>Asignar a</label>
        <select name="assigned_to_user_id" id="assigned_to_user_id" required>
          <option value="">Selecciona un analista…</option>
          <?php foreach ($analysts as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>"><?php echo h($a['full_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Fecha y hora máxima</label>
        <input type="datetime-local" name="due_at" id="due_at" required>
      </div>

      <div class="form-group">
        <label>Título</label>
        <input type="text" name="title" id="title" maxlength="180" required>
      </div>

      <div class="form-group">
        <label>Prioridad</label>
        <select name="priority_id" id="priority_id" required>
          <option value="">Selecciona prioridad…</option>
          <?php foreach ($priorities as $p): ?>
            <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['label']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Descripción</label>
        <textarea name="description" id="description" rows="5" required></textarea>
      </div>

      <div class="form-group">
        <label>Adjuntar archivos (opcional)</label>
        <input type="file" name="admin_files[]" id="admin_files" multiple>
      </div>

      <div class="user-modal-actions">
        <button type="button" class="btn-secondary" onclick="closeTaskModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Crear tarea</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTaskModal(){ document.getElementById('taskModal')?.classList.add('is-visible'); }
function closeTaskModal(){ document.getElementById('taskModal')?.classList.remove('is-visible'); }
document.getElementById('taskModal')?.addEventListener('click', (e) => {
  if (e.target.id === 'taskModal') closeTaskModal();
});
</script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
<script src="/HelpDesk_EQF/assets/js/script.js?v=<?php echo time(); ?>"></script>

</body>
</html>
