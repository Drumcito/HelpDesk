<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$sinceId = (int)($_GET['since_id'] ?? 0);

try {
    $pdo = Database::getConnection();

    // Trae SOLO no leídas y mayores a since_id
    $stmt = $pdo->prepare("
        SELECT id, type, title, body, link, created_at
        FROM notifications
        WHERE user_id = :uid
          AND is_read = 0
          AND id > :since_id
        ORDER BY id ASC
        LIMIT 50
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':since_id' => $sinceId
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'notifications' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
