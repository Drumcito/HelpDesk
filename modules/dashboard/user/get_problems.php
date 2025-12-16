<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'unauthorized']);
  exit;
}

$area = trim($_GET['area'] ?? '');
if ($area === '') {
  echo json_encode(['ok'=>true,'items'=>[]]);
  exit;
}

try {
  $pdo = Database::getConnection();
  $st = $pdo->prepare("
    SELECT id, label
    FROM catalog_problems
    WHERE active=1 AND area_code=?
    ORDER BY sort_order ASC, id ASC
  ");
  $st->execute([$area]);
  $items = $st->fetchAll(PDO::FETCH_ASSOC);

  // siempre agrega "OTRO" al final
  $items[] = ['id'=>'OTRO', 'label'=>'Otro'];

  echo json_encode(['ok'=>true,'items'=>$items]);
} catch(Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'db_error']);
}
