<?php
// /HelpDesk_EQF/modules/dashboard/user/tickets.php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  // si era AJAX, responde JSON limpio
  if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// audit_log.details = {"from":"en_proceso","to":"cerrado"}
function parseStatusFromDetails($details): array {
  $old = ''; $new = '';
  $raw = trim((string)$details);
  if ($raw === '') return [$old, $new];
  $j = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
    $old = (string)($j['from'] ?? '');
    $new = (string)($j['to'] ?? '');
  }
  return [$old, $new];
}

/* ==========================
   AJAX: devuelve detalle JSON (ANTES DE INCLUDES)
========================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
  header('Content-Type: application/json; charset=utf-8');

  $ticketId = (int)($_GET['id'] ?? 0);
  if ($ticketId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ticket (solo si es del usuario)
  $st = $pdo->prepare("
    SELECT t.*, u.name AS analyst_name, u.last_name AS analyst_last
    FROM tickets t
    LEFT JOIN users u ON u.id = t.asignado_a
    WHERE t.id = :id AND t.user_id = :uid
    LIMIT 1
  ");
  $st->execute([':id' => $ticketId, ':uid' => $userId]);
  $detail = $st->fetch(PDO::FETCH_ASSOC);

  if (!$detail) {
    echo json_encode(['ok' => false, 'msg' => 'No encontrado'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // status events (audit_log)
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
  $statusEvents = [];

  foreach ($rows as $r) {
    [$old, $new] = parseStatusFromDetails($r['details'] ?? '');
    $statusEvents[] = [
      'old_status' => $old,
      'new_status' => $new,
      'changed_at' => (string)($r['created_at'] ?? ''),
      'actor'      => trim((string)($r['actor_name'] ?? '')),
      'actor_area' => trim((string)($r['actor_area'] ?? '')),
    ];
  }

  // duración por estatus + total
  $durations = [];
  $totalSec  = 0;

  $startAt = (string)($detail['fecha_envio'] ?? '');

  // status inicial: si hay evento, usa old_status del primero; si no, usa estado actual o "abierto"
  $initialStatus = (string)($detail['estado'] ?? 'abierto');
  if (!empty($statusEvents) && !empty($statusEvents[0]['old_status'])) {
    $initialStatus = (string)$statusEvents[0]['old_status'];
  }

  $timeline = [];
  if ($startAt !== '') {
    $timeline[] = ['status' => $initialStatus, 'at' => $startAt];
  }

  foreach ($statusEvents as $ev) {
    $ns = trim((string)$ev['new_status']);
    $at = (string)$ev['changed_at'];
    if ($ns !== '' && $at !== '') $timeline[] = ['status' => $ns, 'at' => $at];
  }

  // fin: si el ticket llegó a cerrado usamos ese momento; si no, NOW()
  $endAt = '';
  for ($i = count($statusEvents) - 1; $i >= 0; $i--) {
    if (trim((string)$statusEvents[$i]['new_status']) === 'cerrado' && !empty($statusEvents[$i]['changed_at'])) {
      $endAt = (string)$statusEvents[$i]['changed_at'];
      break;
    }
  }
  if ($endAt === '') $endAt = date('Y-m-d H:i:s');

  if (!empty($timeline)) {
    $timeline[] = ['status' => '__END__', 'at' => $endAt];

    for ($i = 0; $i < count($timeline) - 1; $i++) {
      $stt = trim((string)$timeline[$i]['status']);
      if ($stt === '' || $stt === '__END__') continue;

      $a = strtotime((string)$timeline[$i]['at']);
      $b = strtotime((string)$timeline[$i + 1]['at']);
      if ($a && $b && $b >= $a) {
        $durations[$stt] = ($durations[$stt] ?? 0) + ($b - $a);
        $totalSec += ($b - $a);
      }
    }
  }

  // tiempo de asignación: MIN(created_at) de ticket_assignments_log
  $assignSec = null;
  $assignAt  = null;
  try {
    $as = $pdo->prepare("SELECT MIN(created_at) FROM ticket_assignments_log WHERE ticket_id = ?");
    $as->execute([$ticketId]);
    $firstAssign = (string)($as->fetchColumn() ?: '');
    if ($firstAssign !== '' && $startAt !== '') {
      $assignAt = $firstAssign;
      $a = strtotime($startAt);
      $b = strtotime($assignAt);
      if ($a && $b && $b >= $a) $assignSec = ($b - $a);
    }
  } catch (Throwable $e) {
    $assignSec = null;
    $assignAt  = null;
  }

  $analyst = trim((string)($detail['analyst_name'] ?? '') . ' ' . (string)($detail['analyst_last'] ?? ''));
  if ($analyst === '') $analyst = 'Sin asignar';

  echo json_encode([
    'ok' => true,
    'detail' => [
      'id'          => (int)$detail['id'],
      'problema'    => (string)($detail['problema'] ?? ''),
      'area'        => (string)($detail['area'] ?? ''),
      'prioridad'   => (string)($detail['prioridad'] ?? ''),
      'estado'      => (string)($detail['estado'] ?? ''),
      'fecha_envio' => (string)($detail['fecha_envio'] ?? ''),
      'analyst'     => $analyst,
      'no_jefe'     => !empty($detail['no_jefe']) ? 1 : 0,
      'nombre_jefe' => (string)($detail['nombre_jefe'] ?? ''),
    ],
    'durations'     => $durations,      // seconds por estado
    'total_sec'     => $totalSec,       // total seconds
    'assign_sec'    => $assignSec,      // seconds o null
    'assign_at'     => $assignAt,       // datetime o null
    'status_events' => $statusEvents,   // timeline
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ==========================
   YA DESPUÉS: INCLUDES (HTML)
========================== */
include __DIR__ . '/../../../template/header.php';
$activePage = 'tickets';
include __DIR__ . '/../../../template/sidebar.php';

/* ==========================
   LISTA: historial (cards)
   (puedes subir/bajar LIMIT)
========================== */
$stmt = $pdo->prepare("
  SELECT
    t.id, t.problema, t.area, t.prioridad, t.estado,
    t.fecha_envio,
    u.name AS analyst_name, u.last_name AS analyst_last
  FROM tickets t
  LEFT JOIN users u ON u.id = t.asignado_a
  WHERE t.user_id = :uid
  ORDER BY t.fecha_envio DESC
  LIMIT 500
");
$stmt->execute([':uid' => $userId]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis tickets | HelpDesk EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">

  <style>
    .tickets-grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:18px;
      align-items:start;
    }

    /* (1) No card blanca en historial */
    .tickets-left{ padding:0; margin:0; }

    .ticket-card{
      cursor:pointer;
      transition: transform .06s ease, box-shadow .12s ease, border-color .12s ease;
    }
    .ticket-card:hover{ transform: translateY(-1px); }

    /* (4) seleccionado rojo */
    .ticket-card.is-selected{
      border: 2px solid #c0182a !important;
      box-shadow: 0 10px 25px rgba(192,24,42,.18);
      background: rgba(192,24,42,.06);
    }

    /* (2) detalle solo al tamaño de contenido */
    .detail-card{
      align-self:start;
      height: fit-content;
    }

    .detail-row{ margin:0 0 6px 0; line-height:1.25rem; }

    .detail-meta{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin:10px 0 0 0;
      opacity:.9;
      font-weight:700;
    }
    .detail-chip{
      padding:6px 10px;
      border-radius:999px;
      background: rgba(110,28,92,.10);
      font-size:12px;
      font-weight:800;
    }

    .detail-hr{ margin:14px 0; opacity:.2; }
    .timeline-ul{ margin:0; padding-left:18px; }

    /* (2) pager tipo tabs */
    #ticketsPager{
      display:flex;
      gap:10px;
      align-items:center;
      margin-top:12px;
      flex-wrap:wrap;
    }
  </style>
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
    </header>

    <section class="user-main-content tickets-grid">

      <!-- IZQ: historial (sin card blanca) -->
      <div class="tickets-left">
        <div id="ticketsList" style="display:flex; flex-direction:column; gap:10px;">
          <?php if (empty($tickets)): ?>
            <p style="opacity:.75;">No hay tickets.</p>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <?php
                $id = (int)$t['id'];
                $analyst = trim((string)($t['analyst_name'] ?? '') . ' ' . (string)($t['analyst_last'] ?? ''));
                if ($analyst === '') $analyst = 'Sin asignar';
              ?>
              <div class="announcement announcement--info ticket-card"
                   data-ticket-id="<?php echo $id; ?>">
                <div class="announcement__top">
                  <div>
                    <p class="announcement__h">Ticket #<?php echo $id; ?> · <?php echo h($t['problema'] ?? ''); ?></p>
                    <p class="announcement__meta">
                      <?php echo h($t['fecha_envio'] ?? ''); ?>
                      <br>Estado: <?php echo h($t['estado'] ?? ''); ?>
                      <br>Atiende: <?php echo h($analyst); ?>
                    </p>
                  </div>
                  <span class="announcement__pill"><?php echo h(strtoupper((string)($t['prioridad'] ?? ''))); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- (2) pager estilo "pestañas" -->
        <?php if (!empty($tickets)): ?>
          <div id="ticketsPager"></div>
        <?php endif; ?>
      </div>

      <!-- DER: detalle (auto height) -->
      <div class="user-info-card detail-card" id="detailCard">
        <h2>Detalle</h2>
        <p style="opacity:.75; margin:10px 0 0 0;">Selecciona un ticket para ver la información.</p>
      </div>

    </section>

  </section>
</main>

<?php include __DIR__ . '/../../../template/footer.php'; ?>

<script>
function escapeHtml(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function fmtSeconds(sec){
  sec = Math.max(0, parseInt(sec||0,10));
  const d = Math.floor(sec/86400); sec%=86400;
  const h = Math.floor(sec/3600);  sec%=3600;
  const m = Math.floor(sec/60);

  if (d>0) return `${d}d ${h}h`;
  if (h>0) return `${h}h ${m}m`;
  if (m>0) return `${m}m`;
  return `${sec}s`;
}

function renderDetail(payload){
  const card = document.getElementById('detailCard');
  if (!card) return;

  if (!payload || !payload.ok){
    card.innerHTML = `<h2>Detalle</h2><p style="opacity:.75;">No se pudo cargar el detalle.</p>`;
    return;
  }

  const d = payload.detail || {};
  const durations = payload.durations || {};
  const total = payload.total_sec ?? 0;
  const assignSec = payload.assign_sec;
  const assignAt = payload.assign_at;
  const events = Array.isArray(payload.status_events) ? payload.status_events : [];

  const durLis = Object.keys(durations).length
    ? Object.entries(durations).map(([st,sec]) => `<li><strong>${escapeHtml(st)}:</strong> ${escapeHtml(fmtSeconds(sec))}</li>`).join('')
    : `<li style="opacity:.75;">Sin datos suficientes.</li>`;

  const timelineLis = [
    `<li><strong>CREADO</strong> · ${escapeHtml(d.fecha_envio||'')}</li>`,
    ...events.map(ev => {
      const a = (ev.actor ? ` · ${escapeHtml(ev.actor)}` : '');
      return `<li><strong>ESTATUS</strong>: ${escapeHtml(ev.old_status||'?')} → ${escapeHtml(ev.new_status||'?')} · ${escapeHtml(ev.changed_at||'')}${a}</li>`;
    })
  ].join('');

  const chips = `
    <div class="detail-meta">
      <span class="detail-chip">Total: ${escapeHtml(fmtSeconds(total))}</span>
      <span class="detail-chip">Asignación: ${assignSec == null ? '—' : escapeHtml(fmtSeconds(assignSec))}</span>
      ${assignAt ? `<span class="detail-chip">Asignado: ${escapeHtml(assignAt)}</span>` : ``}
    </div>
  `;

  card.innerHTML = `
    <h2>Detalle</h2>

    <p class="detail-row"><strong>Ticket #${escapeHtml(d.id)}</strong></p>
    <p class="detail-row"><strong>Problema:</strong> ${escapeHtml(d.problema||'')}</p>
    <p class="detail-row"><strong>Área:</strong> ${escapeHtml(d.area||'')}</p>
    <p class="detail-row"><strong>Prioridad:</strong> ${escapeHtml(d.prioridad||'')}</p>
    <p class="detail-row"><strong>Estado actual:</strong> ${escapeHtml(d.estado||'')}</p>
    <p class="detail-row"><strong>Atiende:</strong> ${escapeHtml(d.analyst||'')}</p>
    <p class="detail-row"><strong>Creado:</strong> ${escapeHtml(d.fecha_envio||'')}</p>

    ${d.no_jefe && d.nombre_jefe ? `<p class="detail-row"><strong>Solicitado para:</strong> ${escapeHtml(d.nombre_jefe)}</p>` : ``}

    ${chips}

    <hr class="detail-hr">

    <h3 style="margin:0 0 10px 0;">Tiempo por estatus</h3>
    <ul class="timeline-ul">${durLis}</ul>

    <hr class="detail-hr">

    <h3 style="margin:0 0 10px 0;">Timeline</h3>
    <ul class="timeline-ul">${timelineLis}</ul>
  `;
}

async function loadTicketDetail(id){
  const url = `/HelpDesk_EQF/modules/dashboard/user/tickets.php?ajax=1&id=${encodeURIComponent(id)}&_=${Date.now()}`;

  const card = document.getElementById('detailCard');
  if (card){
    card.innerHTML = `<h2>Detalle</h2><p style="opacity:.75;">Cargando…</p>`;
  }

  try{
    const r = await fetch(url, { cache:'no-store', headers:{'Accept':'application/json'} });
    const j = await r.json();
    renderDetail(j);
  }catch(e){
    renderDetail({ok:false});
  }
}

// click: sin refresh, solo pinta info + rojo seleccionado
document.addEventListener('click', (e) => {
  const el = e.target.closest('.ticket-card[data-ticket-id]');
  if (!el) return;

  const id = parseInt(el.getAttribute('data-ticket-id') || '0', 10);
  if (!id) return;

  document.querySelectorAll('.ticket-card.is-selected').forEach(x => x.classList.remove('is-selected'));
  el.classList.add('is-selected');

  loadTicketDetail(id);
});

/* ==========================
   (2) Mostrar solo 5 y tabs que se mueven
========================== */
const PAGE_SIZE = 5;
let currentPage = 1;

function getCards(){
  return Array.from(document.querySelectorAll('.ticket-card[data-ticket-id]'));
}

function renderPager(){
  const pager = document.getElementById('ticketsPager');
  if (!pager) return;

  const cards = getCards();
  const totalPages = Math.max(1, Math.ceil(cards.length / PAGE_SIZE));

  // clamp
  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  // show/hide
  const start = (currentPage - 1) * PAGE_SIZE;
  const end = start + PAGE_SIZE;

  cards.forEach((c, idx) => {
    c.style.display = (idx >= start && idx < end) ? '' : 'none';
  });

  // tabs window
  const windowSize = 5;
  let wStart = Math.max(1, currentPage - Math.floor(windowSize/2));
  let wEnd = Math.min(totalPages, wStart + windowSize - 1);
  wStart = Math.max(1, wEnd - windowSize + 1);

  let html = `<button class="task-cancel-link" ${currentPage===1?'disabled':''} data-page-prev>←</button>`;
  for (let p=wStart; p<=wEnd; p++){
    const active = p === currentPage;
    html += `
      <button class="${active ? 'task-link-blue' : 'task-cancel-link'}"
              data-page="${p}"
              style="min-width:44px;">
        ${p}
      </button>
    `;
  }
  html += `<button class="task-cancel-link" ${currentPage===totalPages?'disabled':''} data-page-next>→</button>`;
  html += `<span style="opacity:.7; font-weight:800;">${currentPage}/${totalPages}</span>`;

  pager.innerHTML = html;
}

document.addEventListener('click', (e) => {
  const prev = e.target.closest('[data-page-prev]');
  const next = e.target.closest('[data-page-next]');
  const tab  = e.target.closest('[data-page]');

  if (prev){ currentPage--; renderPager(); }
  if (next){ currentPage++; renderPager(); }
  if (tab){
    currentPage = parseInt(tab.getAttribute('data-page'),10) || 1;
    renderPager();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  // pager (muestra solo 5)
  renderPager();

  // auto-seleccionar el primer ticket visible
  const cards = getCards();
  if (cards.length){
    const firstVisible = cards.find(c => c.style.display !== 'none') || cards[0];
    firstVisible.classList.add('is-selected');
    const id = parseInt(firstVisible.getAttribute('data-ticket-id') || '0', 10);
    if (id) loadTicketDetail(id);
  }
});
</script>

</body>
</html>
