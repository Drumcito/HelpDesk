<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

include __DIR__ . '/../../../template/header.php';
$activePage = 'sla_global';
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

function tableColumns(PDO $pdo, string $table): array {
  $st = $pdo->prepare("DESCRIBE `$table`");
  $st->execute();
  return array_map(fn($r) => $r['Field'], $st->fetchAll(PDO::FETCH_ASSOC));
}
function pickColumn(array $cols, array $candidates): ?string {
  foreach ($candidates as $c) if (in_array($c, $cols, true)) return $c;
  return null;
}
function normalize(string $v): string {
  $v = trim(mb_strtolower($v));
  $v = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $v);
  $v = preg_replace('/\s+/', '_', $v);
  return $v;
}

/* ============================
   SLA (config rápido)
   - Ajusta aquí tus tiempos
============================ */
$slaHours = [
  'alta'  => 4,   // 4 horas
  'media' => 8,   // 8 horas
  'baja'  => 24,  // por si algún día la usas
];

/* ============================
   Detectar columnas tickets
============================ */
$schemaWarning = [];
try {
  $ticketCols = tableColumns($pdo, 'tickets');
} catch(Throwable $e) {
  $ticketCols = [];
  $schemaWarning[] = "No se pudo leer la estructura de la tabla tickets.";
}

$statusCol  = pickColumn($ticketCols, ['estatus','status','estado','ticket_status']);
$dateCol    = pickColumn($ticketCols, ['fecha_creacion','created_at','fecha','fecha_registro','createdAt','created']);
$closedCol  = pickColumn($ticketCols, ['fecha_cierre','closed_at','fecha_cerrado','fecha_resuelto','resolved_at','updated_at']);
$areaCol    = pickColumn($ticketCols, ['area_soporte','area','id_area','fk_area','departamento']);
$prioCol    = pickColumn($ticketCols, ['prioridad','priority','id_prioridad','fk_prioridad']);
$titleCol   = pickColumn($ticketCols, ['problema','titulo','title','asunto','subject']);

if (!$statusCol) $schemaWarning[] = "No encontré columna de estatus en tickets.";
if (!$dateCol)   $schemaWarning[] = "No encontré columna de fecha de creación en tickets.";
if (!$prioCol)   $schemaWarning[] = "No encontré columna de prioridad en tickets.";
if (!$areaCol)   $schemaWarning[] = "No encontré columna de área en tickets.";

/* ============================
   Helpers SLA SQL
============================ */
$hAlta  = (int)($slaHours['alta']  ?? 4);
$hMedia = (int)($slaHours['media'] ?? 8);
$hBaja  = (int)($slaHours['baja']  ?? 24);

// CASE para calcular vencimiento (due_at) por prioridad
// prioridad en BD: "alta" / "media" / "baja"
$dueExpr = "DATE_ADD(`$dateCol`, INTERVAL (
  CASE
    WHEN LOWER(`$prioCol`)='alta'  THEN $hAlta
    WHEN LOWER(`$prioCol`)='media' THEN $hMedia
    WHEN LOWER(`$prioCol`)='baja'  THEN $hBaja
    ELSE $hMedia
  END
) HOUR)";

// Definimos "cerrado" para SLA (cerrado/resuelto = ya no corre)
$closedStatuses = ["cerrado","resuelto"];
$closedInSql = "'" . implode("','", array_map('addslashes', $closedStatuses)) . "'";

$now = (new DateTime('now'))->format('Y-m-d H:i:s');
$todayStart = (new DateTime('now'))->format('Y-m-d 00:00:00');
$todayEnd   = (new DateTime('now'))->format('Y-m-d 23:59:59');

/* ============================
   KPIs
============================ */
$kpi = [
  'open_total' => 0,
  'open_breached' => 0,
  'open_ok' => 0,
  'closed_today' => 0,
  'closed_today_ok' => 0,
  'closed_today_breached' => 0,
  'close_sla_pct_today' => 0,
  'open_breach_pct' => 0,
];

$byArea = [];   // [area => ['open'=>, 'breached'=>]]
$byPrio = [];   // [prio => ['open'=>, 'breached'=>]]

$breachedTickets = [];

try {
  if ($statusCol && $dateCol && $prioCol) {

    // Abiertos (no cerrado/resuelto)
    $sqlOpen = "SELECT COUNT(*) FROM tickets
                WHERE LOWER(`$statusCol`) NOT IN ($closedInSql)";
    $kpi['open_total'] = (int)$pdo->query($sqlOpen)->fetchColumn();

    // Abiertos vencidos SLA
    $sqlBreached = "SELECT COUNT(*) FROM tickets
                    WHERE LOWER(`$statusCol`) NOT IN ($closedInSql)
                      AND $dueExpr < ?";
    $st = $pdo->prepare($sqlBreached);
    $st->execute([$now]);
    $kpi['open_breached'] = (int)$st->fetchColumn();

    $kpi['open_ok'] = max(0, $kpi['open_total'] - $kpi['open_breached']);
    $kpi['open_breach_pct'] = $kpi['open_total'] > 0
      ? (int)round(($kpi['open_breached'] / $kpi['open_total']) * 100)
      : 0;

    // Cerrados hoy (usamos fecha_cierre si existe; si no, usamos created_at para “hoy” como fallback)
    $closeDate = $closedCol ?: $dateCol;

    $sqlClosedToday = "SELECT COUNT(*) FROM tickets
                       WHERE LOWER(`$statusCol`) IN ($closedInSql)
                         AND `$closeDate` BETWEEN ? AND ?";
    $st = $pdo->prepare($sqlClosedToday);
    $st->execute([$todayStart, $todayEnd]);
    $kpi['closed_today'] = (int)$st->fetchColumn();

    // Cerrados hoy dentro SLA (close_date <= due_at)
    $sqlClosedTodayOk = "SELECT COUNT(*) FROM tickets
                         WHERE LOWER(`$statusCol`) IN ($closedInSql)
                           AND `$closeDate` BETWEEN ? AND ?
                           AND `$closeDate` <= $dueExpr";
    $st = $pdo->prepare($sqlClosedTodayOk);
    $st->execute([$todayStart, $todayEnd]);
    $kpi['closed_today_ok'] = (int)$st->fetchColumn();

    $kpi['closed_today_breached'] = max(0, $kpi['closed_today'] - $kpi['closed_today_ok']);
    $kpi['close_sla_pct_today'] = $kpi['closed_today'] > 0
      ? (int)round(($kpi['closed_today_ok'] / $kpi['closed_today']) * 100)
      : 0;

    // Por Área (abiertos + vencidos)
    if ($areaCol) {
      $sqlByArea = "
        SELECT `$areaCol` AS area,
               COUNT(*) AS open_total,
               SUM(CASE WHEN $dueExpr < ? THEN 1 ELSE 0 END) AS open_breached
        FROM tickets
        WHERE LOWER(`$statusCol`) NOT IN ($closedInSql)
        GROUP BY `$areaCol`
        ORDER BY open_breached DESC, open_total DESC
      ";
      $st = $pdo->prepare($sqlByArea);
      $st->execute([$now]);
      foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $a = (string)($r['area'] ?? '—');
        $byArea[$a] = [
          'open' => (int)$r['open_total'],
          'breached' => (int)$r['open_breached'],
        ];
      }
    }

    // Por Prioridad (abiertos + vencidos)
    $sqlByPrio = "
      SELECT `$prioCol` AS prio,
             COUNT(*) AS open_total,
             SUM(CASE WHEN $dueExpr < ? THEN 1 ELSE 0 END) AS open_breached
      FROM tickets
      WHERE LOWER(`$statusCol`) NOT IN ($closedInSql)
      GROUP BY `$prioCol`
      ORDER BY open_breached DESC, open_total DESC
    ";
    $st = $pdo->prepare($sqlByPrio);
    $st->execute([$now]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $p = normalize((string)($r['prio'] ?? 'media'));
      $byPrio[$p] = [
        'open' => (int)$r['open_total'],
        'breached' => (int)$r['open_breached'],
      ];
    }

    // Tabla: tickets vencidos (top 20)
    $sel = ["id"];
    if ($titleCol)  $sel[] = "`$titleCol` AS problema";
    if ($areaCol)   $sel[] = "`$areaCol` AS area";
    $sel[] = "`$prioCol` AS prioridad";
    $sel[] = "`$statusCol` AS estatus";
    $sel[] = "`$dateCol` AS creado";
    $sel[] = "$dueExpr AS vence";
    $sel[] = "TIMESTAMPDIFF(MINUTE, $dueExpr, ?) AS minutos_vencido";

    $sqlBreachedList = "
      SELECT ".implode(", ", $sel)."
      FROM tickets
      WHERE LOWER(`$statusCol`) NOT IN ($closedInSql)
        AND $dueExpr < ?
      ORDER BY $dueExpr ASC
      LIMIT 20
    ";
    $st = $pdo->prepare($sqlBreachedList);
    $st->execute([$now, $now]);
    $breachedTickets = $st->fetchAll(PDO::FETCH_ASSOC);

  }
} catch(Throwable $e) {
  $schemaWarning[] = "Error consultando SLA (revisa columnas/tabla tickets).";
}

?>
<link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

<main class="user-main sa-panel">
  <header class="panel-top">
    <div class="panel-top-left">
      <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
      <p class="panel-subtitle">SLA Global</p>
    </div>
  </header>

  <?php if (!empty($schemaWarning)): ?>
    <section class="panel-card panel-alert" style="margin-bottom:18px;">
      <strong>Notas del sistema:</strong>
      <ul class="panel-alert-list">
        <?php foreach ($schemaWarning as $w): ?>
          <li><?= h($w) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <!-- KPIs -->
  <section class="panel-card panel-card-wide">
    <div class="panel-card-head">
      <h2>Indicadores SLA (hoy)</h2>
      <span class="panel-date"><?= h((new DateTime('now'))->format('d/m/Y')) ?></span>
    </div>

    <div class="panel-kpi-row">
      <div class="panel-kpi">
        <div class="panel-kpi-label">Abiertos (global)</div>
        <div class="panel-kpi-value"><?= (int)$kpi['open_total'] ?></div>
      </div>

      <div class="panel-kpi">
        <div class="panel-kpi-label">SLA vencido (abiertos)</div>
        <div class="panel-kpi-value"><?= (int)$kpi['open_breached'] ?></div>
      </div>

      <div class="panel-kpi">
        <div class="panel-kpi-label">% vencidos (abiertos)</div>
        <div class="panel-kpi-value"><?= (int)$kpi['open_breach_pct'] ?>%</div>
      </div>

      <div class="panel-kpi">
        <div class="panel-kpi-label">Cerrados hoy (total)</div>
        <div class="panel-kpi-value"><?= (int)$kpi['closed_today'] ?></div>
      </div>

      <div class="panel-kpi">
        <div class="panel-kpi-label">Cerrados hoy dentro SLA</div>
        <div class="panel-kpi-value"><?= (int)$kpi['closed_today_ok'] ?></div>
      </div>

      <div class="panel-kpi">
        <div class="panel-kpi-label">% cumplimiento hoy (cerrados)</div>
        <div class="panel-kpi-value"><?= (int)$kpi['close_sla_pct_today'] ?>%</div>
      </div>
    </div>

    <div class="panel-mini-note">
      SLA actual: Alta <?= (int)$slaHours['alta'] ?>h • Media <?= (int)$slaHours['media'] ?>h • Baja <?= (int)$slaHours['baja'] ?>h
    </div>
  </section>

  <section class="panel-grid-2">

    <!-- Por Área -->
    <section class="panel-card">
      <div class="panel-card-head">
        <h2>Vencidos por área</h2>
      </div>

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Área</th>
              <th class="ta-right">Abiertos</th>
              <th class="ta-right">Vencidos</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$byArea): ?>
              <tr><td colspan="3" class="panel-empty">Sin datos.</td></tr>
            <?php else: ?>
              <?php foreach ($byArea as $a => $v): ?>
                <tr>
                  <td><?= h($a) ?></td>
                  <td class="ta-right"><?= (int)$v['open'] ?></td>
                  <td class="ta-right"><b><?= (int)$v['breached'] ?></b></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <p class="panel-help" style="margin-top:10px;color:#6b7280;font-size:.85rem;">
        *“Vencidos” = tickets abiertos cuya fecha/hora actual ya rebasó su vencimiento SLA.
      </p>
    </section>

    <!-- Por Prioridad -->
    <section class="panel-card">
      <div class="panel-card-head">
        <h2>Vencidos por prioridad</h2>
      </div>

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Prioridad</th>
              <th class="ta-right">Abiertos</th>
              <th class="ta-right">Vencidos</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$byPrio): ?>
              <tr><td colspan="3" class="panel-empty">Sin datos.</td></tr>
            <?php else: ?>
              <?php foreach ($byPrio as $p => $v): ?>
                <tr>
                  <td><?= h(ucfirst($p)) ?></td>
                  <td class="ta-right"><?= (int)$v['open'] ?></td>
                  <td class="ta-right"><b><?= (int)$v['breached'] ?></b></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </section>

  <!-- Tabla vencidos -->
  <section class="panel-card" style="margin-top:18px;">
    <div class="panel-card-head">
      <h2>Tickets con SLA vencido (abiertos)</h2>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Área</th>
            <th>Problema</th>
            <th>Prioridad</th>
            <th>Estatus</th>
            <th>Creado</th>
            <th>Vence</th>
            <th class="ta-right">Min. vencido</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$breachedTickets): ?>
            <tr><td colspan="8" class="panel-empty">No hay tickets vencidos 🎉</td></tr>
          <?php else: ?>
            <?php foreach ($breachedTickets as $t): ?>
              <tr>
                <td><?= (int)$t['id'] ?></td>
                <td><?= h($t['area'] ?? '—') ?></td>
                <td><?= h($t['problema'] ?? '—') ?></td>
                <td><?= h($t['prioridad'] ?? '—') ?></td>
                <td><?= h($t['estatus'] ?? '—') ?></td>
                <td><?= h($t['creado'] ?? '—') ?></td>
                <td><?= h($t['vence'] ?? '—') ?></td>
                <td class="ta-right"><b><?= (int)($t['minutos_vencido'] ?? 0) ?></b></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

<?php include __DIR__ . '/../../../template/footer.php'; ?>
