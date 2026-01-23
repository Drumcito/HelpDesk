<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

function out(array $x): void {
  echo json_encode($x, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION['user_id'])) out(['ok' => false, 'msg' => 'No autenticado']);

$rol  = (int)($_SESSION['user_rol'] ?? 0);
$area = trim((string)($_SESSION['user_area'] ?? ''));

try {
  $pdo = Database::getConnection();

 
  if (in_array($rol, [2, 3], true)) {
    $stmt = $pdo->query("
      SELECT id, title, body, level, target_area, created_at
      FROM announcements
      WHERE is_active = 1
      ORDER BY created_at DESC
      LIMIT 50
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } else {
    // Usuario normal
    $stmt = $pdo->prepare("
      SELECT id, title, body, level, target_area, created_at
      FROM announcements
      WHERE is_active = 1
        AND (target_area = 'ALL' OR target_area = :area)
      ORDER BY created_at DESC
      LIMIT 50
    ");
    $stmt->execute([':area' => $area !== '' ? $area : 'ALL']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  out([
    'ok' => true,
    'count' => count($rows),
    'items' => $rows
  ]);

} catch (Throwable $e) {
  out(['ok' => false, 'msg' => 'Error snapshot']);
}
