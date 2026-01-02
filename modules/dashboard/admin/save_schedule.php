<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
  header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}

$pdo = Database::getConnection();

$userId      = (int)($_POST['user_id'] ?? 0);
$shift       = trim($_POST['shift'] ?? '');
$satPattern  = trim($_POST['sat_pattern'] ?? '');
$lunchStart  = trim($_POST['lunch_start'] ?? '');
$lunchEnd    = trim($_POST['lunch_end'] ?? '');

$back = '/HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php';

if ($userId <= 0 || $shift==='' || $satPattern==='') {
  header("Location: $back?error=1"); exit;
}

try {
  // valida shift contra catálogo
  $st = $pdo->prepare("SELECT 1 FROM catalog_shifts WHERE code=? AND active=1 LIMIT 1");
  $st->execute([$shift]);
  if (!$st->fetchColumn()) { header("Location: $back?error=1"); exit; }

  // valida sat_pattern contra catálogo
  $st = $pdo->prepare("SELECT 1 FROM catalog_sat_patterns WHERE code=? AND active=1 LIMIT 1");
  $st->execute([$satPattern]);
  if (!$st->fetchColumn()) { header("Location: $back?error=1"); exit; }

  // normaliza comida (puede ir vacío)
  $lunchStartDb = ($lunchStart !== '') ? $lunchStart . ':00' : null;
  $lunchEndDb   = ($lunchEnd   !== '') ? $lunchEnd   . ':00' : null;

  // si uno viene y el otro no -> error
  if (($lunchStartDb && !$lunchEndDb) || (!$lunchStartDb && $lunchEndDb)) {
    header("Location: $back?error=1"); exit;
  }

  // upsert schedule
  $sql = "
    INSERT INTO analyst_schedules (user_id, shift, sat_pattern, lunch_start, lunch_end)
    VALUES (?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      shift=VALUES(shift),
      sat_pattern=VALUES(sat_pattern),
      lunch_start=VALUES(lunch_start),
      lunch_end=VALUES(lunch_end)
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$userId, $shift, $satPattern, $lunchStartDb, $lunchEndDb]);

  header("Location: $back?updated=1"); exit;

} catch(Throwable $e) {
  header("Location: $back?error=1"); exit;
}
