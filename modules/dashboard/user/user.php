<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';



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


    // Tickets abiertos / en proceso del usuario (para el resumen)
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
    /* Ajuste específico para las alertas en la vista de usuario */
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
        height: auto;              /* importante: que NO sea 100% alto */
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
    <?php $alert = $alerts[0]; // usamos la primera alerta ?>
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



    <!-- SIDEBAR IZQUIERDO -->
    <aside class="user-sidebar">
        <div class="user-sidebar-profile">
            <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Foto de perfil"
                 class="user-sidebar-avatar">

            <div class="user-sidebar-info">
                <p class="user-sidebar-name">
                    <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <p class="user-sidebar-email">
                    <?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </div>

        <nav class="user-sidebar-menu">
            <button type="button" class="user-menu-item" onclick="openTicketModal()">
                <span class="user-menu-icon">➕</span>
                <span>Crear ticket</span>
            </button>

            <button type="button" class="user-menu-item" 
                    onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/tickets.php'">
                <span class="user-menu-icon">📄</span>
                <span>Tickets</span>
            </button>
            <button type="button" class="user-menu-item" 
                    onclick="window.location.href='/HelpDesk_EQF/modules/docs/important.php'">
                <span class="user-menu-icon">📎</span>
                <span>Documentos importantes</span>
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
                <!-- Sección de tickets como ancla -->
                <div id="tickets-section" class="user-tickets-placeholder">
  <h3>Tus tickets</h3>

    <?php if (empty($openTickets)): ?>
        <p>No tienes tickets abiertos por el momento. </p>
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
                        class="btn-login"
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

        <button type="button" class="btn-secondary user-main-cta"
                onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/tickets.php'">
            Ver todos los tickets
        </button>
    <?php endif; ?>
                </div>
            </section>

        </section>

    </main>

    <!-- CHATBOT FLOtANTE (OCULTO HASTA CLIC) -->
    <div class="chatbot-container" id="chatbot">
        <button type="button" class="chatbot-toggle" onclick="toggleChatbot()">
            💬
        </button>

        <div class="chatbot-panel">
            <header class="chatbot-header">
                <div class="chatbot-header-info">
                    <span class="chatbot-avatar">🤖</span>
                    <div>
                        <p class="chatbot-title">CAPSULia</p>
                        <p class="chatbot-subtitle">Asistente HelpDesk EQF</p>
                    </div>
                </div>
                <button type="button" class="chatbot-close" onclick="toggleChatbot()">×</button>
            </header>

            <div class="chatbot-body">
                <div class="chatbot-message chatbot-message-bot">
                    <p>
                        ¡Hola! Soy <strong>CAPSULia</strong>.  
                        Cuéntame tu problema y te ayudaré a resolverlo o a crear un ticket.
                    </p>
                </div>

                <div class="chatbot-quick-actions">
                    <button type="button" class="chatbot-chip">Cierre del día</button>
                    <button type="button" class="chatbot-chip">No tengo acceso</button>
                    <button type="button" class="chatbot-chip">No tengo internet</button>
                    <button type="button" class="chatbot-chip chatbot-chip-outline">Otro</button>
                </div>
            </div>

            <form class="chatbot-input-row" onsubmit="event.preventDefault();">
                <button type="button" class="chatbot-attach">📎</button>
                <input type="text" class="chatbot-input" placeholder="Escribe tu mensaje...">
                <button type="submit" class="chatbot-send">Enviar</button>
            </form>
        </div>
    </div>
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

            <!-- USER_ID por si lo necesitas en back -->
            <input type="hidden" name="user_id"
                   value="<?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="area"
                   value="<?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="email"
                   value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- DATOS DEL USUARIO (VISUALES) -->
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

                <div class="form-group">
                    <label>Correo</label>
                    <input type="text"
                            id="emailDisplay"
                           value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>"
                           disabled>
                </div>
            </div>

            <!-- CAMPOS REALES QUE SE ENVIAN AL BACK -->
            <input type="hidden" name="sap" id="sapValue"
                   value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="nombre" id="nombreValue"
                   value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">

                       <!-- CHECKBOX NO SOY JEFE -->
            <div class="form-group checkbox-container">
                <input type="checkbox" id="noJefe">
                <label for="noJefe">No soy jefe de sucursal</label>
            </div>

            <!-- ÁREA DE SOPORTE -->
            <div class="form-group">
                <label>Área de soporte</label>
                <select id="areaSoporte" name="area_soporte" required>
                    <option value="">Selecciona un área</option>
                    <option value="TI">TI</option>
                    <option value="SAP">SAP</option>
                    <option value="MKT">MKT</option>
                </select>
            </div>

            <!-- PROBLEMA (se llenará dinámicamente según área) -->
            <div class="form-group">
                <label>Problema</label>
                <select name="problema" id="problemaSelect" required>
                    <option value="">Selecciona primero un área de soporte</option>
                </select>
            </div>
            <!-- PRIORIDAD (automática según problema) -->
<div class="form-group">
    <label>Prioridad</label>
    <input type="text"
           id="prioridadDisplay"
           value="Media"
           disabled>
</div>

<input type="hidden" name="prioridad" id="prioridadValue" value="media">


            <!-- DESCRIPCIÓN -->
            <div class="form-group form-group-full">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3"
                          placeholder="Describe el problema "
                          required></textarea>
            </div>

            <!-- ADJUNTOS (solo si 'otro') -->
            <div class="form-group form-group-full" id="adjuntoContainer" style="display:none;">
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
                      placeholder="Escribe tu mensaje..."></textarea>
            <button type="submit" class="btn-login" style="min-width: 120px;">
                Enviar
            </button>
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

    const textSpan = document.createElement('span');
    textSpan.textContent = msg.mensaje;
    div.appendChild(textSpan);

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
    if (!input) return;

    const texto = input.value.trim();
    if (!texto) return;

    input.value = '';
    input.disabled = true;

    const body = 'ticket_id=' + encodeURIComponent(currentTicketId)
               + '&mensaje='  + encodeURIComponent(texto);

    fetch('/HelpDesk_EQF/modules/ticket/send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body
    })
    .then(r => r.json())
    .then(data => {
        input.disabled = false;
        input.focus();

        if (!data.ok) {
            alert(data.msg || 'No se pudo enviar el mensaje');
            return;
        }

        // Opcionalmente forzamos un fetch para ver nuestro mensaje ya ordenado
        fetchMessages(false);
    })
    .catch(err => {
        console.error('Error enviando mensaje:', err);
        input.disabled = false;
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
