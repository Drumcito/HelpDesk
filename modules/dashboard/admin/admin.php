<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

$pdo = Database::getConnection();

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


// =============================
// 1) Crear tarea (prioridad ALTA) para analista
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_tarea') {
    $analystId   = (int)($_POST['analyst_id'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fechaLimite = $_POST['fecha_limite'] ?? null;

    $fechaLimiteDB = null;
    if (!empty($fechaLimite)) {
        $fechaLimiteDB = str_replace('T', ' ', $fechaLimite) . ':00';
    }

    // Archivo opcional
    $archivoRuta = null;
    if (!empty($_FILES['archivo_tarea']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/tasks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $nombreOriginal = $_FILES['archivo_tarea']['name'];
        $ext            = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreSeguro   = uniqid('tarea_') . '.' . $ext;
        $destino        = $uploadDir . $nombreSeguro;

        if (move_uploaded_file($_FILES['archivo_tarea']['tmp_name'], $destino)) {
            $archivoRuta = 'uploads/tasks/' . $nombreSeguro;
        }
    }

    if ($analystId > 0 && $titulo !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO analyst_tasks (
                area, admin_id, analyst_id,
                titulo, descripcion, fecha_limite, archivo_ruta
            ) VALUES (
                :area, :admin_id, :analyst_id,
                :titulo, :descripcion, :fecha_limite, :archivo_ruta
            )
        ");
        $stmt->execute([
            ':area'         => $areaAdmin,
            ':admin_id'     => $userId,
            ':analyst_id'   => $analystId,
            ':titulo'       => $titulo,
            ':descripcion'  => $descripcion,
            ':fecha_limite' => $fechaLimiteDB,
            ':archivo_ruta' => $archivoRuta,
        ]);

        // (Opcional) Notificación al analista si ya tienes tabla notifications
        // $pdo->prepare("INSERT INTO notifications (user_id,type,title,body,link) VALUES (?,?,?,?,?)")
        //     ->execute([$analystId,'task_assigned','Nueva tarea asignada',$titulo,'/HelpDesk_EQF/modules/dashboard/analyst/tasks.php']);

$_SESSION['flash_ok'] = "Tarea creada (prioridad alta) y asignada al analista.";
header('Location: /HelpDesk_EQF/modules/dashboard/admin/admin.php');
exit;
    } else {
$_SESSION['flash_err'] = "Selecciona un analista y escribe un título.";
header('Location: /HelpDesk_EQF/modules/dashboard/admin/admin.php');
exit;
    }
}

// =============================
// 2) Analistas del área (select tareas)
// =============================
$stmtAnalysts = $pdo->prepare("
    SELECT id, name, last_name
    FROM users
    WHERE rol = 3
      AND area = :area
    ORDER BY last_name ASC, name ASC
");
$stmtAnalysts->execute([':area' => $areaAdmin]);
$analysts = $stmtAnalysts->fetchAll(PDO::FETCH_ASSOC);

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
// 4) Últimas tareas creadas por este Admin
// =============================
$stmtTareas = $pdo->prepare("
    SELECT t.*, u.name, u.last_name
    FROM analyst_tasks t
    JOIN users u ON u.id = t.analyst_id
    WHERE t.area = :area
      AND t.admin_id = :admin_id
    ORDER BY t.created_at DESC
    LIMIT 8
");
$stmtTareas->execute([
    ':area'     => $areaAdmin,
    ':admin_id' => $userId
]);
$tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

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

        <section class="button">
          <button type="button" class="btn-primary" onclick="openTaskModal () " style="width: 150px; height: 40px;">+ Crear tarea</button>
        </section>
<div class="user-modal-backdrop" id="taskModal" style="display:none;">
  <div class="user-modal">
    <header class="user-modal-header">
      <h2>Crear tarea </h2>
      <button type="button" class="user-modal-close" onclick="closeTaskModal()">✕</button>
    </header>

    <form method="POST" enctype="multipart/form-data" class="admin-task-form">
      <input type="hidden" name="accion" value="crear_tarea">

      <div class="form-group">
        <label for="analyst_id">Asignar a analista</label>
        <select name="analyst_id" id="analyst_id" required>
          <option value="">Selecciona un analista
                <?php foreach ($analysts as $a): ?>
                <option value="<?php echo (int)$a['id']; ?>">
                <?php echo htmlspecialchars($a['name'] . ' ' . $a['last_name'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
            <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="titulo">Título</label>
        <input type="text" name="titulo" id="titulo" required maxlength="150">
      </div>

      <div class="form-group">
        <label for="descripcion">Descripción</label><br>
        <textarea name="descripcion" id="descripcion" rows="3"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="fecha_limite">Fecha límite</label>
          <input type="datetime-local" name="fecha_limite" id="fecha_limite">
        </div>
        <div class="form-group">
          <label for="archivo_tarea">Adjunto</label>
          <input type="file" name="archivo_tarea" id="archivo_tarea">
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
        <button type="button" class="btn-secondary" onclick="closeTaskModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Crear</button>
      </div>
    </form>
  </div>
</div>


            <!-- Tareas recientes -->
            <section class="admin-card">
                <h2>Tareas activas</h2>
                <?php if (empty($tareas)): ?>
                    <p class="admin-empty">No has creado tareas todavía.</p>
                <?php else: ?>
                    <div class="admin-task-list">
                        <?php foreach ($tareas as $t): ?>
                            <article class="admin-task-card">
                                <header class="admin-task-header">
                                    <h3><?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="badge badge-prioridad-alta">Alta</span>
                                </header>

                                <p class="admin-task-meta">
                                    Para: <?php echo htmlspecialchars($t['name'] . ' ' . $t['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    · Estado: <?php echo htmlspecialchars($t['estado'] ?? 'pendiente', ENT_QUOTES, 'UTF-8'); ?>
                                </p>

                                <?php if (!empty($t['fecha_limite'])): ?>
                                    <p class="admin-task-meta">Fecha límite: <?php echo htmlspecialchars($t['fecha_limite'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($t['descripcion'])): ?>
                                    <p class="admin-task-desc"><?php echo nl2br(htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($t['archivo_ruta'])): ?>
                                    <p class="admin-task-meta">
                                        <a href="/HelpDesk_EQF/<?php echo htmlspecialchars($t['archivo_ruta'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Ver archivo adjunto</a>
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
<script src="/HelpDesk_EQF/assets/js/script.js?v=<?php echo time(); ?>"></script>

</main>
<?php include __DIR__ . '/../../../template/footer.php'; ?>
<?php if ((int)($_SESSION['user_rol'] ?? 0) === 2): ?>
<div class="eqf-modal-backdrop" id="announceModal">
  <div class="eqf-modal eqf-announce-modal">
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
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btnOpenAnnouncement');
  const modal = document.getElementById('announceModal');

  console.log('BTN:', btn, 'MODAL:', modal);

  if (btn && modal) {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      modal.classList.add('show');
      console.log('Modal abierto ✅');
    });
  }
});
</script>

</body>
</html>
<script>
function openTaskModal(){
  document.getElementById('taskModal').style.display = 'flex';
}
function closeTaskModal(){
  document.getElementById('taskModal').style.display = 'none';
}
// cerrar si das click fuera
document.addEventListener('click', function(e){
  const backdrop = document.getElementById('taskModal');
  if(!backdrop) return;
  if(e.target === backdrop) closeTaskModal();
});
</script>
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
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btnOpenAnnouncement');
  const modal = document.getElementById('announceModal');

  if (!btn || !modal) {
    console.warn('No se encontró btnOpenAnnouncement o announceModal');
    return;
  }

  btn.addEventListener('click', (e) => {
    e.preventDefault();
    modal.classList.add('show');
  });

  // click en backdrop para cerrar
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.classList.remove('show');
  });

  document.getElementById('btnCloseAnnouncement')?.addEventListener('click', (e) => {
    e.preventDefault();
    modal.classList.remove('show');
  });

  document.getElementById('btnCancelAnnouncement')?.addEventListener('click', (e) => {
    e.preventDefault();
    modal.classList.remove('show');
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const sendBtn = document.getElementById('btnSendAnnouncement');
  if (!sendBtn) return;

  sendBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    const payload = {
      title: (document.getElementById('ann_title')?.value || '').trim(),
      body: (document.getElementById('ann_body')?.value || '').trim(),
      level: document.getElementById('ann_level')?.value || 'INFO',
      target_area: document.getElementById('ann_area')?.value || 'ALL',
      starts_at: document.getElementById('ann_starts')?.value || null,
      ends_at: document.getElementById('ann_ends')?.value || null
    };

    if (!payload.title || !payload.body) {
      alert('Título y descripción son obligatorios.');
      return;
    }

    try {
      const res = await fetch('/HelpDesk_EQF/modules/dashboard/admin/ajax/create_announcement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const raw = await res.text();
      console.log('RESP RAW:', raw);

      let data = {};
      try { data = JSON.parse(raw); } catch {}

      if (!res.ok || !data.ok) {
        alert(data.msg || ('No se pudo enviar el aviso. HTTP ' + res.status));
        return;
      }

      alert('Aviso enviado ✅');
      document.getElementById('announceModal')?.classList.remove('show');

      // opcional: limpiar campos
      document.getElementById('ann_title').value = '';
      document.getElementById('ann_body').value  = '';
      document.getElementById('ann_level').value = 'INFO';
      document.getElementById('ann_area').value  = 'ALL';
      document.getElementById('ann_starts').value = '';
      document.getElementById('ann_ends').value   = '';

    } catch (err) {
      console.error(err);
      alert('Error de red / fetch. Revisa consola.');
    }
  });
});
</script>


