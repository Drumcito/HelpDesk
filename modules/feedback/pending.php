<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

$pdo = Database::getConnection();

$stmt = $pdo->prepare("
    SELECT f.token, t.problema
    FROM ticket_feedback f
    JOIN tickets t ON t.id = f.ticket_id
    WHERE f.user_id = ?
      AND f.answered_at IS NULL
");
$stmt->execute([$_SESSION['user_id']]);
$pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Tienes encuestas pendientes</h2>
<p>Para crear un nuevo ticket, primero responde las siguientes encuestas:</p>

<ul>
<?php foreach ($pendientes as $p): ?>
  <li>
    <?= htmlspecialchars($p['problema']) ?> —
    <a href="feedback.php?token=<?= htmlspecialchars($p['token']) ?>">
        Responder encuesta
    </a>
  </li>
<?php endforeach; ?>
</ul>
