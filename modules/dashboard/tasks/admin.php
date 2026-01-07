<?php
// /HelpDesk_EQF/modules/dashboard/tasks/admin.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

$rol = (int)($_SESSION['user_rol'] ?? ($_SESSION['rol'] ?? 0));
if (!isset($_SESSION['user_id']) || $rol !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();
$adminId = (int)$_SESSION['user_id'];
$adminArea = trim($_SESSION['user_area'] ?? ($_SESSION['area'] ?? ''));

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// prioridades
$priorities = $pdo->query("
  SELECT id, label
  FROM catalog_priorities
  WHERE active = 1
  ORDER BY sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// analistas (de tu área)
$stmtA = $pdo->prepare("
    SELECT id, CONCAT(name,' ',last_name) AS name
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
?>

<div class="user-container">

  <div class="user-header-row">
    <h2 style="margin:0;">Tareas (Admin)</h2>
    <button class="user-btn user-btn-primary" onclick="openTaskModal()">Crear tarea</button>
  </div>

  <?php if (empty($tasks)): ?>
    <div class="user-info-card">
      <p style="opacity:.8;margin:0;">Aún no has creado tareas.</p>
    </div>
  <?php else: ?>
    <div class="user-grid" style="display:grid; gap:16px;">
      <?php foreach ($tasks as $t): ?>
        <div class="user-info-card">
          <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
            <div>
              <h3 style="margin:0 0 6px 0;"><?php echo h($t['title']); ?></h3>
              <div style="opacity:.85; font-size:14px;">
                Asignada a: <b><?php echo h($t['analyst_name']); ?></b><br>
                Prioridad: <b><?php echo h($t['priority_name']); ?></b><br>
                Entrega: <b><?php echo h($t['due_at']); ?></b><br>
                Estado: <b><?php echo h($t['status']); ?></b>
              </div>
            </div>

            <a class="user-btn user-btn-ghost" href="/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=<?php echo (int)$t['id']; ?>">
              Ver
            </a>
          </div>

          <p style="margin:12px 0 0 0; opacity:.85;">
            <?php echo nl2br(h($t['description'])); ?>
          </p>

          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <?php if ($t['status'] === 'FINALIZADA'): ?>
              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/upload_admin_files.php" enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                <input type="file" name="admin_files[]" class="user-input" multiple>
                <button class="user-btn user-btn-ghost" type="submit">Agregar adjuntos</button>
              </form>
            <?php else: ?>
              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/upload_admin_files.php" enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                <input type="file" name="admin_files[]" class="user-input" multiple>
                <button class="user-btn user-btn-ghost" type="submit">Agregar adjuntos</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<!-- MODAL CREAR TAREA -->
<div class="user-modal-backdrop" id="taskModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Crear tarea</h2>
      <button type="button" class="user-modal-close" onclick="closeTaskModal()">×</button>
    </header>

    <p class="user-modal-description">
      Asigna una tarea a un analista y define fecha/hora máxima de entrega.
    </p>

    <form method="POST"
          action="/HelpDesk_EQF/modules/dashboard/tasks/create.php"
          enctype="multipart/form-data"
          class="user-modal-form"
          id="taskForm">

      <label class="user-label">Asignar a</label>
      <select name="assigned_to_user_id" id="assigned_to_user_id" class="user-input" required>
        <option value="">Selecciona un analista…</option>
        <?php foreach ($analysts as $a): ?>
          <option value="<?php echo (int)$a['id']; ?>"><?php echo h($a['name']); ?></option>
        <?php endforeach; ?>
      </select>

      <label class="user-label">Fecha y hora máxima</label>
      <input type="datetime-local" name="due_at" id="due_at" class="user-input" required>

      <label class="user-label">Título</label>
      <input type="text" name="title" id="title" class="user-input" maxlength="180" required>

      <label class="user-label">Prioridad</label>
      <select name="priority_id" id="priority_id" class="user-input" required>
        <option value="">Selecciona prioridad…</option>
        <?php foreach ($priorities as $p): ?>
          <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['label']); ?></option>
        <?php endforeach; ?>
      </select>

      <label class="user-label">Descripción</label>
      <textarea name="description" id="description" class="user-textarea" rows="5" required></textarea>

      <label class="user-label">Adjuntar archivos (opcional)</label>
      <input type="file" name="admin_files[]" id="admin_files" class="user-input" multiple>

      <div class="user-modal-actions">
        <button type="button" class="user-btn user-btn-ghost" onclick="closeTaskModal()">Cancelar</button>
        <button type="submit" class="user-btn user-btn-primary">Crear tarea</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTaskModal(){ document.getElementById('taskModal').style.display='flex'; }
function closeTaskModal(){ document.getElementById('taskModal').style.display='none'; }

document.getElementById('taskForm')?.addEventListener('submit', function(e){
  const assigned = document.getElementById('assigned_to_user_id').value.trim();
  const due = document.getElementById('due_at').value.trim();
  const title = document.getElementById('title').value.trim();
  const prio = document.getElementById('priority_id').value.trim();
  const desc = document.getElementById('description').value.trim();
  if(!assigned || !due || !title || !prio || !desc){
    e.preventDefault();
    alert('Completa todos los campos obligatorios.');
  }
});
</script>
