<?php
session_start();
require_once __DIR__.'/../../../config/connectionBD.php';

if ($_SESSION['user_rol'] != 3) {
    header("Location: /HelpDesk_EQF/auth/login.php");
    exit;
}

$pdo = Database::getConnection();

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

// adjuntos
$stmtA = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
$stmtA->execute([$id]);
$adjuntos = $stmtA->fetchAll();
?>

<h2>Ticket #<?php echo $ticket["id"]; ?></h2>

<p><strong>Problema:</strong> <?php echo $ticket["problema"]; ?></p>
<p><strong>Descripción:</strong> <?php echo $ticket["descripcion"]; ?></p>
<p><strong>Estado:</strong> <?php echo $ticket["estado"]; ?></p>

<h3>Adjuntos:</h3>
<ul>
<?php foreach($adjuntos as $a): ?>
    <li><a href="/HelpDesk_EQF/<?php echo $a['ruta_archivo']; ?>" download><?php echo $a['nombre_archivo']; ?></a></li>
<?php endforeach; ?>
</ul>

<form action="ticket_update.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $ticket['id']; ?>">

    <label>Actualizar estado:</label>
    <select name="estado">
        <option value="abierto">Abierto</option>
        <option value="en_proceso">En proceso</option>
        <option value="resuelto">Resuelto</option>
        <option value="cerrado">Cerrado</option>
    </select>

    <button type="submit">Guardar cambios</button>
</form>
