<?php
// /HelpDesk_EQF/modules/dashboard/tasks/analyst.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

$rol = (int)($_SESSION['user_rol'] ?? ($_SESSION['rol'] ?? 0));
if (!isset($_SESSION['user_id']) || $rol !== 3) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$stmtT = $pdo->prepare("
    SELECT t.*,
           cp.label AS priority_name,
           CONCAT(a.name,' ',a.last_name) AS admin_name
    FROM tasks t
    JOIN catalog_priorities cp ON cp.id = t.priority_id
    JOIN users a ON a.id = t.created_by_admin_id
    WHERE t.assigned_to_user_id = ?
    ORDER BY t.created_at DESC
");
$stmtT->execute([$analystId]);
$tasks = $stmtT->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="user-container">
  <h2 style="margin:0 0 14px 0;">Mis tareas (Analista)</h2>

  <?php if (empty($tasks)): ?>
    <div class="user-info-card">
      <p style="opacity:.8;margin:0;">No tienes tareas asignadas.</p>
    </div>
  <?php else: ?>
    <div class="user-grid" style="display:grid; gap:16px;">
      <?php foreach ($tasks as $t): ?>
        <div class="user-info-card">
          <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
            <div>
              <h3 style="margin:0 0 6px 0;"><?php echo h($t['title']); ?></h3>
              <div style="opacity:.85; font-size:14px;">
                Creada por: <b><?php echo h($t['admin_name']); ?></b><br>
                Prioridad: <b><?php echo h($t['priority_name']); ?></b><br>
                Entrega: <b><?php echo h($t['due_at']); ?></b><br>
                Estado: <b><?php echo h($t['status']); ?></b>
              </div>
            </div>

            <a class="user-btn user-btn-ghost" href="/HelpDesk_EQF/modules/dashboard/tasks/view.php?id=<?php echo (int)$t['id']; ?>">Ver</a>
          </div>

          <p style="margin:12px 0 0 0; opacity:.85;">
            <?php echo nl2br(h($t['description'])); ?>
          </p>

          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <?php if ($t['status'] === 'ASIGNADA'): ?>
              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/ack.php">
                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                <button class="user-btn user-btn-primary" type="submit">Enterado</button>
              </form>

            <?php elseif ($t['status'] === 'EN_PROCESO'): ?>
              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/finish.php">
                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                <button class="user-btn user-btn-primary" type="submit">Finalizar</button>
              </form>

              <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/upload_evidence.php" enctype="multipart/form-data" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                <input type="file" name="evidence_files[]" class="user-input" multiple required>
                <button class="user-btn user-btn-ghost" type="submit">Subir evidencias</button>
              </form>

            <?php else: ?>
              <span style="opacity:.75;">Sin acciones disponibles.</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
