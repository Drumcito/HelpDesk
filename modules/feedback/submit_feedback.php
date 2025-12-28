<?php
require_once __DIR__ . '/../../config/connectionBD.php';
$pdo = Database::getConnection();

$token = $_POST['token'] ?? '';

$q1 = (int)($_POST['q1'] ?? 0);
$q2 = (int)($_POST['q2'] ?? 0);
$q3 = (int)($_POST['q3'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!$token || !$q1 || !$q2 || !$q3) {
    exit('Datos incompletos.');
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
    exit('Encuesta inválida o ya respondida.');
}

echo "¡Gracias por tu feedback!";
