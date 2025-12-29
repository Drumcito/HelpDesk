<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'No autenticado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Permitimos SA/Admin/Analista (1,2,3) a crear tickets desde panel
    $rol = (int)($_SESSION['user_rol'] ?? 0);
    if (!in_array($rol, [1,2,3], true)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'Sin permisos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok'=>false,'msg'=>'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = Database::getConnection();

    $creatorId   = (int)($_SESSION['user_id'] ?? 0);
    $userId      = (int)($_POST['user_id'] ?? 0);
    $areaDestino = trim((string)($_POST['area_destino'] ?? ''));
    $problema    = trim((string)($_POST['problema'] ?? ''));
    $prioridad   = strtolower(trim((string)($_POST['prioridad'] ?? 'media')));
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));

    $inicio      = trim((string)($_POST['inicio'] ?? '')); // datetime-local o vacío
    $fin         = trim((string)($_POST['fin'] ?? ''));    // datetime-local o vacío
    $ticketParaMi = (int)($_POST['ticket_para_mi'] ?? 0) === 1;

    if ($userId <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Falta user_id'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($descripcion === '') {
        echo json_encode(['ok'=>false,'msg'=>'La descripción es obligatoria'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validar prioridad (tu enum es baja/media/alta)
    if (!in_array($prioridad, ['baja','media','alta'], true)) {
        $prioridad = 'media';
    }

    // Si ticket_para_mi => fuerza TI
    if ($ticketParaMi) {
        $areaDestino = 'TI';
        $problema = '';   // oculto en UI
        $prioridad = 'media';
        $inicio = '';
        $fin = '';
    } else {
        if ($areaDestino === '') {
            echo json_encode(['ok'=>false,'msg'=>'Selecciona área destino'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($problema === '') {
            echo json_encode(['ok'=>false,'msg'=>'Selecciona problema'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 1) Obtener datos del usuario desde users
    $stmtU = $pdo->prepare("SELECT id, number_sap, name, last_name, email, area FROM users WHERE id = ? LIMIT 1");
    $stmtU->execute([$userId]);
    $u = $stmtU->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        echo json_encode(['ok'=>false,'msg'=>'Usuario no existe en users'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sapUser    = (string)($u['number_sap'] ?? '');
    $nombreUser = trim(($u['name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    $emailUser  = (string)($u['email'] ?? '');

    // 2) Determinar si se crea cerrado (si hay FIN)
    // Convertimos datetime-local "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:SS"
    $toSqlDT = function(string $v): ?string {
        $v = trim($v);
        if ($v === '') return null;
        $v = str_replace('T', ' ', $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $v)) {
            return $v . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $v)) {
            return $v;
        }
        return null;
    };

    $inicioSql = $toSqlDT($inicio);
    $finSql    = $toSqlDT($fin);

    $isClosed = ($finSql !== null); // si capturaron FIN => cerrado

    $estado = $isClosed ? 'cerrado' : 'abierto';

    // Si se crea cerrado: lo “asignamos” al creador (para trazabilidad)
    $asignadoA = $isClosed ? $creatorId : null;

    // timestamps
    $nowIp = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua    = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // 3) Insert en tickets
    // OJO: tu columna "area" la usas como área destino (porque filtras por area en panel de analista)
    $sql = "
        INSERT INTO tickets
        (user_id, sap, nombre, area, email, problema, prioridad, descripcion,
         fecha_envio, estado, asignado_a, fecha_asignacion, fecha_primera_respuesta, fecha_resolucion,
         creado_por_ip, creado_por_navegador)
        VALUES
        (:user_id, :sap, :nombre, :area, :email, :problema, :prioridad, :descripcion,
         NOW(), :estado, :asignado_a, :fecha_asignacion, :fecha_primera_respuesta, :fecha_resolucion,
         :ip, :ua)
    ";

    $stmt = $pdo->prepare($sql);

    // Para cerrados: si no mandaron inicio pero sí fin, usamos fin como inicio “mínimo”
    $fechaAsign = $isClosed ? ($inicioSql ?? $finSql) : null;
    $fechaPrim  = $isClosed ? ($inicioSql ?? $finSql) : null;

    $stmt->execute([
        ':user_id' => $userId,
        ':sap' => $sapUser,
        ':nombre' => $nombreUser,
        ':area' => $areaDestino,
        ':email' => $emailUser,
        ':problema' => $problema,
        ':prioridad' => $prioridad,
        ':descripcion' => $descripcion,
        ':estado' => $estado,
        ':asignado_a' => $asignadoA,
        ':fecha_asignacion' => $fechaAsign,
        ':fecha_primera_respuesta' => $fechaPrim,
        ':fecha_resolucion' => ($isClosed ? $finSql : null),
        ':ip' => $nowIp,
        ':ua' => $ua,
    ]);

    $ticketId = (int)$pdo->lastInsertId();

    echo json_encode([
        'ok' => true,
        'id' => $ticketId,
        'estado' => $estado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'=>false,
        'msg'=>'Error interno',
        'debug'=>$e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
