<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$pdo = Database::getConnection();

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];

$title = trim((string)($in['title'] ?? ''));
$body  = trim((string)($in['body'] ?? ''));

// level
$level = strtoupper(trim((string)($in['level'] ?? 'INFO')));
$allowedLevel = ['INFO','WARN','CRITICAL'];
if (!in_array($level, $allowedLevel, true)) $level = 'INFO';

// target_area (solo Sucursal / Corporativo)
$area = trim((string)($in['target_area'] ?? ''));
$allowedAreas = ['Sucursal','Corporativo'];
if (!in_array($area, $allowedAreas, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Área inválida. Usa Sucursal o Corporativo.']);
    exit;
}

if ($title === '' || $body === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Título y descripción son obligatorios']);
    exit;
}

// datetime-local: "2026-01-02T15:30"
$starts_at = $in['starts_at'] ?? null;
$ends_at   = $in['ends_at'] ?? null;

$starts_at = $starts_at ? (str_replace('T', ' ', $starts_at) . ':00') : null;
$ends_at   = $ends_at ? (str_replace('T', ' ', $ends_at) . ':00') : null;

try {
    $stmt = $pdo->prepare("
      INSERT INTO announcements (title, body, level, target_area, starts_at, ends_at)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $body, $level, $area, $starts_at, $ends_at]);

    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar anuncio']);
}
