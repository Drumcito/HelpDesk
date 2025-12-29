<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$taskId   = (int)($_POST['task_id'] ?? 0);
$analystId = (int)$_SESSION['user_id'];
$analystName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));

if ($taskId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Tarea inválida'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = Database::getConnection();

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("SELECT id, admin_id, analyst_id, estado, titulo FROM analyst_tasks WHERE id = :id LIMIT 1");
  $stmt->execute([':id'=>$taskId]);
  $t = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$t) {
    $pdo->rollBack();
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'No encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ((int)$t['analyst_id'] !== $analystId) {
    $pdo->rollBack();
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No puedes modificar esta tarea'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ($t['estado'] !== 'en_proceso') {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'msg'=>'Primero marca ENTERADO (en proceso)'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $up = $pdo->prepare("UPDATE analyst_tasks SET estado='cerrada', updated_at = NOW() WHERE id=:id");
  $up->execute([':id'=>$taskId]);

  // Notificar admin
  $adminId = (int)$t['admin_id'];
  if ($adminId > 0) {
    $title = 'Tarea finalizada';
    $body  = sprintf('El analista %s marcó FINALIZADA la tarea #%d: %s',
      ($analystName !== '' ? $analystName : 'Analista'),
      $taskId,
      (string)($t['titulo'] ?? '')
    );

    // >>>> AJUSTA AQUÍ si tu tabla no se llama staff_notifications <<<<
    $ins = $pdo->prepare("
      INSERT INTO staff_notifications (user_id, title, body, leido, created_at)
      VALUES (:uid, :title, :body, 0, NOW())
    ");
    $ins->execute([
      ':uid'   => $adminId,
      ':title' => $title,
      ':body'  => $body
    ]);
  }

  $pdo->commit();
  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('finish_task error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno'], JSON_UNESCAPED_UNICODE);
  exit;
}
