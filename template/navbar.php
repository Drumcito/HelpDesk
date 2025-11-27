<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Página activa que mandas desde cada módulo, ej.
// $activePage = 'inicio';
// $activePage = 'directorio'; etc.
$activePage = $activePage ?? '';

// Rol del usuario logueado (tal como lo pones en login.php)
$rol = isset($_SESSION['user_rol']) ? (int)$_SESSION['user_rol'] : null;

// Define aquí los IDs de rol que usas en tu BD
const ROL_SA       = 1;
const ROL_ADMIN    = 2;
const ROL_ANALISTA = 3;

// Configuración de las pastillas del menú
$menuItems = [

    // ====== INICIO (uno por rol, usando url_por_rol) ======
    [
        'id'    => 'inicio',
        'icon'  => '🏠',
        'title' => 'Inicio',
        'url_por_rol' => [
            ROL_SA       => '/HelpDesk_EQF/modules/dashboard/sa/sa.php',
            ROL_ADMIN    => '/HelpDesk_EQF/modules/dashboard/admin/admin.php',
            ROL_ANALISTA => '/HelpDesk_EQF/modules/dashboard/analist/analist.php',
        ],
        'roles' => [ROL_SA, ROL_ADMIN, ROL_ANALISTA],
    ],

    [
        'id'    => 'directorio',
        'icon'  => '👥',
        'title' => 'Directorio',
        'url_por_rol' => [
            ROL_SA       => '/HelpDesk_EQF/modules/directory/directory.php',
            ROL_ADMIN    => '/HelpDesk_EQF/modules/dashboard/admin/admin_directory.php',
        ],
        'roles' => [ROL_SA, ROL_ADMIN], 
    ],

    [
        'id'    => 'tickets',
        'icon'  => '🎫',
        'title' => 'tickets',
        'url_por_rol' => [
            ROL_SA       => '/HelpDesk_EQF/modules/dashboard/sa/tickets.php',
            ROL_ADMIN    => '/HelpDesk_EQF/modules/dashboard/admin/admin_tickets.php',
            ROL_ANALISTA => '/HelpDesk_EQF/modules/dashboard/analist/analist_tickets.php',

        ],
         'roles' => [ROL_SA, ROL_ADMIN, ROL_ANALISTA],    ],
    [
        'id'    => 'tareas',
        'icon'  => '📝',
        'title' => 'Task',
                'url_por_rol' => [
            ROL_SA       => '/HelpDesk_EQF/modules/dashboard/sa/tasks.php',
            ROL_ADMIN    => '/HelpDesk_EQF/modules/dashboard/admin/admin_task.php',
            ROL_ANALISTA => '/HelpDesk_EQF/modules/dashboard/analist/analist_task.php',
        ],
        'roles' => [ROL_SA, ROL_ADMIN, ROL_ANALISTA],
    ],
    [
        'id'    => 'ticket_pending',
        'icon'  => '🎟️',
        'title' => 'ticket_pending',
                'url_por_rol' => [
           ROL_ANALISTA => '/HelpDesk_EQF/modules/dashboard/analist/ticket_pending.php',
        ],
        'roles' => [ROL_ANALISTA],
    ],
        [
        'id'    => 'kpis',
        'icon'  => '📊',
        'title' => 'kpis',
      'url_por_rol' => [
            ROL_SA       => '/HelpDesk_EQF/modules/dashboard/sa/kpis.php',
            ROL_ADMIN    => '/HelpDesk_EQF/modules/dashboard/admin/admin_kpis.php',
            ROL_ANALISTA => '/HelpDesk_EQF/modules/dashboard/analist/analist_kpis.php',
        ],
        'roles' => [ROL_SA, ROL_ADMIN, ROL_ANALISTA],  
        ],
            [
        'id'    => 'settings',
        'icon'  => '⚙️',
        'title' => 'settings',
        'url'   => '#',
        'roles' => [ROL_SA],
    ],

];

?>

<aside class="sidebar-pill">
    <div class="sidebar-pill-inner">

        <?php foreach ($menuItems as $item): ?>

            <?php
            // Si el rol actual no está autorizado, no mostramos la pastilla
            if (!in_array($rol, $item['roles'], true)) {
                continue;
            }

            // URL dependiendo del rol (si usas url_por_rol) o la genérica
            if (isset($item['url_por_rol']) && is_array($item['url_por_rol'])) {
                $url = $item['url_por_rol'][$rol] ?? '#';
            } else {
                $url = $item['url'];
            }

            $isActive = ($activePage === $item['id']) ? 'active' : '';
            ?>

            <a href="<?php echo htmlspecialchars($url); ?>"
               class="pill-item <?php echo $isActive; ?>"
               title="<?php echo htmlspecialchars($item['title']); ?>">
                <span class="pill-icon"><?php echo $item['icon']; ?></span>
            </a>

        <?php endforeach; ?>

        <!-- Cerrar sesión (común para todos) -->
        <a href="/HelpDesk_EQF/auth/logout.php"
           class="pill-item"
           title="Cerrar sesión">
           <span class="pill-icon">⏻</span>
        </a>

    </div>
</aside>
