<?php
if (!isset($_SESSION)) {
    session_start();
}

$rol       = (int)($_SESSION['user_rol'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';

/**
 * FOTO DE PERFIL SEGÚN ROL / ÁREA / CORREO
 */
$profileImg = '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg';

if ($rol === 3) {
    // ANALISTA → lógica por área/correo
    $area  = strtolower(trim($userArea));
    $email = strtolower(trim($userEmail));

    $profileImg = match (true) {
        // TI
        $area === 'ti'
        || str_starts_with($email, 'ti@')
        || str_starts_with($email, 'ti1@')
        || str_starts_with($email, 'ti2@')
        || str_starts_with($email, 'ti3@')
        || str_starts_with($email, 'ti4@')
        || str_starts_with($email, 'ti5@')
        || str_starts_with($email, 'ti6@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_ti.jpg',

        // SAP
        $area === 'sap'
        || str_starts_with($email, 'administracion@')
        || str_starts_with($email, 'administracion1@')
        || str_starts_with($email, 'administracion2@')
        || str_starts_with($email, 'administracion3@')
        || str_starts_with($email, 'administracion4@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_sap.jpg',

        // MKT
        $area === 'mkt'
        || str_starts_with($email, 'gerente.mercadotecnia@')
        || str_starts_with($email, 'mkt@')
        || str_starts_with($email, 'mkt1@')
        || str_starts_with($email, 'mkt2@')
        || str_starts_with($email, 'mkt3@')
        || str_starts_with($email, 'mkt4@')
        || str_starts_with($email, 'mkt5@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_mkt.jpg',

        // DISEÑO
        $area === 'diseno'
        || str_starts_with($email, 'diseno@')
        || str_starts_with($email, 'diseno1@')
        || str_starts_with($email, 'diseno2@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_diseno.jpg',

        default =>
            '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg',
    };

} elseif ($rol === 4) {
    // USUARIO FINAL
    if ($userArea === 'Sucursal') {
        $profileImg = '/HelpDesk_EQF/assets/img/pp/pp_sucursal.jpg';
    } else {
        $profileImg = '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg';
    }

} elseif ($rol === 2) {
    // ADMIN
    $profileImg = '/HelpDesk_EQF/assets/img/pp/pp_admin.jpg';

} elseif ($rol === 1) {
    // SUPER ADMIN
    $profileImg = '/HelpDesk_EQF/assets/img/pp/pp_sa.jpg';
}
?>
<button id="sidebarToggle" class="sidebar-toggle" type="button" 
onclick="toggleSidebar()" aria-label="Abrir/cerrar menú">
≡
</button>




<aside class="user-sidebar">
<button id="sidebarToggle" class="sidebar-toggle" type="button" aria-label="Abrir/cerrar menú">
  
</button>

    <div class="user-sidebar-profile">
        <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>"
             alt="Foto de perfil"
             class="user-sidebar-avatar">

        <div class="user-sidebar-info">
            <p class="user-sidebar-name">
                <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p class="user-sidebar-email">
                <?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php if ($rol !== 4): ?>
                <p class="user-sidebar-email" style="opacity:0.8;">
                    Área: <?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <nav class="user-sidebar-menu">
        <?php if ($rol === 1): ?>
            <!-- ================= SA ================= -->
<button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/sa.php'">
        <span class="user-menu-icon">⭐</span>
        <span>Super Admin</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/directory.php'">
        <span class="user-menu-icon">👥</span>
        <span>Usuarios</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/tickets_global.php'">
        <span class="user-menu-icon">🎫</span>
        <span>Tickets</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/documents.php'">
        <span class="user-menu-icon">📁</span>
        <span>Documentos</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/reports.php'">
        <span class="user-menu-icon">📊</span>
        <span>Reportes & KPIs</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/catalogs.php'">
        <span class="user-menu-icon">⚙️</span>
        <span>Catálogos</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/sla_global.php'">
        <span class="user-menu-icon">⏱️</span>
        <span>SLA Global</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/auditoria.php'">
        <span class="user-menu-icon">🧾</span>
        <span>Auditoría</span>
    </button>

<?php elseif ($rol === 2): ?>
    <!-- ================= ADMIN ================= -->

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/admin.php'">
        <span class="user-menu-icon">🏢</span>
        <span>Panel Admin</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php'">
        <span class="user-menu-icon">🎫</span>
        <span>Tickets de mi área</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/tasks.php'">
        <span class="user-menu-icon">📝</span>
        <span>Tareas a analistas</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/analysts.php'">
        <span class="user-menu-icon">👥</span>
        <span>Analistas de mi área</span>
    </button>

    <button type="button" class="user-menu-item"
            onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/reports.php'">
        <span class="user-menu-icon">📊</span>
        <span>Reportes y KPIs</span>
    </button>

        <?php elseif ($rol === 3): ?>
            <!-- ================= ANALISTA ================= -->
            <button type="button" class="user-menu-item" onclick="scrollToSection('analyst-dashboard')">
                <span class="user-menu-icon">📊</span>
                <span>Dashboard</span>
            </button>

            <button type="button" class="user-menu-item" onclick="scrollToSection('incoming-section')">
                <span class="user-menu-icon">📥</span>
                <span>Tickets entrantes</span>
            </button>

            <button type="button" class="user-menu-item" onclick="scrollToSection('mytickets-section')">
                <span class="user-menu-icon">🧑‍💻</span>
                <span>Mis tickets</span>
            </button>

            <button type="button" class="user-menu-item" 
                onclick="window.location.href='/HelpDesk_EQF/modules/ticket/history.php'">
                <span class="user-menu-icon">📈</span>
                <span>KPI</span>
            </button>

            <button type="button" class="user-menu-item" 
                    onclick="window.location.href='/HelpDesk_EQF/modules/ticket/history.php'">
                <span class="user-menu-icon">📚</span>
                <span>Historial</span>
            </button>

        <?php elseif ($rol === 4): ?>
            <!-- ================= USUARIO ================= -->
            <button type="button" class="user-menu-item" onclick="openTicketModal()">
                <span class="user-menu-icon">➕</span>
                <span>Crear ticket</span>
            </button>

            <button type="button" class="user-menu-item"
                    onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/tickets.php'">
                <span class="user-menu-icon">📄</span>
                <span>Tickets</span>
            </button>

            <button type="button" class="user-menu-item"
                    onclick="window.location.href='/HelpDesk_EQF/modules/docs/important.php'">
                <span class="user-menu-icon">📎</span>
                <span>Documentos importantes</span>
            </button>
        <?php endif; ?>

        <!-- Común a todos -->
        <button type="button" class="cerrar-sesion-sidebar"
                onclick="window.location.href='/HelpDesk_EQF/auth/logout.php'">
            <span class="user-menu-icon">⏻</span>
        </button>
    </nav>
</aside>
