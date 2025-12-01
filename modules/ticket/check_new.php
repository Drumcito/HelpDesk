<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['new' => false]);
    exit;
}

$pdo  = Database::getConnection();
$area = $_SESSION['user_area'] ?? '';
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$stmt = $pdo->prepare("
    SELECT id, problema, fecha_envio
    FROM tickets
    WHERE area = :area
      AND estado = 'abierto'
      AND (asignado_a IS NULL OR asignado_a = 0)
      AND id > :last_id
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([':area' => $area, ':last_id' => $lastId]);
$ticket = $stmt->fetch();

header('Content-Type: application/json');

if ($ticket) {
    echo json_encode([
        'new'      => true,
        'id'       => (int)$ticket['id'],
        'problema' => $ticket['problema'],
        'fecha'    => $ticket['fecha_envio']
    ]);
} else {
    echo json_encode(['new' => false]);
}
