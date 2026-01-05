<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']);
  exit;
}

$rol  = (int)($_SESSION['user_rol'] ?? 0);
$area = trim((string)($_SESSION['user_area'] ?? ''));

// solo admin(2) y analista(3) pueden intentar desactivar
if (!in_array($rol, [2,3], true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
  exit;
}

$pdo = Database::getConnection();

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$id  = (int)($in['id'] ?? 0);

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

// trae el anuncio (incluye quién lo creó)
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
  echo json_encode(['ok'=>false,'msg'=>'No existe el anuncio']);
  exit;
}

// si ya está desactivado
if ((int)$ann['is_active'] === 0) {
  echo json_encode(['ok'=>true, 'already'=>true]);
  exit;
}

$createdByArea = trim((string)($ann['created_by_area'] ?? ''));

if ($createdByArea === '') {
  if ($rol !== 2) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Anuncio antiguo sin área: solo Admin puede desactivarlo.']);
    exit;
  }
} else {
  // Admin puede todo (si quieres)
  if ($rol === 2) {
    // ok
  } else {
    // Analista: solo su área
    if ($area === '' || strcasecmp($createdByArea, $area) !== 0) {
      http_response_code(403);
      echo json_encode(['ok'=>false,'msg'=>'No puedes desactivar anuncios de otra área']);
      exit;
    }
  }
}


// desactiva
$upd = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = ? LIMIT 1");
$upd->execute([$id]);

echo json_encode(['ok'=>true]);
