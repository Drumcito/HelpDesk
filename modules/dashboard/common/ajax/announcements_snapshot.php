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

function scopeFromArea(string $area): string {
  $a = mb_strtolower(trim($area));
  if ($a === '') return 'Corporativo';
  if (str_contains($a, 'sucursal')) return 'Sucursal';
  return 'Corporativo';
}

$scope = scopeFromArea($userArea);
$mode = trim((string)($_GET['mode'] ?? ''));
$seeAll = ($mode === 'all' && in_array($userRol, [1,2,3], true)); // SA/Admin/Analista

try {
  // NOTA: NO filtramos por starts_at/ends_at porque son “demostrativas”
if ($seeAll) {
  $stmt = $pdo->prepare("
    SELECT
      a.id, a.title, a.body, a.level, a.target_area,
      a.starts_at, a.ends_at, a.created_at, a.created_by_area
    FROM announcements a
    WHERE a.is_active = 1
    ORDER BY a.created_at DESC
    LIMIT 50
  ");
  $stmt->execute();
} else {
  $stmt = $pdo->prepare("
    SELECT
      a.id, a.title, a.body, a.level, a.target_area,
      a.starts_at, a.ends_at, a.created_at, a.created_by_area
    FROM announcements a
    WHERE a.is_active = 1
      AND (a.target_area = 'ALL' OR a.target_area = :scope)
    ORDER BY a.created_at DESC
    LIMIT 20
  ");
  $stmt->execute([':scope' => $scope]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];


function normArea(string $s): string {
  $s = mb_strtolower(trim($s));
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

foreach ($rows as &$r) {
  $createdByArea = trim((string)($r['created_by_area'] ?? ''));
  $can = 0;

  if (in_array($userRol, [2,3], true)
      && $createdByArea !== ''
      && $userArea !== ''
      && normArea($createdByArea) === normArea($userArea)) {
    $can = 1;
  }

  $r['can_disable'] = $can ? '1' : '0';
}
unset($r);


  $sigBase = '';
  foreach ($rows as $r) {
    $sigBase .= ($r['id'] ?? '') . '|' . ($r['created_at'] ?? '') . '|' . ($r['target_area'] ?? '') . '|' . ($r['level'] ?? '') . ';';
  }
  $signature = sha1($sigBase);

  echo json_encode(['ok'=>true,'signature'=>$signature,'items'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error snapshot anuncios']);
}
