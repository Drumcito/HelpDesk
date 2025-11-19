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
    <title>Dashboard SA | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-layout">
        <!-- SIDEBAR ICONOS -->
        <aside class="sidebar sidebar-compact">
            <div class="sidebar-logo-block">
                <div class="sidebar-logo-circle">EQF</div>
            </div>

            <nav class="sidebar-menu-compact">
                <a href="#" class="sidebar-link-compact active" data-section="dashboard">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-label">Inicio</span>
                </a>
                <a href="#" class="sidebar-link-compact" data-section="kpis">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">KPIs</span>
                </a>
                <a href="#" class="sidebar-link-compact" data-section="directorio">
                    <span class="nav-icon">📒</span>
                    <span class="nav-label">Directorio</span>
                </a>
                <a href="#" class="sidebar-link-compact" data-section="soporte">
                    <span class="nav-icon">🧑‍💻</span>
                    <span class="nav-label">Soporte</span>
                </a>
                <a href="#" class="sidebar-link-compact" data-section="tickets">
                    <span class="nav-icon">🎫</span>
                    <span class="nav-label">Tickets</span>
                </a>
            </nav>

            <div class="sidebar-footer-compact">
                <div class="sidebar-user-mini">
                    <div class="sidebar-user-avatar-mini">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'S', 0, 1)); ?>
                    </div>
                </div>
                <a href="../../auth/logout.php" class="logout-icon" title="Cerrar sesión">⏻</a>
            </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1>Mesa de Ayuda · Super Administrador</h1>
                    <p class="dashboard-subtitle">
                        Control central de KPIs, usuarios, analistas y tickets.
                    </p>
                </div>
                <div class="dashboard-user-pill">
                    <span><?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </header>

            <!-- SECCIÓN: INICIO / DASHBOARD GENERAL -->
            <section class="view-section active" id="section-dashboard">
                <div class="section-header-main">
                    <h2>Inicio</h2>
                    <p>Resumen general de la mesa de ayuda.</p>
                </div>

                <div class="kpi-layout">
                    <div class="kpi-main-card">
                        <h3>Panel KPIs (Power BI)</h3>
                        <p class="kpi-placeholder-text">
                            Aquí se mostrará tu dashboard de Power BI embebido con los tiempos de atención,
                            resolución, SLA por área y analista.
                        </p>
                        <div class="kpi-placeholder-box">
                            <span>Placeholder Power BI</span>
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

            <!-- SECCIÓN: KPIs (Power BI) -->
            <section class="view-section" id="section-kpis">
                <div class="section-header-main">
                    <h2>Dashboard de KPIs</h2>
                    <p>Vista dedicada para tu reporte de Power BI.</p>
                </div>
                <div class="card-full">
                    <p class="section-placeholder">
                        Aquí podrás integrar el IFrame de Power BI con tus reportes de:
                        tiempos de atención, tiempos de resolución, cumplimiento de SLA,
                        volumen de tickets por área (TI, MKT, SAP), sucursal y analista.
                    </p>
                    <div class="kpi-placeholder-box kpi-placeholder-large">
                        <span>Zona para incrustar Power BI</span>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN: DIRECTORIO -->
            <section class="view-section" id="section-directorio">
                <div class="section-header-main">
                    <h2>Directorio</h2>
                    <p>Consulta rápida de usuarios, áreas y contactos clave.</p>
                </div>
                <div class="card-full">
                    <div class="toolbar">
                        <input type="text" class="input-search" placeholder="Buscar por nombre, área o correo (placeholder)">
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Área</th>
                                    <th>Correo</th>
                                    <th>Extensión</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="table-empty">
                                        Aquí se mostrará el directorio una vez conectado a la base de datos.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN: SOPORTE (ANALISTAS) -->
            <section class="view-section" id="section-soporte">
                <div class="section-header-main">
                    <h2>Soporte · Analistas</h2>
                    <p>Vista de analistas activos, ocupados y su carga de trabajo.</p>
                </div>
                <div class="card-full">
                    <div class="analysts-grid">
                        <div class="analyst-card placeholder">
                            <h3>Analistas activos</h3>
                            <p class="section-placeholder">
                                Aquí podrás ver cuántos analistas están logueados y disponibles para tomar tickets.
                            </p>
                        </div>
                        <div class="analyst-card placeholder">
                            <h3>Analistas ocupados</h3>
                            <p class="section-placeholder">
                                Aquí se mostrarán los analistas con tickets en curso y su número de casos asignados.
                            </p>
                        </div>
                        <div class="analyst-card placeholder">
                            <h3>Distribución por área</h3>
                            <p class="section-placeholder">
                                Aquí podrás ver cuántos analistas están dedicados a TI, MKT, SAP, etc.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN: TICKETS -->
            <section class="view-section" id="section-tickets">
                <div class="section-header-main">
