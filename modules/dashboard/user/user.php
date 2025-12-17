<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

/* ALERTAS */
$alerts = [];
if (isset($_GET['created'])) {
    $alerts[] = [
        'type' => 'success',
        'icon' => 'capsulin_add.png',
        'text' => 'TICKET REGISTRADO EXITOSAMENTE, EN BREVE TE ATENDEREMOS'
    ];
}

if (isset($_GET['deleted'])) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => 'capsulin_delete.png',
        'text' => 'OCURRIÓ UN ERROR AL REGISTRAR EL TICKET'
    ];
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();

$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';
$userSap   = $_SESSION['number_sap'] ?? '';

$profileImg = ($userArea === 'Sucursal')
    ? '/HelpDesk_EQF/assets/img/pp/pp_sucursal.jpg'
    : '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg';

/* Tickets abiertos / en proceso del usuario (para el resumen) */
$stmtOpen = $pdo->prepare("
    SELECT id, problema, fecha_envio, estado
    FROM tickets
    WHERE user_id = :uid
      AND estado IN ('abierto','en_proceso')
    ORDER BY fecha_envio DESC
    LIMIT 5
");
$stmtOpen->execute([':uid' => $userId]);
$openTickets = $stmtOpen->fetchAll();

function problemaLabel(string $p): string {
    return match ($p) {
        'cierre_dia'   => 'Cierre del día',
        'no_legado'    => 'Sin acceso a legado/legacy',
        'no_internet'  => 'Sin internet',
        'no_checador'  => 'No funciona checador',
        'rastreo'      => 'Rastreo de checada',
        'otro'         => 'Otro',
        default        => $p,
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>User | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
    <style>
    .user-body #eqf-alert-container {
        position: fixed;
        top: 24px;
        left: 50%;
        transform: translateX(-50%);
        width: 360px;
        max-width: calc(100% - 40px);
        padding: 16px 24px;
        border-radius: 18px;
        display: flex;
        flex-direction: row;
        align-items: center;
        background: none;
        justify-content: center;
        gap: 12px;
        height: auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        z-index: 9999;
    }
    .user-body #eqf-alert-container .eqf-alert-icon img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        display: block;
        margin: 0;
    }
    .user-body #eqf-alert-container .eqf-alert-text {
        font-size: 14px;
        font-weight: 700;
        text-align: center;
    }
    </style>
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
            <header class="user-main-header">
                <div>
                    <p class="login-brand">
                        <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
                    </p>
                    <p class="user-main-subtitle">
                        Bienvenid@, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>.
                    </p>
                </div>
            </header>

            <section class="user-main-content">
                <div class="user-info-card">
                    <h2>Resumen</h2>
                    <p>
                        Desde aquí puedes crear tickets, consultar el historial de los que has levantado
                        y acceder a documentos importantes para la operación de tu sucursal o área.
                    </p>
                </div>

                <div class="button">
                    <button type="button" class="btn-primary user-main-cta" onclick="openTicketModal()">
                        Crear nuevo ticket
                    </button>
                </div>

                <div id="tickets-section" class="user-tickets-placeholder">
                    <h3>Tus tickets</h3>

                    <?php if (empty($openTickets)): ?>
                        <p>No tienes tickets abiertos por el momento.</p>
                    <?php else: ?>
                        <ul class="user-tickets-list">
                            <?php foreach ($openTickets as $t): ?>
                                <li class="user-ticket-item">
                                    <div class="user-ticket-info">
                                        <div>
                                            <strong>#<?php echo (int)$t['id']; ?></strong>
                                            — <?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>

                                        <small>
                                            <?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?>
                                            · <?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?>
                                        </small>
                                    </div>

                                    <div class="user-ticket-actions">
                                        <button type="button"
                                                class="btn-main-combined"
                                                style="padding:6px 14px; font-size:0.75rem;"
                                                onclick="openTicketChat(
                                                    <?php echo (int)$t['id']; ?>,
                                                    '<?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?>'
                                                )">
                                            Ver chat
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>

 
    <!-- MODAL CREAR TICKET -->
    <div class="user-modal-backdrop" id="ticketModal">
        <div class="user-modal">
            <header class="user-modal-header">
                <h2>Crear ticket</h2>
                <button type="button" class="user-modal-close" onclick="closeTicketModal()">×</button>
            </header>

            <p class="user-modal-description">
                Completa la información para registrar tu incidencia en el HelpDesk EQF.
            </p>

            <form method="POST"
                  action="/HelpDesk_EQF/modules/ticket/create.php"
                  enctype="multipart/form-data"
                  class="user-modal-form"
                  id="ticketForm">

                <input type="hidden" name="user_id"
                       value="<?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="area"
                       value="<?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="email"
                       value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>">
<!--
                <div class="user-modal-grid">
                    <div class="form-group">
                        <label># SAP</label>
                        <input type="text"
                               id="sapDisplay"
                               value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>"
                               disabled>  
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text"
                               id="nombreDisplay"
                               value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
                               disabled>
                    </div>
                            -->
 <div class="form-group form-row">
                    <div class="field">
                        <label># SAP</label>
                        <input type="text"
                               id="sapDisplay"
                               value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>"
                               disabled>  
                    </div>
                    <div class="field">
                        <label>Nombre</label>
                        <input type="text"
                               id="nombreDisplay"
                               value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
                               disabled>  
                    </div>

                    <div class="form-group">
                        <label>Correo</label>
                        <input type="text"
                               id="emailDisplay"
                               value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>"
                               disabled>
                    </div>
                </div>

                <input type="hidden" name="sap" id="sapValue"
                       value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="nombre" id="nombreValue"
                       value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group checkbox-container">
                    <input type="checkbox" id="noJefe">
                    <label for="noJefe">No soy jefe de sucursal</label>
                </div>

                <div class="form-group">
                    <label>Área de soporte</label>
                    <select id="areaSoporte" name="area_soporte" required>
                        <option value="">Selecciona un área</option>
                        <option value="TI">TI</option>
                        <option value="SAP">SAP</option>
                        <option value="MKT">MKT</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Problema</label>
                    <select name="problema" id="problemaSelect" required>
                        <option value="">Selecciona primero un área</option>
                    </select>
                </div>
<!--
                <div class="form-group">
                    <label>Prioridad</label>
                    <input type="text"
                           id="prioridadDisplay"
                           value="Media"
                           disabled>
                </div>
                            -->
                <input type="hidden" name="prioridad" id="prioridadValue" value="media">

                <div class="form-group form-group-full">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"
                              placeholder="Describe el problema "
                              required></textarea>
                </div>

                    <div class="form-group form-group-full" id="adjuntoContainer">
                    <label>Adjuntar archivos</label>
                    <input type="file"
                           name="adjuntos[]"
                           multiple
                           accept=".pdf,.jpg,.jpeg,.webp,.docx,.png,.xls,.xlsx,.csv">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeTicketModal()">Cancelar</button>
                    <button type="submit" class="btn-login">Enviar ticket</button>
                </div>
            </form>
        </div>
    </div>

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

    <script>
    function openTicketModal() {
        document.getElementById('ticketModal').classList.add('is-visible');
    }

    function closeTicketModal() {
        document.getElementById('ticketModal').classList.remove('is-visible');
    }

    function toggleChatbot() {
        const c = document.getElementById('chatbot');
        c.classList.toggle('is-open');
    }

    function scrollToTickets() {
        const section = document.getElementById('tickets-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    </script>

    <script>
    const CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
    </script>

    <script>
    let currentTicketId = null;
    let lastMessageId   = 0;
    let chatPollTimer   = null;

    function openTicketChat(ticketId, tituloExtra) {
        currentTicketId = ticketId;
        lastMessageId   = 0;

        const titleEl = document.getElementById('ticketChatTitle');
        if (titleEl) {
            titleEl.textContent = 'Chat del ticket #' + ticketId + (tituloExtra ? ' – ' + tituloExtra : '');
        }

        const bodyEl = document.getElementById('ticketChatBody');
        if (bodyEl) {
            bodyEl.innerHTML = '';
        }

        const modal = document.getElementById('ticket-chat-modal');

        if (typeof openModal === 'function') {
            openModal('ticket-chat-modal');
        } else if (modal) {
            modal.classList.add('show');
        }

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
        currentTicketId = null;
    }

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

    function sendTicketMessage(ev) {
        ev.preventDefault();
        if (!currentTicketId) return;

        const input     = document.getElementById('ticketChatInput');
        const fileInput = document.getElementById('ticketChatFile');
        if (!input) return;

        const texto = input.value.trim();
        const file  = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;

        if (!texto && !file) {
            return;
        }

        input.disabled = true;
        if (fileInput) fileInput.disabled = true;

        const formData = new FormData();
        formData.append('ticket_id', currentTicketId);
        formData.append('mensaje', texto);
        if (file) {
            formData.append('adjunto', file);
        }

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
    </script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    function showUserToast(msg) {
        const toast = document.createElement('div');
        toast.className = 'eqf-toast-ticket';
        toast.textContent = msg;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function showDesktopNotification(msg) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        new Notification('HelpDesk EQF', {
            body: msg,
            icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png'
        });
    }

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function pollUserNotifications() {
        fetch('/HelpDesk_EQF/modules/ticket/check_user_notifications.php')
            .then(r => r.json())
            .then(data => {
                if (!data.ok || !data.has) return;
                if (!Array.isArray(data.notifications)) return;

                data.notifications.forEach(n => {
                    const msg = n.mensaje || 'Tienes una actualización de tu ticket.';
                    showUserToast(msg);
                    showDesktopNotification(msg);
                });
            })
            .catch(err => {
                console.error('Error consultando notificaciones de usuario:', err);
            });
    }

    setInterval(pollUserNotifications, 10000);
});

</script>

</body>
</html>
