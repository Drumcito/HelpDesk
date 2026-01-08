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

function prioClass(string $label): string {
  $x = mb_strtolower(trim($label));
  if (in_array($x, ['baja','low'], true)) return 'task-priority-low';
  if (in_array($x, ['media','medium'], true)) return 'task-priority-med';
  if (in_array($x, ['alta','high'], true)) return 'task-priority-high';
  return 'task-priority-med';
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
function fmtSeconds(?int $sec): string {
  if (!$sec || $sec < 0) return '—';
  $h = intdiv($sec, 3600);
  $m = intdiv($sec % 3600, 60);
  $s = $sec % 60;
  return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

/* prioridades */
$priorities = $pdo->query("
  SELECT id, label
  FROM catalog_priorities
  WHERE active = 1
  ORDER BY sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* analistas del área (rol=3) */
$stmtA = $pdo->prepare("
  SELECT id, CONCAT(name,' ',last_name) AS full_name
  FROM users
  WHERE rol = 3 AND area = ?
  ORDER BY last_name, name
");
$stmtA->execute([$adminArea]);
$analysts = $stmtA->fetchAll(PDO::FETCH_ASSOC);

// PENDIENTES (cards): ASIGNADA y EN_PROCESO
$stmtT = $pdo->prepare("
  SELECT t.*,
         cp.label AS priority_name,
         CONCAT(u.name,' ',u.last_name) AS analyst_name
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users u ON u.id = t.assigned_to_user_id
  WHERE t.created_by_admin_id = ?
    AND t.status IN ('ASIGNADA','EN_PROCESO')
  ORDER BY t.created_at DESC
");
$stmtT->execute([$adminId]);
$tasks = $stmtT->fetchAll(PDO::FETCH_ASSOC) ?: [];


/*TAREAS FINALIZADAS (HISTORIAL)*/

$stmtH = $pdo->prepare("
  SELECT
    t.id, t.title, t.due_at, t.finished_at,
    CONCAT(u.name,' ',u.last_name) AS analyst_name,
    TIMESTAMPDIFF(SECOND, t.created_at, t.finished_at) AS elapsed_sec,
    (SELECT COUNT(*) FROM task_files f
      WHERE f.task_id=t.id AND f.is_deleted=0 AND f.file_type='ADMIN_ATTACHMENT') AS admin_files_count,
    (SELECT COUNT(*) FROM task_files f
      WHERE f.task_id=t.id AND f.is_deleted=0 AND f.file_type='EVIDENCE') AS evidence_files_count
  FROM tasks t
  JOIN users u ON u.id = t.assigned_to_user_id
  WHERE t.created_by_admin_id = ?
    AND t.status = 'FINALIZADA'
  ORDER BY t.finished_at DESC
");
$stmtH->execute([$adminId]);
$history = $stmtH->fetchAll(PDO::FETCH_ASSOC) ?: [];


include __DIR__ . '/../../../template/header.php'; ?>
<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<?php
include __DIR__ . '/../../../template/sidebar.php';
?>

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
                  <div class="ticket-value" style="text-align:left;">
                    <span class="task-priority-pill <?php echo prioClass($t['priority_name'] ?? ''); ?>">
  <?php echo h($t['priority_name'] ?? '—'); ?>
</span>


                  </div>
                </div>

                <div class="ticket-row">
                  <div class="ticket-label">Estado</div>
                  <span class="<?php echo statusPillClass($t['status'] ?? ''); ?>">
                    <?php echo h(statusLabel($t['status'] ?? '')); ?>
                  </span>
                </div>

                <div class="ticket-desc"><?php echo h($t['description']); ?></div>
              </div>

              <div class="ticket-card__actions" style="align-items:center;">
                <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=<?php echo (int)$t['id']; ?>">Ver </a>
<form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/reassign.php" style="display:flex; gap:8px; margin:0;">
  <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
  <select name="new_assigned_to_user_id" required>
    <option value="">Reasignar…</option>
    <?php foreach ($analysts as $a): ?>
      <option value="<?php echo (int)$a['id']; ?>"><?php echo h($a['full_name']); ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-secondary" type="submit">Aplicar</button>
</form>

<form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/cancel.php" style="margin:0;">
  <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
  <button class="btn-secondary" type="submit" onclick="return confirm('¿Cancelar esta tarea?');">
    Cancelar
  </button>
</form>

                <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/upload_admin_files.php"
          enctype="multipart/form-data" style="margin:0;">
      <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">

      <input id="ev_<?php echo (int)$t['id']; ?>" type="file" name="evidence_files[]" multiple required style="display:none"
             onchange="this.form.submit()">

      <button type="button" class="task-link-combined"
              onclick="document.getElementById('ev_<?php echo (int)$t['id']; ?>').click();">
        Adjuntar Archivos
      </button>
    </form>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </section>

  <?php if (!empty($history)): ?>
  <div class="user-info-card" style="margin-top:16px;">
    <h2 style="margin:0 0 12px 0;">Historial de tareas finalizadas</h2>

    <div style="overflow:auto;">
      <table id="tasksHistoryTable" class="display" style="width:100%;">
        <thead>
          <tr>
            <th>Tarea</th>
            <th>Fecha de entrega</th>
            <?php if ($rol === 2): ?><th>Analista</th><?php endif; ?>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $t): ?>
            <tr>
              <td><?php echo h($t['title']); ?></td>
              <td><?php echo h($t['due_at']); ?></td>
              <?php if ($rol === 2): ?><td><?php echo h($t['analyst_name'] ?? '—'); ?></td><?php endif; ?>
              <td>
                <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=<?php echo (int)$t['id']; ?>">Ver</a>
                &nbsp;|&nbsp;
                <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/reassign.php" style="display:flex; gap:8px; margin:0;">
  <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
  <select name="new_assigned_to_user_id" required>
    <option value="">Reasignar…</option>
    <?php foreach ($analysts as $a): ?>
      <option value="<?php echo (int)$a['id']; ?>"><?php echo h($a['full_name']); ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-secondary" type="submit">Aplicar</button>
</form>

<form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/cancel.php" style="margin:0;">
  <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
  <button class="btn-secondary" type="submit" onclick="return confirm('¿Cancelar esta tarea?');">
    Cancelar
  </button>
</form>

                <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/tasks/report_pdf.php?id=<?php echo (int)$t['id']; ?>" target="_blank" rel="noopener">PDF</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- DataTables (CDN rápido) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
    $(function(){
      $('#tasksHistoryTable').DataTable({
        pageLength: 10,
        order: [[1,'desc']],
        language: {
          search: "Buscar:",
          lengthMenu: "Mostrar _MENU_",
          info: "Mostrando _START_ a _END_ de _TOTAL_",
          paginate: { previous: "Anterior", next: "Siguiente" },
          zeroRecords: "Sin registros"
        }
      });
    });
  </script>
<?php endif; ?>


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
<div class="user-modal-backdrop" id="taskDetailModal">
  <div class="user-modal" style="max-width:900px;">
    <header class="user-modal-header">
      <h2>Detalle de tarea</h2>
      <button type="button" class="user-modal-close" onclick="closeTaskDetailModal()">×</button>
    </header>
    <div id="taskDetailBody" style="padding:14px 2px 2px 2px;">
      Cargando...
    </div>
  </div>
</div>

<script>
async function openTaskDetailModal(taskId){
  const modal = document.getElementById('taskDetailModal');
  modal.classList.add('is-visible');

  // placeholders
  modal.querySelector('[data-title]').textContent = 'Cargando...';
  modal.querySelector('[data-admin-files]').innerHTML = '<li style="opacity:.7;">Cargando…</li>';
  modal.querySelector('[data-evidence-files]').innerHTML = '';

  const r = await fetch(`/HelpDesk_EQF/modules/dashboard/tasks/ajax/task_detail.php?id=${taskId}`, {cache:'no-store'});
  const j = await r.json();
  if(!j.ok){ alert(j.msg || 'No se pudo cargar'); return; }

  const task = j.task;
  modal.querySelector('[data-title]').textContent = task.title || '—';
  modal.querySelector('[data-desc]').textContent = task.description || '';
  modal.querySelector('[data-due]').textContent = task.due_at || '—';
  modal.querySelector('[data-priority]').textContent = task.priority_name || '—';
  modal.querySelector('[data-status]').textContent = task.status || '—';

  const adminUL = modal.querySelector('[data-admin-files]');
  const evUL = modal.querySelector('[data-evidence-files]');
  adminUL.innerHTML = '';
  evUL.innerHTML = '';

  const baseAdmin = '/HelpDesk_EQF/uploads/tasks/admin/';
  const baseEv = '/HelpDesk_EQF/uploads/tasks/evidence/';

  const adminFiles = j.files.filter(f => f.file_type === 'ADMIN_ATTACHMENT');
  const evFiles    = j.files.filter(f => f.file_type === 'EVIDENCE');

  adminUL.innerHTML = adminFiles.length ? '' : '<li style="opacity:.7;">Sin adjuntos.</li>';
  evUL.innerHTML    = evFiles.length ? '' : '<li style="opacity:.7;">Sin evidencias.</li>';

  adminFiles.forEach(f=>{
    const li=document.createElement('li');
    li.innerHTML = `<a target="_blank" rel="noopener" href="${baseAdmin}${encodeURIComponent(f.stored_name)}">${f.original_name}</a>`;
    adminUL.appendChild(li);
  });

  evFiles.forEach(f=>{
    const li=document.createElement('li');
    li.innerHTML = `<a target="_blank" rel="noopener" href="${baseEv}${encodeURIComponent(f.stored_name)}">${f.original_name}</a>`;
    evUL.appendChild(li);
  });
}

</script>

<script>
function openTaskModal(){ document.getElementById('taskModal')?.classList.add('is-visible'); }
function closeTaskModal(){ document.getElementById('taskModal')?.classList.remove('is-visible'); }
document.getElementById('taskModal')?.addEventListener('click', (e) => {
  if (e.target.id === 'taskModal') closeTaskModal();
});
</script>

<script>
(function(){
  let lastSig = '';
  let sinceEventId = parseInt(localStorage.getItem('tasks_since_event_id') || '0', 10);

  function showToast(msg){
    // usa tu toast del sistema si ya tienes; por mientras:
    alert(msg);
  }

  async function poll(){
    try{
      const url = `/HelpDesk_EQF/modules/dashboard/tasks/ajax/tasks_signature.php?since_event_id=${sinceEventId}`;
      const r = await fetch(url, {cache:'no-store'});
      const j = await r.json();
      if(!j.ok) return;

      // notificaciones
      if (Array.isArray(j.events) && j.events.length){
        j.events.forEach(ev => {
          if(ev.event_type === 'REASSIGNED') showToast('Te reasignaron/retiraron una tarea: ' + (ev.note || ''));
          if(ev.event_type === 'CANCELED')   showToast('Cancelaron una tarea: ' + (ev.note || ''));
        });
      }

      if (typeof j.max_event_id === 'number' && j.max_event_id > sinceEventId){
        sinceEventId = j.max_event_id;
        localStorage.setItem('tasks_since_event_id', String(sinceEventId));
      }

      // refresh si cambió la firma
      if(!lastSig){ lastSig = j.signature || ''; return; }
      if((j.signature || '') !== lastSig) location.reload();

    }catch(e){}
  }

  poll();
  setInterval(poll, 4000);
  document.addEventListener('visibilitychange', () => { if(!document.hidden) poll(); });
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('tasksHistory');
  if(!el || !window.jQuery || !jQuery.fn.DataTable) return;

  jQuery(el).DataTable({
    pageLength: 5,
    lengthMenu: [5,10,25,50],
    order: [],
    language: {
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_",
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      paginate: { next: ">", previous: "<" },
      zeroRecords: "Sin resultados",
      infoEmpty: "Sin registros",
      infoFiltered: "(filtrado de _MAX_)"
    }
  });
});
</script>
<script src="/HelpDesk_EQF/assets/js/script.js?v=<?php echo time(); ?>"></script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
