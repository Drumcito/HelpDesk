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
$userArea  = $_SESSION['user_area'] ?? '';

// -------- ALERTAS ----------
$alerts = [];
if (isset($_GET['updated'])) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'capsulin_update.png',
        'text' => 'TICKET ACTUALIZADO EXITOSAMENTE'
    ];
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
        SUM(estado = 'cerrado')      AS cerrados,
        COUNT(*)                     AS total
    FROM tickets
    WHERE area = :area
");
$stmtKpi->execute([':area' => $userArea]);
$kpi = $stmtKpi->fetch() ?: [
    'abiertos'    => 0,
    'en_proceso'  => 0,
    'cerrados'    => 0,
    'total'       => 0,
];

/* ===========================
   MIS SOLICITUDES A TI (Ticket para mí)
   - user_id = yo
   - area = 'TI'
   - abierto/en_proceso
   - cerrado + encuesta pendiente
=========================== */
$stmtMyToTI = $pdo->prepare("
  SELECT 
    t.id,
    t.fecha_envio,
    t.estado,
    t.asignado_a,
    CONCAT(COALESCE(u.name,''),' ',COALESCE(u.last_name,'')) AS atendido_por,
    f.token AS feedback_token
  FROM tickets t
  LEFT JOIN users u
    ON u.id = t.asignado_a
  LEFT JOIN ticket_feedback f
    ON f.ticket_id = t.id
   AND f.answered_at IS NULL
  WHERE t.user_id = :uid
    AND t.area = 'TI'
    AND (
          t.estado IN ('abierto','en_proceso')
       OR (t.estado = 'cerrado' AND f.id IS NOT NULL)
    )
  ORDER BY t.fecha_envio DESC
  LIMIT 10
");
$stmtMyToTI->execute([':uid' => $userId]);
$myToTI = $stmtMyToTI->fetchAll(PDO::FETCH_ASSOC);



/* Tickets entrantes (abiertos, sin asignar) + label desde catálogo */
$stmtIncoming = $pdo->prepare("
    SELECT 
        t.id, t.sap, t.nombre, t.email,
        t.problema AS problema_raw,
        COALESCE(cp.label, t.problema) AS problema_label,
        t.descripcion, t.fecha_envio, t.estado, t.prioridad
    FROM tickets t
    LEFT JOIN catalog_problems cp
           ON cp.id = t.problema
    WHERE t.area = :area
      AND t.estado = 'abierto'
      AND (t.asignado_a IS NULL OR t.asignado_a = 0)
    ORDER BY t.fecha_envio ASC
");
$stmtIncoming->execute([':area' => $userArea]);
$incomingTickets = $stmtIncoming->fetchAll(PDO::FETCH_ASSOC);

/* máximo id actual para iniciar el polling */
$maxIncomingId = 0;
foreach ($incomingTickets as $t) {
    $tid = (int)$t['id'];
    if ($tid > $maxIncomingId) $maxIncomingId = $tid;
}

/* Mis tickets activos (abiertos y en proceso) + label desde catálogo */
$stmtMy = $pdo->prepare("
    SELECT 
        t.id, t.sap, t.nombre, t.email,
        t.problema AS problema_raw,
        COALESCE(cp.label, t.problema) AS problema_label,
        t.descripcion, t.fecha_envio, t.estado, t.prioridad
    FROM tickets t
    LEFT JOIN catalog_problems cp
           ON cp.code = t.problema
    WHERE t.area = :area
      AND t.asignado_a = :uid
      AND t.estado IN ('abierto','en_proceso')
    ORDER BY t.fecha_envio DESC
");
$stmtMy->execute([':area' => $userArea, ':uid' => $userId]);
$myTickets = $stmtMy->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Analista | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

    <style>
      .analyst-actions{
        display:flex;
        gap:8px;
        justify-content:flex-end;
        flex-wrap:wrap;
      }
      .btn-mini{
        padding:6px 10px;
        border-radius:12px;
        font-weight:800;
        border:1px solid var(--eqf-border,#e5e7eb);
        background:#fff;
        cursor:pointer;
      }
      .btn-mini.primary{
        background: var(--eqf-combined,#6e1c5c);
        color:#fff;
        border-color: transparent;
      }
      .ticket-detail-grid{
        display:grid;
        grid-template-columns: 1fr;
        gap:10px;
        font-size:14px;
      }
      .ticket-detail-meta{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        opacity:.9;
        font-size:13px;
      }
      .ticket-attachments{
        display:flex;
        flex-direction:column;
        gap:8px;
      }
      .ticket-attachments a{
        display:inline-block;
        padding:8px 10px;
        border:1px solid var(--eqf-border,#e5e7eb);
        border-radius:12px;
        text-decoration:none;
      }
      .kpi-live{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:10px;
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
            <div class="eqf-alert-text">
                <?php echo htmlspecialchars($alert['text']); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

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

            <!-- KPIs -->
            <div class="user-info-card">
                <h2>Resumen Diario</h2>
                <p>Aquí podrás ver tu resumen diario.</p>

                <div class="kpi-analyst-row" id="kpiRow">
                    <div class="kpi-card kpi-green">
                        <span class="kpi-label">Abiertos</span>
                        <span class="kpi-value" id="kpiAbiertos"><?php echo (int)$kpi['abiertos']; ?></span>
                    </div>
                    <div class="kpi-card kpi-blue">
                        <span class="kpi-label">En proceso</span>
                        <span class="kpi-value" id="kpiEnProceso"><?php echo (int)$kpi['en_proceso']; ?></span>
                    </div>
                    <div class="kpi-card kpi-yellow">
                        <span class="kpi-label">Resueltos</span>
                        <span class="kpi-value" id="kpiResueltos"><?php echo (int)$kpi['cerrados']; ?></span>
                    </div>
                    <div class="kpi-card kpi-gray">
                        <span class="kpi-label">Total</span>
                        <span class="kpi-value" id="kpiTotal"><?php echo (int)$kpi['total']; ?></span>
                    </div>
                </div>
            </div>
<!-- MIS TICKETS PARA TI -->
 <div class="user-info-card" id="my-ti-requests">
  <h3>Mis solicitudes a TI</h3>

  <?php if (empty($myToTI)): ?>
    <p style="opacity:.8;">No tienes solicitudes activas a TI ni encuestas pendientes.</p>
  <?php else: ?>
    <ul class="user-tickets-list" style="margin-top:10px;">
      <?php foreach ($myToTI as $t): 
        $ticketId = (int)$t['id'];
        $atendido = trim((string)$t['atendido_por']);
        $atendido = $atendido !== '' ? $atendido : 'Sin asignar';
      ?>
        <li class="user-ticket-item">
          <div class="user-ticket-info">
            <div>
              <strong>#<?php echo $ticketId; ?></strong>
              <?php if (!empty($t['feedback_token'])): ?>
                <span class="feedback-badge">encuesta pendiente</span>
              <?php endif; ?>
            </div>
            <small>
              <?php echo htmlspecialchars((string)$t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?>
              · <?php echo htmlspecialchars((string)$t['estado'], ENT_QUOTES, 'UTF-8'); ?>
              · <strong>Atiende:</strong> <?php echo htmlspecialchars($atendido, ENT_QUOTES, 'UTF-8'); ?>
            </small>
          </div>

          <div class="user-ticket-actions">
            <?php if (!empty($t['feedback_token'])): ?>
              <button type="button"
                      class="btn-main-combined"
                      style="padding:6px 14px; font-size:0.75rem;"
                      onclick="openFeedbackIframe(
                        '<?php echo htmlspecialchars((string)$t['feedback_token'], ENT_QUOTES, 'UTF-8'); ?>',
                        <?php echo $ticketId; ?>,
                        'Encuesta ticket #<?php echo $ticketId; ?>'
                      )">
                Encuesta
              </button>
            <?php else: ?>
              <button type="button"
                      class="btn-main-combined"
                      style="padding:6px 14px; font-size:0.75rem;"
                      onclick="openTicketChat(<?php echo $ticketId; ?>,'Solicitud a TI')">
                Ver chat
              </button>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>


            <!-- ENTRANTES -->
            <div id="incoming-section" class="user-info-card">
                <h3>Tickets entrantes</h3>

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
                                <td><?php echo htmlspecialchars($t['problema_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="priority-pill priority-<?php echo htmlspecialchars(strtolower($t['prioridad'] ?? 'media'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(prioridadLabel($t['prioridad'] ?? 'media'), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                  <div class="analyst-actions">
                                    <button type="button"
                                            class="btn-mini"
                                            onclick="openTicketDetail(<?php echo (int)$t['id']; ?>)">
                                      Previsualizar
                                    </button>

                                    <button type="button"
                                            class="btn-mini primary btn-assign-ticket"
                                            data-ticket-id="<?php echo (int)$t['id']; ?>">
                                      Asignar
                                    </button>
                                  </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- MIS TICKETS -->
            <div id="mytickets-section" class="user-info-card">
                <h3>Mis tickets</h3>

                <table id="myTicketsTable" class="data-table display analyst-tickets-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Problema</th>
                            <th>Prioridad</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myTickets as $t): ?>
                            <tr data-ticket-id="<?php echo (int)$t['id']; ?>">
                                <td>#<?php echo (int)$t['id']; ?></td>
                                <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($t['problema_label'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                                        <option value="cerrado"    <?php if ($t['estado'] === 'cerrado')    echo 'selected'; ?>>Cerrado</option>
                                    </select>
                                </td>
                                <td>
                                  <div class="analyst-actions">
                                    <button type="button" class="btn-mini"
                                            onclick="openTicketDetail(<?php echo (int)$t['id']; ?>)">
                                      Ver
                                    </button>
                                    <button type="button" class="btn-mini primary"
  data-chat-btn
  data-ticket-id="<?php echo (int)$t['id']; ?>"
  onclick="openTicketChat(<?php echo (int)$t['id']; ?>,'<?php echo htmlspecialchars($t['nombre'],ENT_QUOTES,'UTF-8'); ?>')"
>
  Chat <span class="chat-badge" style="display:none;"></span>
</button>

                                  </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </section>
</main>

<!-- MODAL DETALLE TICKET -->
<div class="modal-backdrop" id="ticket-detail-modal">
  <div class="modal-card" style="max-width:760px;">
    <div class="modal-header">
      <h3 id="ticketDetailTitle">Detalle del ticket</h3>
      <button type="button" class="modal-close" onclick="closeTicketDetail()">✕</button>
    </div>
    <div class="modal-body" style="padding:14px 18px;">
      <div id="ticketDetailContent" class="ticket-detail-grid">
        <div style="opacity:.8;">Cargando...</div>
      </div>
      <div class="modal-actions" style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="btn-secondary" onclick="closeTicketDetail()">Cerrar</button>
        <button type="button"  class="btn-primary" id="ticketDetailChatBtn" style="display:none;">Abrir chat</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CHAT (tu modal existente) -->
<div class="modal-backdrop" id="ticket-chat-modal">
    <div class="modal-card ticket-chat-modal-card">
        <div class="modal-header">
            <h3 id="ticketChatTitle">Chat del ticket</h3>
            <button type="button" class="modal-close" onclick="closeTicketChat()">✕</button>
        </div>

        <div class="ticket-chat-body" id="ticketChatBody"></div>

        <form class="ticket-chat-form" onsubmit="sendTicketMessage(event)">
            <div class="ticket-chat-internal-row" style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
              <input type="checkbox" id="ticketChatInternal" value="1">
              <label for="ticketChatInternal" style="font-size:13px; opacity:.85;">
                Nota interna (solo equipo)
              </label>
            </div>

            <textarea id="ticketChatInput" rows="2" placeholder="Escribe tu mensaje..." style="width:100%"></textarea>
            <div class="ticket-chat-input-row">
                <input type="file" id="ticketChatFile" name="adjunto" class="ticket-chat-file"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv" style="width:100%">
                <button type="submit" class="btn-primary" style="min-width: 60px;">Enviar</button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL CREAR TICKET (Analista) -->
<div class="eqf-modal-backdrop" id="createTicketBackdrop" aria-hidden="true">
  <div class="eqf-modal" role="dialog" aria-modal="true" aria-labelledby="createTicketTitle">

    <div class="eqf-modal-header">
      <h2 id="createTicketTitle">Crear ticket</h2>
      <button type="button" class="eqf-modal-close" id="btnCloseCreateTicket" aria-label="Cerrar">✕</button>
    </div>

    <form id="formCreateTicket">
      <div class="eqf-modal-body">

        <!-- CORREO + CHECK -->
        <div class="eqf-grid-2" style="align-items:end;">
          <div class="eqf-field" style="position:relative;">
            <label for="ct_email">Correo del usuario</label>

            <div style="position:relative;">
              <input
                type="text"
                id="ct_email"
                name="email"
                list="usersEmailList"
                placeholder="usuario@eqf.com"
                autocomplete="off"
                required
                style="padding-right:34px;"
              >
              <span id="ct_email_ok" class="ct-ok" aria-hidden="true" title="Usuario encontrado"
                    style="display:none; position:absolute; right:12px; top:50%; transform:translateY(-50%);">
                ✓
              </span>
            </div>

            <datalist id="usersEmailList"></datalist>
          </div>

          <div class="eqf-field" style="display:flex; align-items:center; gap:10px; padding-top:22px;">
            <input type="checkbox" id="ct_ticket_mi">
            <label for="ct_ticket_mi" style="margin:0;">Ticket para mí</label>
          </div>
        </div>

        <!-- DATOS AUTORELLENOS -->
        <div class="eqf-grid-2">
          <div class="eqf-field">
            <label>#SAP</label>
            <input type="text" id="ct_sap" disabled>
          </div>
          <div class="eqf-field">
            <label>Nombre</label>
            <input type="text" id="ct_nombre" disabled>
          </div>
        </div>

        <div class="eqf-grid-2">
          <div class="eqf-field">
            <label>Área</label>
            <input type="text" id="ct_area" disabled>
          </div>
          <div class="eqf-field">
            <label>Correo</label>
            <input type="text" id="ct_email_locked" disabled>
          </div>
        </div>

        <!-- ÁREA DESTINO (solo lectura: la define el sistema) -->
        <div class="eqf-field">
          <label>Área destino</label>
          <input type="text" id="ct_area_destino" disabled value="">
        </div>

        <hr class="eqf-hr">

        <!-- FECHAS -->
        <div class="eqf-grid-2">
          <div class="eqf-field">
            <label for="ct_inicio">Inicio (fecha y hora)</label>
            <input type="datetime-local" id="ct_inicio" name="inicio">
          </div>
          <div class="eqf-field">
            <label for="ct_fin">Fin (fecha y hora)</label>
            <input type="datetime-local" id="ct_fin" name="fin">
          </div>
        </div>

        <!-- DESCRIPCION -->
        <div class="eqf-field">
          <label for="ct_descripcion">Descripción</label>
          <textarea
            id="ct_descripcion"
            name="descripcion"
            rows="4"
            required
            placeholder="Describe el problema. Incluye sucursal/equipo, mensaje de error y qué intentaron."
          ></textarea>
        </div>

        <div class="eqf-alert" id="ct_msg" style="display:none;"></div>
      </div>

      <div class="eqf-modal-footer">
        <button type="button" class="btn-secondary" id="btnCancelCreateTicket">Cancelar</button>
        <button type="submit" class="btn-primary" id="btnSubmitCreateTicket" disabled>Guardar</button>
      </div>
    </form>

  </div>
</div>


<?php include __DIR__ . '/../../../template/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
// ===============================
//  Variables globales
// ===============================
const CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
let currentTicketId = null;
let lastMessageId   = 0;
let chatPollTimer   = null;

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

function escapeHtml(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

// ===============================
//  DETALLE TICKET (nuevo)
// ===============================
let detailTicketId = null;
let detailTicketUserName = '';

function openTicketDetail(ticketId){
  detailTicketId = ticketId;

  const modal = document.getElementById('ticket-detail-modal');
  const content = document.getElementById('ticketDetailContent');
  const title = document.getElementById('ticketDetailTitle');
  const chatBtn = document.getElementById('ticketDetailChatBtn');

  if (title) title.textContent = 'Detalle del ticket #' + ticketId;
  if (content) content.innerHTML = '<div style="opacity:.8;">Cargando...</div>';
  if (chatBtn) chatBtn.style.display = 'none';

  if (typeof openModal === 'function') openModal('ticket-detail-modal');
  else modal.classList.add('show');

  fetch('/HelpDesk_EQF/modules/ticket/ticket_view.php?ticket_id=' + encodeURIComponent(ticketId))
    .then(r => r.json())
    .then(data => {
      if (!data.ok) {
        content.innerHTML = '<div style="color:#b91c1c; font-weight:800;">' + escapeHtml(data.msg || 'No se pudo cargar') + '</div>';
        return;
      }

      const t = data.ticket || {};
      const atts = Array.isArray(data.attachments) ? data.attachments : [];
      detailTicketUserName = t.nombre || '';

      const meta = `
        <div class="ticket-detail-meta">
          <div><strong>Usuario:</strong> ${escapeHtml(t.nombre || '')} ${t.sap ? '(SAP: '+escapeHtml(t.sap)+')' : ''}</div>
          <div><strong>Correo:</strong> ${escapeHtml(t.email || '')}</div>
          <div><strong>Área:</strong> ${escapeHtml(t.area || '')}</div>
          <div><strong>Prioridad:</strong> ${escapeHtml(t.prioridad || '')}</div>
          <div><strong>Estatus:</strong> ${escapeHtml(t.estado || '')}</div>
        </div>
      `;

      const prob = `<div><strong>Problema:</strong> ${escapeHtml(t.problema_label || t.problema_raw || '')}</div>`;
      const desc = `<div><strong>Descripción:</strong><div style="margin-top:6px; white-space:pre-wrap;">${escapeHtml(t.descripcion || '')}</div></div>`;

      let attHtml = '<div><strong>Adjuntos:</strong><div style="margin-top:6px; opacity:.75;">Sin adjuntos.</div></div>';
      if (atts.length){
        attHtml = `<div><strong>Adjuntos:</strong><div class="ticket-attachments" style="margin-top:6px;">${
          atts.map(a => {
            const name = a.file_name || 'archivo';
            const path = a.file_path || '#';
            return `<a href="${escapeHtml(path)}" target="_blank" rel="noopener">📎 ${escapeHtml(name)}</a>`;
          }).join('')
        }</div></div>`;
      }

      content.innerHTML = meta + prob + desc + attHtml;

      // Mostrar botón "Abrir chat"
      if (chatBtn){
        chatBtn.style.display = 'inline-block';
        chatBtn.onclick = () => {
          closeTicketDetail();
          openTicketChat(ticketId, detailTicketUserName || ('Ticket #' + ticketId));
        };
      }
    })
    .catch(err => {
      console.error(err);
      content.innerHTML = '<div style="color:#b91c1c; font-weight:800;">Error al cargar detalle.</div>';
    });
}

function closeTicketDetail(){
  const modal = document.getElementById('ticket-detail-modal');
  if (typeof closeModal === 'function') closeModal('ticket-detail-modal');
  else modal.classList.remove('show');
  detailTicketId = null;
  detailTicketUserName = '';
}

// ===============================
//  CHAT (tu lógica base, la dejo intacta)
// ===============================
function openTicketChat(ticketId, tituloExtra) {
    currentTicketId = ticketId;
    lastMessageId   = 0;

    const titleEl = document.getElementById('ticketChatTitle');
    if (titleEl) titleEl.textContent = 'Chat del ticket #' + ticketId + (tituloExtra ? ' – ' + tituloExtra : '');

    const bodyEl = document.getElementById('ticketChatBody');
    if (bodyEl) bodyEl.innerHTML = '';

    const modal = document.getElementById('ticket-chat-modal');
    if (typeof openModal === 'function') openModal('ticket-chat-modal');
    else modal.classList.add('show');

    fetch('/HelpDesk_EQF/modules/ticket/get_transfer_context.php?ticket_id=' + encodeURIComponent(ticketId))
      .then(r => r.json())
      .then(data => { if (data && data.ok) renderTransferBlock(data); })
      .catch(err => console.error('Error transfer context:', err));

    fetchMessages(true);

    if (chatPollTimer) clearInterval(chatPollTimer);
    chatPollTimer = setInterval(() => fetchMessages(false), 5000);

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
    else modal.classList.remove('show');

    if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }

    const old = document.getElementById('transfer-block');
    if (old) old.remove();

    currentTicketId = null;
}

function appendChatMessage(msg) {
    const bodyEl = document.getElementById('ticketChatBody');
    if (!bodyEl) return;

    const div = document.createElement('div');
    div.className = 'ticket-chat-message';

    if (String(msg.is_internal) === '1') {
      const badge = document.createElement('span');
      badge.textContent = 'NOTA ';
      badge.style.fontSize = '12px';
      badge.style.opacity = '.8';
      badge.style.display = 'block';
      badge.style.marginBottom = '4px';
      div.appendChild(badge);
    }

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
    meta.textContent = ((msg.sender_role ? msg.sender_role + ' · ' : '') + (msg.created_at || ''));
    div.appendChild(meta);

    bodyEl.appendChild(div);
    bodyEl.scrollTop = bodyEl.scrollHeight;
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

    const input     = document.getElementById('ticketChatInput');
    const fileInput = document.getElementById('ticketChatFile');
    if (!input) return;

    const texto = input.value.trim();
    const file  = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;
    if (!texto && !file) return;

    input.disabled = true;
    if (fileInput) fileInput.disabled = true;

    const formData = new FormData();
    const internalCb = document.getElementById('ticketChatInternal');
    const isInternal = internalCb && internalCb.checked ? 1 : 0;

    formData.append('interno', isInternal);
    formData.append('ticket_id', currentTicketId);
    formData.append('mensaje', texto);
    if (file) formData.append('adjunto', file);

    fetch('/HelpDesk_EQF/modules/ticket/send_messages.php', { method: 'POST', body: formData })
    .then(response => {
        input.disabled = false;
        if (fileInput) {
            fileInput.disabled = false;
            fileInput.value = '';
            if (internalCb) internalCb.checked = false;
        }
        if (!response.ok) { alert('No se pudo enviar el mensaje'); return; }
        input.value = '';
        input.focus();
        fetchMessages(false);
    })
    .catch(err => {
        console.error(err);
        input.disabled = false;
        if (fileInput) fileInput.disabled = false;
        alert('Error al enviar el mensaje');
    });
}

// ===============================
//  DataTables + asignación + estado + polling
// ===============================
document.addEventListener('DOMContentLoaded', function () {

    function initOrGetTable(selector, options) {
        if (!window.jQuery || !$.fn.dataTable || !$(selector).length) return null;
        if ($.fn.dataTable.isDataTable(selector)) return $(selector).DataTable();
        return $(selector).DataTable(options || {});
    }

    const incomingDT = initOrGetTable('#incomingTable', { pageLength: 5, order: [[1, 'asc']] });
    const myDT       = initOrGetTable('#myTicketsTable', { pageLength: 5, order: [[1, 'desc']] });

    function bumpKpi(deltaAbiertos, deltaEnProceso){
      const a = document.getElementById('kpiAbiertos');
      const p = document.getElementById('kpiEnProceso');
      if (a) a.textContent = Math.max(0, (parseInt(a.textContent || '0',10) + deltaAbiertos));
      if (p) p.textContent = Math.max(0, (parseInt(p.textContent || '0',10) + deltaEnProceso));
    }

    // ---- CAMBIO ESTATUS ----
    document.addEventListener('change', function (e) {
        const select = e.target.closest('.ticket-status-select');
        if (!select) return;

        const ticketId    = select.dataset.ticketId;
        const nuevoEstado = select.value;
        const prevEstado  = select.dataset.prev || 'abierto';
        const rowEl       = select.closest('tr');

        fetch('/HelpDesk_EQF/modules/ticket/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ticket_id=' + encodeURIComponent(ticketId) + '&estado=' + encodeURIComponent(nuevoEstado)
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

            // si se cierra/resuelve, lo removemos de mis tickets
            if (nuevoEstado === 'resuelto' || nuevoEstado === 'cerrado') {
                if (rowEl) {
                    if (myDT) myDT.row($(rowEl)).remove().draw(false);
                    else rowEl.remove();
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error interno al actualizar el estatus.');
            select.value = prevEstado;
        });
    });

    // ---- ASIGNAR (mueve a "Mis tickets" en vivo) ----
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-assign-ticket');
        if (!btn) return;

        const rowEl = btn.closest('tr');
        const ticketId = btn.dataset.ticketId || (rowEl && rowEl.getAttribute('data-ticket-id'));
        if (!ticketId || !rowEl) return;

        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = 'Asignando...';

        fetch('/HelpDesk_EQF/modules/ticket/assign.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ticket_id=' + encodeURIComponent(ticketId)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                alert(data.msg || 'No se pudo asignar.');
                btn.disabled = false;
                btn.textContent = original;
                return;
            }

            // Quitar de incoming
            if (incomingDT) incomingDT.row($(rowEl)).remove().draw(false);
            else rowEl.remove();

            // Construir fila para Mis tickets (con payload del assign)
            const t = data.ticket || {};
            const id = t.id || ticketId;
            const fecha = t.fecha_envio || '';
            const usuario = t.usuario || '';
            const problema = (t.problema_label || t.problema_raw || '');
            const prioridadRaw = (t.prioridad || 'media').toLowerCase();

            const priorityHtml = `<span class="priority-pill priority-${escapeHtml(prioridadRaw)}">${escapeHtml(prioridadRaw.charAt(0).toUpperCase()+prioridadRaw.slice(1))}</span>`;

            const statusSelect = `
              <select class="ticket-status-select status-en_proceso"
                      data-ticket-id="${id}"
                      data-prev="en_proceso">
                <option value="abierto">Abierto</option>
                <option value="en_proceso" selected>En proceso</option>
                <option value="cerrado">Cerrado</option>
              </select>
            `;

            const actions = `
              <div class="analyst-actions">
                <button type="button" class="btn-mini" onclick="openTicketDetail(${id})">Ver</button>
                <button type="button" class="btn-mini primary" onclick="openTicketChat(${id}, '${escapeHtml(usuario)}')">Chat</button>
              </div>
            `;

            const rowData = [
              `#${id}`,
              escapeHtml(fecha),
              escapeHtml(usuario),
              escapeHtml(problema),
              priorityHtml,
              statusSelect,
              actions
            ];

            if (myDT) {
              myDT.row.add(rowData).draw(false);
            } else {
              const tbody = document.querySelector('#myTicketsTable tbody');
              if (tbody) {
                const tr = document.createElement('tr');
                tr.setAttribute('data-ticket-id', id);
                tr.innerHTML = `
                  <td>#${escapeHtml(id)}</td>
                  <td>${escapeHtml(fecha)}</td>
                  <td>${escapeHtml(usuario)}</td>
                  <td>${escapeHtml(problema)}</td>
                  <td>${priorityHtml}</td>
                  <td>${statusSelect}</td>
                  <td>${actions}</td>
                `;
                tbody.prepend(tr);
              }
            }

            // KPI: baja abiertos, sube en proceso
            bumpKpi(-1, +1);

            showTicketToast('Ticket #' + id + ' asignado a ti.');
        })
        .catch(err => {
            console.error(err);
            alert('Error al asignar el ticket.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = original;
        });
    });

    // ---- POLLING NUEVOS TICKETS ----
    let lastTicketId = <?php echo (int)$maxIncomingId; ?>;

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function showDesktopNotification(ticket) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        new Notification('Nuevo ticket entrante (#' + ticket.id + ')', {
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
        return `<span class="priority-pill priority-${escapeHtml(p)}">${escapeHtml(label)}</span>`;
    }

    function addIncomingTicketRow(ticket) {
        if (!ticket || !ticket.id) return;

        // evita duplicados
        if (document.querySelector(`#incomingTable tr[data-ticket-id="${ticket.id}"]`)) return;

        const prioridadHtml = renderPriorityPill(ticket.prioridad || 'media');

        const actions = `
          <div class="analyst-actions">
            <button type="button" class="btn-mini" onclick="openTicketDetail(${ticket.id})">Ver</button>
            <button type="button" class="btn-mini primary btn-assign-ticket" data-ticket-id="${ticket.id}">Asignar</button>
          </div>
        `;

        const rowData = [
            ticket.id,
            escapeHtml(ticket.fecha || ''),
            escapeHtml(ticket.usuario || ''),
            escapeHtml(ticket.problema || ''),
            prioridadHtml,
            escapeHtml(ticket.descripcion || ''),
            actions
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
                <td>${rowData[4]}</td>
                <td>${rowData[5]}</td>
                <td>${rowData[6]}</td>
            `;
            tbody.prepend(tr);
        }

        // KPI: sube abiertos
        bumpKpi(+1, 0);
    }

    function pollNewTickets() {
        fetch('/HelpDesk_EQF/modules/ticket/check_new.php?last_id=' + lastTicketId)
            .then(r => r.json())
            .then(data => {
                if (!data || !data.new) return;

                lastTicketId = data.id;

                showTicketToast('Nuevo ticket #' + data.id + ' – ' + (data.problema || ''));
                showDesktopNotification(data);

                addIncomingTicketRow(data);
            })
            .catch(err => console.error('Error comprobando nuevos tickets:', err));
    }

    // ---- POLLING LIMPIEZA (quita tickets ya tomados por otro) ----
    // Esto evita que se queden en tu tabla como "fantasma".
    function cleanupIncomingTaken() {
      const rows = document.querySelectorAll('#incomingTable tbody tr[data-ticket-id]');
      if (!rows.length) return;

      const ids = [];
      rows.forEach(r => ids.push(r.getAttribute('data-ticket-id')));

      // endpoint rápido: revisa cuáles siguen disponibles
      fetch('/HelpDesk_EQF/modules/ticket/incoming_snapshot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ids=' + encodeURIComponent(ids.join(','))
      })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) return;
        const still = new Set((data.available_ids || []).map(x => String(x)));

        rows.forEach(r => {
          const id = r.getAttribute('data-ticket-id');
          if (!still.has(String(id))) {
            // ya lo tomó alguien → quítalo
            if (incomingDT) incomingDT.row($(r)).remove().draw(false);
            else r.remove();
          }
        });
      })
      .catch(()=>{});
    }

    pollNewTickets();
    setInterval(pollNewTickets, 10000);

    // cada 15s limpiamos entrantes
    setInterval(cleanupIncomingTaken, 10000);
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


function pollStaffUnread(){
  fetch('/HelpDesk_EQF/modules/ticket/staff_unread.php', {cache:'no-store'})
    .then(r=>r.json())
    .then(data=>{
      if (!data.ok) return;
      applyUnreadBadges(data.items);
    })
    .catch(()=>{});
}
setInterval(pollStaffUnread, 7000);
pollStaffUnread();

</script>

<script>
window.CURRENT_USER = {
  id: <?php echo (int)$_SESSION['user_id']; ?>,
  email: <?php echo json_encode($_SESSION['user_email'] ?? ''); ?>,
  sap: <?php echo json_encode($_SESSION['number_sap'] ?? ''); ?>,
  name: <?php echo json_encode($_SESSION['user_name'] ?? ''); ?>,
  last_name: <?php echo json_encode($_SESSION['user_last'] ?? ''); ?>,
  area: <?php echo json_encode($_SESSION['user_area'] ?? ''); ?>
};
</script>


<script>
(() => {
  'use strict';

  // Abrir modal desde sidebar 
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('#btnOpenCreateTicket');
    if (!btn) return;
    e.preventDefault();

    const backdrop = document.getElementById('createTicketBackdrop');
    if (!backdrop) return;

    backdrop.classList.add('show');
    backdrop.setAttribute('aria-hidden', 'false');

    // reset rápido al abrir
    const emailInput = document.getElementById('ct_email');
    if (emailInput) {
      emailInput.disabled = false;
      setTimeout(() => emailInput.focus(), 30);
    }

    const ok = document.getElementById('ct_email_ok');
    if (ok) ok.classList.remove('show');

    const msg = document.getElementById('ct_msg');
    if (msg) { msg.style.display = 'none'; msg.textContent = ''; }

    // área destino informativa (por regla: mi área)
    const areaDestino = document.getElementById('ct_area_destino');
    const me = (window.CURRENT_USER || {});
    if (areaDestino) areaDestino.value = me.area || '';
  });

  document.addEventListener('DOMContentLoaded', () => {
    const backdrop  = document.getElementById('createTicketBackdrop');
    const btnClose  = document.getElementById('btnCloseCreateTicket');
    const btnCancel = document.getElementById('btnCancelCreateTicket');

    const form      = document.getElementById('formCreateTicket');
    const msg       = document.getElementById('ct_msg');
    const btnSubmit = document.getElementById('btnSubmitCreateTicket');

    const emailInput  = document.getElementById('ct_email');
    const datalist    = document.getElementById('usersEmailList');
    const emailOk     = document.getElementById('ct_email_ok');

    const sap         = document.getElementById('ct_sap');
    const nombre      = document.getElementById('ct_nombre');
    const area        = document.getElementById('ct_area');
    const emailLocked = document.getElementById('ct_email_locked');

    const areaDestino = document.getElementById('ct_area_destino'); // input disabled (info)
    const ticketMi    = document.getElementById('ct_ticket_mi');

    const dtInicio    = document.getElementById('ct_inicio');
    const dtFin       = document.getElementById('ct_fin');

    const txtDesc      = document.getElementById('ct_descripcion');

    if (!backdrop || !form || !msg || !btnSubmit || !emailInput || !datalist || !areaDestino || !txtDesc) {
      console.error('Modal crear ticket: faltan elementos del DOM (IDs).');
      return;
    }

    form.setAttribute('novalidate','novalidate');

    let foundUserId = null;
    let timer = null;
    let isAutofilling = false;
    let topSuggestion = '';

    function isValidEmail(v){
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v||'').trim());
    }

    function showMsg(text){
      msg.style.display = 'block';
      msg.style.color = '#9a3412';
      msg.textContent = text || '';
    }

    function hideMsg(){
      msg.style.display = 'none';
      msg.textContent = '';
    }

    function showOk(on){
      if (!emailOk) return;
      emailOk.classList.toggle('show', !!on);
    }

    function closeModal(){
      backdrop.classList.remove('show');
      backdrop.setAttribute('aria-hidden','true');
      hideMsg();
      showOk(false);
    }

    function clearUser(){
      foundUserId = null;
      if (sap) sap.value = '';
      if (nombre) nombre.value = '';
      if (emailLocked) emailLocked.value = '';
      if (area) area.value = '';
      btnSubmit.disabled = true;
      showOk(false);
    }

    function setUser(u){
      foundUserId = u.id;
      if (sap) sap.value = u.number_sap ?? '';
      if (nombre) nombre.value = `${u.name ?? ''} ${u.last_name ?? ''}`.trim();
      if (area) area.value = u.area ?? '';
      if (emailLocked) emailLocked.value = u.email ?? '';
      btnSubmit.disabled = false;
      showOk(true);
    }

    function toDateTimeLocal(d){
      const pad = (n)=> String(n).padStart(2,'0');
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    const wrapOf = (el) => el ? (el.closest('.eqf-field') || el.closest('.eqf-grid-2') || el.parentElement) : null;
    const showEl = (el, show) => {
      const w = wrapOf(el);
      if (w) w.style.display = show ? '' : 'none';
    };

    btnClose?.addEventListener('click', closeModal);
    btnCancel?.addEventListener('click', closeModal);

    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && backdrop.classList.contains('show')) closeModal();
    });

    // Ticket para mí:
    // - Oculta inicio/fin (como pediste en chat anterior para ticket-mi)
    // - Deshabilita búsqueda
    // - Área destino visible: TI
    function applyTicketMiMode(on){
      hideMsg();
      datalist.innerHTML = '';
      topSuggestion = '';
      clearUser();
      showOk(false);

      if (on) {
        showEl(dtInicio, false);
        showEl(dtFin, false);

        emailInput.value = '';
        emailInput.disabled = true;

        areaDestino.value = 'TI';

        if (dtInicio) dtInicio.value = '';
        if (dtFin) dtFin.value = '';

        const me = (window.CURRENT_USER || {});
        if (me.id && me.email) {
          setUser({
            id: me.id,
            email: me.email,
            number_sap: me.sap,
            name: me.name,
            last_name: me.last_name || '',
            area: me.area
          });
          hideMsg();
        } else {
          showMsg('Faltan datos del analista (CURRENT_USER) para autollenado.');
        }

        txtDesc.focus();
      } else {
        showEl(dtInicio, true);
        showEl(dtFin, true);

        emailInput.disabled = false;

        // área destino vuelve a mi área
        const me = (window.CURRENT_USER || {});
        areaDestino.value = me.area || '';

        clearUser();
        emailInput.focus();

        // default inicio
        if (dtInicio && !dtInicio.value) dtInicio.value = toDateTimeLocal(new Date());
      }
    }

    if (ticketMi) {
      ticketMi.addEventListener('change', () => applyTicketMiMode(ticketMi.checked));
      ticketMi.checked = false;
      applyTicketMiMode(false);
    }

    function applyInlineSuggestion(typed, suggestion){
      if (!typed || !suggestion) return;

      const t = typed.toLowerCase();
      const s = suggestion.toLowerCase();

      if (!s.startsWith(t) || suggestion.length <= typed.length) return;
      if (document.activeElement !== emailInput) return;

      isAutofilling = true;
      emailInput.value = suggestion;
      try { emailInput.setSelectionRange(typed.length, suggestion.length); } catch(e) {}
      isAutofilling = false;
    }

    emailInput.addEventListener('input', () => {
      if (ticketMi && ticketMi.checked) return;
      if (isAutofilling) return;

      const typed = emailInput.value.trim();

      hideMsg();
      clearUser();
      showOk(false);

      clearTimeout(timer);
      timer = setTimeout(async () => {
        if (typed.length < 1) { datalist.innerHTML=''; topSuggestion=''; return; }

        try {
          const res = await fetch(`/HelpDesk_EQF/modules/dashboard/analyst/ajax/search_users.php?q=${encodeURIComponent(typed)}`, {cache:'no-store'});
          const raw = await res.text();
          let j = null; try { j = JSON.parse(raw); } catch(e) {}

          if (!res.ok || !j || !j.ok) {
            console.error('search_users error', res.status, raw);
            datalist.innerHTML = '';
            topSuggestion = '';
            return;
          }

          const items = j.items || [];
          topSuggestion = items[0]?.email || '';

          datalist.innerHTML = items.map(u =>
            `<option value="${u.email}">${u.email} — ${u.number_sap} — ${u.name} ${u.last_name}</option>`
          ).join('');

          applyInlineSuggestion(typed, topSuggestion);

        } catch(err) {
          console.error('autocomplete error', err);
        }
      }, 180);
    });

    emailInput.addEventListener('keydown', (e) => {
      if (ticketMi && ticketMi.checked) return;

      if (e.key === 'Tab' || e.key === 'ArrowRight') {
        const start = emailInput.selectionStart;
        const end   = emailInput.selectionEnd;

        if (typeof start === 'number' && typeof end === 'number' && end > start) {
          e.preventDefault();
          emailInput.setSelectionRange(end, end);
          emailInput.dispatchEvent(new Event('change'));
        }
      }
    });

    emailInput.addEventListener('change', async () => {
      if (ticketMi && ticketMi.checked) return;

      const email = emailInput.value.trim();
      if (!isValidEmail(email)) { showOk(false); return; }

      try {
        const res = await fetch(`/HelpDesk_EQF/modules/dashboard/analyst/ajax/get_user_by_email.php?email=${encodeURIComponent(email)}`, {cache:'no-store'});
        const raw = await res.text();
        let j = null; try { j = JSON.parse(raw); } catch(e) {}

        if (!res.ok || !j) {
          console.error('get_user_by_email error', res.status, raw);
          clearUser();
          showMsg('Error consultando usuario (revisa consola).');
          return;
        }

        if (!j.ok) {
          clearUser();
          showMsg(j.msg || 'Usuario no encontrado');
          return;
        }

        setUser(j.user);
        hideMsg();

        if (dtInicio && !dtInicio.value) dtInicio.value = toDateTimeLocal(new Date());

      } catch(err) {
        console.error(err);
        clearUser();
        showMsg('Error al buscar usuario.');
      }
    });

    // Guardar
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideMsg();

      const isMine = (ticketMi && ticketMi.checked);

      if (!foundUserId) { showMsg('Selecciona un usuario válido.'); return; }
      if (!txtDesc.value.trim()) { showMsg('La descripción es obligatoria.'); txtDesc.focus(); return; }

      btnSubmit.disabled = true;
      const prevTxt = btnSubmit.textContent;
      btnSubmit.textContent = 'Guardando...';

      const payload = new URLSearchParams();
      payload.append('user_id', String(foundUserId));
      payload.append('ticket_para_mi', isMine ? '1' : '0');
      payload.append('descripcion', txtDesc.value || '');
      payload.append('inicio', isMine ? '' : (dtInicio?.value || ''));
      payload.append('fin', isMine ? '' : (dtFin?.value || ''));

      try {
        const res = await fetch('/HelpDesk_EQF/modules/ticket/create_ticket_by_analyst.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body: payload.toString()
        });

        const raw = await res.text();
        let j = null; try { j = JSON.parse(raw); } catch(e) {}

        if (!res.ok || !j) {
          console.error('create_ticket_by_analyst error', res.status, raw);
          showMsg('No se pudo guardar (revisa consola).');
          return;
        }

        if (!j.ok) {
          showMsg(j.msg || 'No se pudo guardar.');
          return;
        }

        showOk(true);
        hideMsg();
        setTimeout(() => closeModal(), 350);

      } catch(err) {
        console.error(err);
        showMsg('Error al guardar ticket (revisa consola).');
      } finally {
        btnSubmit.textContent = prevTxt || 'Guardar';
        btnSubmit.disabled = false;
      }
    });

  });
})();
</script>

<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

</body>
</html>
