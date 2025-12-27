<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
$limit   = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1 || $limit > 30) $limit = 10;

$pdo = Database::getConnection();

try {
    // OJO: si tu columna es user_ide, cambia "user_id" por "user_ide"
    $stmt = $pdo->prepare("
        SELECT id, type, title, body, link, created_at
        FROM notifications
        WHERE user_id = :uid
          AND is_read = 0
          AND id > :since_id
        ORDER BY id ASC
        LIMIT {$limit}
    ");
    $stmt->execute([
        ':uid'      => $userId,
        ':since_id' => $sinceId
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $maxId = $sinceId;
    foreach ($rows as $r) $maxId = max($maxId, (int)$r['id']);

    echo json_encode([
        'ok' => true,
        'max_id' => $maxId,
        'notifications' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
