<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';

$activePage = 'reportes';
include __DIR__ . '/../../../template/sidebar.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 1) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ===============================
// Catálogos (para etiquetas bonitas)
// ===============================
$priorities = [];
$st = $pdo->query("SELECT code, label, sla_hours FROM catalog_priorities WHERE active=1 ORDER BY sort_order ASC, id ASC");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) $priorities[$row['code']] = $row;

$statuses = [];
$st = $pdo->query("SELECT code, label FROM catalog_status WHERE active=1 ORDER BY sort_order ASC, id ASC");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) $statuses[$row['code']] = $row['label'];

// Áreas fijas
$areas = ['TI' => 'TI', 'SAP' => 'SAP', 'MKT' => 'MKT'];

// ===============================
// Filtros (GET)
// ===============================
$fArea      = trim($_GET['area'] ?? '');
$fStatus    = trim($_GET['estado'] ?? '');
$fPriority  = trim($_GET['prioridad'] ?? '');
$fSearch    = trim($_GET['q'] ?? '');
$fFrom      = trim($_GET['from'] ?? ''); // YYYY-MM-DD
$fTo        = trim($_GET['to'] ?? '');   // YYYY-MM-DD

$where = [];
$params = [];

// area
if ($fArea !== '' && isset($areas[$fArea])) {
    $where[] = "t.area = :area";
    $params[':area'] = $fArea;
}
// estado
if ($fStatus !== '' && isset($statuses[$fStatus])) {
    $where[] = "t.estado = :estado";
    $params[':estado'] = $fStatus;
}
// prioridad
if ($fPriority !== '' && isset($priorities[$fPriority])) {
    $where[] = "t.prioridad = :prioridad";
    $params[':prioridad'] = $fPriority;
}
// búsqueda (sap / nombre / correo / id)
if ($fSearch !== '') {
    $where[] = "(t.sap LIKE :q OR t.nombre LIKE :q OR t.email LIKE :q OR CAST(t.id AS CHAR) LIKE :q)";
    $params[':q'] = '%' . $fSearch . '%';
}
// fechas (usa fecha_envio)
if ($fFrom !== '') {
    $where[] = "DATE(t.fecha_envio) >= :from";
    $params[':from'] = $fFrom;
}
if ($fTo !== '') {
    $where[] = "DATE(t.fecha_envio) <= :to";
    $params[':to'] = $fTo;
}

$whereSQL = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// ===============================
// Query principal
// ===============================
$sql = "
SELECT
  t.id,
  t.sap,
  t.nombre,
  t.email,
  t.area,
  t.problema,
  t.prioridad,
  t.descripcion,
  t.estado,
  t.fecha_envio,
  t.asignado_a,
  t.fecha_asignacion,
  t.fecha_primera_respuesta,
  t.fecha_resolucion
FROM tickets t
{$whereSQL}
ORDER BY t.id DESC
LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// KPIs rápidos
$kpiTotal = count($tickets);
$kpiAbiertos = 0;
$kpiCerrados = 0;

foreach ($tickets as $t) {
    if (($t['estado'] ?? '') === 'cerrado') $kpiCerrados++;
    if (($t['estado'] ?? '') !== 'cerrado') $kpiAbiertos++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes & KPI's | HelpDesk EQF</title>
  <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">
</head>

<body class="user-body">

  <!-- ✅ CLAVE: todo el contenido debe ir dentro de user-main para respetar sidebar -->
  <main class="user-main">
    <div class="sa-panel">

      <div class="panel-top panel-card panel-card-wide">
        <div class="panel-top-left">
          <span>HelpDesk <span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span></span>
          <div class="panel-subtitle">Reportes & KPI's (preparado para Power BI)</div>
        </div>
      </div>

      <div class="panel-card panel-card-wide">
        <div class="panel-card-head">
          <h2>Filtros</h2>
        </div>

        <form method="GET" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap:10px; align-items:end;">
          <div class="form-group">
            <label style="font-size:.82rem; font-weight:700; color:#4b5563;">Área</label>
            <select name="area" style="width:100%; border-radius:10px; border:1px solid #d1d5db; padding:8px 10px;">
              <option value="">Todas</option>
              <?php foreach ($areas as $k => $lbl): ?>
                <option value="<?=h($k)?>" <?= $fArea===$k?'selected':''; ?>><?=h($lbl)?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:.82rem; font-weight:700; color:#4b5563;">Estatus</label>
            <select name="estado" style="width:100%; border-radius:10px; border:1px solid #d1d5db; padding:8px 10px;">
              <option value="">Todos</option>
              <?php foreach ($statuses as $code => $lbl): ?>
                <option value="<?=h($code)?>" <?= $fStatus===$code?'selected':''; ?>><?=h($lbl)?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:.82rem; font-weight:700; color:#4b5563;">Prioridad</label>
            <select name="prioridad" style="width:100%; border-radius:10px; border:1px solid #d1d5db; padding:8px 10px;">
              <option value="">Todas</option>
              <?php foreach ($priorities as $code => $p): ?>
                <option value="<?=h($code)?>" <?= $fPriority===$code?'selected':''; ?>><?=h($p['label'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:.82rem; font-weight:700; color:#4b5563;">Desde</label>
            <input type="date" name="from" value="<?=h($fFrom)?>"
                   style="width:100%; border-radius:10px; border:1px solid #d1d5db; padding:8px 10px;">
          </div>

          <div class="form-group">
            <label style="font-size:.82rem; font-weight:700; color:#4b5563;">Hasta</label>
            <input type="date" name="to" value="<?=h($fTo)?>"
                   style="width:100%; border-radius:10px; border:1px solid #d1d5db; padding:8px 10px;">
          </div>

          <div style="display:flex; gap:8px; justify-content:flex-end;">
            <button type="submit" class="btn-login" style="width:auto;">Aplicar</button>
            <a class="btn-secondary" style="width:auto; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;"
               href="/HelpDesk_EQF/modules/dashboard/sa/reports.php">Limpiar</a>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label style="font-size:.82rem; font-weight:700; color:#4b5563;">Buscar</label>
            <input type="text" name="q" value="<?=h($fSearch)?>" placeholder="SAP / nombre / correo / ID"
                   style="width:100%; border-radius:10px; border:1px solid #d1d5db; padding:8px 10px;">
          </div>
        </form>
      </div>

      <div class="panel-card panel-card-wide" style="margin-top:18px;">
        <div class="panel-card-head">
          <h2>Resumen (según filtros)</h2>
          <div class="panel-muted"><?=date('d/m/Y')?></div>
        </div>

        <div class="panel-kpi-row panel-kpi-row-compact">
          <div class="panel-kpi">
            <div class="panel-kpi-label">Total (listado)</div>
            <div class="panel-kpi-value"><?= (int)$kpiTotal ?></div>
          </div>
          <div class="panel-kpi">
            <div class="panel-kpi-label">Abiertos (≠ cerrado)</div>
            <div class="panel-kpi-value"><?= (int)$kpiAbiertos ?></div>
          </div>
          <div class="panel-kpi">
            <div class="panel-kpi-label">Cerrados</div>
            <div class="panel-kpi-value"><?= (int)$kpiCerrados ?></div>
          </div>
          <div class="panel-kpi">
            <div class="panel-kpi-label">Power BI</div>
            <div class="panel-kpi-value" style="font-size:1rem; font-weight:800;">Próximamente</div>
          </div>
        </div>

        <div class="panel-mini-note">
          Aquí luego colocamos el embed de Power BI (iframe / reportId / workspace) y estos filtros se pueden mapear a slicers.
        </div>
      </div>

      <div class="panel-card panel-card-wide" style="margin-top:18px;">
        <div class="panel-card-head">
          <h2>Tickets (listado)</h2>
          <div class="panel-muted">Acciones: PDF (plantilla) / detalle</div>
        </div>

        <div class="panel-table-wrap">
          <table class="panel-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha envío</th>
                <th>SAP</th>
                <th>Nombre</th>
                <th>Área</th>
                <th>Problema</th>
                <th>Prioridad</th>
                <th>Estatus</th>
                <th class="ta-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$tickets): ?>
                <tr><td colspan="9" class="panel-empty">Sin resultados.</td></tr>
              <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                  <?php
                    $pCode = (string)($t['prioridad'] ?? '');
                    $sCode = (string)($t['estado'] ?? '');
                    $pLbl  = $priorities[$pCode]['label'] ?? $pCode;
                    $sLbl  = $statuses[$sCode] ?? $sCode;
                  ?>
                  <tr>
                    <td><?= (int)$t['id'] ?></td>
                    <td><?= h($t['fecha_envio']) ?></td>
                    <td><?= h($t['sap']) ?></td>
                    <td><?= h($t['nombre']) ?></td>
                    <td><?= h($t['area']) ?></td>
                    <td><?= h($t['problema']) ?></td>
                    <td><?= h($pLbl) ?></td>
                    <td><?= h($sLbl) ?></td>
                    <td class="ta-right" style="white-space:nowrap;">
                      <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/sa/reports_ticket_pdf.php?id=<?= (int)$t['id'] ?>" target="_blank">PDF</a>
                      <span class="panel-muted" style="margin:0 8px;">|</span>
                      <a class="panel-link" href="/HelpDesk_EQF/modules/dashboard/sa/tickets_global.php?ticket_id=<?= (int)$t['id'] ?>">Ver</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="panel-mini-note">
          Nota: el botón PDF por ahora puede ser plantilla/placeholder; después lo conectamos a “export del ticket + gráficas Power BI”.
        </div>
      </div>

    </div>
  </main>

  <script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>
</body>
</html>
