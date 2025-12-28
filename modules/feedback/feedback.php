<?php
require_once __DIR__ . '/../../config/connectionBD.php';
$pdo = Database::getConnection();

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("
    SELECT f.id, f.ticket_id, t.problema
    FROM ticket_feedback f
    JOIN tickets t ON t.id = f.ticket_id
    WHERE f.token = ?
      AND f.answered_at IS NULL
");
$stmt->execute([$token]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$feedback) {
    exit('Encuesta inválida o ya respondida.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Encuesta de satisfacción</title>
</head>
<body>

<h2>Encuesta de satisfacción</h2>
<p><?= htmlspecialchars($feedback['problema']) ?></p>

<form method="POST" action="submit_feedback.php">

<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

<h4>¿Cómo calificas la atención recibida?</h4>
<label>
  <input type="radio" name="q1" value="1" required>
  Malo
</label>
<label>
  <input type="radio" name="q1" value="2">
  Regular
</label>
<label>
  <input type="radio" name="q1" value="3">
  Bueno
</label>

<h4>¿El problema fue resuelto completamente?</h4>
<label>
  <input type="radio" name="q2" value="1" required>
  No
</label>
<label>
  <input type="radio" name="q2" value="2">
  Sí
</label>

<h4>¿Cómo calificas el tiempo de respuesta?</h4>
<label>
  <input type="radio" name="q3" value="1" required>
  Malo
</label>
<label>
  <input type="radio" name="q3" value="2">
  Regular
</label>
<label>
  <input type="radio" name="q3" value="3">
  Bueno
</label>

<textarea name="comment" placeholder="Comentarios (opcional)"></textarea>

<button type="submit">Enviar encuesta</button>

</form>
</body>
</html>
