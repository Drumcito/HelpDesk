<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId  = (int)($_SESSION['user_id'] ?? 0);
$userRol = (int)($_SESSION['user_rol'] ?? 0);

// Solo Staff (SA/Admin/Analista)
if (!in_array($userRol, [1,2,3], true)) {
    echo json_encode(['ok' => false, 'error' => 'Sin permiso'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    // Trae no leídas (máx 10)
    $stmt = $pdo->prepare("
    SELECT id, type, title, body, link, created_at
    FROM notifications
    WHERE user_id = :uid
      AND is_read = 0
    ORDER BY created_at ASC
    LIMIT 10
");

    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode(['ok' => true, 'has' => false, 'notifications' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Marcar como leídas
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $up = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders)");
    $up->execute($ids);

    echo json_encode([
        'ok' => true,
        'has' => true,
        'notifications' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[check_staff_notifications] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
