<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'sa';
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
    $cols = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[] = $r['Field'];
    return $cols;
}

/** Devuelve el primer nombre de columna que exista dentro de candidatos */
function pickColumn(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
}

/** Count helper */
function qCount(PDO $pdo, string $sql, array $params = []): int {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}

/** Normaliza estatus */
function normalizeStatus(string $v): string {
    $v = trim(mb_strtolower($v));
    $v = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $v);
    $v = preg_replace('/\s+/', ' ', $v);
    return $v;
}

/* =========================================================
   Detectar columnas tickets (para no romper por nombres)
========================================================= */
$schemaWarning = [];
$ticketCols = [];

try {
    $ticketCols = tableColumns($pdo, 'tickets');
} catch (Throwable $e) {
    $schemaWarning[] = "No se pudo leer la estructura de la tabla tickets.";
    $ticketCols = [];
}

$statusCol = pickColumn($ticketCols, ['estatus','status','estado','ticket_status','id_status','fk_status']);
$dateCol   = pickColumn($ticketCols, ['fecha_creacion','created_at','fecha','fecha_registro','createdAt','created']);
$areaCol   = pickColumn($ticketCols, ['area','id_area','fk_area','departamento']);
$prioCol   = pickColumn($ticketCols, ['prioridad','priority','id_prioridad','fk_prioridad']);
$titleCol  = pickColumn($ticketCols, ['problema','titulo','title','asunto','subject']);

/* =========================================================
   Resumen "al día de hoy"
========================================================= */
$today = new DateTime('now');
$todayStart = $today->format('Y-m-d 00:00:00');
$todayEnd   = $today->format('Y-m-d 23:59:59');
$todayLabel = $today->format('d/m/Y');

$createdToday = 0;
$closedToday  = 0;
$openGlobal = $inProgGlobal = $waitingGlobal = $closedGlobal = 0;
$totalGlobal = 0;

try {
    $totalGlobal = qCount($pdo, "SELECT COUNT(*) FROM tickets");

    // Global por estatus
    if ($statusCol) {
        $st = $pdo->prepare("SELECT `$statusCol` AS st, COUNT(*) AS c FROM tickets GROUP BY `$statusCol`");
        $st->execute();
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $stn = normalizeStatus((string)($r['st'] ?? ''));
            $cnt = (int)($r['c'] ?? 0);

            if (in_array($stn, ['abierto','open','nuevo','new'], true)) $openGlobal += $cnt;
            elseif (in_array($stn, ['en proceso','proceso','in progress','in_progress','progress'], true)) $inProgGlobal += $cnt;
            elseif (in_array($stn, ['en espera','espera','waiting','hold','on hold'], true)) $waitingGlobal += $cnt;
            elseif (in_array($stn, ['cerrado','cerrada','closed','resuelto','resuelta','done'], true)) $closedGlobal += $cnt;
        }
    } else {
        $schemaWarning[] = "No encontré columna de estatus (estatus/status/estado...).";
    }

    // Hoy
    if ($dateCol) {
        $createdToday = qCount(
            $pdo,
            "SELECT COUNT(*) FROM tickets WHERE `$dateCol` BETWEEN ? AND ?",
            [$todayStart, $todayEnd]
        );

        if ($statusCol) {
            $st2 = $pdo->prepare("
              SELECT `$statusCol` AS st, COUNT(*) AS c
              FROM tickets
              WHERE `$dateCol` BETWEEN ? AND ?
              GROUP BY `$statusCol`
            ");
            $st2->execute([$todayStart, $todayEnd]);
            foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $stn = normalizeStatus((string)($r['st'] ?? ''));
                $cnt = (int)($r['c'] ?? 0);
                if (in_array($stn, ['cerrado','cerrada','closed','resuelto','resuelta','done'], true)) {
                    $closedToday += $cnt;
                }
            }
        }
    } else {
        $schemaWarning[] = "No encontré columna de fecha en tickets (created_at/fecha...). Resumen diario limitado.";
    }
} catch (Throwable $e) {
    $schemaWarning[] = "Error consultando KPIs del sistema.";
}

$closePctToday = ($createdToday > 0) ? (int)round(($closedToday / $createdToday) * 100) : 0;
$closePctGlobal = ($totalGlobal > 0) ? (int)round(($closedGlobal / $totalGlobal) * 100) : 0;

/* =========================================================
   Recuperación de contraseña (pendientes)
========================================================= */
$recoveryReqs = [];
try {
    $st = $pdo->prepare("
      SELECT id, requester_email, created_at
      FROM password_recovery_requests
      WHERE status='PENDIENTE'
      ORDER BY created_at DESC
      LIMIT 20
    ");
    $st->execute();
    $recoveryReqs = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recoveryReqs = [];
}

/* =========================================================
   Tickets recientes (solo para referencia rápida)
========================================================= */
$recentTickets = [];
try {
    $orderBy = $dateCol ? "`$dateCol` DESC" : "id DESC";

    $select = ["id"];
    if ($titleCol)  $select[] = "`$titleCol` AS problema";
    if ($prioCol)   $select[] = "`$prioCol` AS prioridad";
    if ($areaCol)   $select[] = "`$areaCol` AS area";
    if ($statusCol) $select[] = "`$statusCol` AS estatus";
    if ($dateCol)   $select[] = "`$dateCol` AS fecha";

    $sql = "SELECT " . implode(", ", $select) . " FROM tickets ORDER BY $orderBy LIMIT 8";
    $stR = $pdo->prepare($sql);
    $stR->execute();
    $recentTickets = $stR->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recentTickets = [];
}
?>

<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<main class="user-main sa-panel">
    <!-- Header tipo “HelpDesk EQF / Panel ...” (igual estética) -->
    <header class="panel-top">
        <div class="panel-top-left">
            <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>

            <p class="panel-subtitle">Panel Super Admin</p>
        </div>
    </header>

    <?php if (!empty($schemaWarning)): ?>
        <section class="panel-card panel-alert">
            <strong>Notas del sistema:</strong>
            <ul class="panel-alert-list">
                <?php foreach ($schemaWarning as $w): ?>
                    <li><?= h($w) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <!-- CARD GRANDE: Resumen al día de hoy -->
    <section class="panel-card panel-card-wide">
        <div class="panel-card-head">
            <h2>Resumen al día de hoy</h2>
            <span class="panel-date"><?= h($todayLabel) ?></span>
        </div>

        <div class="panel-kpi-row">
            <div class="panel-kpi">
                <div class="panel-kpi-label">Tickets creados (hoy)</div>
                <div class="panel-kpi-value"><?= (int)$createdToday ?></div>
            </div>
            <div class="panel-kpi">
                <div class="panel-kpi-label">Tickets cerrados (hoy)</div>
                <div class="panel-kpi-value"><?= (int)$closedToday ?></div>
            </div>
            <div class="panel-kpi">
                <div class="panel-kpi-label">% cierre (hoy)</div>
                <div class="panel-kpi-value"><?= (int)$closePctToday ?>%</div>
            </div>
            <div class="panel-kpi">
                <div class="panel-kpi-label">Abiertos (global)</div>
                <div class="panel-kpi-value"><?= (int)$openGlobal ?></div>
            </div>
        </div>

        <div class="panel-mini-note">
            Global: total <?= (int)$totalGlobal ?> • cerrados <?= (int)$closedGlobal ?> (<?= (int)$closePctGlobal ?>%)
        </div>
    </section>

    <!-- GRID 2: izquierda solicitudes, derecha gráficas -->
    <section class="panel-grid-2">
        <!-- Solicitud de restablecer contraseña -->
        <section class="panel-card">
            <div class="panel-card-head">
                <h2>Solicitud de reestablecer contraseña</h2>
            </div>

            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Correo solicitante</th>
                            <th class="ta-right">Atendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recoveryReqs): ?>
                            <tr><td colspan="3" class="panel-empty">No hay solicitudes pendientes.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recoveryReqs as $r): ?>
                                <tr>
                                    <td><?= h($r['created_at']) ?></td>
                                    <td><?= h($r['requester_email']) ?></td>
                                    <td class="ta-right">
                                        <form method="POST" action="/HelpDesk_EQF/modules/dashboard/sa/recovery_attend.php" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button type="submit" class="chip-btn" title="Marcar atendido y enviar contraseña temporal">✅</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="panel-help">
                Al atender se envía correo con contraseña temporal <b>12345a</b> al solicitante.
            </p>
        </section>

        <!-- Gráficas generales (placeholder ahora, Power BI después) -->
        <section class="panel-card">
            <div class="panel-card-head">
                <h2>Gráficas generales</h2>
            </div>

            <div class="panel-chart-placeholder">
                <p class="panel-muted">
                    Aquí se integrará Power BI (global). Por ahora: resumen rápido.
                </p>

                <div class="panel-kpi-row panel-kpi-row-compact">
                    <div class="panel-kpi">
                        <div class="panel-kpi-label">En proceso</div>
                        <div class="panel-kpi-value"><?= (int)$inProgGlobal ?></div>
                    </div>
                    <div class="panel-kpi">
                        <div class="panel-kpi-label">En espera</div>
                        <div class="panel-kpi-value"><?= (int)$waitingGlobal ?></div>
                    </div>
                    <div class="panel-kpi">
                        <div class="panel-kpi-label">% cerrados</div>
                        <div class="panel-kpi-value"><?= (int)$closePctGlobal ?>%</div>
                    </div>
                </div>

                <div class="panel-divider"></div>

                <h3 class="panel-mini-title">Tickets recientes</h3>
                <?php if (!$recentTickets): ?>
                    <div class="panel-muted">Sin tickets recientes.</div>
                <?php else: ?>
                    <ul class="panel-mini-list">
                        <?php foreach ($recentTickets as $t): ?>
                            <li>
                                <span class="muted">#<?= (int)$t['id'] ?></span>
                                <span><?= h($t['problema'] ?? '-') ?></span>
                                <span class="muted">• <?= h($t['estatus'] ?? '-') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="panel-actions">
                    <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/sa/reports.php">Ver reportes →</a>
                </div>
            </div>
        </section>
    </section>
</main>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
