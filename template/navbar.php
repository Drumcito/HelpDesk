<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$activePage = $activePage ?? ''; 
?>

<aside class="sidebar-pill">
    <div class="sidebar-pill-inner">

        <a href="/HelpDesk_EQF/modules/dashboard/sa.php"
           class="pill-item <?php echo $activePage === 'inicio' ? 'active' : ''; ?>"
           title="Inicio">
           <span class="pill-icon">🏠</span>
        </a>

        <a href="/HelpDesk_EQF/modules/directory/directory.php"
           class="pill-item <?php echo $activePage === 'directorio' ? 'active' : ''; ?>"
           title="Directorio">
           <span class="pill-icon">👥</span>
        </a>

        <a href="#"
           class="pill-item <?php echo $activePage === 'soporte' ? 'active' : ''; ?>"
           title="Soporte (próx.)">
           <span class="pill-icon">💻</span>
        </a>

        <a href="#"
           class="pill-item <?php echo $activePage === 'tickets' ? 'active' : ''; ?>"
           title="Tickets (próx.)">
           <span class="pill-icon">🎫</span>
        </a>

        <a href="/HelpDesk_EQF/auth/logout.php"
           class="pill-item"
           title="Cerrar sesión">
           <span class="pill-icon">⏻</span>
        </a>

    </div>
</aside>
