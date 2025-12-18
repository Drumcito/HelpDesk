<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die('Ticket inválido'); }

$autoload = __DIR__ . '/../../../vendor/autoload.php';
if (!file_exists($autoload)) {
  die('No está instalado Dompdf. Ejecuta: composer require dompdf/dompdf');
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = Database::getConnection();

// Ticket
$st = $pdo->prepare("
  SELECT id, sap, nombre, email, area, problema, prioridad, descripcion, estado, fecha_envio,
         creado_por_ip, creado_por_navegador
  FROM tickets
  WHERE id = ?
  LIMIT 1
");
$st->execute([$id]);
$t = $st->fetch(PDO::FETCH_ASSOC);
if (!$t) die('No existe el ticket');

// Catálogos (labels)
$priorities = [];
$st2 = $pdo->query("SELECT code, label, sla_hours FROM catalog_priorities WHERE active=1");
foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $r) $priorities[$r['code']] = $r;

$statuses = [];
$st3 = $pdo->query("SELECT code, label FROM catalog_status WHERE active=1");
foreach ($st3->fetchAll(PDO::FETCH_ASSOC) as $r) $statuses[$r['code']] = $r['label'];

$priorityLabel = $priorities[$t['prioridad']]['label'] ?? $t['prioridad'];
$statusLabel   = $statuses[$t['estado']] ?? $t['estado'];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today = date('d/m/Y H:i');
$filename = 'Ticket_'.$t['id'].'_'.$t['sap'].'.pdf';

// HTML del PDF (lo puedes “tunar” después para parecerse más a tu ticket_20.pdf)
$html = '
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  body{ font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
  .head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
  .brand{ font-size:16px; font-weight:800; }
  .muted{ color:#6b7280; font-size:11px; }
  .card{ border:1px solid #e5e7eb; border-radius:12px; padding:14px; }
  table{ width:100%; border-collapse:collapse; margin-top:10px; }
  td{ padding:8px; border:1px solid #e5e7eb; vertical-align:top; }
  .k{ width:160px; background:#f9fafb; font-weight:700; }
  .title{ font-size:14px; font-weight:800; margin:0 0 6px; }
  .pill{ display:inline-block; padding:4px 10px; border-radius:999px; font-weight:700; font-size:11px; border:1px solid #e5e7eb; }
</style>
</head>
<body>

  <div class="head">
    <div>
      <div class="brand">HelpDesk EQF</div>
      <div class="muted">Reporte de Ticket • Generado: '.$today.'</div>
    </div>
    <div class="muted">Ticket #'.(int)$t['id'].'</div>
  </div>

  <div class="card">
    <p class="title">Información del ticket</p>

    <table>
      <tr>
        <td class="k"># SAP</td><td>'.h($t['sap']).'</td>
        <td class="k">Área</td><td>'.h($t['area']).'</td>
      </tr>
      <tr>
        <td class="k">Nombre</td><td>'.h($t['nombre']).'</td>
        <td class="k">Correo</td><td>'.h($t['email']).'</td>
      </tr>
      <tr>
        <td class="k">Problema</td><td>'.h($t['problema']).'</td>
        <td class="k">Fecha envío</td><td>'.h($t['fecha_envio']).'</td>
      </tr>
      <tr>
        <td class="k">Prioridad</td><td><span class="pill">'.h($priorityLabel).'</span></td>
        <td class="k">Estatus</td><td><span class="pill">'.h($statusLabel).'</span></td>
      </tr>
      <tr>
        <td class="k">Descripción</td><td colspan="3">'.nl2br(h($t['descripcion'])).'</td>
      </tr>
      <tr>
        <td class="k">IP</td><td>'.h($t['creado_por_ip']).'</td>
        <td class="k">Navegador</td><td>'.h($t['creado_por_navegador']).'</td>
      </tr>
    </table>

    <div class="muted" style="margin-top:10px;">
      *Este PDF es base. Luego aquí insertamos las gráficas/export de Power BI.
    </div>
  </div>

</body>
</html>
';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo $dompdf->output();
exit;
