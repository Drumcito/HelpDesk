<?php
session_start();
require_once __DIR__ . '/../../../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']); exit;
}

function currentRole(): int {
  if (isset($_SESSION['user_rol'])) return (int)$_SESSION['user_rol'];
  if (isset($_SESSION['rol'])) return (int)$_SESSION['rol'];
  return 0;
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
$rol = currentRole();

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

// Traer task
$stmt = $pdo->prepare("
  SELECT t.*,
         cp.label AS priority_name
  FROM tasks t
  JOIN catalog_priorities cp ON cp.id = t.priority_id
  WHERE t.id = ?
  LIMIT 1
");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) { echo json_encode(['ok'=>false,'msg'=>'No existe']); exit; }

// permisos: admin solo si la creó, analista solo si se la asignaron
if ($rol === 2 && (int)$task['created_by_admin_id'] !== $userId) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
if ($rol === 3 && (int)$task['assigned_to_user_id'] !== $userId) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }

// archivos
$stmtF = $pdo->prepare("
  SELECT id, file_type, original_name, stored_name, created_at
  FROM task_files
  WHERE task_id = ? AND is_deleted = 0
  ORDER BY created_at DESC
");
$stmtF->execute([$taskId]);
$files = $stmtF->fetchAll(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
  'ok'=>true,
  'task'=>$task,
  'files'=>$files
], JSON_UNESCAPED_UNICODE);
