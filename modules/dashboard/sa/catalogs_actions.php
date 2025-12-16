<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: /HelpDesk_EQF/auth/login.php'); exit;
}
if ((int)($_SESSION['user_rol'] ?? 0) !== 1) {
  header('Location: /HelpDesk_EQF/modules/dashboard/user/user.php'); exit;
}

$pdo = Database::getConnection();

$action = $_POST['action'] ?? '';
$tab    = $_POST['tab'] ?? 'areas';
$id     = (int)($_POST['id'] ?? 0);

$back = '/HelpDesk_EQF/modules/dashboard/sa/catalogs.php?tab=' . urlencode($tab);
function go($qs=''){ header('Location: ' . $GLOBALS['back'] . $qs); exit; }

$map = [
  'areas' => ['table'=>'catalog_areas', 'needsArea'=>false, 'needsSla'=>false],
  'status' => ['table'=>'catalog_status', 'needsArea'=>false, 'needsSla'=>false],
  'priorities' => ['table'=>'catalog_priorities', 'needsArea'=>false, 'needsSla'=>true],
  'problems' => ['table'=>'catalog_problems', 'needsArea'=>true, 'needsSla'=>false],
];

if (!isset($map[$tab])) go('&error=1');
$table = $map[$tab]['table'];

try {
  if ($action === 'create') {
    $code = trim($_POST['code'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    if ($code==='' || $label==='') go('&error=1');

    if ($map[$tab]['needsArea']) {
      $area = trim($_POST['area_code'] ?? '');
      if ($area==='') go('&error=1');
      $st = $pdo->prepare("INSERT INTO `$table` (area_code, code, label, sort_order, active) VALUES (?,?,?,?,?)");
      $st->execute([$area, $code, $label, $sort, $active]);
      go('&created=1');
    }

    if ($map[$tab]['needsSla']) {
      $sla = (int)($_POST['sla_hours'] ?? 0);
      $st = $pdo->prepare("INSERT INTO `$table` (code, label, sla_hours, sort_order, active) VALUES (?,?,?,?,?)");
      $st->execute([$code, $label, $sla, $sort, $active]);
      go('&created=1');
    }

    $st = $pdo->prepare("INSERT INTO `$table` (code, label, sort_order, active) VALUES (?,?,?,?)");
    $st->execute([$code, $label, $sort, $active]);
    go('&created=1');
  }

  if ($action === 'update') {
    $code = trim($_POST['code'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    if ($id<=0 || $code==='' || $label==='') go('&error=1');

    if ($map[$tab]['needsArea']) {
      $area = trim($_POST['area_code'] ?? '');
      if ($area==='') go('&error=1');
      $st = $pdo->prepare("UPDATE `$table` SET area_code=?, code=?, label=?, sort_order=?, active=? WHERE id=?");
      $st->execute([$area, $code, $label, $sort, $active, $id]);
      go('&updated=1');
    }

    if ($map[$tab]['needsSla']) {
      $sla = (int)($_POST['sla_hours'] ?? 0);
      $st = $pdo->prepare("UPDATE `$table` SET code=?, label=?, sla_hours=?, sort_order=?, active=? WHERE id=?");
      $st->execute([$code, $label, $sla, $sort, $active, $id]);
      go('&updated=1');
    }

    $st = $pdo->prepare("UPDATE `$table` SET code=?, label=?, sort_order=?, active=? WHERE id=?");
    $st->execute([$code, $label, $sort, $active, $id]);
    go('&updated=1');
  }

  if ($action === 'toggle') {
    if ($id<=0) go('&error=1');
    $st = $pdo->prepare("UPDATE `$table` SET active = IF(active=1,0,1) WHERE id=?");
    $st->execute([$id]);
    go('&toggled=1');
  }

  go('&error=1');

} catch(Throwable $e) {
  go('&error=1');
}
