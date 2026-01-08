<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol']))      return (int)$_SESSION['rol'];
  return 0;
}

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No auth']);
  exit;
}

$pdo = Database::getConnection();
$uid = (int)$_SESSION['user_id'];
$rol = currentRole();

$taskId = (int)($_GET['task_id'] ?? 0); // opcional para view.php

try {
  if (!in_array($rol, [2,3], true)) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No role']);
    exit;
  }

  if ($rol === 2) {
    if ($taskId > 0) {
      $stmt = $pdo->prepare("SELECT id,status,due_at,created_at FROM tasks WHERE id=? AND created_by_admin_id=? LIMIT 1");
      $stmt->execute([$taskId, $uid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
      echo json_encode(['ok'=>true,'signature'=>sha1(json_encode($row))]);
      exit;
    }

    $stmt = $pdo->prepare("
      SELECT id,status,due_at,created_at,assigned_to_user_id,priority_id
      FROM tasks
      WHERE created_by_admin_id=?
      ORDER BY created_at DESC
      LIMIT 200
    ");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['ok'=>true,'signature'=>sha1(json_encode($rows))]);
    exit;
  }

  // rol 3 (analista)
  if ($taskId > 0) {
    $stmt = $pdo->prepare("SELECT id,status,due_at,created_at FROM tasks WHERE id=? AND assigned_to_user_id=? LIMIT 1");
    $stmt->execute([$taskId, $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['ok'=>true,'signature'=>sha1(json_encode($row))]);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT id,status,due_at,created_at,created_by_admin_id,priority_id
    FROM tasks
    WHERE assigned_to_user_id=?
    ORDER BY created_at DESC
    LIMIT 200
  ");
  $stmt->execute([$uid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  echo json_encode(['ok'=>true,'signature'=>sha1(json_encode($rows))]);

} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Server error']);
}
