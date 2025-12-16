<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php');
  exit;
}
if ((int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php');
  exit;
}

$pdo = Database::getConnection();
$action = $_POST['action'] ?? '';
$back = '/HelpDesk_EQF/modules/dashboard/sa/priority_catalog.php';

function go($qs=''){ header('Location: ' . $GLOBALS['back'] . $qs); exit; }

try {
  if ($action === 'create') {
    $code = trim($_POST['code'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $sla = (int)($_POST['sla_hours'] ?? 0);
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    if ($code === '' || $label === '') go('?error=1');

    $st = $pdo->prepare("INSERT INTO catalog_priorities (code, label, sla_hours, sort_order, active)
                         VALUES (?,?,?,?,?)");
    $st->execute([$code, $label, $sla, $sort, $active]);
    go('?created=1');
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $sla = (int)($_POST['sla_hours'] ?? 0);
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    if ($id <= 0 || $code === '' || $label === '') go('?error=1');

    $st = $pdo->prepare("UPDATE catalog_priorities
                         SET code=?, label=?, sla_hours=?, sort_order=?, active=?
                         WHERE id=?");
    $st->execute([$code, $label, $sla, $sort, $active, $id]);
    go('?updated=1');
  }

  if ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) go('?error=1');

    $st = $pdo->prepare("UPDATE catalog_priorities SET active = IF(active=1,0,1) WHERE id=?");
    $st->execute([$id]);
    go('?toggled=1');
  }

  go('?error=1');

} catch (Throwable $e) {
  go('?error=1');
}
