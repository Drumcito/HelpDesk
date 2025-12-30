<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';
header('Content-Type: application/json; charset=utf-8');

try{
  if(!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }

  $area = trim((string)($_GET['area'] ?? ''));
  if($area===''){ echo json_encode(['ok'=>true,'items'=>[]]); exit; }

  $pdo = Database::getConnection();

  // AJUSTA a tu estructura real:
  // Si catalog_problems tiene area (varchar) -> fácil:
  $st = $pdo->prepare("SELECT id, label FROM catalog_problems WHERE area = ? ORDER BY label ASC");
  $st->execute([$area]);

  echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'msg'=>'Error interno','debug'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
