<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$ids = $data['ids'] ?? [];
if (!is_array($ids) || !$ids) {
    echo json_encode(['ok' => true, 'updated' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$ids = array_values(array_unique(array_map('intval', $ids)));
$ids = array_filter($ids, fn($x) => $x > 0);

if (!$ids) {
    echo json_encode(['ok' => true, 'updated' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    // placeholders (?, ?, ?)
    $ph = implode(',', array_fill(0, count($ids), '?'));

    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE user_ide = ?
          AND id IN ($ph)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$userId], $ids));

    echo json_encode([
        'ok' => true,
        'updated' => $stmt->rowCount()
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
