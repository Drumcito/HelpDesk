<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Admin (2) o SA (1)
$rol = (int)($_SESSION['user_rol'] ?? 0);
if (!isset($_SESSION['user_id']) || !in_array($rol, [1,2], true)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$idsCsv = trim((string)($_POST['ids'] ?? ''));

if ($idsCsv === '') {
    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    exit;
}

$ids = array_values(array_filter(array_map('intval', explode(',', $idsCsv))));
if (!$ids) {
    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

try {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE staff_notifications SET leido=1 WHERE user_id = ? AND id IN ($in)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$userId], $ids));

    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('mark_staff_notifications_read error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
