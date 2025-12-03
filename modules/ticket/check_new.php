<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json');

// Solo analistas (rol = 3)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['new' => false, 'msg' => 'No autorizado']);
    exit;
}

$pdo    = Database::getConnection();
$area   = $_SESSION['user_area'] ?? '';
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT id, problema, fecha_envio, nombre, descripcion
        FROM tickets
        WHERE area = :area
          AND estado = 'abierto'
          AND (asignado_a IS NULL OR asignado_a = 0)
          AND id > :last_id
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':area'    => $area,
        ':last_id' => $lastId
    ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['new' => false]);
        exit;
    }

    // Etiqueta bonita del problema (igual que en analyst.php)
    $p = $ticket['problema'] ?? '';
    $problemaLabel = match ($p) {
        'cierre_dia'  => 'Cierre del día',
        'no_legado'   => 'Sin acceso a legado/legacy',
        'no_internet' => 'Sin internet',
        'no_checador' => 'No funciona checador',
        'rastreo'     => 'Rastreo de checada',
        'replica'     => 'Replica',
        'no_sap'      => 'Sin acceso a SAP',
        'update_cliente' => 'Modificación de cliente',
        'alta_cliente'   => 'Alta de cliente',
        'Descuentos'     => 'Descuentos',
        'otro'        => 'Otro',
        default       => $p,
    };

    echo json_encode([
        'new'          => true,
        'id'           => (int)$ticket['id'],
        'problema'     => $problemaLabel,      // para mostrar en tabla / notificación
        'problema_raw' => $p,                  // por si luego quieres el código crudo
        'fecha'        => $ticket['fecha_envio'],
        'usuario'      => $ticket['nombre'],
        'descripcion'  => $ticket['descripcion']
    ]);
    exit;

} catch (Exception $e) {
    error_log('Error en check_new.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['new' => false, 'msg' => 'Error interno']);
    exit;
}
