<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;

$pdo = Database::getConnection();
$areaAdmin = $_SESSION['user_area'] ?? '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Ticket inválido");
}

// Solo tickets del área del admin
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = :id AND area = :area LIMIT 1");
$stmt->execute([':id' => $id, ':area' => $areaAdmin]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
    die("No encontrado o sin permiso");
}

$html = '
<html><head><meta charset="utf-8">
<style>
  body{ font-family: DejaVu Sans, sans-serif; font-size:12px; }
  .h{ font-size:16px; font-weight:bold; margin-bottom:8px; }
  .box{ border:1px solid #ddd; padding:10px; border-radius:8px; margin-bottom:10px; }
  .k{ color:#555; width:160px; display:inline-block; }
</style>
</head><body>
<div class="h">Reporte de Ticket #'.(int)$t['id'].'</div>

<div class="box">
  <div><span class="k">Área:</span> '.htmlspecialchars($t['area']).'</div>
  <div><span class="k">SAP:</span> '.htmlspecialchars($t['sap'] ?? '').'</div>
  <div><span class="k">Usuario:</span> '.htmlspecialchars($t['nombre'] ?? '').'</div>
  <div><span class="k">Correo:</span> '.htmlspecialchars($t['email'] ?? '').'</div>
  <div><span class="k">Problema:</span> '.htmlspecialchars($t['problema'] ?? '').'</div>
  <div><span class="k">Prioridad:</span> '.htmlspecialchars($t['prioridad'] ?? '').'</div>
  <div><span class="k">Estado:</span> '.htmlspecialchars($t['estado'] ?? '').'</div>
  <div><span class="k">Fecha envío:</span> '.htmlspecialchars($t['fecha_envio'] ?? '').'</div>
</div>

<div class="box">
  <div class="h" style="font-size:13px;">Descripción</div>
  <div>'.nl2br(htmlspecialchars($t['descripcion'] ?? '')).'</div>
</div>

</body></html>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("ticket_{$id}.pdf", ["Attachment" => false]);
exit;
