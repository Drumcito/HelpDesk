<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

// Solo USUARIO (ajusta si tu rol de usuario es otro)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 4) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

date_default_timezone_set('America/Mexico_City');

$pdo = Database::getConnection();

$area = trim((string)($_GET['area'] ?? ''));

/**
 * Helpers para adaptar el endpoint a columnas reales (sin romperse)
 */
function hasColumn(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '::' . $column;
    if (isset($cache[$key])) return $cache[$key];

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :t
              AND COLUMN_NAME = :c
        ");
        $stmt->execute([':t' => $table, ':c' => $column]);
        $cache[$key] = ((int)$stmt->fetchColumn() > 0);
        return $cache[$key];
    } catch (Throwable $e) {
        $cache[$key] = false;
        return false;
    }
}

function pickColumn(PDO $pdo, string $table, array $candidates): ?string {
    foreach ($candidates as $c) {
        if (hasColumn($pdo, $table, $c)) return $c;
    }
    return null;
}

function nowWeekdayMySql(): int {
    // WEEKDAY(): 0=Lunes ... 6=Domingo
    // En PHP: N = 1 (Lunes) ... 7 (Domingo)
    $n = (int)date('N'); 
    return $n - 1;
}

function isScheduleOk(PDO $pdo, int $userId): bool {
    // Detectar columnas posibles
    $tblSch = 'analyst_schedules';
    if (!hasColumn($pdo, $tblSch, 'id')) {
        // si la tabla no existe o no accesible, asumimos "sin horario"
        return false;
    }

    $colUser = pickColumn($pdo, $tblSch, ['user_id', 'analyst_id', 'staff_id']);
    $colDOW  = pickColumn($pdo, $tblSch, ['day_of_week', 'weekday', 'dow', 'day']);
    $colStart= pickColumn($pdo, $tblSch, ['start_time', 'time_start', 'hora_inicio']);
    $colEnd  = pickColumn($pdo, $tblSch, ['end_time', 'time_end', 'hora_fin']);
    $colActive = pickColumn($pdo, $tblSch, ['is_active', 'active', 'enabled']);

    $colShift = pickColumn($pdo, $tblSch, ['shift_id', 'id_shift', 'catalog_shift_id']);
    $tblShift = 'catalog_shifts';
    $shiftStart = hasColumn($pdo, $tblShift, 'start_time') ? 'start_time' : (hasColumn($pdo, $tblShift, 'time_start') ? 'time_start' : null);
    $shiftEnd   = hasColumn($pdo, $tblShift, 'end_time')   ? 'end_time'   : (hasColumn($pdo, $tblShift, 'time_end')   ? 'time_end'   : null);

    if (!$colUser) return false;

    $params = [':uid' => $userId, ':dow' => nowWeekdayMySql()];

    // Si hay shift_id y catalog_shifts con start/end, usamos eso
    if ($colShift && $shiftStart && $shiftEnd) {
        $sql = "
            SELECT 1
            FROM {$tblSch} sch
            JOIN {$tblShift} sh ON sh.id = sch.{$colShift}
            WHERE sch.{$colUser} = :uid
        ";
        if ($colDOW) $sql .= " AND sch.{$colDOW} = :dow ";
        if ($colActive) $sql .= " AND sch.{$colActive} = 1 ";

        $sql .= " AND TIME(NOW()) BETWEEN sh.{$shiftStart} AND sh.{$shiftEnd} ";
        $sql .= " LIMIT 1";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    // Si tiene start_time/end_time directos, usamos eso
    if ($colStart && $colEnd) {
        $sql = "
            SELECT 1
            FROM {$tblSch} sch
            WHERE sch.{$colUser} = :uid
        ";
        if ($colDOW) $sql .= " AND sch.{$colDOW} = :dow ";
        if ($colActive) $sql .= " AND sch.{$colActive} = 1 ";

        $sql .= " AND TIME(NOW()) BETWEEN sch.{$colStart} AND sch.{$colEnd} ";
        $sql .= " LIMIT 1";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    // Si no podemos inferir horario, marcamos como NO disponible (por seguridad)
    return false;
}

function hasActiveOverride(PDO $pdo, int $userId): bool {
    $tblOv = 'analyst_status_overrides';
    if (!hasColumn($pdo, $tblOv, 'id')) {
        // si no existe, asumimos sin override
        return false;
    }

    $colUser = pickColumn($pdo, $tblOv, ['user_id', 'analyst_id', 'staff_id']);
    if (!$colUser) return false;

    $colActive = pickColumn($pdo, $tblOv, ['is_active', 'active', 'enabled']);

    // Formato A: start_at / end_at (DATETIME)
    $colStartAt = pickColumn($pdo, $tblOv, ['start_at', 'from_at', 'inicio', 'date_start', 'starts_at']);
    $colEndAt   = pickColumn($pdo, $tblOv, ['end_at', 'to_at', 'fin', 'date_end', 'ends_at']);

    // Formato B: date_from/date_to y/o time_from/time_to
    $colDateFrom = pickColumn($pdo, $tblOv, ['date_from', 'from_date', 'fecha_inicio']);
    $colDateTo   = pickColumn($pdo, $tblOv, ['date_to', 'to_date', 'fecha_fin']);
    $colTimeFrom = pickColumn($pdo, $tblOv, ['time_from', 'from_time', 'hora_inicio']);
    $colTimeTo   = pickColumn($pdo, $tblOv, ['time_to', 'to_time', 'hora_fin']);

    $params = [':uid' => $userId];

    // Caso A: DATETIME completo
    if ($colStartAt && $colEndAt) {
        $sql = "
            SELECT 1
            FROM {$tblOv} ov
            WHERE ov.{$colUser} = :uid
        ";
        if ($colActive) $sql .= " AND ov.{$colActive} = 1 ";
        $sql .= " AND NOW() BETWEEN ov.{$colStartAt} AND ov.{$colEndAt} ";
        $sql .= " LIMIT 1";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    // Caso B: fechas (y opcional horas)
    if ($colDateFrom && $colDateTo) {
        $sql = "
            SELECT 1
            FROM {$tblOv} ov
            WHERE ov.{$colUser} = :uid
        ";
        if ($colActive) $sql .= " AND ov.{$colActive} = 1 ";

        // Si también hay horas, construimos ventana dentro del mismo día; si no, solo por fechas.
        if ($colTimeFrom && $colTimeTo) {
            $sql .= "
                AND (
                    (CURDATE() > ov.{$colDateFrom} OR (CURDATE() = ov.{$colDateFrom} AND TIME(NOW()) >= ov.{$colTimeFrom}))
                )
                AND (
                    (CURDATE() < ov.{$colDateTo} OR (CURDATE() = ov.{$colDateTo} AND TIME(NOW()) <= ov.{$colTimeTo}))
                )
            ";
        } else {
            $sql .= " AND CURDATE() BETWEEN ov.{$colDateFrom} AND ov.{$colDateTo} ";
        }

        $sql .= " LIMIT 1";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    // Si no podemos inferir, no bloqueamos
    return false;
}

try {
    // USERS: columnas comunes
    $tblUsers = 'users';

    $colName   = pickColumn($pdo, $tblUsers, ['name', 'nombre', 'user_name']);
    $colLast   = pickColumn($pdo, $tblUsers, ['last', 'apellido', 'user_last']);
    $colEmail  = pickColumn($pdo, $tblUsers, ['email', 'correo', 'user_email']);
    $colPhone  = pickColumn($pdo, $tblUsers, ['phone', 'telefono', 'phone_number', 'tel']);
    $colArea   = pickColumn($pdo, $tblUsers, ['area', 'user_area']);
    $colRol    = pickColumn($pdo, $tblUsers, ['rol', 'role', 'user_rol']);
    $colActive = pickColumn($pdo, $tblUsers, ['active', 'is_active', 'enabled']);

    if (!$colName || !$colEmail || !$colArea || !$colRol) {
        throw new Exception("Estructura de users no compatible (faltan columnas base).");
    }

    $sql = "
        SELECT
            id,
            {$colName} AS n,
            " . ($colLast ? "{$colLast} AS a," : "'' AS a,") . "
            {$colEmail} AS e,
            " . ($colPhone ? "{$colPhone} AS p," : "'' AS p,") . "
            {$colArea} AS ar,
            {$colRol} AS r
        FROM {$tblUsers}
        WHERE {$colRol} IN (2,3)
    ";

    $params = [];
    if ($colActive) {
        $sql .= " AND {$colActive} = 1 ";
    }
    if ($area !== '') {
        $sql .= " AND {$colArea} = :area ";
        $params[':area'] = $area;
    }

    $sql .= " ORDER BY CASE WHEN {$colRol}=2 THEN 0 ELSE 1 END, {$colName} ASC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $admin = [];
    $analistas = [];

    foreach ($rows as $u) {
        $uid = (int)$u['id'];
        $nombre = trim(($u['n'] ?? '') . ' ' . ($u['a'] ?? ''));
        if ($nombre === '') $nombre = '—';

        // Disponibilidad simplificada:
        // No disponible si: override activo OR fuera de horario
        // Disponible si: dentro de horario y sin override
        $override = hasActiveOverride($pdo, $uid);
        $inSchedule = isScheduleOk($pdo, $uid);

        $disp = (!$override && $inSchedule) ? 'Disponible' : 'No disponible';

        $item = [
            'id' => $uid,
            'nombre' => $nombre,
            'email' => (string)($u['e'] ?? ''),
            'phone' => (string)($u['p'] ?? ''),
            'area' => (string)($u['ar'] ?? ''),
            'rol' => (int)($u['r'] ?? 0),
            'disponibilidad' => $disp
        ];

        if ((int)$u['r'] === 2) $admin[] = $item;
        if ((int)$u['r'] === 3) $analistas[] = $item;
    }

    echo json_encode([
        'ok' => true,
        'area' => $area,
        'admin' => $admin,
        'analistas' => $analistas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al cargar el equipo de soporte.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
