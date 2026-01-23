<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']);
  exit;
}

$pdo = Database::getConnection();

$userRol  = (int)($_SESSION['user_rol'] ?? 0);
$userArea = trim((string)($_SESSION['user_area'] ?? ''));

if (!in_array($userRol, [2,3], true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'Sin permisos']);
  exit;
}

// JSON o form
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) $payload = $_POST;

$id = (int)($payload['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

// helper audiencia igual que arriba
function getAudienceUserIds(PDO $pdo, string $target): array {
  if ($target === 'ALL') {
    $st = $pdo->query("SELECT id FROM users WHERE rol = 4");
    return array_map(fn($r)=>(int)$r['id'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
  }
  if ($target === 'Sucursal') {
    $st = $pdo->prepare("SELECT id FROM users WHERE rol = 4 AND area LIKE '%Sucursal%'");
    $st->execute();
    return array_map(fn($r)=>(int)$r['id'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
  }
  $st = $pdo->prepare("SELECT id FROM users WHERE rol = 4 AND area IN ('Corporativo','TI','SAP','MKT')");
  $st->execute();
  return array_map(fn($r)=>(int)$r['id'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

try {
  $pdo->beginTransaction();

  // lee el aviso para validar área creadora y para saber target/level/title
  $st = $pdo->prepare("SELECT id, title, level, target_area, created_by_area, is_active FROM announcements WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $a = $st->fetch(PDO::FETCH_ASSOC);

  if (!$a) {
    $pdo->rollBack();
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'No existe']);
    exit;
  }

  if ((int)$a['is_active'] !== 1) {
    $pdo->rollBack();
    echo json_encode(['ok'=>true,'msg'=>'Ya estaba desactivado']);
    exit;
  }

  // regla: solo el área que lo creó
  $createdByArea = trim((string)($a['created_by_area'] ?? ''));
  if ($createdByArea === '' || strcasecmp($createdByArea, $userArea) !== 0) {
    $pdo->rollBack();
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Solo el área que lo creó puede desactivar']);
    exit;
  }

  $up = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id = ? LIMIT 1");
  $up->execute([$id]);

  // notificación
  $target = (string)($a['target_area'] ?? 'ALL');
  $level  = strtoupper((string)($a['level'] ?? 'INFO'));
  $title  = (string)($a['title'] ?? '');

  $userIds = getAudienceUserIds($pdo, $target);
  if (!empty($userIds)) {
    $ntTitle = "Aviso desactivado";
    $ntBody  = "[$level] $title";
    $link    = "/HelpDesk_EQF/modules/dashboard/user/user.php#announcements";

    $placeholders = [];
    $values = [];
    foreach ($userIds as $uid) {
      $placeholders[] = "(?, ?, ?, ?, ?, 0, NOW())";
      array_push($values, $uid, 'announcement_off', $ntTitle, $ntBody, $link);
    }

    $sql = "INSERT INTO notifications (user_ide, type, title, body, link, is_read, created_at)
            VALUES " . implode(',', $placeholders);
    $stN = $pdo->prepare($sql);
    $stN->execute($values);
  }

  $pdo->commit();
  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'No se pudo desactivar']);
}
