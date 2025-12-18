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
$filtro = $_GET['filtro'] ?? 'todos'; // todos | activos | inactivos
$diasActivos = (int)($_GET['dias'] ?? 30);
if ($diasActivos <= 0) $diasActivos = 30;

// Fecha límite (hoy - X días)
$limite = (new DateTime())->modify("-{$diasActivos} days")->format('Y-m-d H:i:s');

// -----------------------------
// Query: usuarios (rol=4) del área + métricas por tickets
// Nota: usamos :area1 y :area2 para NO repetir placeholder
// -----------------------------
$sql = "
    SELECT
        u.id,
        u.number_sap,
        u.name,
        u.last_name,
        u.email,
        u.area,
        COALESCE(tk.total_tickets, 0) AS total_tickets,
        tk.ultimo_ticket
    FROM users u
    LEFT JOIN (
        SELECT
            email,
            COUNT(*) AS total_tickets,
            MAX(fecha_envio) AS ultimo_ticket
        FROM tickets
        WHERE area = :area1
        GROUP BY email
    ) tk ON tk.email = u.email
    WHERE u.rol = 3
      AND u.area = :area2
";

$params = [
    ':area1' => $areaAdmin,
    ':area2' => $areaAdmin,
];

// Filtro activos/inactivos por último ticket
if ($filtro === 'activos') {
    $sql .= " AND tk.ultimo_ticket IS NOT NULL
              AND tk.ultimo_ticket >= :limite";
    $params[':limite'] = $limite;

} elseif ($filtro === 'inactivos') {
    $sql .= " AND (tk.ultimo_ticket IS NULL
              OR tk.ultimo_ticket < :limite)";
    $params[':limite'] = $limite;
}

$sql .= " ORDER BY u.last_name ASC, u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reasignacion de tickets | HelpDesk EQF</title>
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
                    Reasignacion de Tickets - <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>

        </header>

        <section class="user-main-content">


            <form method="get" class="user-filters-row">
                <div class="form-group">
                    <label for="filtro">Filtro</label>
                    <select name="filtro" id="filtro">
                        <option value="todos" <?php if ($filtro==='todos') echo 'selected'; ?>>Todos</option>
                        <option value="activos" <?php if ($filtro==='activos') echo 'selected'; ?>>Activos</option>
                        <option value="inactivos" <?php if ($filtro==='inactivos') echo 'selected'; ?>>Inactivos</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dias">Días para considerar activo</label>
                    <select name="dias" id="dias">
                        <?php foreach ([7, 15, 30, 60, 90] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php if ($diasActivos===$d) echo 'selected'; ?>>
                                <?php echo $d; ?> días
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" 
                class="btn-primary" 
                style="width: 100px; height:30px; padding: 0;">
                Aplicar</button>
            </form>

            <div class="user-tickets-table-wrapper">
                <table id="usersAreaTable" class="data-table display">
                    <thead>
                        <tr>
                            <th># SAP</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Total de tickets</th>
                            <th>Último ticket</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                            $ultimo = $u['ultimo_ticket'] ?? null;
                            $activo = false;

                            if ($ultimo) {
                                $dtUlt = new DateTime($ultimo);
                                $dtLim = (new DateTime())->modify("-{$diasActivos} days");
                                $activo = $dtUlt >= $dtLim;
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['number_sap'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(trim(($u['name'] ?? '').' '.($u['last_name'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)($u['total_tickets'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($ultimo ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $activo ? 'Activo' : 'Inactivo'; ?></td>
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
$(function(){
  $('#usersAreaTable').DataTable({
    pageLength: 10,
    order: [[4,'desc']]
  });
});
</script>
<script src="/HelpDesk_EQF/assets/js/script.js?v=20251208a"></script>

</body>
</html>
