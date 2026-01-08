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

$stmtT = $pdo->prepare("
  SELECT t.*,
         cp.label AS priority_name,
         CONCAT(ad.name,' ',ad.last_name) AS admin_name
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  WHERE t.assigned_to_user_id = ?
  ORDER BY t.created_at DESC
");
$stmtT->execute([$analystId]);
$tasks = $stmtT->fetchAll(PDO::FETCH_ASSOC);

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
        <p class="user-main-subtitle">Mis tareas (Analista)</p>
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

                <?php if (($t['status'] ?? '') === 'ASIGNADA'): ?>
                  <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/ack.php" style="margin:0;">
                    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                    <button class="btn-primary" type="submit" style="width:auto; padding:0 16px;">Enterado</button>
                  </form>

                <?php elseif (($t['status'] ?? '') === 'EN_PROCESO'): ?>
                  <form method="POST" action="/HelpDesk_EQF/modules/dashboard/tasks/finish.php" style="margin:0;">
                    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                    <button class="btn-primary" type="submit" style="width:auto; padding:0 16px;">Finalizar</button>
                  </form>

                <?php elseif (($t['status'] ?? '') === 'FINALIZADA'): ?>
                  <form method="POST"
                        action="/HelpDesk_EQF/modules/dashboard/tasks/upload_evidence.php"
                        enctype="multipart/form-data"
                        style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:0;">
                    <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                    <input type="file" name="evidence_files[]" multiple required>
                    <button class="btn-secondary" type="submit" style="width:auto; padding:0 16px;">Subir evidencias</button>
                  </form>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </section>
  </section>
</main>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
<script src="/HelpDesk_EQF/assets/js/script.js?v=<?php echo time(); ?>"></script>

</body>
</html>
