<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';

/* ===========================
   ALERTAS
=========================== */
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

/* ===========================
   AUTH
=========================== */
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


/* ===========================
   CATÁLOGO DE PROBLEMAS (SA)
   AJUSTA ESTA TABLA si es necesario
=========================== */
$CATALOG_TABLE = 'catalog_problems'; // <-- cámbiala si tu tabla se llama diferente
$CATALOG_CODE_COL  = 'code';
$CATALOG_LABEL_COL = 'label';


/* ===========================
   TICKETS A MOSTRAR:
   - abiertos/en_proceso
   - cerrado + encuesta pendiente
=========================== */
$stmtOpen = $pdo->prepare("
    SELECT 
        t.id,
        t.problema,
        t.fecha_envio,
        t.estado,
        f.token AS feedback_token
    FROM tickets t
    LEFT JOIN ticket_feedback f
        ON f.ticket_id = t.id
       AND f.answered_at IS NULL
    WHERE t.user_id = :uid
      AND (
            t.estado IN ('abierto','en_proceso')
         OR (t.estado = 'cerrado' AND f.id IS NOT NULL)
      )
    ORDER BY t.fecha_envio DESC
    LIMIT 10
");
$stmtOpen->execute([':uid' => $userId]);
$openTickets = $stmtOpen->fetchAll(PDO::FETCH_ASSOC);


/* ===========================
   ENCUESTAS PENDIENTES (BLOQUEO)
=========================== */
$stmtPending = $pdo->prepare("
  SELECT COUNT(*)
  FROM ticket_feedback
  WHERE user_id = ? AND answered_at IS NULL
");
$stmtPending->execute([$userId]);
$pendingCount = (int)$stmtPending->fetchColumn();


/* ===========================
   MAPA code => label DESDE BD (SA)
=========================== */
$problemMap = [];

try {
    // juntar todos los codes que aparezcan en esta lista
    $problemCodes = array_values(array_unique(array_filter(array_map(
        fn($t) => $t['problema'] ?? null,
        $openTickets
    ))));

    if (!empty($problemCodes)) {
        $in = implode(',', array_fill(0, count($problemCodes), '?'));

        $sql = "
            SELECT {$CATALOG_CODE_COL} AS code, {$CATALOG_LABEL_COL} AS label
            FROM {$CATALOG_TABLE}
            WHERE {$CATALOG_CODE_COL} IN ($in)
        ";
        $stmtProb = $pdo->prepare($sql);
        $stmtProb->execute($problemCodes);

        foreach ($stmtProb->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $problemMap[$row['code']] = $row['label'];
        }
    }
} catch (Throwable $e) {
    // Si el catálogo no existe o falla, NO rompemos user.php; solo mostraremos el code
    error_log('Catalog problems error: ' . $e->getMessage());
}

function problemLabelFromDb(string $code, array $map): string {
    return $map[$code] ?? $code;
}


/* ===========================
   AUTO-ABRIR ENCUESTA (si hay token)
=========================== */
$autoFeedbackToken = null;
$autoFeedbackTicketId = 0;
$autoFeedbackTitle = '';

foreach ($openTickets as $t) {
    if (!empty($t['feedback_token'])) {
        $autoFeedbackToken    = $t['feedback_token'];
        $autoFeedbackTicketId = (int)$t['id'];
        $autoFeedbackTitle    = problemLabelFromDb((string)$t['problema'], $problemMap);
        break;
    }
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

        /* Feedback wizard */
        .feedback-options{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }
        .feedback-option-btn{
            border: 1px solid var(--eqf-border, #e5e7eb);
            background:#fff;
            padding:10px 12px;
            border-radius:14px;
            cursor:pointer;
            font-weight:700;
        }
        .feedback-option-btn.is-active{
            outline: 2px solid var(--eqf-combined, #6e1c5c);
        }
        .feedback-badge{
            display:inline-block;
            margin-left:8px;
            padding:2px 8px;
            border-radius:999px;
            font-size:11px;
            font-weight:800;
            background: rgba(110, 28, 92, 0.12);
        }
    </style>
</head>

<body class="user-body">

<?php if (!empty($alerts)): ?>
    <?php $alert = $alerts[0]; ?>
    <div id="eqf-alert-container">
        <div class="eqf-alert eqf-alert-<?php echo htmlspecialchars($alert['type']); ?>">
            <img class="eqf-alert-icon"
                 src="/HelpDesk_EQF/assets/img/icons/<?php echo htmlspecialchars($alert['icon']); ?>"
                 alt="alert icon">
            <div class="eqf-alert-text"><?php echo htmlspecialchars($alert['text']); ?></div>
        </div>
    </div>
<?php endif; ?>

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

            <?php if ($pendingCount > 0): ?>
    <div class="user-info-card" id="pendingFeedbackCard">

                    <h2>Encuestas pendientes</h2>
                    <p>
                        Tienes <strong><?php echo $pendingCount; ?></strong> encuesta(s) pendiente(s).
                        Debes responderlas antes de crear un nuevo ticket.
                    </p>
                </div>
            <?php endif; ?>

            <div class="button">
                <button id="btnCreateTicket" type="button"
                        class="btn-primary user-main-cta"
                        onclick="<?php echo ($pendingCount > 0) ? 'return false;' : 'openTicketModal()'; ?>"
                        <?php echo ($pendingCount > 0) ? 'disabled style="opacity:.6; cursor:not-allowed;"' : ''; ?>>
                    Crear ticket
                </button>
            </div>

            <div id="tickets-section" class="user-tickets-placeholder">
                <h3>Tus tickets</h3>

                <?php if (empty($openTickets)): ?>
                    <p>No tienes tickets activos ni encuestas pendientes por el momento.</p>
                <?php else: ?>
                    <ul class="user-tickets-list" id="userTicketsList">
                        <?php foreach ($openTickets as $t): ?>
                            <?php
                                $ticketId = (int)$t['id'];
                                $problemCode = (string)($t['problema'] ?? '');
                                $problemLabel = problemLabelFromDb($problemCode, $problemMap);
                            ?>
                            <li class="user-ticket-item">
                                <div class="user-ticket-info">
                                    <div>
                                        <strong>#<?php echo $ticketId; ?></strong>
                                        — <?php echo htmlspecialchars($problemLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($t['feedback_token'])): ?>
                                            <span class="feedback-badge">encuesta pendiente</span>
                                        <?php endif; ?>
                                    </div>

                                    <small>
                                        <?php echo htmlspecialchars((string)$t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?>
                                        · <?php echo htmlspecialchars((string)$t['estado'], ENT_QUOTES, 'UTF-8'); ?>
                                    </small>
                                </div>

                                <div class="user-ticket-actions">
                                    <?php if (!empty($t['feedback_token'])): ?>
                                        <button type="button"
                                                class="btn-main-combined"
                                                style="padding:6px 14px; font-size:0.75rem;"
                                                onclick="openFeedbackWizard(
                                                    '<?php echo htmlspecialchars((string)$t['feedback_token'], ENT_QUOTES, 'UTF-8'); ?>',
                                                    <?php echo $ticketId; ?>,
                                                    '<?php echo htmlspecialchars($problemLabel, ENT_QUOTES, 'UTF-8'); ?>'
                                                )">
                                            Encuesta pendiente
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
  class="btn-main-combined"
  style="padding:6px 14px; font-size:0.75rem;"
  onclick="openTicketChat(<?php echo $ticketId; ?>,'<?php echo htmlspecialchars($problemLabel,ENT_QUOTES,'UTF-8'); ?>')"
  data-chat-btn
  data-ticket-id="<?php echo $ticketId; ?>"
>
  Ver chat <span class="chat-badge" style="display:none;"></span>
</button>

                                    <?php endif; ?>
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

            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string)$userId, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="area" value="<?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group form-row">
                <div class="field">
                    <label># SAP</label>
                    <input type="text" id="sapDisplay" value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>
                <div class="field">
                    <label>Nombre</label>
                    <input type="text" id="nombreDisplay" value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Correo</label>
                    <input type="text" id="emailDisplay" value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>
            </div>

            <input type="hidden" name="sap" id="sapValue" value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="nombre" id="nombreValue" value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">

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

            <input type="hidden" name="prioridad" id="prioridadValue" value="media">

            <div class="form-group form-group-full">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3" placeholder="Describe el problema" required></textarea>
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
                <button type="submit" class="btn-primary">Enviar ticket</button>
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

        <div class="ticket-chat-body" id="ticketChatBody"></div>

        <form class="ticket-chat-form" onsubmit="sendTicketMessage(event)">
            <textarea id="ticketChatInput" rows="2" placeholder="Escribe tu mensaje..." style="width:100%"></textarea>

            <div class="ticket-chat-input-row">
                <input type="file"
                       id="ticketChatFile"
                       name="adjunto"
                       class="ticket-chat-file"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv"
                       style="width:100%">

                <button type="submit" class="btn-login" style="min-width: 60px;">Enviar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL FEEDBACK -->
<div class="modal-backdrop" id="feedback-modal">
  <div class="modal-card" style="max-width:520px;">
    <div class="modal-header">
      <h3 id="feedbackTitle">Encuesta de satisfacción</h3>
      <button type="button" class="modal-close" onclick="closeFeedbackWizard()">✕</button>
    </div>

    <div class="modal-body" style="padding: 14px 18px;">
      <div id="feedbackStepInfo" style="font-weight:700; margin-bottom:10px;">Pregunta 1/3</div>
      <div id="feedbackQuestion" style="margin-bottom:12px; font-size:14px;"></div>
      <div id="feedbackOptions" class="feedback-options"></div>

      <div id="feedbackCommentWrap" style="display:none; margin-top:12px;">
        <label style="display:block; font-weight:700; margin-bottom:6px;">Comentarios (opcional)</label>
        <textarea id="feedbackComment" rows="3" style="width:100%;"></textarea>
      </div>

      <div class="modal-actions" style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn-secondary" onclick="resetFeedbackWizard()">Reiniciar</button>
        <button type="button" class="btn-secondary" onclick="prevFeedbackStep()">Atrás</button>
        <button type="button" class="btn-login" id="feedbackNextBtn" onclick="nextFeedbackStep()">Siguiente</button>
      </div>
    </div>
  </div>
</div>

<script>
function openTicketModal() {
    document.getElementById('ticketModal').classList.add('is-visible');
}
function closeTicketModal() {
    document.getElementById('ticketModal').classList.remove('is-visible');
}
</script>

<script>
const CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
</script>

<script>
/* ===========================
   CHAT
=========================== */
let currentTicketId = null;
let lastMessageId   = 0;
let chatPollTimer   = null;

function openTicketChat(ticketId, tituloExtra) {
    currentTicketId = ticketId;
    lastMessageId = 0;

    const titleEl = document.getElementById('ticketChatTitle');
    if (titleEl) {
        titleEl.textContent = 'Chat del ticket #' + ticketId + (tituloExtra ? ' – ' + tituloExtra : '');
    }

    const bodyEl = document.getElementById('ticketChatBody');
    if (bodyEl) bodyEl.innerHTML = '';

    const modal = document.getElementById('ticket-chat-modal');
    if (typeof openModal === 'function') openModal('ticket-chat-modal');
    else if (modal) modal.classList.add('show');

    fetchMessages();

    if (chatPollTimer) clearInterval(chatPollTimer);
    chatPollTimer = setInterval(() => fetchMessages(), 5000);
fetch('/HelpDesk_EQF/modules/ticket/mark_read.php', {
  method:'POST',
  headers:{'Content-Type':'application/x-www-form-urlencoded'},
  body:'ticket_id=' + encodeURIComponent(ticketId)
}).then(()=> {
  // quita badge local inmediato (sin esperar al polling)
  const btn = document.querySelector(`[data-chat-btn][data-ticket-id="${ticketId}"] .chat-badge`);
  if (btn){ btn.style.display='none'; btn.textContent=''; }
}).catch(()=>{});



}

function closeTicketChat() {
    const modal = document.getElementById('ticket-chat-modal');
    if (typeof closeModal === 'function') closeModal('ticket-chat-modal');
    else if (modal) modal.classList.remove('show');

    if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
    currentTicketId = null;
}

function appendChatMessage(msg) {
    const bodyEl = document.getElementById('ticketChatBody');
    if (!bodyEl) return;

    const div = document.createElement('div');
    div.className = 'ticket-chat-message';

    const senderId = parseInt(msg.sender_id, 10);
    const isMine = (senderId === CURRENT_USER_ID);
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
            imgLink.href = url;
            imgLink.target = '_blank';
            imgLink.rel = 'noopener';

            const img = document.createElement('img');
            img.src = url;
            img.alt = name;
            img.className = 'ticket-chat-image';

            imgLink.appendChild(img);
            fileWrapper.appendChild(imgLink);
        } else {
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = '📎 ' + name;
            fileWrapper.appendChild(link);
        }

        div.appendChild(fileWrapper);
    }

    const meta = document.createElement('span');
    meta.className = 'ticket-chat-meta';
    meta.textContent = (msg.sender_role ? msg.sender_role + ' · ' : '') + (msg.created_at || '');
    div.appendChild(meta);

    bodyEl.appendChild(div);
    bodyEl.scrollTop = bodyEl.scrollHeight;
}

function fetchMessages() {
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

function sendTicketMessage(ev) {
    ev.preventDefault();
    if (!currentTicketId) return;

    const input = document.getElementById('ticketChatInput');
    const fileInput = document.getElementById('ticketChatFile');
    if (!input) return;

    const texto = input.value.trim();
    const file = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;
    if (!texto && !file) return;

    input.disabled = true;
    if (fileInput) fileInput.disabled = true;

    const formData = new FormData();
    formData.append('ticket_id', currentTicketId);
    formData.append('mensaje', texto);
    if (file) formData.append('adjunto', file);

    fetch('/HelpDesk_EQF/modules/ticket/send_messages.php', { method: 'POST', body: formData })
        .then(resp => {
            input.disabled = false;
            if (fileInput) { fileInput.disabled = false; fileInput.value = ''; }

            if (!resp.ok) { alert('No se pudo enviar el mensaje'); return; }

            input.value = '';
            input.focus();
            fetchMessages();
        })
        .catch(err => {
            console.error(err);
            input.disabled = false;
            if (fileInput) fileInput.disabled = false;
            alert('Error al enviar el mensaje');
        });
}
</script>

<script>
/* ===========================
   FEEDBACK WIZARD
=========================== */
let FEEDBACK = { token:null, ticketId:null, title:'', step:1, q1:0, q2:0, q3:0 };

function openFeedbackWizard(token, ticketId, title){
  FEEDBACK.token = token;
  FEEDBACK.ticketId = ticketId;
  FEEDBACK.title = title || '';
  FEEDBACK.step = 1;
  FEEDBACK.q1 = 0; FEEDBACK.q2 = 0; FEEDBACK.q3 = 0;

  document.getElementById('feedbackTitle').textContent =
    'Encuesta – Ticket #' + ticketId + (title ? ' · ' + title : '');

  document.getElementById('feedbackComment').value = '';
  document.getElementById('feedbackCommentWrap').style.display = 'none';
  document.getElementById('feedbackNextBtn').textContent = 'Siguiente';

  renderFeedbackStep();

  const modal = document.getElementById('feedback-modal');
  if (typeof openModal === 'function') openModal('feedback-modal');
  else modal.classList.add('show');
}

function closeFeedbackWizard(){
  const modal = document.getElementById('feedback-modal');
  if (typeof closeModal === 'function') closeModal('feedback-modal');
  else modal.classList.remove('show');
}

function resetFeedbackWizard(){
  FEEDBACK.step = 1;
  FEEDBACK.q1 = 0; FEEDBACK.q2 = 0; FEEDBACK.q3 = 0;
  document.getElementById('feedbackComment').value = '';
  document.getElementById('feedbackCommentWrap').style.display = 'none';
  document.getElementById('feedbackNextBtn').textContent = 'Siguiente';
  renderFeedbackStep();
}

function prevFeedbackStep(){
  if (FEEDBACK.step <= 1) return;
  FEEDBACK.step--;
  document.getElementById('feedbackCommentWrap').style.display = (FEEDBACK.step === 3) ? 'block' : 'none';
  document.getElementById('feedbackNextBtn').textContent = (FEEDBACK.step === 3) ? 'Enviar' : 'Siguiente';
  renderFeedbackStep();
}

function nextFeedbackStep(){
  if (FEEDBACK.step === 1 && FEEDBACK.q1 === 0) return alert('Selecciona una opción.');
  if (FEEDBACK.step === 2 && FEEDBACK.q2 === 0) return alert('Selecciona una opción.');
  if (FEEDBACK.step === 3 && FEEDBACK.q3 === 0) return alert('Selecciona una opción.');

  if (FEEDBACK.step < 3){
    FEEDBACK.step++;
    document.getElementById('feedbackCommentWrap').style.display = (FEEDBACK.step === 3) ? 'block' : 'none';
    document.getElementById('feedbackNextBtn').textContent = (FEEDBACK.step === 3) ? 'Enviar' : 'Siguiente';
    renderFeedbackStep();
    return;
  }
  submitFeedback();
}

function renderFeedbackStep(){
  const info = document.getElementById('feedbackStepInfo');
  const qEl  = document.getElementById('feedbackQuestion');
  const opt  = document.getElementById('feedbackOptions');

  info.textContent = 'Pregunta ' + FEEDBACK.step + '/3';
  opt.innerHTML = '';

  if (FEEDBACK.step === 1){
    qEl.textContent = '¿Cómo calificas la atención recibida?';
    renderOptions([
      {value: 1, label: 'Malo'},
      {value: 2, label: 'Regular'},
      {value: 3, label: 'Bueno'},
    ], FEEDBACK.q1, (v)=>FEEDBACK.q1=v);
  } else if (FEEDBACK.step === 2){
    qEl.textContent = '¿El problema fue resuelto completamente?';
    renderOptions([
      {value: 2, label: 'Sí'},
      {value: 1, label: 'No'},
    ], FEEDBACK.q2, (v)=>FEEDBACK.q2=v);
  } else {
    qEl.textContent = '¿Cómo calificas el tiempo de respuesta?';
    renderOptions([
      {value: 1, label: 'Malo'},
      {value: 2, label: 'Regular'},
      {value: 3, label: 'Bueno'},
    ], FEEDBACK.q3, (v)=>FEEDBACK.q3=v);
  }
}

function renderOptions(items, selected, onPick){
  const opt = document.getElementById('feedbackOptions');

  items.forEach(it => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'feedback-option-btn' + (selected === it.value ? ' is-active' : '');
    btn.textContent = it.label; // aquí puedes reemplazar por imagen
    btn.onclick = () => { onPick(it.value); renderFeedbackStep(); };
    opt.appendChild(btn);
  });
}

function submitFeedback(){
  const fd = new FormData();
  fd.append('token', FEEDBACK.token);
  fd.append('q1', FEEDBACK.q1);
  fd.append('q2', FEEDBACK.q2);
  fd.append('q3', FEEDBACK.q3);
  fd.append('comment', document.getElementById('feedbackComment').value.trim());

  fetch('/HelpDesk_EQF/modules/feedback/submit_feedback.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) return alert(data.msg || 'No se pudo enviar');
      closeFeedbackWizard();
      window.location.reload();
    })
    .catch(err => {
      console.error(err);
      alert('Error al enviar la encuesta.');
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
                    const msg = n.body || 'Tienes una actualización de tu ticket.';
                    const title = n.title || 'HelpDesk EQF';

                    showUserToast(msg);

                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification(title, {
                            body: msg,
                            icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png'
                        });
                    }
                });
            })
            .catch(err => console.error('Error consultando notificaciones de usuario:', err));
    }

    setInterval(pollUserNotifications, 10000);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  function setCreateTicketEnabled(enabled){
    const btn = document.getElementById('btnCreateTicket');
    if (!btn) return;

    if (enabled){
      btn.disabled = false;
      btn.style.opacity = '';
      btn.style.cursor = '';
      btn.setAttribute('onclick', 'openTicketModal()');
    } else {
      btn.disabled = true;
      btn.style.opacity = '.6';
      btn.style.cursor = 'not-allowed';
      btn.setAttribute('onclick', 'return false;');
    }
  }

  function renderTicketsList(tickets){
    const ul = document.getElementById('userTicketsList');
    if (!ul) return;

    if (!tickets || !tickets.length){
      ul.innerHTML = '';
      const wrap = document.querySelector('#tickets-section');
      if (wrap){
        // si quieres, podrías actualizar el texto vacío aquí
      }
      return;
    }

    ul.innerHTML = tickets.map(t => {
      const badge = t.feedback_token
        ? `<span class="feedback-badge">encuesta pendiente</span>`
        : '';

      const actionBtn = t.feedback_token
        ? `<button type="button"
                  class="btn-main-combined"
                  style="padding:6px 14px; font-size:0.75rem;"
                  onclick="openFeedbackWizard('${escapeAttr(t.feedback_token)}', ${t.id}, '${escapeAttr(t.problema_label)}')">
              Encuesta pendiente
           </button>`
        : `<button type="button"
                  class="btn-main-combined"
                  style="padding:6px 14px; font-size:0.75rem;"
                  onclick="openTicketChat(${t.id}, '${escapeAttr(t.problema_label)}')">
              Ver chat
           </button>`;

      return `
        <li class="user-ticket-item">
          <div class="user-ticket-info">
            <div>
              <strong>#${t.id}</strong>
              — ${escapeHtml(t.problema_label)}
              ${badge}
            </div>
            <small>
              ${escapeHtml(t.fecha_envio)} · ${escapeHtml(t.estado)}
            </small>
          </div>
          <div class="user-ticket-actions">
            ${actionBtn}
          </div>
        </li>
      `;
    }).join('');
  }

  function escapeHtml(str){
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function escapeAttr(str){
    // para meter en onclick con comillas simples
    return String(str ?? '').replaceAll("\\", "\\\\").replaceAll("'", "\\'");
  }

  function updatePendingUI(pendingCount){
    const card = document.getElementById('pendingCard');

    if (pendingCount > 0){
      setCreateTicketEnabled(false);

      // Si no existe la card (porque estaba en 0 al cargar), la creamos arriba del botón
      if (!card){
        const content = document.querySelector('.user-main-content');
        const btnWrap = document.querySelector('.button');
        if (content && btnWrap){
          const div = document.createElement('div');
          div.className = 'user-info-card';
          div.id = 'pendingCard';
          div.innerHTML = `
            <h2>Encuestas pendientes</h2>
            <p>
              Tienes <strong>${pendingCount}</strong> encuesta(s) pendiente(s).
              Debes responderlas antes de crear un nuevo ticket.
            </p>
          `;
          content.insertBefore(div, btnWrap);
        }
      } else {
        // si ya existe, solo actualizamos número
        const strong = card.querySelector('strong');
        if (strong) strong.textContent = String(pendingCount);
      }
    } else {
      setCreateTicketEnabled(true);
      if (card) card.remove();
    }
  }

  async function refreshUserTickets(){
    try {
      const r = await fetch('/HelpDesk_EQF/modules/ticket/user_tickets_snapshot.php');
      const data = await r.json();
      if (!data.ok) return;

      updatePendingUI(parseInt(data.pendingCount || 0, 10));
      renderTicketsList(data.tickets || []);
    } catch (e) {
      console.error('refreshUserTickets error:', e);
    }
  }

  // ✅ Hook: cuando llegue notificación, actualiza UI (estado / encuesta / lista)
  const oldPoll = window.pollUserNotifications;
  window.pollUserNotifications = function(){
    fetch('/HelpDesk_EQF/modules/ticket/check_user_notifications.php')
      .then(r => r.json())
      .then(data => {
        if (!data.ok || !data.has) return;

        // tu lógica actual de toasts/notifs
        if (Array.isArray(data.notifications)){
          data.notifications.forEach(n => {
            const msg = n.body || 'Tienes una actualización de tu ticket.';
            const title = n.title || 'HelpDesk EQF';
            // reusa tu función showUserToast si existe
            if (typeof showUserToast === 'function') showUserToast(msg);

            if ('Notification' in window && Notification.permission === 'granted') {
              new Notification(title, { body: msg, icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png' });
            }
          });
        }

        //  y aquí refrescamos lista/estado/bloqueo
        refreshUserTickets();
      })
      .catch(err => console.error('Error consultando notificaciones de usuario:', err));
  };

  // Si ya traías interval, no lo duplicamos: solo refrescamos una vez y listo
  refreshUserTickets();
});
</script>

<?php if (!empty($autoFeedbackToken)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Auto abrir encuesta pendiente
  openFeedbackWizard(
    "<?php echo htmlspecialchars((string)$autoFeedbackToken, ENT_QUOTES, 'UTF-8'); ?>",
    <?php echo (int)$autoFeedbackTicketId; ?>,
    "<?php echo htmlspecialchars((string)$autoFeedbackTitle, ENT_QUOTES, 'UTF-8'); ?>"
  );
});
</script>
<?php endif; ?>
<script>
/* ===========================
   LIVE REFRESH - USER
=========================== */

function escapeHtml(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function buildTicketLi(t){
  const id = t.id;
  const problema = t.problema_label || t.problema_raw || '';
  const estado = t.estado || '';
  const fecha = t.fecha_envio || '';
  const token = t.feedback_token;

  const badge = token ? `<span class="feedback-badge">encuesta pendiente</span>` : '';

  const actionBtn = token
    ? `<button type="button"
              class="btn-main-combined"
              style="padding:6px 14px; font-size:0.75rem;"
              onclick="openFeedbackWizard('${escapeHtml(token)}', ${id}, '${escapeHtml(problema)}')">
          Encuesta pendiente
       </button>`
    : `<button type="button"
              class="btn-main-combined"
              style="padding:6px 14px; font-size:0.75rem;"
              onclick="openTicketChat(${id}, '${escapeHtml(problema)}')">
          Ver chat
       </button>`;

  return `
    <li class="user-ticket-item" data-ticket-id="${id}">
      <div class="user-ticket-info">
        <div>
          <strong>#${id}</strong> — ${escapeHtml(problema)} ${badge}
        </div>
        <small>
          ${escapeHtml(fecha)} · <span class="ticket-estado">${escapeHtml(estado)}</span>
        </small>
      </div>
      <div class="user-ticket-actions">
        ${actionBtn}
      </div>
    </li>
  `;
}

function applyUserSnapshot(payload){
  if (!payload || !payload.ok) return;

  // 1) Bloqueo "Crear ticket"
  const pending = parseInt(payload.pending_feedback_count || 0, 10);
  const btn = document.getElementById('btnCreateTicket');
  const card = document.getElementById('pendingFeedbackCard');
  const countEl = document.getElementById('pendingFeedbackCount');

  if (countEl) countEl.textContent = String(pending);

  if (pending > 0){
    if (btn){
      btn.disabled = true;
      btn.style.opacity = '.6';
      btn.style.cursor = 'not-allowed';
      btn.onclick = () => false;
    }
    if (!card){
      // si la tarjeta no existía (porque antes era 0), la creamos arriba del botón
      const btnWrap = document.querySelector('.button');
      if (btnWrap){
        const div = document.createElement('div');
        div.className = 'user-info-card';
        div.id = 'pendingFeedbackCard';
        div.innerHTML = `
          <h2>Encuestas pendientes</h2>
          <p>
            Tienes <strong id="pendingFeedbackCount">${pending}</strong> encuesta(s) pendiente(s).
            Debes responderlas antes de crear un nuevo ticket.
          </p>
        `;
        btnWrap.parentNode.insertBefore(div, btnWrap);
      }
    }
  } else {
    if (btn){
      btn.disabled = false;
      btn.style.opacity = '';
      btn.style.cursor = '';
      btn.onclick = () => openTicketModal();
    }
    if (card) card.remove();
  }

  // 2) Refrescar lista "Tus tickets"
  const list = document.querySelector('.user-tickets-list');
  const placeholder = document.getElementById('tickets-section');

  const tickets = Array.isArray(payload.tickets) ? payload.tickets : [];

  // si no hay lista y sí hay tickets, creamos la UL
  let ul = list;
  if (!ul && tickets.length){
    // elimina el mensaje "No tienes tickets..." si existe
    const p = placeholder ? placeholder.querySelector('p') : null;
    if (p) p.remove();

    ul = document.createElement('ul');
    ul.className = 'user-tickets-list';
    if (placeholder) placeholder.appendChild(ul);
  }

  // si no hay tickets, limpia UL y muestra mensaje
  if (!tickets.length){
    if (ul) ul.remove();
    if (placeholder && !placeholder.querySelector('p')){
      const p = document.createElement('p');
      p.textContent = 'No tienes tickets activos ni encuestas pendientes por el momento.';
      placeholder.appendChild(p);
    }
    return;
  }

  if (!ul) return;

  // set de ids nuevos
  const newIds = new Set(tickets.map(t => String(t.id)));

  // quitar los que ya no deben mostrarse
  ul.querySelectorAll('li[data-ticket-id]').forEach(li => {
    const id = li.getAttribute('data-ticket-id');
    if (!newIds.has(String(id))) li.remove();
  });

  // upsert en orden (top = más reciente)
  // simple: re-render completo para mantener orden correcto
  ul.innerHTML = tickets.map(buildTicketLi).join('');
}

function pollUserSnapshot(){
  fetch('/HelpDesk_EQF/modules/ticket/user_snapshot.php', { cache: 'no-store' })
    .then(r => r.json())
    .then(applyUserSnapshot)
    .catch(()=>{});
}

document.addEventListener('DOMContentLoaded', () => {
  pollUserSnapshot();
  setInterval(pollUserSnapshot, 9000);
});


function applyUnreadBadges(items){
  const map = new Map();
  (items || []).forEach(it => map.set(String(it.ticket_id), parseInt(it.unread_count||0,10)));

  document.querySelectorAll('[data-chat-btn][data-ticket-id]').forEach(btn => {
    const id = btn.getAttribute('data-ticket-id');
    const badge = btn.querySelector('.chat-badge');
    const count = map.get(String(id)) || 0;

    if (!badge) return;

    if (count > 0){
      badge.style.display = 'inline-flex';
      badge.textContent = count > 9 ? '9+' : String(count);
    } else {
      badge.style.display = 'none';
      badge.textContent = '';
    }
  });
}

function pollUserUnread(){
  fetch('/HelpDesk_EQF/modules/ticket/user_unread.php', {cache:'no-store'})
    .then(r=>r.json())
    .then(data=>{
      if (!data.ok) return;
      applyUnreadBadges(data.items);
    })
    .catch(()=>{});
}
setInterval(pollUserUnread, 7000);
pollUserUnread();


</script>

</body>
</html>
