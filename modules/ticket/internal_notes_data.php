<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado', 'rows' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = Database::getConnection();
$rol  = (int)($_SESSION['user_rol'] ?? 0);
$area = trim($_SESSION['user_area'] ?? '');

// filtros
$problemaFiltro = trim($_GET['problema'] ?? 'todos'); // puede venir 'todos', '7', 'cierre', etc.
$q = trim($_GET['q'] ?? ''); // keywords SOLO en descripcion

try {
    $sql = "
        SELECT
            t.id AS id,
            t.fecha_envio AS nota_fecha,
            COALESCE(cp.label, t.problema) AS problema_label,
            t.descripcion,
            m.mensaje AS nota_interna
        FROM tickets t

        INNER JOIN (
            SELECT ticket_id, MAX(id) AS last_msg_id
            FROM ticket_messages
            WHERE is_internal = 1
            GROUP BY ticket_id
        ) x ON x.ticket_id = t.id

        INNER JOIN ticket_messages m
            ON m.id = x.last_msg_id

        LEFT JOIN catalog_problems cp
            ON (
                (t.problema REGEXP '^[0-9]+$' AND cp.id = CAST(t.problema AS UNSIGNED))
                OR
                (t.problema NOT REGEXP '^[0-9]+$' AND cp.code = t.problema)
            )
        WHERE 1=1
    ";

    $params = [];

    // Admin/Analista: solo su área
    if (in_array($rol, [2,3], true) && $area !== '') {
        $sql .= " AND t.area = :area ";
        $params[':area'] = $area;
    }

    // filtro por problema (si no es todos)
    if ($problemaFiltro !== '' && $problemaFiltro !== 'todos') {
        if (preg_match('/^\d+$/', $problemaFiltro)) {
            // filtro numérico (id)
            $sql .= " AND (
                (t.problema REGEXP '^[0-9]+$' AND CAST(t.problema AS UNSIGNED) = :pid)
                OR (cp.id = :pid)
            ) ";
            $params[':pid'] = (int)$problemaFiltro;
        } else {
            // filtro por code
            $sql .= " AND (
                (t.problema NOT REGEXP '^[0-9]+$' AND t.problema = :pcode)
                OR (cp.code = :pcode)
            ) ";
            $params[':pcode'] = $problemaFiltro;
        }
    }

    // keywords SOLO en descripcion
    if ($q !== '') {
        $sql .= " AND t.descripcion LIKE :q ";
        $params[':q'] = '%' . $q . '%';
    }

    $sql .= " ORDER BY t.fecha_envio DESC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok'   => true,
        'rows' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'ok'   => false,
        'msg'  => $e->getMessage(),
        'rows' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
