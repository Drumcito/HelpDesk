<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Solo analistas (rol=3)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo       = Database::getConnection();
$analystId = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            area,
            admin_id,
            analyst_id,
            titulo,
            descripcion,
            estado,
            fecha_limite,
            archivo_ruta,
            created_at,
            updated_at
        FROM analyst_tasks
        WHERE analyst_id = :aid
        ORDER BY
          CASE estado
            WHEN 'pendiente' THEN 0
            WHEN 'en_proceso' THEN 1
            WHEN 'cerrada' THEN 2
            WHEN 'cancelada' THEN 3
            ELSE 9
          END,
          created_at DESC
        LIMIT 200
    ");
    $stmt->execute([':aid' => $analystId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tasks = [];
    foreach ($rows as $r) {
        $ruta = trim((string)($r['archivo_ruta'] ?? ''));

        // Normalizar ruta a URL pública
        $archivoUrl = null;
        if ($ruta !== '') {
            if (preg_match('~^https?://~i', $ruta)) {
                $archivoUrl = $ruta;
            } elseif (str_starts_with($ruta, '/HelpDesk_EQF/')) {
                $archivoUrl = $ruta;
            } elseif (str_starts_with($ruta, '/uploads/')) {
                $archivoUrl = '/HelpDesk_EQF' . $ruta;
            } elseif (str_starts_with($ruta, 'uploads/')) {
                $archivoUrl = '/HelpDesk_EQF/' . $ruta;
            } else {
                $archivoUrl = '/HelpDesk_EQF/uploads/tasks/' . ltrim($ruta, '/');
            }
        }

        $archivoNombre = null;
        if ($archivoUrl) {
            $archivoNombre = basename(parse_url($archivoUrl, PHP_URL_PATH) ?? $archivoUrl);
        }

        $tasks[] = [
            'id'             => (int)$r['id'],
            'titulo'         => (string)($r['titulo'] ?? ''),
            'descripcion'    => (string)($r['descripcion'] ?? ''),
            'estado'         => (string)($r['estado'] ?? ''),
            'fecha_limite'   => $r['fecha_limite'] ? (string)$r['fecha_limite'] : '',
            'created_at'     => $r['created_at'] ? (string)$r['created_at'] : '',
            'updated_at'     => $r['updated_at'] ? (string)$r['updated_at'] : '',
            'archivo_url'    => $archivoUrl,
            'archivo_nombre' => $archivoNombre
        ];
    }

    echo json_encode(['ok' => true, 'tasks' => $tasks], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('get_my_tasks.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
