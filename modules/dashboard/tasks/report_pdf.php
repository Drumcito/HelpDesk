<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

require_once __DIR__ . '/../../../vendor/autoload.php';
use Dompdf\Dompdf;

if (!isset($_SESSION['user_id'])) { header('Location:/HelpDesk_EQF/auth/login.php'); exit; }

$pdo = Database::getConnection();
$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) { http_response_code(400); exit('ID inválido'); }

// Traer datos (ajusta columnas si tu users usa name/last_name)
$stmt = $pdo->prepare("
  SELECT
    t.id, t.title, t.description, t.status, t.due_at, t.created_at,
    t.acknowledged_at, t.finished_at,
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
if (!$t) { http_response_code(404); exit('No encontrada'); }

// Archivos
$stmtF = $pdo->prepare("
  SELECT file_type, original_name, stored_name
  FROM task_files
  WHERE task_id = ? AND is_deleted = 0
  ORDER BY created_at ASC
");
$stmtF->execute([$taskId]);
$files = $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];

$adminFiles = array_values(array_filter($files, fn($x)=>($x['file_type']??'')==='ADMIN_ATTACHMENT'));
$evidFiles  = array_values(array_filter($files, fn($x)=>($x['file_type']??'')==='EVIDENCE'));

$mins = null;
if (!empty($t['acknowledged_at']) && !empty($t['finished_at'])) {
  $stmtD = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, ?, ?) AS m");
  $stmtD->execute([$t['acknowledged_at'], $t['finished_at']]);
  $mins = (int)($stmtD->fetchColumn() ?? 0);
}

$year = (int)date('Y');
$fecha = date('d \d\e F \d\e Y');

$html = '
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  body{ font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111; }
  .top{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
  .title{ font-size:22px; font-weight:800; margin:10px 0 0 0; }
  .sub{ margin-top:4px; font-size:12px; }
  .hr{ height:2px; background:#0b3a55; margin:10px 0 14px; }
  h3{ margin:0 0 6px 0; font-size:14px; }
  .box{ border-top:2px solid #0b3a55; padding-top:10px; margin-top:10px; }
  ul{ margin:6px 0 0 16px; padding:0; }
  .grid2{ display:flex; gap:24px; }
  .col{ flex:1; }
  table{ width:100%; border-collapse:collapse; margin-top:6px; }
  th,td{ border:1px solid #cfd8df; padding:8px; vertical-align:top; }
  th{ background:#0b3a55; color:#fff; text-align:left; }
  .note{ height:70px; border:1px solid #cfd8df; margin-top:8px; }
  .sign{ display:flex; gap:60px; margin-top:26px; justify-content:center; }
  .line{ width:240px; border-top:1px solid #777; text-align:center; padding-top:6px; color:#444; }
  .footer{ margin-top:14px; font-size:9px; color:#444; text-align:center; }
</style>
</head>
<body>

<div class="top">
  <div>
    <div class="title">REPORTE DE ACTIVIDADES: ANALISTA</div>
    <div class="sub"><b>Departamento:</b> '.htmlspecialchars($t["analyst_area"] ?? "—").' &nbsp;|&nbsp; <b>Fecha:</b> '.$fecha.'</div>
  </div>
  <div style="text-align:right;">
    <div style="font-weight:700;">Equilibrio Farmacéutico</div>
  </div>
</div>

<div class="hr"></div>

<div class="box">
  <h3>1. Resumen del Colaborador</h3>
  <ul>
    <li><b>Nombre:</b> '.htmlspecialchars($t["analyst_name"] ?? "—").'</li>
    <li><b>Asignado por:</b> '.htmlspecialchars($t["admin_name"] ?? "—").'</li>
    <li><b>Periodo:</b> '.htmlspecialchars(substr((string)$t["created_at"],0,10)).' a '.htmlspecialchars(substr((string)$t["finished_at"],0,10)).'</li>
  </ul>
</div>

<div class="box">
  <h3>2. Detalle de Tareas Asignadas</h3>
  <table>
    <thead>
      <tr>
        <th>Tarea</th>
        <th>Prioridad</th>
        <th>Entrega</th>
        <th>Estatus</th>
        <th>Tiempo (min)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>'.htmlspecialchars($t["title"]).'<br><span style="color:#555;">'.nl2br(htmlspecialchars($t["description"])).'</span></td>
        <td>'.htmlspecialchars($t["priority_name"] ?? "—").'</td>
        <td>'.htmlspecialchars($t["due_at"] ?? "—").'</td>
        <td>'.htmlspecialchars($t["status"] ?? "—").'</td>
        <td>'.($mins === null ? "—" : (string)$mins).'</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="box">
  <h3>ARCHIVOS ADJUNTOS</h3>
  <div class="grid2">
    <div class="col">
      <b>ARCHIVOS DE REFERENCIA</b>
      <ul>';
        if(empty($adminFiles)){ $html .= '<li>Sin archivos</li>'; }
        else foreach($adminFiles as $f){ $html .= '<li>'.htmlspecialchars($f["original_name"]).'</li>'; }
$html .= '</ul>
    </div>
    <div class="col">
      <b>EVIDENCIA DE ENTREGA</b>
      <ul>';
        if(empty($evidFiles)){ $html .= '<li>Sin evidencias</li>'; }
        else foreach($evidFiles as $f){ $html .= '<li>'.htmlspecialchars($f["original_name"]).'</li>'; }
$html .= '</ul>
    </div>
  </div>
</div>

<div class="box">
  <h3>4. Observaciones y Notas</h3>
  <div class="note"></div>
</div>

<div class="sign">
  <div class="line">Nombre del Analista</div>
  <div class="line">Nombre del Responsable</div>
</div>

<div class="footer">
  REPORTE GENERADO AUTOMATICAMENTE POR EL SISTEMA HELPDESK EQF.<br> TODOS LOS DERECHOS RESERVADOS ©'.$year.' EQUILIBRIO FARMACEUTICO
</div>

</body>
</html>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="reporte_tarea_'.$taskId.'.pdf"');
echo $dompdf->output();
