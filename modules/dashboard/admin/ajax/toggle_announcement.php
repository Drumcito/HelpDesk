<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$rol  = (int)($_SESSION['user_rol'] ?? 0);
$userArea = trim((string)($_SESSION['user_area'] ?? ''));

if (!in_array($rol, [2,3], true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

function normArea(string $s): string {
  $s = mb_strtolower(trim($s));
  // colapsa espacios
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

$pdo = Database::getConnection();

// aceptar JSON o FormData
$ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$in = [];
if (str_contains($ct, 'application/json')) {
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true) ?: [];
} else {
  $in = $_POST ?: [];
}

$id = (int)($in['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $pdo->prepare("
  SELECT id, is_active, created_by_area
  FROM announcements
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$ann = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ann) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'msg'=>'No existe el anuncio'], JSON_UNESCAPED_UNICODE);
  exit;
}

if ((int)$ann['is_active'] === 0) {
  echo json_encode(['ok'=>true, 'already'=>true], JSON_UNESCAPED_UNICODE);
  exit;
}

$createdByArea = trim((string)($ann['created_by_area'] ?? ''));

if ($createdByArea === '' || $userArea === '' || normArea($createdByArea) !== normArea($userArea)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No puedes desactivar anuncios de otra área'], JSON_UNESCAPED_UNICODE);
  exit;
}

// desactiva
$upd = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = ? LIMIT 1");
$upd->execute([$id]);

echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
