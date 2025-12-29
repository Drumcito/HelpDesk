<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Debe haber sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rol    = (int)($_SESSION['user_rol'] ?? 0); // 1 SA, 2 Admin, 3 Analista, 4 Usuario (según tu sistema)
$userId = (int)($_SESSION['user_id'] ?? 0);
$area   = (string)($_SESSION['user_area'] ?? '');

// Solo staff (SA/Admin/Analista) puede previsualizar desde analyst.php
if (!in_array($rol, [1,2,3], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin permisos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'ticket_id inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();

try {
    // 1) Traer ticket + label del catálogo
    $stmt = $pdo->prepare("
        SELECT 
            t.id, t.area, t.sap, t.nombre, t.email,
            t.problema AS problema_raw,
            COALESCE(cp.label, t.problema) AS problema_label,
            t.descripcion, t.estado, t.prioridad,
            t.fecha_envio, t.fecha_asignacion, t.fecha_resolucion,
            t.user_id, t.asignado_a
        FROM tickets t
        LEFT JOIN catalog_problems cp
               ON cp.code = t.problema
        WHERE t.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $ticketId]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$t) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2) Reglas de acceso por seguridad:
    // - SA/Admin: pueden ver todo
    // - Analista: puede ver tickets de su área (y si ya está asignado a otro analista, igual es de su área)
    if ($rol === 3) {
        if ((string)$t['area'] !== $area) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'No puedes ver tickets de otra área'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 3) Adjuntos desde ticket_attachments
    $stmtAtt = $pdo->prepare("
        SELECT 
            id,
            nombre_archivo,
            ruta_archivo,
            tipo,
            peso,
            subido_en
        FROM ticket_attachments
        WHERE ticket_id = :tid
        ORDER BY id ASC
    ");
    $stmtAtt->execute([':tid' => $ticketId]);
    $atts = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

    // Normaliza URL pública de adjuntos
    $attachments = [];
    foreach ($atts as $a) {
        $ruta = trim((string)($a['ruta_archivo'] ?? ''));
        $url = null;

        if ($ruta !== '') {
            if (preg_match('~^https?://~i', $ruta)) {
                $url = $ruta;
            } elseif (str_starts_with($ruta, '/HelpDesk_EQF/')) {
                $url = $ruta;
            } elseif (str_starts_with($ruta, '/uploads/')) {
                $url = '/HelpDesk_EQF' . $ruta;
            } elseif (str_starts_with($ruta, 'uploads/')) {
                $url = '/HelpDesk_EQF/' . $ruta;
            } else {
                // si solo guardaste el nombre, asume uploads/tickets (ajusta si tu ruta real es otra)
                $url = '/HelpDesk_EQF/uploads/' . ltrim($ruta, '/');
            }
        }

        $attachments[] = [
            'id'        => (int)$a['id'],
            'file_name' => (string)($a['nombre_archivo'] ?? 'archivo'),
            'file_path' => $url,
            'file_type' => (string)($a['tipo'] ?? ''),
            'file_size' => isset($a['peso']) ? (int)$a['peso'] : null,
            'uploaded'  => (string)($a['subido_en'] ?? ''),
        ];
    }

    // 4) Respuesta
    echo json_encode([
        'ok' => true,
        'ticket' => [
            'id'             => (int)$t['id'],
            'area'           => (string)($t['area'] ?? ''),
            'sap'            => (string)($t['sap'] ?? ''),
            'nombre'         => (string)($t['nombre'] ?? ''),
            'email'          => (string)($t['email'] ?? ''),
            'prioridad'      => (string)($t['prioridad'] ?? ''),
            'estado'         => (string)($t['estado'] ?? ''),
            'descripcion'    => (string)($t['descripcion'] ?? ''),
            'problema_raw'   => (string)($t['problema_raw'] ?? ''),
            'problema_label' => (string)($t['problema_label'] ?? ''),
            'fecha_envio'    => (string)($t['fecha_envio'] ?? ''),
            'fecha_asignacion' => (string)($t['fecha_asignacion'] ?? ''),
            'fecha_resolucion' => (string)($t['fecha_resolucion'] ?? ''),
            'user_id'        => (int)($t['user_id'] ?? 0),
            'asignado_a'     => (int)($t['asignado_a'] ?? 0),
        ],
        'attachments' => $attachments
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('ticket_view.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
