<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/Mexico_City');

// Solo Admin (rol=2)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

$pdo       = Database::getConnection();
$areaAdmin = trim($_SESSION['user_area'] ?? '');
if ($areaAdmin === '') {
    echo json_encode(['ok' => true, 'data' => [], 'server_time' => date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------------------
   Helpers
--------------------------- */

function is5thSaturday(DateTime $d): bool {
    if ((int)$d->format('N') !== 6) return false; // sábado
    $day = (int)$d->format('j');
    return $day >= 29; // 29-31 => 5to sábado si existe
}

function saturdayWeekIndex(DateTime $d): int {
    $day = (int)$d->format('j');
    return (int)floor(($day - 1) / 7) + 1; // 1..5
}

function satWorksToday(string $pattern, DateTime $now): bool {
    // pattern: all | 1_3 | 2_4
    if ((int)$now->format('N') !== 6) return false;

    // regla: si hay 5 sábados, trabajan todos
    if (is5thSaturday($now)) return true;

    $idx = saturdayWeekIndex($now); // 1..4
    if ($pattern === 'all') return true;
    if ($pattern === '1_3') return in_array($idx, [1,3], true);
    if ($pattern === '2_4') return in_array($idx, [2,4], true);

    return false;
}

function resolveAvailability(array $a, DateTime $now): array {
    // Prioridad:
    // 1) Override vigente => NO_DISPONIBLE por motivo
    // 2) Fuera de horario => FUERA_DE_HORARIO
    // 3) En comida => EN_COMIDA
    // 4) Disponible

    // Override
    if (!empty($a['override_status']) && !empty($a['override_start']) && !empty($a['override_end'])) {
        $os = new DateTime($a['override_start']);
        $oe = new DateTime($a['override_end']);
        if ($now >= $os && $now <= $oe) {
            return [
                'status' => strtoupper((string)$a['override_status']),
                'start'  => $os->format('Y-m-d H:i'),
                'end'    => $oe->format('Y-m-d H:i'),
            ];
        }
    }

    $dayN = (int)$now->format('N'); // 1..7
    if ($dayN === 7) return ['status' => 'FUERA_DE_HORARIO']; // domingo

    $shift = (string)($a['shift'] ?? '8_1730');

    // Horario base
    $start = null;
    $end   = null;

    if ($dayN >= 1 && $dayN <= 5) {
        if ($shift === '9_1830') {
            $start = '09:00:00';
            $end   = '18:30:00';
        } else {
            $start = '08:00:00';
            $end   = '17:30:00';
        }
    } elseif ($dayN === 6) {
        // Sábado 08:00–14:00 SOLO si le toca
        $pattern = (string)($a['sat_pattern'] ?? 'all');
        if (!satWorksToday($pattern, $now)) {
            return ['status' => 'FUERA_DE_HORARIO'];
        }
        $start = '08:00:00';
        $end   = '14:00:00';
    }

    if (!$start || !$end) return ['status' => 'FUERA_DE_HORARIO'];

    $date = $now->format('Y-m-d');
    $s = new DateTime($date . ' ' . $start);
    $e = new DateTime($date . ' ' . $end);

    if ($now < $s || $now > $e) {
        return ['status' => 'FUERA_DE_HORARIO'];
    }

    // Comida (automático si aplica)
    if (!empty($a['lunch_start']) && !empty($a['lunch_end'])) {
        $ls = new DateTime($date . ' ' . $a['lunch_start']);
        $le = new DateTime($date . ' ' . $a['lunch_end']);
        if ($now >= $ls && $now <= $le) {
            return ['status' => 'EN_COMIDA'];
        }
    }

    return ['status' => 'DISPONIBLE'];
}

function toBadgePayload(array $av): array {
    $badgeClass = 'b-gray';
    $badgeText  = 'Auto';
    $extraHtml  = '';

    if ($av['status'] === 'DISPONIBLE') {
        $badgeClass='b-ok'; $badgeText='Disponible';
    } elseif ($av['status'] === 'EN_COMIDA') {
        $badgeClass='b-warn'; $badgeText='En comida';
    } elseif ($av['status'] === 'FUERA_DE_HORARIO') {
        $badgeClass='b-gray'; $badgeText='Fuera de horario';
    } else {
        // override
        $badgeClass='b-bad';
        $badgeText = ucfirst(strtolower($av['status']));
        if (!empty($av['start']) && !empty($av['end'])) {
            $extraHtml = 'Desde: ' . htmlspecialchars($av['start']) . '<br>Hasta: ' . htmlspecialchars($av['end']);
        }
    }

    return [
        'status'      => $av['status'],
        'badge_class' => $badgeClass,
        'badge_text'  => $badgeText,
        'extra_html'  => $extraHtml
    ];
}

/* ---------------------------
   Query: analistas del área + horario + última override
--------------------------- */

try {
    $sql = "
        SELECT
            u.id,
            u.name,
            u.last_name,
            u.email,

            s.shift,
            s.sat_pattern,
            s.lunch_start,
            s.lunch_end,

            o.status    AS override_status,
            o.starts_at AS override_start,
            o.ends_at   AS override_end

        FROM users u
        LEFT JOIN analyst_schedules s
               ON s.user_id = u.id

        LEFT JOIN (
            SELECT x.*
            FROM analyst_status_overrides x
            INNER JOIN (
                SELECT user_id, MAX(id) AS max_id
                FROM analyst_status_overrides
                GROUP BY user_id
            ) m ON m.user_id = x.user_id AND m.max_id = x.id
        ) o ON o.user_id = u.id

        WHERE u.rol = 3
          AND u.area = :area
        ORDER BY u.last_name ASC, u.name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':area' => $areaAdmin]);
    $analysts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $now = new DateTime();

    $out = [];
    foreach ($analysts as $a) {
        $av = resolveAvailability($a, $now);
        $b  = toBadgePayload($av);

        $out[] = [
            'id'          => (int)$a['id'],
            'badge_class' => $b['badge_class'],
            'badge_text'  => $b['badge_text'],
            'extra_html'  => $b['extra_html'],
            'status'      => $b['status'],
        ];
    }

    echo json_encode([
        'ok' => true,
        'server_time' => $now->format('Y-m-d H:i:s'),
        'data' => $out
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
