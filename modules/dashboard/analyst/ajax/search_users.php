<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') {
  echo json_encode(['ok'=>true,'items'=>[]], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = Database::getConnection();
  $like = "%{$q}%";

  // OJO: campos reales de users: email, number_sap, name, last_name, area
  $stmt = $pdo->prepare("
    SELECT id, email, number_sap, name, last_name, area
    FROM users
    WHERE email LIKE :like
       OR number_sap LIKE :like
       OR name LIKE :like
       OR last_name LIKE :like
       OR CONCAT(name,' ',last_name) LIKE :like
    ORDER BY email ASC
    LIMIT 10
  ");
  $stmt->execute([':like' => $like]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'items'=>$items], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);

  // DEBUG: temporal para que veas el motivo exacto en navegador
  echo json_encode([
    'ok' => false,
    'msg' => 'Error interno',
    'debug' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
