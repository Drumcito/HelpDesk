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
<div class="rating-group">
  <label class="btn-rating malo">
    <input type="radio" name="q1" value="1" required>
    <div class="content">
      <img src="../../assets/img/feedback_bad.png" alt="Malo">
      <span>Malo</span>
    </div>
  </label>
<label class="btn-rating regular">
    <input type="radio" name="q1" value="2">
    <div class="content">
      <img src="../../assets/img/feedback_reg.png" alt="Regular">
      <span>Regular</span>
    </div>
  </label>
<label>
<label class="btn-rating bueno">
    <input type="radio" name="q1" value="3">
    <div class="content">
      <img src="../../assets/img/feedback_good.png" alt="Bueno">
      <span>Bueno</span>
    </div>
  </label>
</div>

<h4>¿El problema fue resuelto completamente?</h4>
<div class="rating-group">
  <label class="btn-rating malo">
    <input type="radio" name="q2" value="1" required>
    <div class="content">
      <img src="../../assets/img/feedback_no.png" alt="No">
      <span>No</span>
    </div>
  </label>

  <label class="btn-rating bueno">
    <input type="radio" name="q2" value="2">
    <div class="content">
      <img src="../../assets/img/feedback_yes.png" alt="Sí">
      <span>Sí</span>
    </div>
  </label>
</div>

<h4>¿Cómo calificas el tiempo de respuesta?</h4>
<div class="rating-group">
  <label class="btn-rating malo">
    <input type="radio" name="q3" value="1" required>
    <div class="content">
      <img src="../../assets/img/feedback_bad.png" alt="Lento">
      <span>Malo</span>
    </div>
  </label>

  <label class="btn-rating regular">
    <input type="radio" name="q3" value="2">
    <div class="content">
      <img src="../../assets/img/feedback_reg.png" alt="Normal">
      <span>Regular</span>
    </div>
  </label>

  <label class="btn-rating bueno">
    <input type="radio" name="q3" value="3">
    <div class="content">
      <img src="../../assets/img/feedback_good.png" alt="Rápido">
      <span>Bueno</span>
    </div>
  </label>
</div>

<textarea name="comment" placeholder="Comentarios (opcional)"></textarea>

<button type="submit">Enviar encuesta</button>

</form>
</body>
</html>
