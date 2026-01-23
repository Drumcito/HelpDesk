<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

$pdo = Database::getConnection();

$annCards = [];
$annAdminList = [];

try {
  // Cards (vista tipo usuario)
  $stmt = $pdo->query("
    SELECT id, title, body, level, target_area, starts_at, ends_at, created_at, created_by_area
    FROM announcements
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 20
  ");
  $annCards = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // Lista admin (compacta)
  $stmt2 = $pdo->query("
    SELECT id, title, level, target_area, created_at, created_by_area
    FROM announcements
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 20
  ");
  $annAdminList = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (Throwable $e) {
  $annCards = [];
  $annAdminList = [];
}



if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$areaAdmin = $_SESSION['user_area'] ?? '';

$mensajeExito = $_SESSION['flash_ok'] ?? '';
$mensajeError = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);


// ============================
// HELPERS (para anuncios)
// ============================
if (!function_exists('h')) {
  function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('annClass')) {
  function annClass(string $level): string {
    $level = strtoupper(trim($level));
    return match ($level) {
      'CRITICAL' => 'announcement--critical',
      'WARN'     => 'announcement--warn',
      default    => 'announcement--info',
    };
  }
}
if (!function_exists('annLabel')) {
  function annLabel(string $level): string {
    $level = strtoupper(trim($level));
    return match ($level) {
      'CRITICAL' => 'Crítico',
      'WARN'     => 'Aviso',
      default    => 'Info',
    };
  }
}


// =============================
// 3) KPIs del área (cards)
// =============================
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE area = :area");
$stmtTotal->execute([':area' => $areaAdmin]);
$totalTickets = (int)$stmtTotal->fetchColumn();

$stmtAbiertos = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado IN ('abierto','en_proceso','en_espera')
");
$stmtAbiertos->execute([':area' => $areaAdmin]);
$totalAbiertos = (int)$stmtAbiertos->fetchColumn();

$stmtVencidos = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado = 'vencido'
");
$stmtVencidos->execute([':area' => $areaAdmin]);
$totalVencidos = (int)$stmtVencidos->fetchColumn();

$stmtSinAsignar = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND (asignado_a IS NULL OR asignado_a = 0)
      AND estado IN ('abierto','en_proceso','en_espera','vencido')
");
$stmtSinAsignar->execute([':area' => $areaAdmin]);
$totalSinAsignar = (int)$stmtSinAsignar->fetchColumn();

// Estancados (heurística: abiertos/en_proceso/en_espera con +48h desde fecha_envio)
$stmtEstancados = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado IN ('abierto','en_proceso','en_espera')
      AND fecha_envio <= (NOW() - INTERVAL 2 DAY)
");
$stmtEstancados->execute([':area' => $areaAdmin]);
$totalEstancados = (int)$stmtEstancados->fetchColumn();

$stmtCerrados = $pdo->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE area = :area
      AND estado = 'cerrado'
");
$stmtCerrados->execute([':area' => $areaAdmin]);
$totalCerrados = (int)$stmtCerrados->fetchColumn();

$porcCierre = 0;
if ($totalTickets > 0) {
    $porcCierre = round(($totalCerrados / $totalTickets) * 100, 1);
}

// (Opcional) Transferencias (si aún no existe, esto quedará en 0 si las columnas no están)
$totalEntrantes = 0;
$totalSalientes = 0;
try {
    $stmtEntr = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE area = :area AND parent_ticket_id IS NOT NULL");
    $stmtEntr->execute([':area' => $areaAdmin]);
    $totalEntrantes = (int)$stmtEntr->fetchColumn();

    $stmtSal = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE area = :area AND transferred_at IS NOT NULL");
    $stmtSal->execute([':area' => $areaAdmin]);
    $totalSalientes = (int)$stmtSal->fetchColumn();
} catch (Throwable $e) {
    // si no existen columnas todavía, ignoramos
}


// =============================
// 5) Resumen rápido de tickets del área
// =============================
$stmtTickets = $pdo->prepare("
    SELECT id, problema, estado, prioridad, fecha_envio
    FROM tickets
    WHERE area = :area
      AND estado IN ('abierto','en_proceso')
    ORDER BY fecha_envio DESC
    LIMIT 8
");

$stmtTickets->execute([':area' => $areaAdmin]);
$ticketsArea = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

// =============================
// 6) (Opcional) Notificaciones del Admin
// =============================
$notificaciones = [];
try {
    $stmtNoti = $pdo->prepare("
        SELECT id, title, body, link, is_read, created_at
        FROM notifications
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $stmtNoti->execute([':uid' => $userId]);
    $notificaciones = $stmtNoti->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // si no existe tabla, no pasa nada
}

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin | Mesa de Ayuda EQF</title>
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
                <p class="user-main-subtitle">
                    Panel Admin – Área <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?><br>
                </p>
            </div>
        </header>
<div class="user-info-card" style="margin-top:18px;" id="annWrap">
  <h2>Anuncios</h2>
  <div class="user-announcements">
    <div class="user-announcements__head">
      <h3 class="user-announcements__title">
        Activos
<span class="user-announcements__badge" id="annBadge"><?php echo count($annCards ?? []); ?></span>
      </h3>
    </div>

    <div class="user-announcements__list" id="annList">
      <?php if (empty($annCards)): ?>
        <p style="margin:0; color:#6b7280;">No hay anuncios activos.</p>
      <?php else: ?>
<?php foreach ($annCards as $a): ?>
  <?php $lvl = strtoupper(trim((string)($a['level'] ?? 'INFO'))); ?>

  <div class="announcement <?php echo annClass($lvl); ?>">
    <div class="announcement__top">
      <div>
        <p class="announcement__h"><?php echo h($a['title'] ?? ''); ?></p>
        <p class="announcement__meta">
          <?php echo h('Dirigido a: ' . ($a['target_area'] ?? '')); ?>
          <?php if (!empty($a['starts_at'])): ?>
            <br><?php echo h('Hora de inicio: ' . $a['starts_at']); ?>
          <?php endif; ?>
          <?php if (!empty($a['ends_at'])): ?>
            <br><?php echo h('Hora estimada fin: ' . $a['ends_at']); ?>
          <?php endif; ?>
        </p>
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <span class="announcement__pill"><?php echo annLabel($lvl); ?></span>

        <?php
          // Regla FINAL: Admin y Analista = MISMA regla → solo su misma área
          $createdArea = trim((string)($a['created_by_area'] ?? ''));
          $myArea      = trim((string)($areaAdmin ?? ''));
          $canDisable  = ($createdArea !== '' && $myArea !== '' && strcasecmp($createdArea, $myArea) === 0);
        ?>

        <?php if ($canDisable): ?>
          <button type="button"
                  class="task-cancel-link"
                  data-ann-disable
                  data-id="<?php echo (int)($a['id'] ?? 0); ?>">
            Desactivar
          </button>
        <?php endif; ?>

      </div>
    </div>

    <div class="announcement__body">
      <?php echo nl2br(h($a['body'] ?? '')); ?>
    </div>
  </div>
<?php endforeach; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

        <section class="user-main-content">

            <?php if ($mensajeExito): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($mensajeError): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <!-- KPIs: cards clicables (atajos) -->
            <section class="admin-kpi-grid">
                <a class="admin-kpi-card" href="/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php" style="text-decoration:none;">
                    <span class="admin-kpi-label">Total de tickets del área</span>
                    <span class="admin-kpi-value"><?php echo $totalTickets; ?></span>
                </a>

                <a class="admin-kpi-card" href="/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=abierto" style="text-decoration:none;">
                    <span class="admin-kpi-label">Tickets abiertos / en proceso / en espera</span>
                    <span class="admin-kpi-value"><?php echo $totalAbiertos; ?></span>
                </a>

                <a class="admin-kpi-card admin-kpi-danger" href="/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estado=vencido" style="text-decoration:none;">
                    <span class="admin-kpi-label">Tickets vencidos</span>
                    <span class="admin-kpi-value"><?php echo $totalVencidos; ?></span>
                </a>

                <a class="admin-kpi-card" href="/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?sin_asignar=1" style="text-decoration:none;">
                    <span class="admin-kpi-label">Sin asignar</span>
                    <span class="admin-kpi-value"><?php echo $totalSinAsignar; ?></span>
                </a>

                <a class="admin-kpi-card" href="/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php?estancados=1" style="text-decoration:none;">
                    <span class="admin-kpi-label">Estancados (+48h)</span>
                    <span class="admin-kpi-value"><?php echo $totalEstancados; ?></span>
                </a>

                <a class="admin-kpi-card admin-kpi-success" href="/HelpDesk_EQF/modules/dashboard/admin/reports.php" style="text-decoration:none;">
                    <span class="admin-kpi-label">% cierre (cerrados / total)</span>
                    <span class="admin-kpi-value"><?php echo $porcCierre; ?>%</span>
                </a>
            </section>

            <!-- Tickets recientes -->
            <section class="admin-card">
                <h2>Tickets en proceso</h2>
                <?php if (empty($ticketsArea)): ?>
                    <p class="admin-empty">No hay tickets registrados para esta área.</p>
                <?php else: ?>
                    <div class="admin-ticket-list">
                        <?php foreach ($ticketsArea as $tk): ?>
                            <div class="admin-ticket-row">
                                <strong>
                                    #<?php echo (int)$tk['id']; ?>
                                    - <?php echo htmlspecialchars($tk['problema'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>
                                <div class="admin-ticket-meta">
                                    Estado: <?php echo htmlspecialchars($tk['estado'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    · Prioridad: <?php echo htmlspecialchars($tk['prioridad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    · Fecha: <?php echo htmlspecialchars($tk['fecha_envio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    · <a href="/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php">Abrir</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </section>
    </section>

</main>
<?php include __DIR__ . '/../../../template/footer.php'; ?>
<?php if ((int)($_SESSION['user_rol'] ?? 0) === 2): ?>
<div class="eqf-modal-backdrop" id="announceModal">
  <div class="eqf-modal eqf-announce-modal" data-ann-modal>
    <div class="eqf-modal-header">
      <div>
        <strong>Nuevo aviso</strong>
        <div class="panel-muted">Se mostrará en “Resumen” del usuario y se enviará como notificación del navegador.</div>
      </div>
      <button class="eqf-modal-close" type="button" id="btnCloseAnnouncement">✕</button>
    </div>

    <div class="eqf-modal-body eqf-announce-body">
      <div class="eqf-field">
        <label>Título</label>
        <input type="text" id="ann_title" maxlength="120" placeholder="Ej. Mantenimiento programado">
      </div>

      <div class="eqf-field eqf-announce-mt">
        <label>Descripción</label>
        <textarea id="ann_body" rows="4" maxlength="600" placeholder="Escribe el mensaje..."></textarea>
      </div>

      <div class="eqf-grid-2 eqf-announce-mt">
        <div class="eqf-field">
          <label>Categoría</label>
          <select id="ann_level">
            <option value="INFO">INFORMATIVO</option>
            <option value="WARN">ADVERTENCIA</option>
            <option value="CRITICAL">CRITICO</option>
          </select>
        </div>

        <div class="eqf-field">
          <label>Área</label>
          <select id="ann_area">
  <option value="ALL">ALL</option>
  <option value="Sucursal">Sucursal</option>
  <option value="Corporativo">Corporativo</option>
</select>

        </div>
      </div>

      <div class="eqf-grid-2 eqf-announce-mt">
        <div class="eqf-field">
          <label>Inicio (opcional)</label>
          <input type="datetime-local" id="ann_starts">
        </div>
        <div class="eqf-field">
          <label>Fin (opcional)</label>
          <input type="datetime-local" id="ann_ends">
        </div>
      </div>
    </div>

    <div class="eqf-modal-footer">
      <button class="eqf-btn eqf-btn-secondary" type="button" id="btnCancelAnnouncement">Cancelar</button>
      <button class="eqf-btn eqf-btn-primary" type="button" id="btnSendAnnouncement">Enviar</button>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function () {

  function showToast(msg) {
    const toast = document.createElement('div');
    toast.className = 'eqf-toast-ticket';
    toast.textContent = msg || '';
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.classList.add('hide');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  function pollStaffNotifications() {
    fetch('/HelpDesk_EQF/modules/notifications/check_staff_notifications.php')
      .then(r => r.json())
      .then(data => {
        if (!data.ok || !data.has) return;
        if (!Array.isArray(data.notifications)) return;

        data.notifications.forEach(n => {
          const title = n.title || 'HelpDesk EQF';
          const body  = n.body  || 'Tienes una notificación nueva.';
          showToast(body);

          if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
              body,
              icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png'
            });
          }
        });
      })
      .catch(()=>{});
  }

  pollStaffNotifications();
  setInterval(pollStaffNotifications, 10000);
});
</script>

<script>
/* Modal Aviso (unificado): abre con [data-open-announcement], cierra con X/Cancelar, click fuera, ESC */
(() => {
  const modal = document.getElementById('announceModal');
  if (!modal) return;

  function open() { modal.classList.add('show'); }
  function close(){ modal.classList.remove('show'); }

  window.openAnnounceModal = open;
  window.closeAnnounceModal = close;

  document.addEventListener('click', (e) => {
    // ABRIR (sidebar)
    if (e.target.closest('[data-open-announcement]')) {
      e.preventDefault();
      open();
      return;
    }

    // CERRAR (soporta admin ids + analyst data-*)
    if (
      e.target.closest('[data-close-announcement]') ||
      e.target.closest('[data-cancel-announcement]') ||
      e.target.closest('#btnCloseAnnouncement') ||
      e.target.closest('#btnCancelAnnouncement')
    ) {
      e.preventDefault();
      close();
      return;
    }

    // click fuera
    if (modal.classList.contains('show')) {
      const inner = modal.querySelector('[data-ann-modal]') || modal.querySelector('.eqf-modal');
      if (inner && !inner.contains(e.target) && e.target.closest('#announceModal')) close();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('show')) close();
  });
})();
</script>


<script>
document.getElementById('btnSendAnnouncement')?.addEventListener('click', async () => {

  // Agarra valores (si tus IDs cambian entre admin/analyst, esto te salva)
  const getVal = (id) => (document.getElementById(id)?.value ?? '').trim();

  const title = getVal('ann_title');
  const body  = getVal('ann_body');

  if (!title || !body) {
    alert('Faltan campos obligatorios');
    return;
  }

  const fd = new FormData();
  fd.append('title', title);
  fd.append('body', body);

  // level/area tal cual, el backend ya los normaliza si son ENUM
  fd.append('level', (document.getElementById('ann_level')?.value ?? '').trim());
  fd.append('target_area', (document.getElementById('ann_area')?.value ?? '').trim());

  fd.append('starts_at', (document.getElementById('ann_starts')?.value ?? '').trim());
  fd.append('ends_at', (document.getElementById('ann_ends')?.value ?? '').trim());

  try {
    const res = await fetch('/HelpDesk_EQF/modules/dashboard/admin/ajax/create_announcement.php', {
      method: 'POST',
      body: fd
    });

    const data = await res.json().catch(() => null);
    if (!data || !data.ok) {
      alert((data && data.msg) ? data.msg : 'Error al guardar aviso');
      return;
    }

    alert('Aviso creado correctamente ✅');

    // cerrar modal
    document.getElementById('announceModal')?.classList.remove('show');

    // limpiar campos
    ['ann_title','ann_body','ann_starts','ann_ends'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });

  } catch (e) {
    console.error(e);
    alert('Error de red al crear aviso');
  }
});
</script>



<script>
  window.EQF_CAN_DEACTIVATE_ANN = true;
</script>
<script src="/HelpDesk_EQF/assets/js/announcements_live.js?v=1"></script>

<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>