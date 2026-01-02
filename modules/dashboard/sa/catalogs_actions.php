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
  'areas'           => ['table'=>'catalog_areas',           'needsArea'=>false, 'needsSla'=>false, 'needsShiftTimes'=>false],
  'status'          => ['table'=>'catalog_status',         'needsArea'=>false, 'needsSla'=>false, 'needsShiftTimes'=>false],
  'priorities'      => ['table'=>'catalog_priorities',     'needsArea'=>false, 'needsSla'=>true,  'needsShiftTimes'=>false],
  'problems'        => ['table'=>'catalog_problems',       'needsArea'=>true,  'needsSla'=>false, 'needsShiftTimes'=>false],

  'absence_reasons' => ['table'=>'catalog_absence_reasons','needsArea'=>false, 'needsSla'=>false, 'needsShiftTimes'=>false],
  'shifts'          => ['table'=>'catalog_shifts',         'needsArea'=>false, 'needsSla'=>false, 'needsShiftTimes'=>true],
  'sat_patterns'    => ['table'=>'catalog_sat_patterns',   'needsArea'=>false, 'needsSla'=>false, 'needsShiftTimes'=>false],
];

if (!isset($map[$tab])) go('&error=1');
$table = $map[$tab]['table'];

try {

  /* =========================
     CREATE
  ========================= */
  if ($action === 'create') {
    $code   = trim($_POST['code'] ?? '');
    $label  = trim($_POST['label'] ?? '');
    $sort   = (int)($_POST['sort_order'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    if ($code==='' || $label==='') go('&error=1');

    // Shifts: requiere start/end
    if ($map[$tab]['needsShiftTimes']) {
      $start = trim($_POST['start_time'] ?? '');
      $end   = trim($_POST['end_time'] ?? '');
      if ($start==='' || $end==='') go('&error=1');

      $st = $pdo->prepare("INSERT INTO `$table` (code, label, start_time, end_time, sort_order, active)
                           VALUES (?,?,?,?,?,?)");
      $st->execute([$code, $label, $start, $end, $sort, $active]);
      go('&created=1');
    }

    // Problems: requiere area_code
    if ($map[$tab]['needsArea']) {
      $area = trim($_POST['area_code'] ?? '');
      if ($area==='') go('&error=1');

      $st = $pdo->prepare("INSERT INTO `$table` (area_code, code, label, sort_order, active)
                           VALUES (?,?,?,?,?)");
      $st->execute([$area, $code, $label, $sort, $active]);
      go('&created=1');
    }

    // Priorities: requiere sla_hours
    if ($map[$tab]['needsSla']) {
      $sla = (int)($_POST['sla_hours'] ?? 0);

      $st = $pdo->prepare("INSERT INTO `$table` (code, label, sla_hours, sort_order, active)
                           VALUES (?,?,?,?,?)");
      $st->execute([$code, $label, $sla, $sort, $active]);
      go('&created=1');
    }

    // Default
    $st = $pdo->prepare("INSERT INTO `$table` (code, label, sort_order, active)
                         VALUES (?,?,?,?)");
    $st->execute([$code, $label, $sort, $active]);
    go('&created=1');
  }

  /* =========================
     UPDATE
  ========================= */
  if ($action === 'update') {
    $code   = trim($_POST['code'] ?? '');
    $label  = trim($_POST['label'] ?? '');
    $sort   = (int)($_POST['sort_order'] ?? 0);
    $active = (int)($_POST['active'] ?? 1);

    if ($id<=0 || $code==='' || $label==='') go('&error=1');

    // Shifts: requiere start/end
    if ($map[$tab]['needsShiftTimes']) {
      $start = trim($_POST['start_time'] ?? '');
      $end   = trim($_POST['end_time'] ?? '');
      if ($start==='' || $end==='') go('&error=1');

      $st = $pdo->prepare("UPDATE `$table`
                           SET code=?, label=?, start_time=?, end_time=?, sort_order=?, active=?
                           WHERE id=?");
      $st->execute([$code, $label, $start, $end, $sort, $active, $id]);
      go('&updated=1');
    }

    // Problems: requiere area_code
    if ($map[$tab]['needsArea']) {
      $area = trim($_POST['area_code'] ?? '');
      if ($area==='') go('&error=1');

      $st = $pdo->prepare("UPDATE `$table`
                           SET area_code=?, code=?, label=?, sort_order=?, active=?
                           WHERE id=?");
      $st->execute([$area, $code, $label, $sort, $active, $id]);
      go('&updated=1');
    }

    // Priorities: requiere sla_hours
    if ($map[$tab]['needsSla']) {
      $sla = (int)($_POST['sla_hours'] ?? 0);

      $st = $pdo->prepare("UPDATE `$table`
                           SET code=?, label=?, sla_hours=?, sort_order=?, active=?
                           WHERE id=?");
      $st->execute([$code, $label, $sla, $sort, $active, $id]);
      go('&updated=1');
    }

    // Default
    $st = $pdo->prepare("UPDATE `$table`
                         SET code=?, label=?, sort_order=?, active=?
                         WHERE id=?");
    $st->execute([$code, $label, $sort, $active, $id]);
    go('&updated=1');
  }

  /* =========================
     TOGGLE
  ========================= */
  if ($action === 'toggle') {
    if ($id<=0) go('&error=1');

    $st = $pdo->prepare("UPDATE `$table` SET active = IF(active=1,0,1) WHERE id=?");
    $st->execute([$id]);
    go('&toggled=1');
  }

  go('&error=1');

} catch (Throwable $e) {
  go('&error=1');
}
