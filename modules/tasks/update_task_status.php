<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
$estado = trim((string)($_POST['estado'] ?? ''));

$allowed = ['pendiente', 'en_proceso', 'cerrada', 'cancelada'];
if ($taskId <= 0 || !in_array($estado, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];

try {
    // Validar que sea del analista
    $stmt = $pdo->prepare("SELECT id, analyst_id, estado FROM analyst_tasks WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $taskId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Tarea no encontrada'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$row['analyst_id'] !== $analystId) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'No puedes modificar esta tarea'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtUp = $pdo->prepare("
        UPDATE analyst_tasks
        SET estado = :estado, updated_at = NOW()
        WHERE id = :id
    ");
    $stmtUp->execute([':estado' => $estado, ':id' => $taskId]);

    echo json_encode(['ok' => true, 'estado' => $estado], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('update_task_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
