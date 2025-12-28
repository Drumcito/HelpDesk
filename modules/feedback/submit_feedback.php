<?php
require_once __DIR__ . '/../../config/connectionBD.php';
$pdo = Database::getConnection();

header('Content-Type: application/json; charset=utf-8');

$token = trim((string)($_POST['token'] ?? ''));

$q1 = (int)($_POST['q1'] ?? 0); // 1..3
$q2 = (int)($_POST['q2'] ?? 0); // 1..2
$q3 = (int)($_POST['q3'] ?? 0); // 1..3
$comment = trim((string)($_POST['comment'] ?? ''));

$valid =
    $token !== '' &&
    in_array($q1, [1, 2, 3], true) &&
    in_array($q2, [1, 2], true) &&
    in_array($q3, [1, 2, 3], true) &&
    mb_strlen($comment, 'UTF-8') <= 500;

if (!$valid) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE ticket_feedback
    SET q1_attention = ?,
        q2_resolved  = ?,
        q3_time      = ?,
        comment      = ?,
        answered_at  = NOW()
    WHERE token = ?
      AND answered_at IS NULL
");
$stmt->execute([$q1, $q2, $q3, $comment, $token]);

if ($stmt->rowCount() === 0) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'msg' => 'Encuesta inválida o ya respondida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'msg' => '¡Gracias por tu feedback!'], JSON_UNESCAPED_UNICODE);
