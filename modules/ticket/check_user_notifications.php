<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

// Solo usuarios autenticados
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([
        'ok'   => false,
        'has'  => false,
        'msg'  => 'No autenticado'
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$pdo = Database::getConnection();

try {
    // Traemos notificaciones NO leídas de este usuario
    $stmt = $pdo->prepare("
        SELECT id, ticket_id, mensaje, created_at
        FROM ticket_notifications
        WHERE user_id = :uid
          AND leido = 0
        ORDER BY created_at ASC
        LIMIT 10
    ");
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode([
            'ok'   => true,
            'has'  => false,
            'notifications' => []
        ]);
        exit;
    }

    // Marcamos como leídas en la misma llamada
    $ids = array_column($rows, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $upd = $pdo->prepare("
        UPDATE ticket_notifications
        SET leido = 1
        WHERE id IN ($in)
    ");
    $upd->execute($ids);

    echo json_encode([
        'ok'   => true,
        'has'  => true,
        'notifications' => $rows
    ]);
    exit;

} catch (Exception $e) {
    error_log('Error en check_user_notifications: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'  => false,
        'has' => false,
        'msg' => 'Error interno'
    ]);
    exit;
}
