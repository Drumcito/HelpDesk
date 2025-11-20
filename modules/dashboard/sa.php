<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

$pdo = Database::getConnection();

$nombreCompleto = $_SESSION['user_name'] . ' ' . $_SESSION['user_last'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio SA | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard-body">

    <!-- MENÚ PASTILLA LATERAL -->
    <aside class="sidebar-pill">
        <nav class="sidebar-pill-menu">
            <a href="sa.php" class="pill-item active" title="Inicio">
                <span class="pill-icon">🏠</span>
            </a>
            <a href="../directory/directory.php" class="pill-item" title="Directorio">
                <span class="pill-icon">👥</span>
            </a>
            <a href="#" class="pill-item" title="Soporte (próx.)">
                <span class="pill-icon">💻</span>
            </a>
            <a href="#" class="pill-item" title="Tickets (próx.)">
                <span class="pill-icon">🎫</span>
            </a>
            <a href="../../auth/logout.php" class="pill-item" title="Cerrar sesión">
                <span class="pill-icon">⏻</span>
            </a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="sa-main">
        <header class="dashboard-header">
            <div>
                <h1>Mesa de Ayuda · Super Administrador</h1>
                <p class="dashboard-subtitle">
                    Panel general y acceso a KPIs, directorio, soporte y tickets.
                </p>
            </div>
            <div class="dashboard-user-pill">
                <span><?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </header>

        <section class="dashboard-section">
            <div class="section-header-main">
                <h2>Inicio</h2>
                <p>Resumen general de la mesa de ayuda.</p>
            </div>

            <div class="kpi-layout">
                <div class="kpi-main-card">
                    <h3>Panel KPIs (Power BI)</h3>
                    <p class="kpi-placeholder-text">
                        Aquí se mostrará tu dashboard de Power BI embebido con tiempos de respuesta,
                        resolución, SLA por área (TI, MKT, SAP) y por analista.
                    </p>
                    <div class="kpi-placeholder-box kpi-placeholder-large">
                        <span>Zona para incrustar Power BI</span>
                    </div>
                </div>

                <div class="kpi-side-cards">
                    <div class="metric-card-lg">
                        <p class="metric-label">Tickets abiertos (placeholder)</p>
                        <p class="metric-value">—</p>
                    </div>
                    <div class="metric-card-lg">
                        <p class="metric-label">TMO de respuesta (placeholder)</p>
                        <p class="metric-value">—</p>
                    </div>
                    <div class="metric-card-lg">
                        <p class="metric-label">Tickets vencidos (placeholder)</p>
                        <p class="metric-value">—</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>