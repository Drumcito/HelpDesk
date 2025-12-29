<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Admin (rol=2) o SA (rol=1)
$rol = (int)($_SESSION['user_rol'] ?? 0);
if (!isset($_SESSION['user_id']) || !in_array($rol, [1,2], true)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$pdo = Database::getConnection();

try {
    // 1) leer no leídas
    $stmt = $pdo->prepare("
        SELECT id, title, body, created_at
        FROM staff_notifications
        WHERE user_id = :uid AND leido = 0
        ORDER BY id ASC
        LIMIT 10
    ");
    $stmt->execute([':uid'=>$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode(['ok'=>true,'has'=>false,'notifications'=>[]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2) marcar como leídas
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $up  = $pdo->prepare("UPDATE staff_notifications SET leido=1 WHERE id IN ($in)");
    $up->execute($ids);

    // 3) devolver formato estándar
    $out = array_map(function($r){
        return [
            'id'   => (int)$r['id'],
            'title'=> (string)$r['title'],
            'body' => (string)$r['body'],
            'at'   => (string)$r['created_at']
        ];
    }, $rows);

    echo json_encode(['ok'=>true,'has'=>true,'notifications'=>$out], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('check_staff_notifications error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
