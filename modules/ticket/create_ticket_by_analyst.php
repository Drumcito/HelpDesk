<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

$userIdReq = (int)($_SESSION['user_id'] ?? 0);

$targetUserId  = (int)($_POST['user_id'] ?? 0);
$ticketParaMi  = (int)($_POST['ticket_para_mi'] ?? 0);

$areaDestino   = trim((string)($_POST['area_destino'] ?? ''));
$problema      = trim((string)($_POST['problema'] ?? ''));
$prioridad     = trim((string)($_POST['prioridad'] ?? 'media'));
$descripcion   = trim((string)($_POST['descripcion'] ?? ''));

$inicio        = trim((string)($_POST['inicio'] ?? ''));
$fin           = trim((string)($_POST['fin'] ?? ''));

if ($ticketParaMi === 1) {
  $areaDestino = 'TI';
  $inicio = '';
  $fin = '';
}

if ($targetUserId <= 0) {
  echo json_encode(['ok'=>false,'msg'=>'Usuario inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($areaDestino === '') {
  echo json_encode(['ok'=>false,'msg'=>'Área destino requerida'], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($descripcion === '') {
  echo json_encode(['ok'=>false,'msg'=>'Descripción requerida'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // Traer datos del usuario desde users (SAP/nombre/email/area solicitante)
  $stmtU = $pdo->prepare("SELECT id, number_sap, name, last_name, email, area FROM users WHERE id = :id LIMIT 1");
  $stmtU->execute([':id'=>$targetUserId]);
  $u = $stmtU->fetch(PDO::FETCH_ASSOC);

  if (!$u) {
    echo json_encode(['ok'=>false,'msg'=>'No existe el usuario'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $sap    = $u['number_sap'] ?? '';
  $nombre = trim(($u['name'] ?? '').' '.($u['last_name'] ?? ''));
  $email  = $u['email'] ?? '';
  $areaSolicitante = $u['area'] ?? '';

  // Estado según reglas
  $estado = 'abierto';
  $fechaEnvio = date('Y-m-d H:i:s');
  $fechaResolucion = null;

  // Si viene inicio válido, úsalo como fecha_envio
  if ($inicio !== '') {
    // datetime-local: 2025-12-29T10:30
    $fechaEnvio = str_replace('T',' ', $inicio) . ':00';
  }

  // Si NO es ticket para mí y viene fin → cerrado
  if ($ticketParaMi === 0 && $fin !== '') {
    $estado = 'cerrado';
    $fechaResolucion = str_replace('T',' ', $fin) . ':00';
  }

  // Insert (ajusta columnas si tu tabla cambia)
  $stmt = $pdo->prepare("
    INSERT INTO tickets (sap, nombre, email, area, problema, descripcion, fecha_envio, estado, prioridad, asignado_a, fecha_resolucion)
    VALUES (:sap, :nombre, :email, :area, :problema, :descripcion, :fecha_envio, :estado, :prioridad, NULL, :fecha_resolucion)
  ");

  $stmt->execute([
    ':sap' => $sap,
    ':nombre' => $nombre,
    ':email' => $email,
    ':area' => $areaDestino,              // DESTINO
    ':problema' => $problema,
    ':descripcion' => $descripcion,
    ':fecha_envio' => $fechaEnvio,
    ':estado' => $estado,
    ':prioridad' => $prioridad,
    ':fecha_resolucion' => $fechaResolucion
  ]);

  $newId = (int)$pdo->lastInsertId();

  // (Paso 3) Aquí luego activaremos bandera de encuesta cuando esté cerrado

  echo json_encode(['ok'=>true,'id'=>$newId,'estado'=>$estado], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno','debug'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
