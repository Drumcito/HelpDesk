<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

// acepta "ids" como CSV: "12,13,14" o como array
$idsRaw = $_POST['ids'] ?? '';
$ids = [];

if (is_array($idsRaw)) {
    $ids = array_map('intval', $idsRaw);
} else {
    $parts = array_filter(array_map('trim', explode(',', (string)$idsRaw)));
    $ids = array_map('intval', $parts);
}

$ids = array_values(array_filter($ids, fn($x) => $x > 0));
if (!$ids) {
    echo json_encode(['ok' => true, 'updated' => 0]);
    exit;
}

try {
    $pdo = Database::getConnection();

    // placeholders dinámicos
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // SOLO del usuario logueado
    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
          AND id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $params = array_merge([$userId], $ids);
    $stmt->execute($params);

    echo json_encode(['ok' => true, 'updated' => $stmt->rowCount()]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    exit;
}
