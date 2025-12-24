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
$status = $_POST['status'] ?? 'AUTO';
$note   = trim($_POST['note'] ?? '');
$until  = trim($_POST['until_at'] ?? '');

$valid = ['AUTO','DISPONIBLE','OCUPADO','AUSENTE','VACACIONES', 'SUCURSAL'];
if ($userId <= 0 || !in_array($status, $valid, true)) {
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

$untilDb = null;
if ($until !== '') {
  // datetime-local viene como 2025-12-24T14:30
  $untilDb = str_replace('T', ' ', $until) . ':00';
}

if ($note !== '' && mb_strlen($note) > 255) $note = mb_substr($note, 0, 255);

// Upsert
$stmtUp = $pdo->prepare("
  INSERT INTO analyst_status_overrides (user_id, status, note, until_at)
  VALUES (:uid, :st, :note, :until)
  ON DUPLICATE KEY UPDATE status=VALUES(status), note=VALUES(note), until_at=VALUES(until_at), updated_at=NOW()
");
$stmtUp->execute([
  ':uid'=>$userId,
  ':st'=>$status,
  ':note'=>($note!==''?$note:null),
  ':until'=>$untilDb
]);

header('Location: /HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php');
exit;
