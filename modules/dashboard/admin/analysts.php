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

// Analistas del área
$stmtAnalysts = $pdo->prepare("
    SELECT id, name, last_name, email, celular
    FROM users
    WHERE rol = 3 AND area = :area
    ORDER BY name ASC, name ASC
");
$stmtAnalysts->execute([':area' => $areaAdmin]);
$analysts = $stmtAnalysts->fetchAll(PDO::FETCH_ASSOC);

// Stats de tickets por analista
$stmtStats = $pdo->prepare("
    SELECT 
        asignado_a AS analyst_id,
        SUM(estado = 'abierto')      AS abiertos,
        SUM(estado = 'en_proceso')   AS en_proceso,
        SUM(estado = 'en_espera')    AS en_espera,
        SUM(estado = 'vencido')      AS vencidos,
        SUM(estado = 'cerrado')      AS cerrados,
        COUNT(*)                     AS total
    FROM tickets
    WHERE area = :area
      AND asignado_a IS NOT NULL
      AND asignado_a <> 0
    GROUP BY asignado_a
");
$stmtStats->execute([':area' => $areaAdmin]);
$statsByAnalyst = [];
foreach ($stmtStats->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $statsByAnalyst[(int)$row['analyst_id']] = $row;
}

// Conteo de tareas por analista
$stmtTasksAgg = $pdo->prepare("
    SELECT analyst_id, COUNT(*) AS total_tareas
    FROM analyst_tasks
    WHERE area = :area
    GROUP BY analyst_id
");
$stmtTasksAgg->execute([':area' => $areaAdmin]);
$tasksByAnalyst = [];
foreach ($stmtTasksAgg->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $tasksByAnalyst[(int)$row['analyst_id']] = (int)$row['total_tareas'];
}

include __DIR__ . '/../../../template/header.php';
include __DIR__ . '/../../../template/sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analistas de mi área | Mesa de Ayuda EQF</title>
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
                    Analistas de - <?php echo htmlspecialchars($areaAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </header>

        <section class="user-main-content">

            <div class="user-info-card">
                <h2>Equipo de analistas</h2>
                <p>Aquí puedes ver a los analistas de tu área y un pequeño resumen de su carga de tickets.</p>
            </div>

            <section class="admin-card">
                <h2>Analistas</h2>

                <?php if (empty($analysts)): ?>
                    <p class="admin-empty">No hay analistas registrados para esta área.</p>
                <?php else: ?>
                    <div class="admin-analysts-list">
                        <?php foreach ($analysts as $a): ?>
                            <?php
                            $idAnalyst = (int)$a['id'];
                            $st = $statsByAnalyst[$idAnalyst] ?? null;

                            $totalTickets = (int)($st['total']        ?? 0);
                            $abiertos     = (int)($st['abiertos']     ?? 0);
                            $enProceso    = (int)($st['en_proceso']   ?? 0);
                            $enEspera     = (int)($st['en_espera']    ?? 0);
                            $vencidos     = (int)($st['vencidos']     ?? 0);
                            $cerrados     = (int)($st['cerrados']     ?? 0);

                            $abiertosGrupo = $abiertos + $enProceso + $enEspera;
                            $tareasCount   = $tasksByAnalyst[$idAnalyst] ?? 0;
                            ?>
                            <article class="admin-task-card">
                                <header class="admin-task-header">
                                    <h3>
                                        <?php echo htmlspecialchars($a['name'] . ' ' . $a['last_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h3>
                                </header>

                                <p class="admin-task-meta">
                                    Correo:
                                    <?php echo htmlspecialchars($a['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <p class="admin-task-meta">
                                    Celular:
                                    <?php echo htmlspecialchars($a['celular'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <p class="admin-task-meta">
                                    Tickets asignados: <?php echo $totalTickets; ?> ·
                                    Abiertos / en proceso / en espera: <?php echo $abiertosGrupo; ?> ·
                                    Vencidos: <?php echo $vencidos; ?> ·
                                    Cerrados: <?php echo $cerrados; ?>
                                </p>

                                <p class="admin-task-meta">
                                    Tareas asignadas (histórico): <?php echo (int)$tareasCount; ?>
                                </p>
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
