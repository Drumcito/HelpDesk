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

// Para usuarios finales (rol 4) filtras por scope. Para staff (2/3) NO.
$userScope = trim((string)($_SESSION['user_scope'] ?? ''));
if ($userScope !== 'Sucursal' && $userScope !== 'Corporativo') $userScope = '';

try {
  $params = [];
  $whereParts = [];
  $whereParts[] = "a.is_active = 1";
  $whereParts[] = "(a.starts_at IS NULL OR a.starts_at <= NOW())";
  $whereParts[] = "(a.ends_at   IS NULL OR a.ends_at   >= NOW())";

  // Staff ve TODO (ALL + Sucursal + Corporativo)
  if (in_array($userRol, [2,3], true)) {
    // nada extra
  } else {
    // Usuario final: ALL + su scope (si existe)
    $scopeWhere = [];
    $scopeWhere[] = "TRIM(a.target_area) = 'ALL'";
    if ($userScope !== '') {
      $scopeWhere[] = "TRIM(a.target_area) = :scope";
      $params[':scope'] = $userScope;
    }
    $whereParts[] = "(" . implode(" OR ", $scopeWhere) . ")";
  }

  $where = implode(" AND ", $whereParts);

  $stmt = $pdo->prepare("
    SELECT
      a.id, a.title, a.body, a.level, a.target_area,
      a.starts_at, a.ends_at, a.created_at, a.created_by_area
    FROM announcements a
    WHERE $where
    ORDER BY a.created_at DESC
    LIMIT 20
  ");
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  foreach ($rows as &$r) {
    $createdByArea = trim((string)($r['created_by_area'] ?? ''));
    $can = 0;

if ($userRol === 1 || $userRol === 2) $can = 1;
else if ($createdByArea !== '' && strcasecmp($createdByArea, $userArea) === 0) $can = 1;

    if ($createdByArea !== '' && $userArea !== '' && strcasecmp($createdByArea, $userArea) === 0) $can = 1;

    $r['can_disable'] = $can ? '1' : '0';
  }
  unset($r);

  $sigBase = '';
  foreach ($rows as $r) {
    $sigBase .= ($r['id'] ?? '') . '|'
             . ($r['created_at'] ?? '') . '|'
             . ($r['level'] ?? '') . '|'
             . ($r['target_area'] ?? '') . '|'
             . ($r['starts_at'] ?? '') . '|'
             . ($r['ends_at'] ?? '') . '|'
             . md5((string)($r['title'] ?? '') . '|' . (string)($r['body'] ?? ''))
             . ';';
  }
  $signature = sha1($sigBase);

  echo json_encode(['ok'=>true,'signature'=>$signature,'items'=>$rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error snapshot anuncios']);
}
