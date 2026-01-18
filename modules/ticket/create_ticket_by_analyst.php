<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

try {
  $creatorId   = (int)$_SESSION['user_id'];
  $creatorArea = trim((string)($_SESSION['user_area'] ?? '')); // TI / SAP / MKT

  $userId = (int)($_POST['user_id'] ?? 0);
  $desc   = trim((string)($_POST['descripcion'] ?? ''));
  $inicio = trim((string)($_POST['inicio'] ?? ''));
  $fin    = trim((string)($_POST['fin'] ?? ''));
  $ticketParaMi = (int)($_POST['ticket_para_mi'] ?? 0) === 1;

  if ($userId <= 0) throw new Exception('Usuario inválido.');
  if ($desc === '') throw new Exception('La descripción es obligatoria.');

  // ====== FORZADOS ======
  $problema  = 'otro';
  $prioridad = 'alta';

  // ====== DESTINO POR REGLA ======
  if ($ticketParaMi) {
    $areaDestino = 'TI';
  } else {
    if ($creatorArea === '') throw new Exception('No se detectó el área del analista.');
    $areaDestino = $creatorArea;
  }

  // ====== TRAER DATOS DEL USUARIO (para llenar snapshot en tickets) ======
  $stU = $pdo->prepare("SELECT id, number_sap, name, last_name, email, area FROM users WHERE id = ? LIMIT 1");
  $stU->execute([$userId]);
  $u = $stU->fetch(PDO::FETCH_ASSOC);
  if (!$u) throw new Exception('Usuario no encontrado.');

  $sap    = (string)$u['number_sap'];
  $nombre = trim(($u['name'] ?? '').' '.($u['last_name'] ?? ''));
  $email  = (string)$u['email'];

  // ====== ESTADO segun fin ======
  $estado = 'abierto';
  $fechaResolucion = null;

  // Si NO es ticket para mi y capturan FIN -> se crea CERRADO
  if (!$ticketParaMi && $fin !== '') {
    $estado = 'cerrado';
    $fechaResolucion = $fin;
    if ($inicio === '') $inicio = date('Y-m-d H:i:s'); // fallback
  }

  // fecha_envio siempre ahora
  $fechaEnvio = date('Y-m-d H:i:s');
// Si llega la misma solicitud (mismo user_id + desc + destino) en ~5 segundos, regresa el último ticket.
$stDup = $pdo->prepare("
  SELECT id
  FROM tickets
  WHERE user_id = :uid
    AND area = :area
    AND descripcion = :desc
    AND fecha_envio >= (NOW() - INTERVAL 5 SECOND)
  ORDER BY id DESC
  LIMIT 1
");
$stDup->execute([':uid'=>$userId, ':area'=>$areaDestino, ':desc'=>$desc]);
$dup = $stDup->fetch(PDO::FETCH_ASSOC);
if ($dup && !empty($dup['id'])) {
  echo json_encode(['ok'=>true,'ticket_id'=>(int)$dup['id'], 'deduped'=>true], JSON_UNESCAPED_UNICODE);
  exit;
}
  $sql = "
  INSERT INTO tickets
    (user_id, sap, nombre, area, email, problema, prioridad, descripcion, fecha_envio, estado,
     asignado_a, fecha_asignacion,
     creado_por_ip, creado_por_navegador,
     fecha_primera_respuesta, fecha_resolucion,
     transferred_from_area, transferred_by, transferred_at)
  VALUES
    (:user_id, :sap, :nombre, :area, :email, :problema, :prioridad, :descripcion, :fecha_envio, :estado,
     :asignado_a, :fecha_asignacion,
     :ip, :ua,
     :fpr, :fres,
     :from_area, :by, :at)
";




  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':user_id' => $userId,
    ':sap' => $sap,
    ':nombre' => $nombre,
    ':area' => $areaDestino,  
    ':email' => $email,
    ':problema' => $problema,
    ':prioridad' => $prioridad,
    ':descripcion' => $desc,
    ':fecha_envio' => $fechaEnvio,
    ':estado' => $estado,
    ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ':fpr' => $inicio !== '' ? $inicio : null,
    ':fres' => $fechaResolucion,
    ':asignado_a' => null,
':fecha_asignacion' => null,

':from_area' => ($ticketParaMi ? $creatorArea : null),
':by'        => ($ticketParaMi ? $creatorId   : null),
':at'        => ($ticketParaMi ? date('Y-m-d H:i:s') : null),

  ]);

  $ticketId = (int)$pdo->lastInsertId();

  // ====== Si se creó CERRADO -> generar encuesta ======
  if ($estado === 'cerrado') {
    $token = bin2hex(random_bytes(32));

    $stF = $pdo->prepare("
      INSERT INTO ticket_feedback (ticket_id, user_id, token, created_at)
      VALUES (:tid, :uid, :tok, NOW())
    ");
    $stF->execute([
      ':tid' => $ticketId,
      ':uid' => $userId,
      ':tok' => $token
    ]);
  }

  echo json_encode(['ok'=>true,'ticket_id'=>$ticketId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Error interno','debug'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
