<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$rol    = (int)($_SESSION['user_rol'] ?? 0);

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Ticket inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

try {
    // 1) Obtener ticket + label de problema desde catálogo
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.user_id,
            t.asignado_a,
            t.area,
            t.sap,
            t.nombre,
            t.email,
            t.problema AS problema_raw,
            COALESCE(cp.label, t.problema) AS problema_label,
            t.descripcion,
            t.prioridad,
            t.estado,
            t.fecha_envio
        FROM tickets t
        LEFT JOIN catalog_problems cp
               ON cp.code = t.problema
        WHERE t.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2) Permisos:
    // SA/Admin -> todo
    // Analista -> solo si asignado_a == userId
    // Usuario -> solo si user_id == userId
    $isAllowed = false;

    if (in_array($rol, [1, 2], true)) {
        $isAllowed = true;
    } elseif ($rol === 3) { // analista
        $isAllowed = ((int)$ticket['asignado_a'] === $userId);
    } else { // usuario
        $isAllowed = ((int)$ticket['user_id'] === $userId);
    }

    if (!$isAllowed) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Sin permisos para ver este ticket'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3) Adjuntos (tu tabla real ticket_attachments)
    $stmtA = $pdo->prepare("
        SELECT 
            id,
            ticket_id,
            nombre_archivo,
            ruta_archivo,
            peso,
            tipo,
            subido_en
        FROM ticket_attachments
        WHERE ticket_id = :id
        ORDER BY id ASC
    ");
    $stmtA->execute([':id' => $ticketId]);
    $rows = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar rutas: si guardas rutas relativas tipo "uploads/...."
    // las convertimos a URL del sistema "/HelpDesk_EQF/..."
    $basePrefix = '/HelpDesk_EQF/';

    $attachments = [];
    foreach ($rows as $r) {
        $path = (string)($r['ruta_archivo'] ?? '');

        // Si ya viene con /HelpDesk_EQF/ o empieza con /, lo dejamos
        if ($path !== '') {
            if (str_starts_with($path, '/HelpDesk_EQF/')) {
                // ok
            } elseif (str_starts_with($path, '/')) {
                // ruta absoluta del sitio (ej. /uploads/..)
            } else {
                // ruta relativa: uploads/...
                $path = $basePrefix . ltrim($path, '/');
            }
        }

        $attachments[] = [
            'id'        => (int)$r['id'],
            'ticket_id' => (int)$r['ticket_id'],
            // Para que tu JS de detalle funcione (file_name/file_path)
            'file_name' => $r['nombre_archivo'],
            'file_path' => $path,
            'file_type' => $r['tipo'],
            'file_size' => $r['peso'],
            'created_at'=> $r['subido_en'],
        ];
    }

    echo json_encode([
        'ok' => true,
        'ticket' => [
            'id'            => (int)$ticket['id'],
            'user_id'       => (int)$ticket['user_id'],
            'asignado_a'    => (int)($ticket['asignado_a'] ?? 0),
            'area'          => $ticket['area'],
            'sap'           => $ticket['sap'],
            'nombre'        => $ticket['nombre'],
            'email'         => $ticket['email'],
            'problema_raw'  => $ticket['problema_raw'],
            'problema_label'=> $ticket['problema_label'],
            'descripcion'   => $ticket['descripcion'],
            'prioridad'     => $ticket['prioridad'],
            'estado'        => $ticket['estado'],
            'fecha_envio'   => $ticket['fecha_envio'],
        ],
        'attachments' => $attachments
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    error_log('ticket_view.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
