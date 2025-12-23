<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

// Solo Analistas (rol = 3)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo       = Database::getConnection();
$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';
$userSap   = $_SESSION['number_sap'] ?? '';

// -------- ALERTAS ----------
$alerts = [];
if (isset($_GET['updated'])) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'capsulin_update.png',
        'text' => 'TICKET ACTUALIZADO EXITOSAMENTE'
    ];
}

/* Helper: label de problema */
function problemaLabel(string $p): string {
    return match ($p) {
        'cierre_dia'  => 'Cierre del día',
        'no_legado'   => 'Sin acceso a legado/legacy',
        'no_internet' => 'Sin internet',
        'no_checador' => 'No funciona checador',
        'rastreo'     => 'Rastreo de checada',
        'otro'        => 'Otro',
        default       => $p,
    };
}

/* Helper: label de prioridad */
function prioridadLabel(string $p): string {
    return match (strtolower($p)) {
        'alta'     => 'Alta',
        'media'    => 'Media',
        'baja'     => 'Baja',
        'critica', 'crítica' => 'Crítica',
        default    => ucfirst($p),
    };
}

/* KPIs del área */
$stmtKpi = $pdo->prepare("
    SELECT 
        SUM(estado = 'abierto')      AS abiertos,
        SUM(estado = 'en_proceso')   AS en_proceso,
        SUM(estado = 'resuelto')     AS resueltos,
        SUM(estado = 'cerrado')      AS cerrados,
        COUNT(*)                     AS total
    FROM tickets
    WHERE area = :area
");
$stmtKpi->execute([':area' => $userArea]);
$kpi = $stmtKpi->fetch() ?: [
    'abiertos'    => 0,
    'en_proceso'  => 0,
    'resueltos'   => 0,
    'cerrados'    => 0,
    'total'       => 0,
];

/* Tickets entrantes (abiertos, sin asignar) */
$stmtIncoming = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado, prioridad
    FROM tickets
    WHERE area = :area
      AND estado = 'abierto'
      AND (asignado_a IS NULL OR asignado_a = 0)
    ORDER BY fecha_envio ASC
");
$stmtIncoming->execute([':area' => $userArea]);
$incomingTickets = $stmtIncoming->fetchAll();

/* máximo id actual para iniciar el polling desde ahí */
$maxIncomingId = 0;
foreach ($incomingTickets as $t) {
    $tid = (int)$t['id'];
    if ($tid > $maxIncomingId) $maxIncomingId = $tid;
}

/* Mis tickets activos (solo abiertos y en proceso) */
$stmtMy = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado, prioridad
    FROM tickets
    WHERE area = :area
      AND asignado_a = :uid
      AND estado IN ('abierto','en_proceso')
    ORDER BY fecha_envio DESC
");
$stmtMy->execute([':area' => $userArea, ':uid' => $userId]);
$myTickets = $stmtMy->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Analista | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
</head>
<body class="user-body">

<?php if (!empty($alerts)): ?>
    <?php $alert = $alerts[0]; ?>
    <div id="eqf-alert-container">
        <div class="eqf-alert eqf-alert-<?php echo htmlspecialchars($alert['type']); ?>">
            <img
                class="eqf-alert-icon"
                src="/HelpDesk_EQF/assets/img/icons/<?php echo htmlspecialchars($alert['icon']); ?>"
                alt="alert icon"
            >
            <div class="eqf-alert-text">
                <?php echo htmlspecialchars($alert['text']); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- CONTENIDO PRINCIPAL -->
<main class="user-main">
    <section class="user-main-inner">

        <header class="user-main-header" id="analyst-dashboard">
            <div>
                <p class="login-brand">
                    <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
                </p>
                <p class="user-main-subtitle">
                    Panel de Analista – <?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </header>

        <section class="user-main-content">

            <!-- RESUMEN / KPIs -->
            <div class="user-info-card">
                <h2>Resumen Diario</h2>
                <p>Aquí podrás ver tu resumen diario.</p>
                <div class="kpi-analyst-row">
                    <div class="kpi-card kpi-green">
                        <span class="kpi-label">Abiertos</span>
                        <span class="kpi-value"><?php echo (int)$kpi['abiertos']; ?></span>
                    </div>
                    <div class="kpi-card kpi-blue">
                        <span class="kpi-label">En proceso</span>
                        <span class="kpi-value"><?php echo (int)$kpi['en_proceso']; ?></span>
                    </div>
                    <div class="kpi-card kpi-yellow">
                        <span class="kpi-label">Resueltos</span>
                        <span class="kpi-value"><?php echo (int)$kpi['resueltos']; ?></span>
                    </div>
                    <div class="kpi-card kpi-gray">
                        <span class="kpi-label">Total</span>
                        <span class="kpi-value"><?php echo (int)$kpi['total']; ?></span>
                    </div>
                </div>
            </div>

            <!-- TICKETS ENTRANTES -->
            <div id="incoming-section" class="user-info-card">
                <h3>Tickets entrantes (sin asignar)</h3>
                <?php if (empty($incomingTickets)): ?>
                    <p>No hay tickets entrantes sin asignar en este momento.</p>
                <?php else: ?>
                    <table id="incomingTable" class="data-table display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Problema</th>
                                <th>Prioridad</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incomingTickets as $t): ?>
                                <tr data-ticket-id="<?php echo (int)$t['id']; ?>">
                                    <td><?php echo (int)$t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="priority-pill priority-<?php echo htmlspecialchars(strtolower($t['prioridad'] ?? 'media'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(prioridadLabel($t['prioridad'] ?? 'media'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <button type="button"
                                                class="btn-assign-ticket"
                                                data-ticket-id="<?php echo (int)$t['id']; ?>">
                                            Asignar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- MIS TICKETS -->
            <div id="mytickets-section" class="user-info-card">
                <h3>Mis tickets</h3>
                <?php if (empty($myTickets)): ?>
                    <p>No tienes tickets asignados en este momento.</p>
                <?php else: ?>
                    <table id="myTicketsTable" class="data-table display analyst-tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Problema</th>
                                <th>Prioridad</th>
                                <th>Estatus</th>
                                <th>Chat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myTickets as $t): ?>
                                <tr data-ticket-id="<?php echo (int)$t['id']; ?>">
                                    <td>#<?php echo (int)$t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="priority-pill priority-<?php echo htmlspecialchars(strtolower($t['prioridad'] ?? 'media'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(prioridadLabel($t['prioridad'] ?? 'media'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select
                                            class="ticket-status-select status-<?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-ticket-id="<?php echo (int)$t['id']; ?>"
                                            data-prev="<?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <option value="abierto"    <?php if ($t['estado'] === 'abierto')    echo 'selected'; ?>>Abierto</option>
                                            <option value="en_proceso" <?php if ($t['estado'] === 'en_proceso') echo 'selected'; ?>>En proceso</option>
                                            <option value="resuelto"   <?php if ($t['estado'] === 'resuelto')   echo 'selected'; ?>>Resuelto</option>
                                            <option value="cerrado"    <?php if ($t['estado'] === 'cerrado')    echo 'selected'; ?>>Cerrado</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn-main-combined"
                                                onclick="openTicketChat(<?php echo (int)$t['id']; ?>, '<?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?>')">
                                            Ver chat
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </section>
    </section>
</main>

<!-- MODAL CHAT DE TICKET -->
<div class="modal-backdrop" id="ticket-chat-modal">
    <div class="modal-card ticket-chat-modal-card">
        <div class="modal-header">
            <h3 id="ticketChatTitle">Chat del ticket</h3>
            <button type="button" class="modal-close" onclick="closeTicketChat()">✕</button>
        </div>

        <div class="ticket-chat-body" id="ticketChatBody">
            <!-- Mensajes se agregan por JS -->
        </div>

        <form class="ticket-chat-form" onsubmit="sendTicketMessage(event)">
            <textarea id="ticketChatInput"
                      rows="2"
                      placeholder="Escribe tu mensaje..."
                      style="width:100%"></textarea>
            <div class="ticket-chat-input-row">
                <input type="file"
                       id="ticketChatFile"
                       name="adjunto"
                       class="ticket-chat-file"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv"
                       style="width:100%">
                <button type="submit" class="btn-login" style="min-width: 60px;">
                    Enviar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../../template/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

<script>
// ===============================
//  Variables globales para chat
// ===============================
const CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;

let currentTicketId = null;
let lastMessageId   = 0;
let chatPollTimer   = null;

// ===============================
//  Utilidades generales
// ===============================
function showTicketToast(text) {
    const toast = document.createElement('div');
    toast.className = 'eqf-toast-ticket';
    toast.textContent = text || '';
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===============================
//  Chat: abrir/cerrar
// ===============================
function openTicketChat(ticketId, tituloExtra) {
    currentTicketId = ticketId;
    lastMessageId   = 0;

    const titleEl = document.getElementById('ticketChatTitle');
    if (titleEl) {
        titleEl.textContent = 'Chat del ticket #' + ticketId + (tituloExtra ? ' – ' + tituloExtra : '');
    }

    const bodyEl = document.getElementById('ticketChatBody');
    if (bodyEl) bodyEl.innerHTML = '';

    const modal = document.getElementById('ticket-chat-modal');
    if (typeof openModal === 'function') {
        openModal('ticket-chat-modal');
    } else if (modal) {
        modal.classList.add('show');
    }

    // ✅ Cargar el historial transferido "bloqueado" (gris)
    fetch('/HelpDesk_EQF/modules/ticket/get_transfer_context.php?ticket_id=' + encodeURIComponent(currentTicketId))
      .then(r => r.json())
      .then(data => {
        if (data && data.ok) renderTransferBlock(data);
      })
      .catch(err => console.error('Error transfer context:', err));

    fetchMessages(true);

    if (chatPollTimer) clearInterval(chatPollTimer);
    chatPollTimer = setInterval(() => fetchMessages(false), 5000);
}

function closeTicketChat() {
    const modal = document.getElementById('ticket-chat-modal');
    if (typeof closeModal === 'function') {
        closeModal('ticket-chat-modal');
    } else if (modal) {
        modal.classList.remove('show');
    }

    if (chatPollTimer) {
        clearInterval(chatPollTimer);
        chatPollTimer = null;
    }

    const old = document.getElementById('transfer-block');
    if (old) old.remove();

    currentTicketId = null;
}

// ===============================
//  Chat: render de mensajes
// ===============================
function appendChatMessage(msg) {
    const bodyEl = document.getElementById('ticketChatBody');
    if (!bodyEl) return;

    const div = document.createElement('div');
    div.className = 'ticket-chat-message';

    const senderId = parseInt(msg.sender_id, 10);
    const isMine   = (senderId === CURRENT_USER_ID);
    div.classList.add(isMine ? 'mine' : 'other');

    if (msg.mensaje) {
        const textSpan = document.createElement('span');
        textSpan.textContent = msg.mensaje;
        div.appendChild(textSpan);
    }

    if (msg.file_url) {
        const fileWrapper = document.createElement('div');
        fileWrapper.style.marginTop = '6px';

        const url  = msg.file_url;
        const name = msg.file_name || 'Archivo adjunto';
        const type = msg.file_type || '';

        if (type && type.startsWith('image/')) {
            const imgLink = document.createElement('a');
            imgLink.href   = url;
            imgLink.target = '_blank';
            imgLink.rel    = 'noopener';

            const img = document.createElement('img');
            img.src = url;
            img.alt = name;
            img.className = 'ticket-chat-image';

            imgLink.appendChild(img);
            fileWrapper.appendChild(imgLink);
        } else {
            const link = document.createElement('a');
            link.href   = url;
            link.target = '_blank';
            link.rel    = 'noopener';
            link.textContent = '📎 ' + name;
            fileWrapper.appendChild(link);
        }

        div.appendChild(fileWrapper);
    }

    const meta = document.createElement('span');
    meta.className = 'ticket-chat-meta';
    const rol = msg.sender_role || '';
    const at  = msg.created_at || '';
    meta.textContent = (rol ? rol + ' · ' : '') + at;
    div.appendChild(meta);

    bodyEl.appendChild(div);
    bodyEl.scrollTop = bodyEl.scrollHeight;
}

// ===============================
//  Chat: transfer block (gris)
// ===============================
function escapeHtml(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function renderTransferBlock(payload){
  const bodyEl = document.getElementById('ticketChatBody');
  if (!bodyEl) return;

  const old = document.getElementById('transfer-block');
  if (old) old.remove();

  if (!payload || !payload.has_transfer) return;

  const t = payload.transfer || {};
  const msgs = Array.isArray(payload.messages) ? payload.messages : [];
  const files = Array.isArray(payload.files) ? payload.files : [];

  const wrap = document.createElement('div');
  wrap.id = 'transfer-block';
  wrap.className = 'ticket-transfer-block';

  const header = document.createElement('div');
  header.className = 'ticket-transfer-header';
  header.innerHTML = `
    <strong>Historial transferido</strong>
    <div class="ticket-transfer-sub">
      ${escapeHtml(t.from_area)} → ${escapeHtml(t.to_area)}
      ${t.created_at ? ' · ' + escapeHtml(t.created_at) : ''}
    </div>
    ${t.motivo ? `<div class="ticket-transfer-motivo">Motivo: ${escapeHtml(t.motivo)}</div>` : ''}
    <div class="ticket-transfer-note">Este historial es informativo (bloqueado).</div>
  `;
  wrap.appendChild(header);

  const list = document.createElement('div');
  list.className = 'ticket-transfer-messages';

  if (!msgs.length) {
    const empty = document.createElement('div');
    empty.className = 'ticket-transfer-empty';
    empty.textContent = 'Sin mensajes transferidos.';
    list.appendChild(empty);
  } else {
    msgs.forEach(m => {
      const item = document.createElement('div');
      item.className = 'ticket-transfer-msg';
      item.innerHTML = `
        <div class="ticket-transfer-msg-top">
          <span class="role">${escapeHtml(m.sender_role || '')}</span>
          <span class="name">${escapeHtml(m.sender_name || '')}</span>
          <span class="at">${escapeHtml(m.created_at || '')}</span>
        </div>
        <div class="ticket-transfer-msg-text">${escapeHtml(m.message || '')}</div>
      `;
      list.appendChild(item);
    });
  }

  if (files.length) {
    const fwrap = document.createElement('div');
    fwrap.className = 'ticket-transfer-files';
    fwrap.innerHTML = `<div class="ticket-transfer-files-title">Adjuntos transferidos</div>`;

    files.forEach(f => {
      const a = document.createElement('a');
      a.className = 'ticket-transfer-file';
      a.href = f.file_path;
      a.target = '_blank';
      a.rel = 'noopener';
      a.textContent = '📎 ' + (f.file_name || 'archivo');
      fwrap.appendChild(a);
    });

    list.appendChild(fwrap);
  }

  wrap.appendChild(list);
  bodyEl.prepend(wrap);
}

// ===============================
//  Chat: obtener mensajes
// ===============================
function fetchMessages(initial) {
    if (!currentTicketId) return;

    const url = '/HelpDesk_EQF/modules/ticket/get_messages.php'
              + '?ticket_id=' + encodeURIComponent(currentTicketId)
              + '&last_id=' + encodeURIComponent(lastMessageId);

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.ok || !Array.isArray(data.messages)) return;

            data.messages.forEach(m => {
                appendChatMessage(m);
                if (m.id > lastMessageId) lastMessageId = m.id;
            });
        })
        .catch(err => console.error('Error obteniendo mensajes:', err));
}

// ===============================
//  Chat: enviar mensaje
// ===============================
function sendTicketMessage(ev) {
    ev.preventDefault();
    if (!currentTicketId) return;

    const input     = document.getElementById('ticketChatInput');
    const fileInput = document.getElementById('ticketChatFile');
    if (!input) return;

    const texto = input.value.trim();
    const file  = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;

    if (!texto && !file) return;

    input.disabled = true;
    if (fileInput) fileInput.disabled = true;

    const formData = new FormData();
    formData.append('ticket_id', currentTicketId);
    formData.append('mensaje', texto);
    if (file) formData.append('adjunto', file);

    fetch('/HelpDesk_EQF/modules/ticket/send_messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        input.disabled = false;
        if (fileInput) {
            fileInput.disabled = false;
            fileInput.value = '';
        }

        if (!response.ok) {
            alert('No se pudo enviar el mensaje');
            return;
        }

        input.value = '';
        input.focus();
        fetchMessages(false);
    })
    .catch(err => {
        console.error('Error enviando mensaje:', err);
        input.disabled = false;
        if (fileInput) fileInput.disabled = false;
        alert('Error al enviar el mensaje');
    });
}

// ===============================
//  INIT: DataTables + asignación + estatus + polling
// ===============================
document.addEventListener('DOMContentLoaded', function () {

    function initOrGetTable(selector, options) {
        if (!window.jQuery || !$.fn.dataTable || !$(selector).length) return null;
        if ($.fn.dataTable.isDataTable(selector)) return $(selector).DataTable();
        return $(selector).DataTable(options || {});
    }

    const incomingDT = initOrGetTable('#incomingTable', {
        pageLength: 5,
        order: [[1, 'asc']]
    });

    initOrGetTable('#myTicketsTable', {
        pageLength: 5,
        order: [[1, 'desc']]
    });

    // ---------- CAMBIO DE ESTATUS ----------
    document.addEventListener('change', function (e) {
        const select = e.target.closest('.ticket-status-select');
        if (!select) return;

        const ticketId    = select.dataset.ticketId;
        const nuevoEstado = select.value;
        const prevEstado  = select.dataset.prev || 'abierto';
        const rowEl       = select.closest('tr');

        if (!ticketId) return;

        fetch('/HelpDesk_EQF/modules/ticket/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ticket_id=' + encodeURIComponent(ticketId) +
                  '&estado='   + encodeURIComponent(nuevoEstado)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                alert(data.msg || 'Error al actualizar el estatus.');
                select.value = prevEstado;
                return;
            }

            select.dataset.prev = nuevoEstado;
            select.className = 'ticket-status-select status-' + nuevoEstado;

            if (nuevoEstado === 'resuelto' || nuevoEstado === 'cerrado') {
                if (rowEl) {
                    if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable('#myTicketsTable')) {
                        const dtMy = $('#myTicketsTable').DataTable();
                        dtMy.row($(rowEl)).remove().draw(false);
                    } else {
                        rowEl.remove();
                    }
                }
            }
        })
        .catch(err => {
            console.error('Error update_status:', err);
            alert('Error interno al actualizar el estatus.');
            select.value = prevEstado;
        });
    });

    // ---------- ASIGNAR TICKET ----------
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-assign-ticket');
        if (!btn) return;

        if (btn.dataset.loading === '1') return;
        btn.dataset.loading = '1';

        const rowEl    = btn.closest('tr');
        const ticketId = btn.dataset.ticketId || (rowEl && rowEl.getAttribute('data-ticket-id'));
        if (!ticketId || !rowEl) {
            btn.dataset.loading = '0';
            return;
        }

        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Asignando...';

        fetch('/HelpDesk_EQF/modules/ticket/assign.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ticket_id=' + encodeURIComponent(ticketId)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                alert(data.msg || 'No se pudo asignar el ticket.');
                btn.disabled = false;
                btn.textContent = originalText;
                btn.dataset.loading = '0';
                return;
            }

            // Quitar de "Tickets entrantes"
            if (incomingDT) incomingDT.row($(rowEl)).remove().draw(false);
            else rowEl.remove();

            showTicketToast('Ticket #' + ticketId + ' asignado a ti.');
            btn.dataset.loading = '0';
        })
        .catch(err => {
            console.error(err);
            alert('Error al asignar el ticket.');
            btn.disabled = false;
            btn.textContent = originalText;
            btn.dataset.loading = '0';
        });
    });

    // ---------- NOTIFICACIONES NUEVOS TICKETS (polling) ----------
    let lastTicketId = <?php echo (int)$maxIncomingId; ?>;

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function showDesktopNotification(ticket) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        new Notification('Nuevo ticket entrante (' + ticket.id + ')', {
            body: ticket.problema || '',
            icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png'
        });
    }

    function renderPriorityPill(priorityRaw) {
        const p = (priorityRaw || 'media').toLowerCase();
        let label = 'Media';
        if (p === 'alta') label = 'Alta';
        else if (p === 'baja') label = 'Baja';
        else if (p === 'critica' || p === 'crítica') label = 'Crítica';
        return `<span class="priority-pill priority-${p}">${label}</span>`;
    }

    function addIncomingTicketRow(ticket) {
        if (!ticket || !ticket.id) return;

        const prioridadHtml = renderPriorityPill(ticket.prioridad);

        const rowData = [
            ticket.id,
            ticket.fecha_envio || ticket.fecha || '',
            ticket.usuario || ticket.nombre || '',
            ticket.problema || '',
            prioridadHtml,
            ticket.descripcion || '',
            `<button type="button" class="btn-assign-ticket" data-ticket-id="${ticket.id}">Asignar</button>`
        ];

        if (incomingDT) {
            incomingDT.row.add(rowData).draw(false);
        } else {
            const tbody = document.querySelector('#incomingTable tbody');
            if (!tbody) return;

            const tr = document.createElement('tr');
            tr.setAttribute('data-ticket-id', ticket.id);
            tr.innerHTML = `
                <td>${rowData[0]}</td>
                <td>${rowData[1]}</td>
                <td>${rowData[2]}</td>
                <td>${rowData[3]}</td>
                <td>${prioridadHtml}</td>
                <td>${rowData[5]}</td>
                <td>${rowData[6]}</td>
            `;
            tbody.prepend(tr);
        }
    }
function pollStaffNotifications() {
    fetch('/HelpDesk_EQF/modules/ticket/check_staff_notifications.php')
        .then(r => r.json())
        .then(data => {
            if (!data.ok || !data.has) return;
            if (!Array.isArray(data.notifications)) return;

            data.notifications.forEach(n => {
                const msg   = n.body  || 'Tienes una notificación nueva.';
                const title = n.title || 'HelpDesk EQF';

                showTicketToast(msg);

                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(title, {
                        body: msg,
                        icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png'
                    });
                }
            });
        })
        .catch(err => {
            console.error('Error consultando notificaciones staff:', err);
        });
}

    function pollNewTickets() {
        fetch('/HelpDesk_EQF/modules/ticket/check_new.php?last_id=' + lastTicketId)
            .then(r => r.json())
            .then(data => {
                if (!data || !data.new) return;

                lastTicketId = data.id;

                const msg = 'Nuevo ticket #' + data.id + ' – ' + (data.problema || '');
                showTicketToast(msg);
                showDesktopNotification(data);

                addIncomingTicketRow(data);
            })
            .catch(err => console.error('Error comprobando nuevos tickets:', err));
    }

    // ✅ Inicia polling
    pollNewTickets();
    setInterval(pollNewTickets, 10000);
});
</script>

</body>
</html>
