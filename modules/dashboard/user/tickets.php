<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

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

// Consulta TODOS los tickets de ese usuario
$stmt = $pdo->prepare("
    SELECT id, problema, descripcion, fecha_envio, estado
    FROM tickets
    WHERE user_id = :uid
    ORDER BY fecha_envio DESC
");
$stmt->execute([':uid' => $userId]);
$tickets = $stmt->fetchAll();

// helper para mostrar texto bonito del problema
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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tus tickets | HELP DESK EQF</title>
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
                        Tus tickets, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>.
                    </p>
                </div>
            </header>

            <section class="user-main-content">
                <div class="user-info-card">
                    <h2>Historial de tickets</h2>
                    <p>
                        Aquí puedes consultar todos los tickets que has creado y su estatus actual.
                    </p>
                </div>

                <div class="user-tickets-table-wrapper">
                    <table id="userTicketsTable" class="data-table display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Problema</th>
                                <th>Descripción</th>
                                <th>Fecha envío</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><?php echo (int)$t['id']; ?></td>
                                <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
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
            $('#userTicketsTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']]
            });
        });
    </script>
</body>
</html>
