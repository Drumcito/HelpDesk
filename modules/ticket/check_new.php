<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Solo analistas (rol = 3)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['new' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo    = Database::getConnection();
$area   = $_SESSION['user_area'] ?? '';
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.problema AS problema_raw,
            COALESCE(cp.label, t.problema) AS problema_label,
            t.fecha_envio,
            t.nombre,
            t.descripcion,
            t.prioridad
        FROM tickets t
        LEFT JOIN catalog_problems cp
               ON cp.code = t.problema
        WHERE t.area = :area
          AND t.estado = 'abierto'
          AND (t.asignado_a IS NULL OR t.asignado_a = 0)
          AND t.id > :last_id
        ORDER BY t.id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':area'    => $area,
        ':last_id' => $lastId
    ]);

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['new' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'new'          => true,
        'id'           => (int)$ticket['id'],
        'problema'     => (string)$ticket['problema_label'], // label bonito
        'problema_raw' => (string)$ticket['problema_raw'],   // code
        'fecha'        => (string)$ticket['fecha_envio'],
        'usuario'      => (string)$ticket['nombre'],
        'descripcion'  => (string)$ticket['descripcion'],
        'prioridad'    => (string)($ticket['prioridad'] ?? 'media') // <-- paréntesis importante
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('Error en check_new.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['new' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
