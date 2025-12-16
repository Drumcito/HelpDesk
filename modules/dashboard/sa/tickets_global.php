<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'tickets_global';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if ($rol !== 1) {
    header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php');
    exit;
}

$pdo = Database::getConnection();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Detecta columnas de una tabla */
function tableColumns(PDO $pdo, string $table): array {
    $st = $pdo->prepare("DESCRIBE `$table`");
    $st->execute();
    return array_map(fn($r) => $r['Field'], $st->fetchAll(PDO::FETCH_ASSOC));
}

/** Devuelve el primer nombre de columna que exista dentro de candidatos */
function pickColumn(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
}

/** Obtiene valores distintos para selects */
function distinctValues(PDO $pdo, string $table, string $col, int $limit = 50): array {
    $sql = "SELECT DISTINCT `$col` AS v FROM `$table` WHERE `$col` IS NOT NULL AND `$col`<>'' ORDER BY `$col` ASC LIMIT $limit";
    $st = $pdo->prepare($sql);
    $st->execute();
    return array_values(array_filter(array_map(fn($r) => (string)$r['v'], $st->fetchAll(PDO::FETCH_ASSOC))));
}

$schemaWarning = [];

try {
    $ticketCols = tableColumns($pdo, 'tickets');
} catch (Throwable $e) {
    $ticketCols = [];
    $schemaWarning[] = "No se pudo leer la estructura de la tabla tickets.";
}

/* ===== Detectar columnas reales ===== */
$statusCol = pickColumn($ticketCols, ['estatus','status','estado','ticket_status','id_status','fk_status']);
$prioCol   = pickColumn($ticketCols, ['prioridad','priority','id_prioridad','fk_prioridad']);
$areaCol   = pickColumn($ticketCols, ['area','departamento','id_area','fk_area']);
$dateCol   = pickColumn($ticketCols, ['created_at','fecha_creacion','fecha','fecha_registro','createdAt','created']);
$titleCol  = pickColumn($ticketCols, ['problema','titulo','title','asunto','subject']);
$descCol   = pickColumn($ticketCols, ['descripcion','description','detalle','details']);
$userIdCol = pickColumn($ticketCols, ['user_id','id_user','fk_user','requester_id','solicitante_id']);

if (!$statusCol) $schemaWarning[] = "No encontré columna de estatus (estatus/status/estado...).";
if (!$dateCol)   $schemaWarning[] = "No encontré columna de fecha (created_at/fecha...).";
if (!$titleCol)  $schemaWarning[] = "No encontré columna de título/problema (problema/titulo/asunto...).";

/* ===== filtros ===== */
$q        = trim($_GET['q'] ?? '');
$status   = trim($_GET['status'] ?? '');
$prio     = trim($_GET['prio'] ?? '');
$area     = trim($_GET['area'] ?? '');
$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to'] ?? '');

/* ===== WHERE dinámico ===== */
$where = [];
$params = [];

/* búsqueda */
if ($q !== '') {
    $parts = [];
    $parts[] = "t.id LIKE ?";
    $params[] = "%$q%";

    if ($titleCol) { $parts[] = "t.`$titleCol` LIKE ?"; $params[] = "%$q%"; }
    if ($descCol)  { $parts[] = "t.`$descCol` LIKE ?";  $params[] = "%$q%"; }

    $where[] = "(" . implode(" OR ", $parts) . ")";
}

if ($status !== '' && $statusCol) { $where[] = "t.`$statusCol` = ?"; $params[] = $status; }
if ($prio   !== '' && $prioCol)   { $where[] = "t.`$prioCol` = ?";   $params[] = $prio; }
if ($area   !== '' && $areaCol)   { $where[] = "t.`$areaCol` = ?";   $params[] = $area; }

if ($dateFrom !== '' && $dateTo !== '' && $dateCol) {
    $where[] = "t.`$dateCol` BETWEEN ? AND ?";
    $params[] = $dateFrom . " 00:00:00";
    $params[] = $dateTo   . " 23:59:59";
}

/* ===== SELECT dinámico ===== */
$select = [
    "t.id",
    ($titleCol ? "t.`$titleCol` AS problema" : "NULL AS problema"),
    ($areaCol  ? "t.`$areaCol` AS area"      : "NULL AS area"),
    ($prioCol  ? "t.`$prioCol` AS prioridad" : "NULL AS prioridad"),
    ($statusCol? "t.`$statusCol` AS estatus" : "NULL AS estatus"),
    ($dateCol  ? "t.`$dateCol` AS created_at": "NULL AS created_at"),
    // agregamos descripción si existe (para el modal)
    ($descCol  ? "t.`$descCol` AS descripcion": "NULL AS descripcion"),
];

$joinUsers = "";
$userSelect = ", NULL AS user_name, NULL AS user_last, NULL AS user_sap, NULL AS user_email";

if ($userIdCol) {
    $joinUsers = " LEFT JOIN users u ON u.id = t.`$userIdCol`";
    $userSelect = ", u.name AS user_name, u.last_name AS user_last, u.number_sap AS user_sap, u.email AS user_email";
}

$sql = "SELECT " . implode(", ", $select) . $userSelect . " FROM tickets t" . $joinUsers;

if ($where) $sql .= " WHERE " . implode(" AND ", $where);

$orderBy = $dateCol ? "t.`$dateCol` DESC" : "t.id DESC";
$sql .= " ORDER BY $orderBy LIMIT 300";

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $tickets = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $tickets = [];
    $schemaWarning[] = "Error consultando tickets. Revisa nombres de columnas en tickets.";
}

/* ===== opciones para selects (desde BD si existen) ===== */
$areas = $areaCol ? distinctValues($pdo, 'tickets', $areaCol) : [];
$prioridades = $prioCol ? distinctValues($pdo, 'tickets', $prioCol) : [];
$estatuses = $statusCol ? distinctValues($pdo, 'tickets', $statusCol) : [];

/* fallback si tabla está vacía */
if (!$areas) $areas = ['TI','MKT','SAP','Corporativo','Sucursales'];
if (!$prioridades) $prioridades = ['baja','media','alta'];
if (!$estatuses) $estatuses = ['abierto','en_proceso','resuelto','cerrado'];
?>

<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<main class="user-main sa-panel">

    <header class="panel-top">
        <div class="panel-top-left">
            <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
            <p class="panel-subtitle">Tickets Global (Super Admin)</p>
        </div>
    </header>

    <?php if (!empty($schemaWarning)): ?>
        <section class="panel-card panel-alert" style="margin-bottom: 18px;">
            <strong>Notas del sistema:</strong>
            <ul class="panel-alert-list">
                <?php foreach ($schemaWarning as $w): ?>
                    <li><?= h($w) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="panel-card">
        <div class="panel-card-head">
            <h2>Filtros</h2>
            <span class="panel-muted">Máx. 300 resultados</span>
        </div>

        <form class="tickets-filter" method="GET">
            <input class="tickets-input" type="text" name="q" value="<?= h($q) ?>" placeholder="Buscar por ID / problema / descripción">

            <select class="tickets-select" name="status" <?= $statusCol ? '' : 'disabled' ?>>
                <option value=""><?= $statusCol ? 'Estatus (todos)' : 'Estatus (no disponible)' ?></option>
                <?php foreach ($estatuses as $s): ?>
                    <option value="<?= h($s) ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= h($s) ?></option>
                <?php endforeach; ?>
            </select>

            <select class="tickets-select" name="prio" <?= $prioCol ? '' : 'disabled' ?>>
                <option value=""><?= $prioCol ? 'Prioridad (todas)' : 'Prioridad (no disponible)' ?></option>
                <?php foreach ($prioridades as $p): ?>
                    <option value="<?= h($p) ?>" <?= ($prio === $p) ? 'selected' : '' ?>><?= h($p) ?></option>
                <?php endforeach; ?>
            </select>

            <select class="tickets-select" name="area" <?= $areaCol ? '' : 'disabled' ?>>
                <option value=""><?= $areaCol ? 'Área (todas)' : 'Área (no disponible)' ?></option>
                <?php foreach ($areas as $a): ?>
                    <option value="<?= h($a) ?>" <?= ($area === $a) ? 'selected' : '' ?>><?= h($a) ?></option>
                <?php endforeach; ?>
            </select>

            <input class="tickets-date" type="date" name="from" value="<?= h($dateFrom) ?>" <?= $dateCol ? '' : 'disabled' ?>>
            <input class="tickets-date" type="date" name="to" value="<?= h($dateTo) ?>" <?= $dateCol ? '' : 'disabled' ?>>

            <button class="btn-login" type="submit" style="width:auto;">Aplicar</button>
            <a class="panel-link" href="tickets_global.php" style="margin-left:10px;">Limpiar</a>
        </form>
    </section>

    <section class="panel-card" style="margin-top:18px;">
        <div class="panel-card-head">
            <h2>Tickets</h2>
            <span class="panel-muted"><?= (int)count($tickets) ?> ticket(s)</span>
        </div>

        <div class="panel-table-wrap">
            <table class="panel-table" id="ticketsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Problema</th>
                        <th>Área</th>
                        <th>Prioridad</th>
                        <th>Estatus</th>
                        <th>Solicitante</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$tickets): ?>
                    <tr><td colspan="7" class="panel-empty">Sin resultados.</td></tr>
                <?php else: foreach ($tickets as $t): ?>
                    <?php
                        $fullName = trim((string)($t['user_name'] ?? '') . ' ' . (string)($t['user_last'] ?? ''));
                        if ($fullName === '') $fullName = '—';
                    ?>
                    <tr class="ticket-row"
                        data-id="<?= (int)$t['id'] ?>"
                        data-problema="<?= h($t['problema'] ?? '') ?>"
                        data-area="<?= h($t['area'] ?? '') ?>"
                        data-prioridad="<?= h($t['prioridad'] ?? '') ?>"
                        data-estatus="<?= h($t['estatus'] ?? '') ?>"
                        data-descripcion="<?= h($t['descripcion'] ?? '') ?>"
                        data-user="<?= h($fullName) ?>"
                        data-sap="<?= h($t['user_sap'] ?? '') ?>"
                        data-email="<?= h($t['user_email'] ?? '') ?>"
                        data-fecha="<?= h($t['created_at'] ?? '') ?>"
                    >
                        <td>#<?= (int)$t['id'] ?></td>
                        <td><?= h($t['problema'] ?? '-') ?></td>
                        <td><?= h($t['area'] ?? '-') ?></td>
                        <td><?= h($t['prioridad'] ?? '-') ?></td>
                        <td><?= h($t['estatus'] ?? '-') ?></td>
                        <td><?= h($fullName) ?></td>
                        <td><?= h($t['created_at'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <p class="panel-help" style="margin-top:10px;">
            Tip: clic en un ticket para ver detalle.
        </p>
    </section>

    <!-- Modal detalle -->
    <div class="modal-backdrop" id="ticketDetail">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="td-title">Ticket</h3>
                <button type="button" class="modal-close" onclick="closeModal('ticketDetail')">✕</button>
            </div>

            <p class="modal-description" id="td-meta"></p>

            <div class="panel-kpi-row panel-kpi-row-compact" style="margin-top:10px;">
                <div class="panel-kpi">
                    <div class="panel-kpi-label">Área</div>
                    <div class="panel-kpi-value" id="td-area" style="font-size:1rem;"></div>
                </div>
                <div class="panel-kpi">
                    <div class="panel-kpi-label">Prioridad</div>
                    <div class="panel-kpi-value" id="td-prio" style="font-size:1rem;"></div>
                </div>
                <div class="panel-kpi">
                    <div class="panel-kpi-label">Estatus</div>
                    <div class="panel-kpi-value" id="td-status" style="font-size:1rem;"></div>
                </div>
            </div>

            <div style="margin-top:14px;">
                <div class="panel-mini-title">Descripción</div>
                <div class="panel-muted" id="td-desc" style="white-space:pre-wrap;"></div>
            </div>

            <div class="modal-actions" style="margin-top:14px;">
                <button type="button" class="btn-secondary" onclick="closeModal('ticketDetail')">Cerrar</button>
            </div>
        </div>
    </div>

</main>

<script>
function openModal(id){ document.getElementById(id)?.classList.add('show'); }
function closeModal(id){ document.getElementById(id)?.classList.remove('show'); }

document.querySelectorAll('.ticket-row').forEach(tr=>{
    tr.addEventListener('click', ()=>{
        const id = tr.dataset.id;

        document.getElementById('td-title').textContent = 'Ticket #' + id;

        const meta = [
            tr.dataset.user || '—',
            'SAP: ' + (tr.dataset.sap || '—'),
            (tr.dataset.email || '—'),
            (tr.dataset.fecha || '—')
        ].join(' • ');
        document.getElementById('td-meta').textContent = meta;

        document.getElementById('td-area').textContent = tr.dataset.area || '—';
        document.getElementById('td-prio').textContent = tr.dataset.prioridad || '—';
        document.getElementById('td-status').textContent = tr.dataset.estatus || '—';
        document.getElementById('td-desc').textContent = tr.dataset.descripcion || '—';

        openModal('ticketDetail');
    });
});
</script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
