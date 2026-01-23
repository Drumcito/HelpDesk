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

$userId   = (int)($_SESSION['user_id'] ?? 0);
$userRol  = (int)($_SESSION['user_rol'] ?? 0);
$userArea = trim((string)($_SESSION['user_area'] ?? ''));

// Solo Admin/Analista crean
if (!in_array($userRol, [2,3], true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'Sin permisos']);
  exit;
}

// lee JSON o form-data
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) $payload = $_POST;

$title = trim((string)($payload['title'] ?? ''));
$body  = trim((string)($payload['body'] ?? ''));
$level = strtoupper(trim((string)($payload['level'] ?? 'INFO')));
$target = trim((string)($payload['target_area'] ?? 'ALL'));
$starts = trim((string)($payload['starts_at'] ?? ''));
$ends   = trim((string)($payload['ends_at'] ?? ''));

if ($title === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Título requerido']);
  exit;
}

$allowedLevels = ['INFO','WARN','CRITICAL'];
if (!in_array($level, $allowedLevels, true)) $level = 'INFO';

$allowedTargets = ['ALL','Sucursal','Corporativo'];
if (!in_array($target, $allowedTargets, true)) $target = 'ALL';

// normaliza fechas (pueden venir vacías; solo son display)
$startsDb = ($starts !== '') ? $starts : null;
$endsDb   = ($ends   !== '') ? $ends   : null;

// helper: usuarios destino (solo rol 4)
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

  // Corporativo = Corporativo, TI, SAP, MKT
  $st = $pdo->prepare("
    SELECT id FROM users
    WHERE rol = 4 AND area IN ('Corporativo','TI','SAP','MKT')
  ");
  $st->execute();
  return array_map(fn($r)=>(int)$r['id'], $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

try {
  $pdo->beginTransaction();

  $ins = $pdo->prepare("
    INSERT INTO announcements (title, body, level, target_area, is_active, starts_at, ends_at, created_by_area, created_at)
    VALUES (:title, :body, :level, :target, 1, :starts_at, :ends_at, :cba, NOW())
  ");
  $ins->execute([
    ':title' => $title,
    ':body'  => ($body !== '' ? $body : null),
    ':level' => $level,
    ':target'=> $target,
    ':starts_at' => $startsDb,
    ':ends_at'   => $endsDb,
    ':cba' => $userArea
  ]);

  $annId = (int)$pdo->lastInsertId();

  // Push/Notificación (tabla notifications)
  $userIds = getAudienceUserIds($pdo, $target);

  if (!empty($userIds)) {
    $ntTitle = "Aviso nuevo";
    $ntBody  = "[$level] $title";
    $link    = "/HelpDesk_EQF/modules/dashboard/user/user.php#announcements";

    // inserción por lotes
    $placeholders = [];
    $values = [];
    foreach ($userIds as $uid) {
      $placeholders[] = "(?, ?, ?, ?, ?, 0, NOW())";
      array_push($values, $uid, 'announcement_new', $ntTitle, $ntBody, $link);
    }

    $sql = "INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at)
            VALUES " . implode(',', $placeholders);
    $stN = $pdo->prepare($sql);
    $stN->execute($values);
  }

  $pdo->commit();

  echo json_encode(['ok'=>true,'id'=>$annId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'No se pudo crear el aviso']);
}
