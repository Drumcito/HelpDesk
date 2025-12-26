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

$userId   = (int)($_POST['user_id'] ?? 0);
$status   = strtoupper(trim($_POST['status'] ?? 'AUTO'));
$startRaw = trim($_POST['starts_at'] ?? '');
$endRaw   = trim($_POST['ends_at'] ?? '');

$allowedStatus = [
    'AUTO',
    'DISPONIBLE',
    'NO_DISPONIBLE',
    'VACACIONES',
    'INCAPACIDAD',
    'PERMISO',
    'SUCURSAL'
];

if ($userId <= 0 || !in_array($status, $allowedStatus, true) || $startRaw === '' || $endRaw === '') {
    header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
    exit;
}

// datetime-local: 2025-12-24T15:30
$startsAt = str_replace('T', ' ', $startRaw) . ':00';
$endsAt   = str_replace('T', ' ', $endRaw) . ':00';

if ($startsAt >= $endsAt) {
    header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
    exit;
}

try {
    // valida que sea analista del área
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id=:id AND rol=3 AND area=:area LIMIT 1");
    $stmtCheck->execute([':id'=>$userId, ':area'=>$adminArea]);
    if (!$stmtCheck->fetchColumn()) {
        header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
        exit;
    }

    // Tu tabla real:
    // analyst_status_override(user_id UNIQUE, status, starts_at, ends_at, updated_at)
    $stmtUp = $pdo->prepare("
        INSERT INTO analyst_status_override (user_id, status, starts_at, ends_at)
        VALUES (:uid, :status, :s, :e)
        ON DUPLICATE KEY UPDATE
          status    = VALUES(status),
          starts_at = VALUES(starts_at),
          ends_at   = VALUES(ends_at),
          updated_at = NOW()
    ");
    $stmtUp->execute([
        ':uid'    => $userId,
        ':status' => $status,
        ':s'      => $startsAt,
        ':e'      => $endsAt
    ]);

} catch (Throwable $e) {
    error_log('save_override error: ' . $e->getMessage());
}

header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
exit;
