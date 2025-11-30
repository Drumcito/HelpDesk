<?php
session_start();
require_once __DIR__.'/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 3) {
    header("Location: /HelpDesk_EQF/auth/login.php");
    exit;
}

$pdo = Database::getConnection();

$area = $_SESSION['user_area'];
$userId = $_SESSION['user_id'];

$whereExtra = "";

if (isset($_GET['mine'])) {
    $whereExtra = " AND asignado_a = $userId ";
}

$stmt = $pdo->query("
    SELECT id, user_id, problema, fecha_envio, estado, asignado_a
    FROM tickets
    WHERE area = '$area'
    $whereExtra
    ORDER BY fecha_envio DESC
");

$tickets = $stmt->fetchAll();
?>
<html>
<head>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
</head>
<body>

<h2>Tickets del área <?php echo htmlspecialchars($area); ?></h2>

<table id="ticketsTable" class="display">
    <thead>
        <tr>
            <th>ID</th>
            <th>Problema</th>
            <th>Fecha</th>
            <th>Estatus</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
            <td><?php echo $t["id"]; ?></td>
            <td><?php echo $t["problema"]; ?></td>
            <td><?php echo $t["fecha_envio"]; ?></td>
            <td><?php echo $t["estado"]; ?></td>
            <td>
                <button onclick="location.href='ticket_view.php?id=<?php echo $t['id']; ?>'">
                    Ver / Actualizar
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    $('#ticketsTable').DataTable();
});
</script>

</body>
</html>
