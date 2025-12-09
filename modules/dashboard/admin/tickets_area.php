<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 2) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo       = Database::getConnection();
$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$areaAdmin = $_SESSION['user_area'] ?? '';

// -----------------------------
// Filtros
// -----------------------------
$estadoFiltro    = $_GET['estado']    ?? 'todos';
$prioridadFiltro = $_GET['prioridad'] ?? 'todas';
$soloSinAnalista = isset($_GET['sin_asignar']);

// Query base
$sql = "
    SELECT 
        t.id,
        t.sap,
        t.nombre,
        t.email,
        t.problema,
        t.descripcion,
        t.fecha_envio,
        t.estado,
        t.prioridad,
        t.asignado_a,
        a.name      AS analyst_name,
        a.last_name AS analyst_last
    FROM tickets t
    LEFT JOIN users a ON a.id = t.asignado_a AND a.rol = 3
    WHERE t.area = :area
";

$params = [':area' => $areaAdmin];

if ($estadoFiltro !== '' && $estadoFiltro !== 'todos') {
    $sql .= " AND t.estado = :estado";
    $params[':estado'] = $estadoFiltro;
}

if ($prioridadFiltro !== '' && $prioridadFiltro !== 'todas') {
    $sql .= " AND t.prioridad = :prioridad";
    $params[':prioridad'] = $prioridadFiltro;
}

if ($soloSinAnalista) {
    $sql .= " AND (t.asignado_a IS NULL OR t.asignado_a = 0)";
}

$sql .= " ORDER BY t.fecha_envio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------
// Helpers
// -----------------------------
function problemaLabel(string $p): string {
    return match ($p) {
        'cierre_dia'  => 'Cierre del día',
        'no_legado'   => 'Sin acceso a legado/legacy',
        'no_internet' => 'Sin internet',
        'no_checador' => 'No funciona checador',
        'rastreo'     => 'Rastreo de checada',
        'otro'        => 'Otro',
        default       => $p,
    };
}

function prioridadLabel(?string $p): string {
    $p = strtolower($p ?? '');
    return match ($p) {
        'alta'      => 'Alta',
        'media'     => 'Media',
        'baja'      => 'Baja',
        'critica',
        'crítica'   => 'Crítica',
        default     => ucfirst($p),
    };
}

function estadoLabel(?string $e): string {
    $e = strtolower($e ?? '');
    return match ($e) {
        'abierto'      => 'Abierto',
        'en_proceso'   => 'En proceso',
        'en_espera'    => 'En espera',
        'vencido'      => 'Vencido',
        'cerrado'      => 'Cerrado',
        default        => ucfirst($e),
    };
}

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tickets de mi área | Mesa de Ayuda EQF</title>
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
                    Tickets de mi área – <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
            <button type="button" class="btn-secondary"
                    onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/admin.php'">
                ⬅ Volver al panel Admin
            </button>
        </header>

        <section class="user-main-content">

            <div class="user-info-card">
                <h2>Listado de tickets del área</h2>
                <p>Puedes filtrar por estatus, prioridad o tickets que aún no tienen analista asignado.</p>
            </div>

            <form method="get" class="user-filters-row">
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado">
                        <?php
                        $estados = [
                            'todos'      => 'Todos',
                            'abierto'    => 'Abierto',
                            'en_proceso' => 'En proceso',
                            'en_espera'  => 'En espera',
                            'vencido'    => 'Vencido',
                            'cerrado'    => 'Cerrado',
                        ];
                        foreach ($estados as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if ($estadoFiltro === $value) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prioridad">Prioridad</label>
                    <select name="prioridad" id="prioridad">
                        <?php
                        $prioridades = [
                            'todas'  => 'Todas',
                            'baja'   => 'Baja',
                            'media'  => 'Media',
                            'alta'   => 'Alta',
                            'critica'=> 'Crítica',
                        ];
                        foreach ($prioridades as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if ($prioridadFiltro === $value) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-group-inline">
                    <label>
                        <input type="checkbox" name="sin_asignar" value="1" <?php if ($soloSinAnalista) echo 'checked'; ?>>
                        Solo sin analista asignado
                    </label>
                </div>

                <button type="submit" class="btn-primary">Aplicar filtros</button>
            </form>

            <div class="user-tickets-table-wrapper">
                <table id="adminTicketsAreaTable" class="data-table display">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha envío</th>
                            <th>Usuario</th>
                            <th>Problema</th>
                            <th>Prioridad</</th>
                            <th>Estatus</th>
                            <th>Analista</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?php echo (int)$t['id']; ?></td>
                            <td><?php echo htmlspecialchars($t['fecha_envio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php echo htmlspecialchars(trim(($t['sap'] ?? '') . ' ' . ($t['nombre'] ?? '')), ENT_QUOTES, 'UTF-8'); ?><br>
                                <small><?php echo htmlspecialchars($t['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(prioridadLabel($t['prioridad']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(estadoLabel($t['estado']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php
                                if (!empty($t['analyst_name'])) {
                                    echo htmlspecialchars($t['analyst_name'] . ' ' . $t['analyst_last'], ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo 'Sin asignar';
                                }
                                ?>
                            </td>
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
        $('#adminTicketsAreaTable').DataTable({
            pageLength: 10,
            order: [[1, 'desc']]
        });
    });
</script>

</body>
</html>
