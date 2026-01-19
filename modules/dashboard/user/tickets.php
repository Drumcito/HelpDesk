<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
$activePage = 'tickets';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();

function h($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fmtSeconds(int $sec): string {
  $sec = max(0, $sec);
  $d = intdiv($sec, 86400); $sec %= 86400;
  $h = intdiv($sec, 3600);  $sec %= 3600;
  $m = intdiv($sec, 60);

  if ($d > 0) return "{$d}d {$h}h";
  if ($h > 0) return "{$h}h {$m}m";
  if ($m > 0) return "{$m}m";
  return "{$sec}s";
}

// ✅ EXACTO a tu audit_log.details: {"from":"en_proceso","to":"cerrado"}
function parseStatusFromDetails($details): array {
  $old = '';
  $new = '';

  if ($details === null) return [$old, $new];
  $raw = trim((string)$details);
  if ($raw === '') return [$old, $new];

  $j = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
    $old = (string)($j['from'] ?? '');
    $new = (string)($j['to'] ?? '');
  }
  return [$old, $new];
}

$userId   = (int)$_SESSION['user_id'];
$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ==========================
   LISTA HISTORIAL
========================== */
$stmt = $pdo->prepare("
  SELECT
    t.id, t.problema, t.area, t.prioridad, t.estado,
    t.fecha_envio,
    t.no_jefe, t.nombre_jefe,
    u.name AS analyst_name, u.last_name AS analyst_last
  FROM tickets t
  LEFT JOIN users u ON u.id = t.asignado_a
  WHERE t.user_id = :uid
  ORDER BY t.fecha_envio DESC
  LIMIT 200
");
$stmt->execute([':uid' => $userId]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ==========================
   DETALLE + EVENTOS
========================== */
$detail = null;
$statusEvents = [];
$durations = [];
$assignEvents = [];
$transferEvents = [];

if ($ticketId > 0) {
  $st = $pdo->prepare("
    SELECT t.*, u.name AS analyst_name, u.last_name AS analyst_last
    FROM tickets t
    LEFT JOIN users u ON u.id = t.asignado_a
    WHERE t.id = :id AND t.user_id = :uid
    LIMIT 1
  ");
  $st->execute([':id' => $ticketId, ':uid' => $userId]);
  $detail = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  if ($detail) {

    // Cambios de estatus desde audit_log
    $sa = $pdo->prepare("
      SELECT created_at, details, actor_name, actor_area
      FROM audit_log
      WHERE action = 'TICKET_STATUS_CHANGE'
        AND entity_id = :tid
        AND (entity = 'tickets' OR entity IS NULL OR entity = '')
      ORDER BY created_at ASC
    ");
    $sa->execute([':tid' => $ticketId]);

    $rows = $sa->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as $r) {
      [$old, $new] = parseStatusFromDetails($r['details'] ?? '');
      $statusEvents[] = [
        'old_status' => $old,
        'new_status' => $new,
        'changed_at' => $r['created_at'] ?? '',
        'actor'      => trim((string)($r['actor_name'] ?? '')),
        'actor_area' => trim((string)($r['actor_area'] ?? '')),
      ];
    }

    // Asignaciones
    try {
      $sq = $pdo->prepare("
        SELECT *
        FROM ticket_assignments_log
        WHERE ticket_id = :tid
        ORDER BY created_at ASC
      ");
      $sq->execute([':tid' => $ticketId]);
      $assignEvents = $sq->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
      $assignEvents = [];
    }

    // Transferencias
    try {
      $sq2 = $pdo->prepare("
        SELECT *
        FROM ticket_transfers
        WHERE ticket_id = :tid
        ORDER BY created_at ASC
      ");
      $sq2->execute([':tid' => $ticketId]);
      $transferEvents = $sq2->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
      $transferEvents = [];
    }

    // Duración por estatus (timeline)
    $timeline = [];
    $startAt = (string)($detail['fecha_envio'] ?? '');
    $initialStatus = (string)($detail['estado'] ?? 'abierto');

    if (!empty($statusEvents) && !empty($statusEvents[0]['old_status'])) {
      $initialStatus = (string)$statusEvents[0]['old_status'];
    }

    $timeline[] = ['status' => $initialStatus, 'at' => $startAt];

    foreach ($statusEvents as $ev) {
      $ns = trim((string)($ev['new_status'] ?? ''));
      $at = (string)($ev['changed_at'] ?? '');
      if ($ns !== '' && $at !== '') {
        $timeline[] = ['status' => $ns, 'at' => $at];
      }
    }

    // fin: si no existe cerrado_at, usamos NOW()
    $endAt = '';
    if (!empty($detail['cerrado_at'])) $endAt = (string)$detail['cerrado_at'];
    elseif (!empty($detail['closed_at'])) $endAt = (string)$detail['closed_at'];
    else $endAt = date('Y-m-d H:i:s');

    $timeline[] = ['status' => '__END__', 'at' => $endAt];

    for ($i=0; $i < count($timeline)-1; $i++) {
      $stt = trim((string)$timeline[$i]['status']);
      if ($stt === '' || $stt === '__END__') continue;

      $a = strtotime((string)$timeline[$i]['at']);
      $b = strtotime((string)$timeline[$i+1]['at']);
      if ($a && $b && $b >= $a) {
        $durations[$stt] = ($durations[$stt] ?? 0) + ($b - $a);
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis tickets | HelpDesk EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="user-body">

<main class="user-main">
  <section class="user-main-inner">

    <header class="user-topbar">
      <div class="user-topbar-left">
        <p class="login-brand">
          <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
        </p>
        <p class="user-main-subtitle">Historial de tickets</p>
      </div>
      <div class="user-topbar-right">
        <a class="btn-primary" href="/HelpDesk_EQF/modules/dashboard/user/user.php" style="text-decoration:none;">Volver</a>
      </div>
    </header>

    <section class="user-main-content" style="display:grid; grid-template-columns: 1fr 1fr; gap:18px;">

      <!-- LISTA -->
      <div class="user-info-card">
        <h2>Mis tickets</h2>

        <?php if (empty($tickets)): ?>
          <p>No hay tickets.</p>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:10px; margin-top:12px;">
            <?php foreach ($tickets as $t): ?>
              <?php
                $id = (int)$t['id'];
                $active = ($id === $ticketId);
                $analyst = trim(($t['analyst_name'] ?? '').' '.($t['analyst_last'] ?? '')) ?: 'Sin asignar';
              ?>
              <a href="?id=<?php echo $id; ?>"
                 style="text-decoration:none;"
                 class="announcement <?php echo $active ? 'announcement--warn' : 'announcement--info'; ?>">
                <div class="announcement__top">
                  <div>
                    <p class="announcement__h">Ticket #<?php echo $id; ?> · <?php echo h($t['problema'] ?? ''); ?></p>
                    <p class="announcement__meta">
                      <?php echo h($t['fecha_envio'] ?? ''); ?>
                      <br>Estado: <?php echo h($t['estado'] ?? ''); ?>
                      <br>Atiende: <?php echo h($analyst); ?>
                      <?php if (!empty($t['no_jefe']) && !empty($t['nombre_jefe'])): ?>
                        <br><strong>Solicitado para:</strong> <?php echo h($t['nombre_jefe']); ?>
                      <?php endif; ?>
                    </p>
                  </div>
                  <span class="announcement__pill"><?php echo h(strtoupper((string)($t['prioridad'] ?? ''))); ?></span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- DETALLE -->
      <div class="user-info-card">
        <h2>Detalle</h2>

        <?php if (!$detail): ?>
          <p>Selecciona un ticket para ver la información.</p>
        <?php else: ?>
          <?php $analyst = trim(($detail['analyst_name'] ?? '').' '.($detail['analyst_last'] ?? '')) ?: 'Sin asignar'; ?>

          <p style="margin-top:10px;">
            <strong>Ticket #<?php echo (int)$detail['id']; ?></strong><br>
            Problema: <?php echo h($detail['problema'] ?? ''); ?><br>
            Área: <?php echo h($detail['area'] ?? ''); ?><br>
            Prioridad: <?php echo h($detail['prioridad'] ?? ''); ?><br>
            Estado actual: <?php echo h($detail['estado'] ?? ''); ?><br>
            Atiende: <?php echo h($analyst); ?><br>
            Creado: <?php echo h($detail['fecha_envio'] ?? ''); ?><br>
            <?php if (!empty($detail['no_jefe']) && !empty($detail['nombre_jefe'])): ?>
              <strong>Solicitado para:</strong> <?php echo h($detail['nombre_jefe']); ?><br>
            <?php endif; ?>
          </p>

          <hr style="margin:14px 0; opacity:.2;">

          <h3 style="margin:0 0 10px 0;">Tiempo por estatus</h3>
          <?php if (empty($durations)): ?>
            <p style="opacity:.75;">Sin registros suficientes para calcular tiempos.</p>
          <?php else: ?>
            <ul style="margin:0; padding-left:18px;">
              <?php foreach ($durations as $st => $sec): ?>
                <li><strong><?php echo h($st); ?>:</strong> <?php echo h(fmtSeconds((int)$sec)); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <hr style="margin:14px 0; opacity:.2;">

          <h3 style="margin:0 0 10px 0;">Timeline</h3>
          <ul style="margin:0; padding-left:18px;">
            <li><strong>CREADO</strong> · <?php echo h($detail['fecha_envio'] ?? ''); ?></li>

            <?php foreach ($assignEvents as $a): ?>
              <li><strong>ASIGNACIÓN</strong> · <?php echo h($a['created_at'] ?? ''); ?></li>
            <?php endforeach; ?>

            <?php foreach ($transferEvents as $tr): ?>
              <li><strong>TRANSFERENCIA</strong> · <?php echo h($tr['created_at'] ?? ''); ?></li>
            <?php endforeach; ?>

            <?php foreach ($statusEvents as $ev): ?>
              <li>
                <strong>ESTATUS</strong>:
                <?php echo h($ev['old_status'] ?: '?'); ?>
                → <?php echo h($ev['new_status'] ?: '?'); ?>
                · <?php echo h($ev['changed_at'] ?? ''); ?>
                <?php if (!empty($ev['actor'])): ?>
                  · <?php echo h($ev['actor']); ?>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

    </section>

  </section>
</main>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
</body>
</html>
