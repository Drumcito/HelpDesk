<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';
include __DIR__ . '/../../template/header.php';
include __DIR__ . '/../../template/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();

$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';
$userSap   = $_SESSION['number_sap'] ?? '';
$rol       = (int)($_SESSION['user_rol'] ?? 0);

// ----------------- helper de problema -----------------
function problemaLabel(string $p): string {
    return match ($p) {
        'cierre_dia'   => 'Cierre del día',
        'no_legado'    => 'Sin acceso a legado/legacy',
        'no_internet'  => 'Sin internet',
        'no_checador'  => 'No funciona checador',
        'rastreo'      => 'Rastreo de checada',
        'otro'         => 'Otro',
        default        => $p,
    };
}

// ----------------- construir consulta según rol -----------------
$showUserColumn = false;   // para la tabla
$title          = 'Historial de tickets';
$subtitle       = '';

if ($rol === 4) {
    // USUARIO FINAL: todos sus tickets
    $sql = "
        SELECT id, nombre, problema, descripcion, fecha_envio, estado, fecha_resolucion
        FROM tickets
        WHERE user_id = :uid
        ORDER BY fecha_envio DESC
    ";
    $params   = [':uid' => $userId];
    $title    = 'Historial de tus tickets';
    $subtitle = 'Aquí puedes consultar todos los tickets que has creado y su estatus actual.';
    $showUserColumn = false; // el usuario siempre es él mismo
} elseif ($rol === 3) {
    // ANALISTA: historial de tickets atendidos
    $sql = "
        SELECT id, nombre, problema, descripcion, fecha_envio, estado, fecha_resolucion
        FROM tickets
        WHERE asignado_a = :uid
          AND estado IN ('resuelto','cerrado')
        ORDER BY fecha_resolucion DESC, fecha_envio DESC
    ";
    $params   = [':uid' => $userId];
    $title    = 'Historial de tickets atendidos';
    $subtitle = 'Tickets que han sido resueltos o cerrados y fueron asignados a ti.';
    $showUserColumn = true; // mostrar columna Usuario
} else {
    // SA / ADMIN: todos los tickets
    $sql = "
        SELECT id, nombre, problema, descripcion, fecha_envio, estado, fecha_resolucion
        FROM tickets
        ORDER BY fecha_envio DESC
    ";
    $params   = [];
    $title    = 'Historial de todos los tickets';
    $subtitle = 'Listado general de tickets en el sistema.';
    $showUserColumn = true;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
</head>
<body class="user-body">

<main class="user-main">
    <section class="user-main-inner">

        <header class="user-main-header">
            <div>
                <p class="login-brand">
                    <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
                </p>
                <p class="user-main-subtitle">
                    <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>

            
        </header>

        <section class="user-main-content">
            <div class="user-info-card">
                <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="user-tickets-table-wrapper">
                <table id="historyTicketsTable" class="data-table display">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if ($showUserColumn): ?>
                                <th>Usuario</th>
                            <?php endif; ?>
                            <th>Problema</th>
                            <th>Descripción</th>
                            <th>Fecha envío</th>
                            <th>Fecha resolución</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?php echo (int)$t['id']; ?></td>

                            <?php if ($showUserColumn): ?>
                                <td><?php echo htmlspecialchars($t['nombre'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <?php endif; ?>

                            <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($t['fecha_resolucion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </section>

    </section>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#historyTicketsTable').DataTable({
            pageLength: 10,
            order: [[4, 'desc']] // orden por fecha envío
        });
    });
</script>
</body>
</html>
