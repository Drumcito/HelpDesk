<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
    exit;
}

$pdo = Database::getConnection();

$adminArea = trim($_SESSION['user_area'] ?? '');
$userId    = (int)($_POST['user_id'] ?? 0);
$shift     = trim($_POST['shift'] ?? '8_1730');
$sat       = trim($_POST['sat_pattern'] ?? '1y3');

$lunchStart = trim($_POST['lunch_start'] ?? '');
$lunchEnd   = trim($_POST['lunch_end'] ?? '');

$allowedShift = ['8_1730','9_1830'];
$allowedSat   = ['1y3','2y4','all'];

if ($userId <= 0 || !in_array($shift, $allowedShift, true) || !in_array($sat, $allowedSat, true)) {
    header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
    exit;
}

// Normaliza comida
$lsDb = ($lunchStart !== '') ? ($lunchStart . ':00') : null;
$leDb = ($lunchEnd   !== '') ? ($lunchEnd   . ':00') : null;

// si solo llenan uno, nullear ambos
if (($lsDb && !$leDb) || (!$lsDb && $leDb)) {
    $lsDb = null;
    $leDb = null;
}

// si están invertidos, nullear ambos (o swap si prefieres)
if ($lsDb && $leDb && $lsDb >= $leDb) {
    $lsDb = null;
    $leDb = null;
}

try {
    // Verifica que sea analista del área del admin
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id=:id AND rol=3 AND area=:area LIMIT 1");
    $stmtCheck->execute([':id'=>$userId, ':area'=>$adminArea]);
    if (!$stmtCheck->fetchColumn()) {
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
        exit;
    }

    $stmtUp = $pdo->prepare("
        INSERT INTO analyst_schedules (user_id, shift, sat_pattern, lunch_start, lunch_end)
        VALUES (:uid, :shift, :sat, :ls, :le)
        ON DUPLICATE KEY UPDATE
            shift=VALUES(shift),
            sat_pattern=VALUES(sat_pattern),
            lunch_start=VALUES(lunch_start),
            lunch_end=VALUES(lunch_end),
            updated_at=NOW()
    ");
    $stmtUp->execute([
        ':uid'   => $userId,
        ':shift' => $shift,
        ':sat'   => $sat,
        ':ls'    => $lsDb,
        ':le'    => $leDb
    ]);

} catch (Throwable $e) {
    // opcional: log
    error_log('save_schedule error: ' . $e->getMessage());
}

header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
exit;
