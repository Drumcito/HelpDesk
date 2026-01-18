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
$userId = (int)$_SESSION['user_id'];
$userArea = (string)($_SESSION['user_area'] ?? '');

try {
  $stmt = $pdo->prepare("
    SELECT
      t.id, t.sap, t.nombre, t.email,
      t.problema AS problema_raw,
      COALESCE(cp1.label, cp2.label, t.problema) AS problema_label,
      t.descripcion, t.fecha_envio, t.estado, t.prioridad,

      req.id AS requester_id,
      req.rol AS requester_rol,
      CONCAT(COALESCE(req.name,''),' ',COALESCE(req.last_name,'')) AS requester_name

    FROM tickets t
    LEFT JOIN catalog_problems cp1 ON cp1.id = t.problema
    LEFT JOIN catalog_problems cp2 ON cp2.code = t.problema
    LEFT JOIN users req ON req.id = t.user_id

    WHERE t.area = :area
      AND t.asignado_a = :uid
      AND t.estado IN ('abierto','en_proceso','soporte')
    ORDER BY t.fecha_envio DESC
    LIMIT 50
  ");
  $stmt->execute([':area'=>$userArea, ':uid'=>$userId]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  $sig = md5(json_encode(array_map(fn($x)=>[
    $x['id'] ?? null,
    $x['estado'] ?? null,
    $x['prioridad'] ?? null,
    $x['problema_label'] ?? null,
    $x['requester_id'] ?? null,
    $x['requester_rol'] ?? null,
  ], $items), JSON_UNESCAPED_UNICODE));

  echo json_encode(['ok'=>true,'items'=>$items,'signature'=>$sig], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error servidor'], JSON_UNESCAPED_UNICODE);
}
