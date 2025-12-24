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

$userId = (int)($_POST['user_id'] ?? 0);
$shift = $_POST['shift'] ?? '8_1730';
$sat   = $_POST['sat_pattern'] ?? '1y3';

$validShift = ['8_1730','9_1830'];
$validSat   = ['1y3','2y4','todos'];

if ($userId <= 0 || !in_array($shift, $validShift, true) || !in_array($sat, $validSat, true)) {
  header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
  exit;
}

// Validar que sea analista del área
$stmt = $pdo->prepare("SELECT id FROM users WHERE id=:id AND rol=3 AND area=:area LIMIT 1");
$stmt->execute([':id'=>$userId, ':area'=>$adminArea]);
if (!$stmt->fetchColumn()) {
  header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
  exit;
}

// Upsert
$stmtUp = $pdo->prepare("
  INSERT INTO analyst_schedules (user_id, shift, sat_pattern)
  VALUES (:uid, :shift, :sat)
  ON DUPLICATE KEY UPDATE shift=VALUES(shift), sat_pattern=VALUES(sat_pattern), updated_at=NOW()
");
$stmtUp->execute([':uid'=>$userId, ':shift'=>$shift, ':sat'=>$sat]);

header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
exit;
