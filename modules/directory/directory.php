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
    <title>Directorio | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard-body">

    <!-- MENÚ PASTILLA LATERAL -->
    <aside class="sidebar-pill">
        <nav class="sidebar-pill-menu">
            <a href="../dashboard/sa.php" class="pill-item" title="Inicio">
                <span class="pill-icon">🏠</span>
            </a>
            <a href="directory.php" class="pill-item active" title="Directorio">
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
                <h1>Directorio · Super Administrador</h1>
                <p class="dashboard-subtitle">
                    Consulta y administración de usuarios de la mesa de ayuda.
                </p>
            </div>
            <div class="dashboard-user-pill">
                <span><?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </header>

        <section class="dashboard-section">
            <div class="section-header-main">
                <h2>Directorio</h2>
                <p>Buscar, filtrar y administrar usuarios (luego aquí conectamos el CRUD completo).</p>
            </div>

            <div class="card-full">
                <div class="toolbar toolbar-between">
                    <input type="text" class="input-search" placeholder="Buscar por nombre, área o correo (placeholder)">
                    <button type="button" class="btn-secondary" onclick="openModal('modal-create-user')">
                        + Agregar usuario
                    </button>
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
                                Aquí se mostrará el directorio cuando conectemos la BD.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal de alta
