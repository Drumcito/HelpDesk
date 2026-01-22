<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// ============================
// AUTH
// ============================
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
  exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if (!in_array($rol, [2, 3], true)) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
  exit;
}

$pdo = Database::getConnection();

// ============================
// INPUT
// ============================
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) $in = [];

$title = trim((string)($in['title'] ?? ''));
$body  = trim((string)($in['body'] ?? ''));

if ($title === '' || $body === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Título y descripción son obligatorios']);
  exit;
}

$level = strtoupper(trim((string)($in['level'] ?? 'INFO')));
$allowedLevel = ['INFO', 'WARN', 'CRITICAL'];
if (!in_array($level, $allowedLevel, true)) $level = 'INFO';

$target_area = strtoupper(trim((string)($in['target_area'] ?? '')));
if ($target_area === '') $target_area = 'ALL';

$mapTarget = [
  'ALL'         => 'ALL',
  'SUCURSAL'    => 'Sucursal',
  'CORPORATIVO' => 'Corporativo',
];

if (!isset($mapTarget[$target_area])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Área inválida. Usa ALL, Sucursal o Corporativo.']);
  exit;
}
$target_area = $mapTarget[$target_area];

$starts_at = $in['starts_at'] ?? null;
$ends_at   = $in['ends_at'] ?? null;

$starts_at = (is_string($starts_at) && trim($starts_at) !== '') ? trim($starts_at) : null;
$ends_at   = (is_string($ends_at)   && trim($ends_at)   !== '') ? trim($ends_at)   : null;

$normalizeDT = function ($v) {
  if (!$v) return null;
  $v = str_replace('T', ' ', $v);
  if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $v)) $v .= ':00';
  return $v;
};

$starts_at = $normalizeDT($starts_at);
$ends_at   = $normalizeDT($ends_at);

// ============================
// INSERT
// ============================
try {
  $createdByUserId = (int)($_SESSION['user_id'] ?? 0);
  $createdByArea   = trim((string)($_SESSION['user_area'] ?? ''));

  $stmt = $pdo->prepare("
    INSERT INTO announcements (
      title, body, level, target_area,
      starts_at, ends_at,
      created_by_user_id, created_by_area
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $stmt->execute([
    $title,
    $body,
    $level,
    $target_area,
    $starts_at,
    $ends_at,
    $createdByUserId,
    $createdByArea
  ]);
  $annId = (int)$pdo->lastInsertId();

  $target = $target_area; // 'ALL'|'Sucursal'|'Corporativo'

  if ($target === 'ALL') {
    $uStmt = $pdo->query("SELECT id FROM users WHERE rol IN (3,4)"); // analistas+usuarios (ajusta)
  } else {
  $uStmt = $pdo->query("SELECT id FROM users WHERE rol IN (3,4)");
  }

  $userIds = $uStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

  if ($userIds) {
    $n = $pdo->prepare("
      INSERT INTO notifications (user_id, title, body, link, is_read, created_at)
      VALUES (?, ?, ?, ?, 0, NOW())
    ");

    $link = '/HelpDesk_EQF/modules/dashboard/user/user.php#announcements'; // ajusta ruta real
    $ntitle = '📣 Aviso: ' . $title;

    foreach ($userIds as $uid) {
      $n->execute([(int)$uid, $ntitle, $body, $link]);
    }
  }

  echo json_encode(['ok' => true, 'id' => $annId]);

  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => 'Error al guardar anuncio']);
}
