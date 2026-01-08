<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

$rol = (int)($_SESSION['user_rol'] ?? ($_SESSION['rol'] ?? 0));
if (!isset($_SESSION['user_id']) || $rol !== 3) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();
$analystId = (int)($_SESSION['user_id'] ?? 0);

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
function fmtSeconds(?int $sec): string {
  if (!$sec || $sec < 0) return '—';
  $h = intdiv($sec, 3600);
  $m = intdiv($sec % 3600, 60);
  $s = $sec % 60;
  return sprintf('%02d:%02d:%02d', $h, $m, $s);
}


// PENDIENTES (cards): ASIGNADA y EN_PROCESO
$stmtT = $pdo->prepare("
  SELECT t.*,
         cp.label AS priority_name,
         CONCAT(ad.name,' ',ad.last_name) AS admin_name
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  WHERE t.assigned_to_user_id = ?
    AND t.status IN ('ASIGNADA','EN_PROCESO')
  ORDER BY t.created_at DESC
");
$stmtT->execute([$analystId]);
$tasks = $stmtT->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmtH = $pdo->prepare("
  SELECT
    t.id, t.title, t.due_at, t.finished_at,
    TIMESTAMPDIFF(SECOND, t.created_at, t.finished_at) AS elapsed_sec,
    (SELECT COUNT(*) FROM task_files f
      WHERE f.task_id=t.id AND f.is_deleted=0 AND f.file_type='EVIDENCE') AS evidence_files_count
  FROM tasks t
  WHERE t.assigned_to_user_id = ?
    AND t.status = 'FINALIZADA'
  ORDER BY t.finished_at DESC
");
$stmtH->execute([$analystId]);
$history = $stmtH->fetchAll(PDO::FETCH_ASSOC) ?: [];


include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis tareas (Analista) | HelpDesk EQF</title>
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
        <p class="user-main-subtitle">Mis tareas</p>
      </div>
    </header>

    <section class="user-main-content">

      <?php if (empty($tasks)): ?>
        <div class="user-info-card">
          <h2>Mis tareas</h2>
          <p style="margin:0; opacity:.85;">No tienes tareas asignadas.</p>
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
                  <div class="ticket-label">Creada por</div>
                  <div class="ticket-value"><?php echo h($t['admin_name']); ?></div>
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
<a href="/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=<?php echo (int)$t['id']; ?>" class="btn-main-combined">
    Ver detalle
</a>
                <?php if (($t['status'] ?? '') === 'ASIGNADA'): ?>
  <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/ack.php" style="margin:0;">
    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
    <button class="btn-secondary" type="submit" style="width:auto; padding:0 16px;">Enterado</button>
  </form>

<?php elseif (($t['status'] ?? '') === 'EN_PROCESO'): ?>
  <form method="POST"
        action="/HelpDesk_EQF/modules/dashboard/tasks/upload_evidence.php"
        enctype="multipart/form-data"
        style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:0;">
    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
    <input type="file" name="evidence_files[]" multiple required>
    <button class="btn-primary" type="submit" >Subir evidencias</button>
  </form>

  <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/finish.php" style="margin:0;">
    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
    <button class="btn-secondary" type="submit" >Finalizar</button>
  </form>

<?php else: ?>
  <span style="opacity:.75;">Sin acciones disponibles.</span>
<?php endif; ?>

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

<?php include __DIR__ . '/../../../template/footer.php'; ?>
<script src="/HelpDesk_EQF/assets/js/script.js?v=<?php echo time(); ?>"></script>
<script>
(function(){
  let lastSig = '';

  async function poll(){
    try{
      const r = await fetch('/HelpDesk_EQF/modules/dashboard/tasks/ajax/tasks_signature.php', {cache:'no-store'});
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

  document.addEventListener('visibilitychange', () => {
    if(!document.hidden) poll();
  });
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

</body>
</html>
