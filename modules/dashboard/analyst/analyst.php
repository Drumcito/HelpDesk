<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

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

/*imagenes de perfil*/

$area  = strtolower(trim($userArea));
$email = strtolower(trim($userEmail));

$profileImg = match (true) {

    // TI
    $area === 'TI'
    || str_starts_with($email, 'ti@')
    || str_starts_with($email, 'ti1@')
    || str_starts_with($email, 'ti2@')
    || str_starts_with($email, 'ti3@')
    || str_starts_with($email, 'ti4@')
    || str_starts_with($email, 'ti5@')
    || str_starts_with($email, 'ti6@') =>
        '/HelpDesk_EQF/assets/img/pp/pp_ti.jpg',

    // SAP
    $area === 'SAP' 
    || str_starts_with($email, 'administracion@')
    || str_starts_with($email, 'administracion1@')
    || str_starts_with($email, 'administracion2@')
    || str_starts_with($email, 'administracion3@')
    || str_starts_with($email, 'administracion4@') =>
        '/HelpDesk_EQF/assets/img/pp/pp_sap.jpg',

    // MKT → gerente de mercadotecnia + mkt + mkt1-5
    $area === 'MKT'
    || str_starts_with($email, 'gerente.mercadotecnia@')
    || str_starts_with($email, 'mkt@')
    || str_starts_with($email, 'mkt1@')
    || str_starts_with($email, 'mkt2@')
    || str_starts_with($email, 'mkt3@')
    || str_starts_with($email, 'mkt4@')
    || str_starts_with($email, 'mkt5@') =>
        '/HelpDesk_EQF/assets/img/pp/pp_mkt.jpg',

    // DISEÑO
    $area === 'diseno' || $area === 'MKT'
    || str_starts_with($email, 'diseno@')
    || str_starts_with($email, 'diseno1@')
    || str_starts_with($email, 'diseno2@') =>
        '/HelpDesk_EQF/assets/img/pp/pp_diseno.jpg',

    // DEFAULT
    default =>
        '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg',
};



// -------- ALERTAS (ej. al actualizar ticket) ----------
$alerts = [];
if (isset($_GET['updated'])) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'capsulin_update.png',
        'text' => 'TICKET ACTUALIZADO EXITOSAMENTE'
    ];
}

// -------- HELPER: etiqueta bonita del problema ----------
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

// -------- KPIs del área ----------
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

// -------- Tickets entrantes (sin asignar, abiertos) ----------
$stmtIncoming = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado
    FROM tickets
    WHERE area = :area
      AND estado = 'abierto'
      AND (asignado_a IS NULL OR asignado_a = 0)
    ORDER BY fecha_envio ASC
");
$stmtIncoming->execute([':area' => $userArea]);
$incomingTickets = $stmtIncoming->fetchAll();

// -------- Mis tickets activos (asignados a mí y NO cerrados) ----------
$stmtMy = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado
    FROM tickets
    WHERE area = :area
      AND asignado_a = :uid
      AND estado IN ('abierto','en_proceso')
    ORDER BY fecha_envio DESC
");
$stmtMy->execute([':area' => $userArea, ':uid' => $userId]);
$myTickets = $stmtMy->fetchAll();

// -------- Historial (asignados a mí y ya resueltos/cerrados) ----------
$stmtHistory = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado, fecha_resolucion
    FROM tickets
    WHERE area = :area
      AND asignado_a = :uid
      AND estado IN ('resuelto','cerrado')
    ORDER BY fecha_resolucion DESC, fecha_envio DESC
");
$stmtHistory->execute([':area' => $userArea, ':uid' => $userId]);
$historyTickets = $stmtHistory->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Analista | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">
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

    <!-- SIDEBAR ANALISTA -->
    <aside class="user-sidebar">
        <div class="user-sidebar-profile">
            <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Foto de perfil"
                 class="user-sidebar-avatar">

            <div class="user-sidebar-avatar-circle">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
            <div class="user-sidebar-info">
                <p class="user-sidebar-name">
                    <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <p class="user-sidebar-email">
                    <?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <p class="user-sidebar-email" style="opacity:0.8;">
                    Área: <?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </div>

        <nav class="user-sidebar-menu">
            <button type="button" class="user-menu-item" onclick="scrollToSection('analyst-dashboard')">
                <span class="user-menu-icon">📊</span>
                <span>Dashboard</span>
            </button>

            <button type="button" class="user-menu-item" onclick="scrollToSection('incoming-section')">
                <span class="user-menu-icon">📥</span>
                <span>Tickets entrantes</span>
            </button>

            <button type="button" class="user-menu-item" onclick="scrollToSection('mytickets-section')">
                <span class="user-menu-icon">🧑‍💻</span>
                <span>Mis tickets</span>
            </button>

            <button type="button" class="user-menu-item" onclick="scrollToSection('history-section')">
                <span class="user-menu-icon">📚</span>
                <span>Historial</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/auth/logout.php'">
                <span class="user-menu-icon">🚪</span>
                <span>Cerrar sesión</span>
            </button>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="user-main">
        <section class="user-main-inner">

            <!-- HEADER -->
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
                <!-- Resumen / KPIs -->
                <div class="user-info-card">
                    <h2>Resumen del área</h2>
                    <p>
                        Aquí puedes gestionar los tickets asignados a tu área, ver tus tickets activos y revisar tu historial de atención.
                    </p>
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
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incomingTickets as $t): ?>
                                    <tr>
                                        <td><?php echo (int)$t['id']; ?></td>
                                        <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <button type="button"
                                                    class="btn-assign-ticket">
                                                    Asignar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- MIS TICKETS ACTIVOS -->
                <div id="mytickets-section" class="user-info-card">
                    <h3>Mis tickets activos</h3>
                    <?php if (empty($myTickets)): ?>
                        <p>No tienes tickets activos asignados en este momento.</p>
                    <?php else: ?>
                        <table id="myTicketsTable" class="data-table display">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Problema</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
<tbody>
<?php foreach ($myTickets as $t): ?>
    <tr>
        <td><?php echo (int)$t['id']; ?></td>
        <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <button type="button"
                    class="btn-login"
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

                <!-- HISTORIAL -->
                <div id="history-section" class="user-info-card">
                    <h3>Historial de tickets atendidos</h3>
                    <?php if (empty($historyTickets)): ?>
                        <p>Todavía no tienes tickets resueltos o cerrados.</p>
                    <?php else: ?>
                        <table id="historyTable" class="data-table display">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha envío</th>
                                    <th>Fecha resolución</th>
                                    <th>Usuario</th>
                                    <th>Problema</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyTickets as $t): ?>
                                    <tr>
                                        <td><?php echo (int)$t['id']; ?></td>
                                        <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($t['fecha_resolucion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
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
        </form>
    </div>
</div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="/HelpDesk_EQF/assets/js/script.js"></script>
    <script>
        function scrollToSection(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        $(document).ready(function () {
            $('#incomingTable').DataTable({
                pageLength: 5,
                order: [[1, 'asc']]
            });

            $('#myTicketsTable').DataTable({
                pageLength: 5,
                order: [[1, 'desc']]
            });

            $('#historyTable').DataTable({
                pageLength: 5,
                order: [[1, 'desc']]
            });
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    // Delegación para botones "Asignar"
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-assign-ticket');
        if (!btn) return;

        const row = btn.closest('tr');
        const ticketId = row ? row.getAttribute('data-ticket-id') : null;
        if (!ticketId) return;

        btn.disabled = true;
        btn.textContent = 'Asignando...';

        fetch('/HelpDesk_EQF/modules/ticket/assign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'ticket_id=' + encodeURIComponent(ticketId)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                alert(data.msg || 'No se pudo asignar el ticket.');
                btn.disabled = false;
                btn.textContent = 'Asignar';
                return;
            }

            // 1) Quitar la fila de "entrantes"
            row.parentNode.removeChild(row);

            // 2) Mostrar aviso visual
            showTicketToast('Ticket #' + ticketId + ' asignado a ti.');

            // 3) (Opcional) Recargar tabla de "Mis tickets activos" via fetch o simplemente:
            location.reload(); // si quieres mantenerlo simple por ahora
        })
        .catch(err => {
            console.error(err);
            alert('Error al asignar el ticket.');
            btn.disabled = false;
            btn.textContent = 'Asignar';
        });
    });

    // Pequeña notificación dentro de la página
    function showTicketToast(text) {
        const toast = document.createElement('div');
        toast.className = 'eqf-toast-ticket';
        toast.textContent = text;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let lastTicketId = 0;

    // Pedimos permiso para notificaciones del navegador
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function showInPageToast(msg) {
        const toast = document.createElement('div');
        toast.className = 'eqf-toast-ticket';
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function showDesktopNotification(ticket) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        new Notification('Nuevo ticket entrante (' + ticket.id + ')', {
            body: ticket.problema,
            icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png' // si tienes uno
        });
    }

    function pollNewTickets() {
        fetch('/HelpDesk_EQF/modules/ticket/check_new.php?last_id=' + lastTicketId)
            .then(r => r.json())
            .then(data => {
                if (!data.new) return;

                lastTicketId = data.id;

                const msg = 'Nuevo ticket #' + data.id + ' – ' + data.problema;
                showInPageToast(msg);
                showDesktopNotification(data);

                // Opcional: recargar tabla de entrantes automáticamente
                // location.reload();
            })
            .catch(err => console.error('Error comprobando nuevos tickets:', err));
    }

    // Llamada inicial para pillar el último id actual (para no notificar lo viejo)
    fetch('/HelpDesk_EQF/modules/ticket/check_new.php?last_id=0')
        .then(r => r.json())
        .then(data => {
            if (data.new) {
                lastTicketId = data.id;
            }
        })
        .catch(() => {});

    // Revisar cada 10 segundos
    setInterval(pollNewTickets, 10000);
});
</script>
<script>
let currentTicketId = null;
let lastMessageId   = 0;
let chatPollTimer   = null;

// Abre el modal de chat para un ticket
function openTicketChat(ticketId, tituloExtra) {
    currentTicketId = ticketId;
    lastMessageId   = 0;

    const titleEl = document.getElementById('ticketChatTitle');
    if (titleEl) {
        titleEl.textContent = 'Chat del ticket #' + ticketId + (tituloExtra ? ' – ' + tituloExtra : '');
    }

    const bodyEl = document.getElementById('ticketChatBody');
    if (bodyEl) {
        bodyEl.innerHTML = ''; // limpiamos mensajes previos
    }

    if (typeof openModal === 'function') {
        openModal('ticket-chat-modal');
    } else {
        // por si acaso
        document.getElementById('ticket-chat-modal')?.classList.add('show');
    }

    // Cargar mensajes iniciales
    fetchMessages(true);

    // Iniciar polling
    if (chatPollTimer) clearInterval(chatPollTimer);
    chatPollTimer = setInterval(() => fetchMessages(false), 5000);
}

function closeTicketChat() {
    if (typeof closeModal === 'function') {
        closeModal('ticket-chat-modal');
    } else {
        document.getElementById('ticket-chat-modal')?.classList.remove('show');
    }
    if (chatPollTimer) {
        clearInterval(chatPollTimer);
        chatPollTimer = null;
    }
    currentTicketId = null;
}

// Pinta un mensaje en el body
function appendChatMessage(msg) {
    const bodyEl = document.getElementById('ticketChatBody');
    if (!bodyEl) return;

    const div = document.createElement('div');
    div.className = 'ticket-chat-message';

    const myId = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
    const isMine = (parseInt(msg.sender_id, 10) === myId);

    div.classList.add(isMine ? 'mine' : 'other');

    // Texto del mensaje
    if (msg.mensaje) {
        const textSpan = document.createElement('span');
        textSpan.textContent = msg.mensaje;
        div.appendChild(textSpan);
    }

    // Si hay archivo adjunto, mostramos link (y preview si es imagen)
    if (msg.file_url) {
        const fileWrapper = document.createElement('div');
        fileWrapper.style.marginTop = '6px';

        const url  = msg.file_url;
        const name = msg.file_name || 'Archivo adjunto';
        const type = msg.file_type || '';

        // Si es imagen, mostramos miniatura clickeable
        if (type.startsWith('image/')) {
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
            // Para otros archivos, solo un link
            const link = document.createElement('a');
            link.href   = url;
            link.target = '_blank';
            link.rel    = 'noopener';
            link.textContent = '📎 ' + name;
            fileWrapper.appendChild(link);
        }

        div.appendChild(fileWrapper);
    }

    // Meta (rol + fecha)
    const meta = document.createElement('span');
    meta.className = 'ticket-chat-meta';

    const rol = msg.sender_role || '';
    const at  = msg.created_at || '';
    meta.textContent = (rol ? rol + ' · ' : '') + at;
    div.appendChild(meta);

    bodyEl.appendChild(div);
    bodyEl.scrollTop = bodyEl.scrollHeight;
}



// Obtener mensajes nuevos
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
                if (m.id > lastMessageId) {
                    lastMessageId = m.id;
                }
            });
        })
        .catch(err => console.error('Error obteniendo mensajes:', err));
}

// Enviar mensaje
function sendTicketMessage(ev) {
    ev.preventDefault();
    if (!currentTicketId) return;

    const input = document.getElementById('ticketChatInput');
    const fileInput = document.getElementById('ticketChatFile');
    if (!input) return;

    const texto = input.value.trim();
    const file  = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;

    if (!texto && !file) {
        return; // no mandes nada vacío
    }

    input.disabled = true;
    if (fileInput) fileInput.disabled = true;

    const formData = new FormData();
    formData.append('ticket_id', currentTicketId);
    formData.append('mensaje', texto);
    if (file) {
        formData.append('adjunto', file);
    }

    fetch('/HelpDesk_EQF/modules/ticket/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        input.disabled = false;
        if (fileInput) {
            fileInput.disabled = false;
            fileInput.value = '';
        }

        if (!data.ok) {
            alert(data.msg || 'No se pudo enviar el mensaje');
            return;
        }

        input.value = '';
        input.focus();

        // fuerza refresh para ver mensaje + adjunto
        fetchMessages(false);
    })
    .catch(err => {
        console.error('Error enviando mensaje:', err);
        input.disabled = false;
        if (fileInput) fileInput.disabled = false;
        alert('Error al enviar el mensaje');
    });
}

</script>
<?php include __DIR__ . '/../../../template/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251129a"></script>

</body>
</html>
