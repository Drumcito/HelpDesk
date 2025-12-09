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

// Analistas del área (para filtros)
$stmtAnalysts = $pdo->prepare("
    SELECT id, name, last_name
    FROM users
    WHERE rol = 3 AND area = :area
    ORDER BY last_name ASC, name ASC
");
$stmtAnalysts->execute([':area' => $areaAdmin]);
$analysts = $stmtAnalysts->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$analystFilter = isset($_GET['analyst_id']) ? (int)$_GET['analyst_id'] : 0;
$estadoFilter  = $_GET['estado'] ?? 'todos';

// Query tareas
$sql = "
    SELECT t.*, u.name, u.last_name
    FROM analyst_tasks t
    JOIN users u ON u.id = t.analyst_id
    WHERE t.area = :area
      AND t.admin_id = :admin_id
";

$params = [
    ':area'     => $areaAdmin,
    ':admin_id' => $userId,
];

if ($analystFilter > 0) {
    $sql .= " AND t.analyst_id = :analyst_id";
    $params[':analyst_id'] = $analystFilter;
}

if ($estadoFilter !== '' && $estadoFilter !== 'todos') {
    $sql .= " AND t.estado = :estado";
    $params[':estado'] = $estadoFilter;
}

$sql .= " ORDER BY t.created_at DESC";

$stmtTareas = $pdo->prepare($sql);
$stmtTareas->execute($params);
$tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tareas a analistas | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css?v=<?php echo time(); ?>">
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
                    Tareas a analistas – Área <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
            <button type="button" class="btn-secondary"
                    onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/admin.php'">
                ⬅ Volver al panel Admin
            </button>
        </header>

        <section class="user-main-content">

            <div class="user-info-card">
                <h2>Historial de tareas</h2>
                <p>
                    Aquí puedes revisar todas las tareas que has asignado a los analistas de tu área.
                </p>
            </div>

            <!-- Filtros -->
            <form method="get" class="user-filters-row">
                <div class="form-group">
                    <label for="analyst_id">Analista</label>
                    <select name="analyst_id" id="analyst_id">
                        <option value="0">Todos</option>
                        <?php foreach ($analysts as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>"
                                <?php if ($analystFilter === (int)$a['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($a['name'] . ' ' . $a['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado">
                        <?php
                        $estados = [
                            'todos'     => 'Todos',
                            'pendiente' => 'Pendiente',
                            'en_proceso'=> 'En proceso',
                            'cerrada'   => 'Cerrada',
                            'cancelada' => 'Cancelada',
                        ];
                        foreach ($estados as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if ($estadoFilter === $value) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Aplicar filtros</button>
            </form>

            <!-- Lista de tareas -->
            <section class="admin-card">
                <h2>Tareas asignadas</h2>
                <?php if (empty($tareas)): ?>
                    <p class="admin-empty">No hay tareas con los filtros seleccionados.</p>
                <?php else: ?>
                    <div class="admin-task-list">
                        <?php foreach ($tareas as $t): ?>
                            <article class="admin-task-card">
                                <header class="admin-task-header">
                                    <h3><?php echo htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="badge badge-prioridad-alta">Alta</span>
                                </header>

                                <p class="admin-task-meta">
                                    Para:
                                    <?php echo htmlspecialchars($t['name'] . ' ' . $t['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    · Estado:
                                    <?php echo htmlspecialchars($t['estado'] ?? 'pendiente', ENT_QUOTES, 'UTF-8'); ?>
                                </p>

                                <p class="admin-task-meta">
                                    Creada:
                                    <?php echo htmlspecialchars($t['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($t['fecha_limite'])): ?>
                                        · Fecha límite:
                                        <?php echo htmlspecialchars($t['fecha_limite'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </p>

                                <?php if (!empty($t['descripcion'])): ?>
                                    <p class="admin-task-desc">
                                        <?php echo nl2br(htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8')); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($t['archivo_ruta'])): ?>
                                    <p class="admin-task-meta">
                                        <a href="/HelpDesk_EQF/<?php echo htmlspecialchars($t['archivo_ruta'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                            Ver archivo adjunto
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </section>
    </section>
</main>

</body>
</html>
