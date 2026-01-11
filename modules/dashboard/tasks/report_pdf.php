<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id'])) {
  header('Location:/HelpDesk_EQF/auth/login.php');
  exit;
}

$pdo = Database::getConnection();
$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
  http_response_code(400);
  exit('ID inválido');
}

/* =========================
   Helpers
========================= */
function h($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fmtDate($dt, string $fallback = '—'): string {
  if (!$dt) return $fallback;
  $ts = strtotime((string)$dt);
  if (!$ts) return $fallback;
  return date('d/m/Y', $ts);
}

function imgDataUri(string $absPath): string {
  if (!is_file($absPath)) return '';
  $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
  $mime = match ($ext) {
    'png' => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    default => 'application/octet-stream'
  };
  $data = base64_encode((string)file_get_contents($absPath));
  return "data:$mime;base64,$data";
}

/* =========================
   Datos tarea
========================= */
$stmt = $pdo->prepare("
  SELECT
    t.id, t.title, t.description, t.status, t.due_at, t.created_at,
    t.acknowledged_at, t.finished_at,
    t.notes,
    cp.label AS priority_name,
    CONCAT(ad.name,' ',ad.last_name) AS admin_name,
    CONCAT(an.name,' ',an.last_name) AS analyst_name,
    an.area AS analyst_area
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  JOIN users ad ON ad.id = t.created_by_admin_id
  JOIN users an ON an.id = t.assigned_to_user_id
  WHERE t.id = ?
  LIMIT 1
");
$stmt->execute([$taskId]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
  http_response_code(404);
  exit('No encontrada');
}

/* =========================
   Tiempo en minutos
========================= */
$mins = null;
if (!empty($t['acknowledged_at']) && !empty($t['finished_at'])) {
  $stmtD = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, ?, ?) AS m");
  $stmtD->execute([$t['acknowledged_at'], $t['finished_at']]);
  $mins = (int)($stmtD->fetchColumn() ?? 0);
}

$year  = (int)date('Y');
$fecha = date('d/m/Y');
$notes = trim((string)($t['notes'] ?? ''));

/* =========================
   Imágenes
========================= */
$projectRoot   = realpath(__DIR__ . '/../../..');
$logoPath      = $projectRoot . '/assets/img/Logo-334x98.png';
$watermarkPath = $projectRoot . '/assets/img/icon_desktop.png';
$chartPath     = $projectRoot . '/assets/img/chart_placeholder.png'; // opcional

$logoData      = imgDataUri($logoPath);
$watermarkData = imgDataUri($watermarkPath);
$chartData     = imgDataUri($chartPath);

/* Textos footer */
$footerLine1 = 'REPORTE GENERADO AUTOMATICAMENTE POR EL SISTEMA HELPDESK EQF';
$footerLine2 = 'TODOS LOS DERECHOS RESERVADOS ©'.$year.' EQUILIBRIO FARMACÉUTICO';

/* =========================
   HTML / CSS
========================= */
$html = '
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  /* ✅ CARTA: dejamos margen inferior pequeño para que el footer baje */
  @page { margin: 24px 34px 24px 34px; }

  body{
    font-family: Arial, Helvetica, sans-serif;
    font-size:12px;
    color:#111;
    font-weight:400;

    /* ✅ Reservamos el espacio “prohibido” de firmas+footer SIN usar margin-bottom */
    padding-bottom: 210px;
  }

  .b{ font-weight:700; }

  /* Header */
  table.header{ width:100%; border-collapse:collapse; }
  table.header td{ vertical-align: top; }
  .title{ font-size:16px; font-weight:700; margin:0; }

  .logoCell{ width:260px; text-align:right; vertical-align: bottom; }
  .logoImg{ width:240px; display:inline-block; vertical-align:bottom; margin-top:12px; }

  /* ✅ Baja el bloque de “Área/Fecha” + “Asignado/Analista” */
  table.metaRow{ width:100%; border-collapse:collapse; margin-top: 34px; }
  table.metaRow td{ vertical-align: top; }
  .meta{ font-size:12px; }
  .assignRight{ text-align:right; font-size:12px; line-height:1.7; }

  /* Línea roja */
  .redline{
    height:2px;
    background:#C8002D;
    margin:8px 0 10px;
  }

  /* ✅ Watermark (más tenue de verdad) */
  .wm{
    position: fixed;
    left: 50%;
    top: 55%;
    transform: translate(-50%, -50%);
    z-index: -1;
    width: 520px;
    text-align:center;
  }
  .wm img{
    width:520px;
    opacity: 0.08; /* aquí, no en el div */
  }

  /* Sección */
  .sectionTitle{
    font-size:14px;
    font-weight:700;
    margin:0 0 6px 0;
  }
  .blueBar{
    height:4px;
    background:#14378A;
    margin:4px 0 12px;
  }

  /* Tabla */
  table.detail{
    width:100%;
    border-collapse:collapse;
    table-layout: fixed;
  }
  table.detail thead{ display: table-header-group; }
  table.detail tbody{ display: table-row-group; }
  table.detail tr{ page-break-inside: avoid; }

  table.detail th, table.detail td{
    border:0.7px solid #6f6f6f;
    padding:10px 8px;
    vertical-align:top;
    font-size:12px;
  }

  table.detail th{
    background-color:#14378A !important;
    color:#ffffff !important;
    font-weight:700;
    font-size:14px;
    text-align:center;
  }

  /* Bloque inferior */
  table.bottomGrid{ width:100%; border-collapse:collapse; margin-top: 18px; }
  table.bottomGrid td{ vertical-align: top; }

  .notesBox{
    border:0.7px solid #cfcfcf;
    min-height:110px;
    padding:10px;
    font-size:12px;
    line-height:1.4;
    word-wrap: break-word;
  }
  .vred{ width:2px; background:#C8002D; }

  .chartBox{ height:130px; }
  .chartPh{
    border:0.7px solid #e5e5e5;
    height:130px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#777;
    font-size:12px;
  }
  .chartImg{ width:100%; height:130px; object-fit:contain; }

  /* ✅ Firmas a buena altura */
  .signFixed{
    position: fixed;
    left: 0;
    right: 0;
    bottom: 95px;   /* firmas arriba del footer */
    padding: 0 34px;
  }
  table.signRow{ width:100%; border-collapse:collapse; }
  table.signRow td{ width:50%; text-align:center; font-size:12px; }
  .signLine{ width:75%; margin:0 auto 8px; border-top:0.7px solid #222; height:1px; }

  /* ✅ Footer REAL hasta el borde inferior */
  .footerWrap{
    position: fixed;
    left:0; right:0;
    bottom: 0px; /* ahora sí baja */
    text-align:center;
    padding-bottom: 0px;
  }
  .foot1{ color:#bdbdbd; font-size:10px; margin:0; font-weight:700; }
  .foot2{ color:#111; font-size:10px; margin:6px 0 0; font-weight:700; }
</style>
</head>
<body>

'.($watermarkData ? '<div class="wm"><img src="'.$watermarkData.'" alt="watermark"></div>' : '').'

<table class="header">
  <tr>
    <td>
      <div class="title">REPORTE DE TAREA</div>
    </td>
    <td class="logoCell">
      '.($logoData ? '<img class="logoImg" src="'.$logoData.'" alt="logo">' : '').'
    </td>
  </tr>
</table>

<table class="metaRow">
  <tr>
    <td class="meta">
      <span class="b">Área:</span> '.h($t["analyst_area"] ?? "—").'
      &nbsp;&nbsp; | &nbsp;&nbsp;
      <span class="b">Fecha de reporte:</span> '.$fecha.'
    </td>
    <td class="assignRight">
      <span class="b">Asignado por:</span> '.h($t["admin_name"] ?? "—").'<br>
      <span class="b">Analista:</span> '.h($t["analyst_name"] ?? "—").'
    </td>
  </tr>
</table>

<div class="redline"></div>
<br>
<div class="sectionTitle">Detalle de la tarea</div>
<table class="detail">
  <thead>
    <tr>
      <th>Tarea</th>
      <th>Prioridad</th>
      <th>Fecha límite de entrega</th>
      <th>Fecha entregada</th>
      <th>Tiempo (min)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>'.h($t["title"] ?? "—").'</td>
      <td style="text-align:center;">'.h($t["priority_name"] ?? "—").'</td>
      <td style="text-align:center;">'.h(fmtDate($t["due_at"] ?? null)).'</td>
      <td style="text-align:center;">'.h(fmtDate($t["finished_at"] ?? null)).'</td>
      <td style="text-align:center;">'.($mins === null ? "—" : (string)$mins).'</td>
    </tr>
    <tr>
      <td colspan="5"><span class="b">Descripción:</span><br>'.nl2br(h($t["description"] ?? "—")).'</td>
    </tr>
  </tbody>
</table>

<table class="bottomGrid" cellpadding="0" cellspacing="0">
  <tr>
    <td style="width:48%; padding-right:12px;">
      <div class="sectionTitle">Observaciones y/o Notas</div>
      <div class="notesBox">'.($notes !== '' ? nl2br(h($notes)) : '').'</div>
    </td>

    <td class="vred"></td>

    <td style="width:49%; padding-left:12px;">
      <div class="sectionTitle">Tiempos</div>
      <div class="chartBox">
        '.($chartData
            ? '<img class="chartImg" src="'.$chartData.'" alt="chart">'
            : '<div class="chartPh">Gráfica (Power BI pendiente)</div>'
        ).'
      </div>
    </td>
  </tr>
</table>

<!-- Firmas -->
<div class="signFixed">
  <table class="signRow" cellpadding="0" cellspacing="0">
    <tr>
      <td>
        <div class="signLine"></div>
        <div class="b">'.h($t["admin_name"] ?? "—").'</div>
        <div style="font-size:11px;">Asignado por</div>
      </td>
      <td>
        <div class="signLine"></div>
        <div class="b">'.h($t["analyst_name"] ?? "—").'</div>
        <div style="font-size:11px;">Analista</div>
      </td>
    </tr>
  </table>
</div>

<!-- Footer -->
<div class="footerWrap">
  <p class="foot1">'.$footerLine1.'</p>
  <p class="foot2">'.$footerLine2.'</p>
</div>

</body>
</html>
';

/* =========================
   Dompdf
========================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="reporte_tarea_'.$taskId.'.pdf"');
echo $dompdf->output();
