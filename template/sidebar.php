<?php
if (!isset($_SESSION)) {
    session_start();
}

$rol       = (int)($_SESSION['user_rol'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';

$areaLower = strtolower(trim($userArea));
$puedeInventario = (
    in_array($rol, [2, 3], true)   // Admin o Analista
    && $areaLower === 'ti'         // Área TI
);

/**
 * FOTO DE PERFIL SEGÚN ROL / ÁREA / CORREO
 */
$profileImg = '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg';

if ($rol === 3) {
    $area  = strtolower(trim($userArea));
    $email = strtolower(trim($userEmail));

    $profileImg = match (true) {
        $area === 'ti'
        || str_starts_with($email, 'ti@')
        || str_starts_with($email, 'ti1@')
        || str_starts_with($email, 'ti2@')
        || str_starts_with($email, 'ti3@')
        || str_starts_with($email, 'ti4@')
        || str_starts_with($email, 'ti5@')
        || str_starts_with($email, 'ti6@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_ti.jpg',

        $area === 'sap'
        || str_starts_with($email, 'administracion@')
        || str_starts_with($email, 'administracion1@')
        || str_starts_with($email, 'administracion2@')
        || str_starts_with($email, 'administracion3@')
        || str_starts_with($email, 'administracion4@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_sap.jpg',

        $area === 'mkt'
        || str_starts_with($email, 'gerente.mercadotecnia@')
        || str_starts_with($email, 'mkt@')
        || str_starts_with($email, 'mkt1@')
        || str_starts_with($email, 'mkt2@')
        || str_starts_with($email, 'mkt3@')
        || str_starts_with($email, 'mkt4@')
        || str_starts_with($email, 'mkt5@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_mkt.jpg',

        $area === 'diseno'
        || str_starts_with($email, 'diseno@')
        || str_starts_with($email, 'diseno1@')
        || str_starts_with($email, 'diseno2@') =>
            '/HelpDesk_EQF/assets/img/pp/pp_diseno.jpg',

        default =>
            '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg',
    };

} elseif ($rol === 4) {
    $profileImg = ($userArea === 'Sucursal')
        ? '/HelpDesk_EQF/assets/img/pp/pp_sucursal.jpg'
        : '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg';

} elseif ($rol === 2) {
    $profileImg = '/HelpDesk_EQF/assets/img/pp/pp_admin.jpg';

} elseif ($rol === 1) {
    $profileImg = '/HelpDesk_EQF/assets/img/pp/pp_sa.jpg';
}
?>

<aside class="user-sidebar" id="appSidebar" aria-label="Sidebar">
    <!-- Toggle dentro del sidebar (como tu img2) -->
    <button id="sidebarToggle" class="sidebar-toggle" type="button" onclick="toggleSidebar()" aria-label="Abrir/cerrar menú">
        ☰
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
                <p class="user-sidebar-area">
                    Área: <?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
<!-- SA -->
    <nav class="user-sidebar-menu" aria-label="Menú">
        <?php if ($rol === 1): ?>
            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/sa.php'">
                <span class="user-menu-icon">⭐</span>
                <span class="user-menu-text">Super Admin</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/directory.php'">
                <span class="user-menu-icon">👥</span>
                <span class="user-menu-text">Usuarios</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/tickets_global.php'">
                <span class="user-menu-icon">🎫</span>
                <span class="user-menu-text">Tickets</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/documents.php'">
                <span class="user-menu-icon">📁</span>
                <span class="user-menu-text">Documentos</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/reports.php'">
                <span class="user-menu-icon">📊</span>
                <span class="user-menu-text">Reportes & KPIs</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/catalogs.php'">
                <span class="user-menu-icon">⚙️</span>
                <span class="user-menu-text">Catálogos</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/sla_global.php'">
                <span class="user-menu-icon">⏱️</span>
                <span class="user-menu-text">SLA Global</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/sa/auditoria.php'">
                <span class="user-menu-icon">🧾</span>
                <span class="user-menu-text">Auditoría</span>
            </button>
<!-- ADMIN -->
        <?php elseif ($rol === 2): ?>
            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/admin.php'">
                <span class="user-menu-icon">⭐</span>
                <span class="user-menu-text">Admin</span>
            </button>

            <button type="button" class="user-menu-item" data-open-announcement>
                <span class="user-menu-icon">📣</span>
                <span class="user-menu-text">Enviar aviso</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/tickets_area.php'">
                <span class="user-menu-icon">🎫</span>
                <span class="user-menu-text">Tickets</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/analysts_cards.php'">
                <span class="user-menu-icon">👥</span>
                <span class="user-menu-text">Analistas</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/tasks/admin.php'">
                <span class="user-menu-icon">📒</span>
                <span class="user-menu-text">Tareas</span>
            </button>

            <?php if ($puedeInventario): ?>
                <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/inventory/index.html'">
                    <span class="user-menu-icon">📦</span>
                    <span class="user-menu-text">Inventario</span>
                </button>
            <?php endif; ?>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/shared/internal_notes.php'">
                <span class="user-menu-icon">📝</span>
                <span class="user-menu-text">Notas</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/admin/reports.php'">
                <span class="user-menu-icon">📊</span>
                <span class="user-menu-text">Reportes y KPIs</span>
            </button>
            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/documents.php'">
                <span class="user-menu-icon">📎</span>
                <span class="user-menu-text">Documentos importantes</span>
            </button>
<!-- ANALISTA -->
        <?php elseif ($rol === 3): ?>
            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/analyst/analyst.php'">
                <span class="user-menu-icon">⭐</span>
                <span class="user-menu-text">Dashboard</span>
            </button>

            <button type="button" class="user-menu-item" id="btnOpenCreateTicket">
                <span class="user-menu-icon">➕</span>
                <span class="user-menu-text">Crear ticket</span>
            </button>

            <button type="button" class="user-menu-item" data-open-announcement>
                <span class="user-menu-icon">📣</span>
                <span class="user-menu-text">Enviar aviso</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/ticket/history.php'">
                <span class="user-menu-icon">📈</span>
                <span class="user-menu-text">KPI</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/shared/internal_notes.php'">
                <span class="user-menu-icon">📝</span>
                <span class="user-menu-text">Notas</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/tasks/analyst.php'">
                <span class="user-menu-icon">📋</span>
                <span class="user-menu-text">Tareas</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/ticket/history.php'">
                <span class="user-menu-icon">📚</span>
                <span class="user-menu-text">Historial</span>
            </button>

            <?php if ($puedeInventario): ?>
                <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/inventory/index.html'">
                    <span class="user-menu-icon">📦</span>
                    <span class="user-menu-text">Inventario</span>
                </button>
            <?php endif; ?>
            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/documents.php'">
                <span class="user-menu-icon">📎</span>
                <span class="user-menu-text">Documentos</span>
            </button>
<!-- USUARIO -->
        <?php elseif ($rol === 4): ?>
            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/user.php'">
                <span class="user-menu-icon">⭐</span>
                <span class="user-menu-text">Dashboard</span>
            </button>

            <button type="button" class="user-menu-item" onclick="openTicketModal()">
                <span class="user-menu-icon">➕</span>
                <span class="user-menu-text">Crear ticket</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/tickets.php'">
                <span class="user-menu-icon">📄</span>
                <span class="user-menu-text">Tickets</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/support_team.php'">
                <span class="user-menu-icon">🫂</span>
                <span class="user-menu-text">Team</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/dashboard/user/documents.php'">
                <span class="user-menu-icon">📎</span>
                <span class="user-menu-text">Documentos importantes</span>
            </button>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <button type="button" class="cerrar-sesion-sidebar" onclick="window.location.href='/HelpDesk_EQF/auth/logout.php'" aria-label="Cerrar sesión">
            <span class="user-menu-icon">⏻</span>
        </button>
    </div>
</aside>


