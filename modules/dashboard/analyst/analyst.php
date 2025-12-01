<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

// Solo Analistas (rol = 3)
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_rol'] ?? 0) !== 3) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo       = Database::getConnection();
$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';
$userSap   = $_SESSION['number_sap'] ?? '';

/* Imagen de perfil */
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

    // DISEÑO (también dentro de MKT)
    $area === 'diseno'
    || str_starts_with($email, 'diseno@')
    || str_starts_with($email, 'diseno1@')
    || str_starts_with($email, 'diseno2@') =>
        '/HelpDesk_EQF/assets/img/pp/pp_diseno.jpg',

    // DEFAULT
    default =>
        '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg',
};

/* ALERTAS */
$alerts = [];
if (isset($_GET['updated'])) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'capsulin_update.png',
        'text' => 'TICKET ACTUALIZADO EXITOSAMENTE'
    ];
}

/* Helper: label de problema */
function problemaLabel(string $p): string {
    return match ($p) {
        'cierre_dia'  => 'Cierre del día',
        'no_legado'   => 'Sin acceso a legado/legacy',
        'no_internet' => 'Sin internet',
        'no_checador' => 'No funciona checador',
        'rastreo'     => 'Rastreo de checada',
        'otro'        => 'Otro',
        default       => $p,
    };
}

/* KPIs del área */
$stmtKpi = $pdo->prepare("
    SELECT 
        SUM(estado = 'abierto')      AS abiertos,
        SUM(estado = 'en_proceso')   AS en_proceso,
        SUM(estado = 'resuelto')     AS resueltos,
        SUM(estado = 'cerrado')      AS cerrados,
        COUNT(*)                     AS total
    FROM tickets
    WHERE area = :area
");
$stmtKpi->execute([':area' => $userArea]);
$kpi = $stmtKpi->fetch() ?: [
    'abiertos'    => 0,
    'en_proceso'  => 0,
    'resueltos'   => 0,
    'cerrados'    => 0,
    'total'       => 0,
];

/* Tickets entrantes (abiertos, sin asignar) */
$stmtIncoming = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado
    FROM tickets
    WHERE area = :area
      AND estado = 'abierto'
      AND (asignado_a IS NULL OR asignado_a = 0)
    ORDER BY fecha_envio ASC
");
$stmtIncoming->execute([':area' => $userArea]);
$incomingTickets = $stmtIncoming->fetchAll();

/* máximo id actual para iniciar el polling desde ahí */
$maxIncomingId = 0;
foreach ($incomingTickets as $t) {
    $tid = (int)$t['id'];
    if ($tid > $maxIncomingId) {
        $maxIncomingId = $tid;
    }
}

/* Mis tickets activos */
$stmtMy = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado
    FROM tickets
    WHERE area = :area
      AND asignado_a = :uid
      AND estado IN ('abierto','en_proceso')
    ORDER BY fecha_envio DESC
");
$stmtMy->execute([':area' => $userArea, ':uid' => $userId]);
$myTickets = $stmtMy->fetchAll();

/* Historial (resueltos / cerrados) */
$stmtHistory = $pdo->prepare("
    SELECT id, sap, nombre, email, problema, descripcion, fecha_envio, estado, fecha_resolucion
    FROM tickets
    WHERE area = :area
      AND asignado_a = :uid
      AND estado IN ('resuelto','cerrado')
    ORDER BY fecha_resolucion DESC, fecha_envio DESC
");
$stmtHistory->execute([':area' => $userArea, ':uid' => $userId]);
$historyTickets = $stmtHistory->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Analista | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
</head>
<body class="user-body">

<?php if (!empty($alerts)): ?>
    <?php $alert = $alerts[0]; ?>
    <div id="eqf-alert-container">
        <div class="eqf-alert eqf-alert-<?php echo htmlspecialchars($alert['type']); ?>">
            <img
                class="eqf-alert-icon"
                src="/HelpDesk_EQF/assets/img/icons/<?php echo htmlspecialchars($alert['icon']); ?>"
                alt="alert icon"
            >
            <div class="eqf-alert-text">
                <?php echo htmlspecialchars($alert['text']); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- SIDEBAR -->
<aside class="user-sidebar">
    <div class="user-sidebar-profile">
        <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>"
             alt="Foto de perfil"
             class="user-sidebar-avatar">

        <div class="user-sidebar-avatar-circle">
            <?php echo strtoupper(substr($userName, 0, 1)); ?>
        </div>
        <div class="user-sidebar-info">
            <p class="user-sidebar-name">
                <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p class="user-sidebar-email">
                <?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p class="user-sidebar-email" style="opacity:0.8;">
                Área: <?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </div>

    <nav class="user-sidebar-menu">
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

        <button type="button" class="user-menu-item" onclick="scrollToSection('history-section')">
            <span class="user-menu-icon">📚</span>
            <span>Historial</span>
        </button>

        <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/auth/logout.php'">
            <span class="user-menu-icon">🚪</span>
            <span>Cerrar sesión</span>
        </button>
    </nav>
</aside>

<!-- CONTENIDO PRINCIPAL -->
<main class="user-main">
    <section class="user-main-inner">

        <header class="user-main-header" id="analyst-dashboard">
            <div>
                <p class="login-brand">
                    <span>HelpDesk </span><span class="eqf-e">E</span><span class="eqf-q">Q</span><span class="eqf-f">F</span>
                </p>
                <p class="user-main-subtitle">
                    Panel de Analista – <?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </header>

        <section class="user-main-content">

            <!-- RESUMEN / KPIs -->
            <div class="user-info-card">
                <h2>Resumen del área</h2>
                <p>
                    Aquí puedes gestionar los tickets asignados a tu área, ver tus tickets activos y revisar tu historial de atención.
                </p>
                <div class="kpi-analyst-row">
                    <div class="kpi-card kpi-green">
                        <span class="kpi-label">Abiertos</span>
                        <span class="kpi-value"><?php echo (int)$kpi['abiertos']; ?></span>
                    </div>
                    <div class="kpi-card kpi-blue">
                        <span class="kpi-label">En proceso</span>
                        <span class="kpi-value"><?php echo (int)$kpi['en_proceso']; ?></span>
                    </div>
                    <div class="kpi-card kpi-yellow">
                        <span class="kpi-label">Resueltos</span>
                        <span class="kpi-value"><?php echo (int)$kpi['resueltos']; ?></span>
                    </div>
                    <div class="kpi-card kpi-gray">
                        <span class="kpi-label">Total</span>
                        <span class="kpi-value"><?php echo (int)$kpi['total']; ?></span>
                    </div>
                </div>
            </div>

            <!-- TICKETS ENTRANTES -->
            <div id="incoming-section" class="user-info-card">
                <h3>Tickets entrantes (sin asignar)</h3>
                <?php if (empty($incomingTickets)): ?>
                    <p>No hay tickets entrantes sin asignar en este momento.</p>
                <?php else: ?>
                    <table id="incomingTable" class="data-table display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Problema</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incomingTickets as $t): ?>
                                <tr data-ticket-id="<?php echo (int)$t['id']; ?>">
                                    <td><?php echo (int)$t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <button type="button"
                                                class="btn-assign-ticket"
                                                data-ticket-id="<?php echo (int)$t['id']; ?>">
                                            Asignar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- MIS TICKETS ACTIVOS -->
            <div id="mytickets-section" class="user-info-card">
                <h3>Mis tickets activos</h3>
                <?php if (empty($myTickets)): ?>
                    <p>No tienes tickets activos asignados en este momento.</p>
                <?php else: ?>
                    <table id="myTicketsTable" class="data-table display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Problema</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myTickets as $t): ?>
                                <tr>
                                    <td><?php echo (int)$t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- HISTORIAL -->
            <div id="history-section" class="user-info-card">
                <h3>Historial de tickets atendidos</h3>
                <?php if (empty($historyTickets)): ?>
                    <p>Todavía no tienes tickets resueltos o cerrados.</p>
                <?php else: ?>
                    <table id="historyTable" class="data-table display">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha envío</th>
                                <th>Fecha resolución</th>
                                <th>Usuario</th>
                                <th>Problema</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyTickets as $t): ?>
                                <tr>
                                    <td><?php echo (int)$t['id']; ?></td>
                                    <td><?php echo htmlspecialchars($t['fecha_envio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['fecha_resolucion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(problemaLabel($t['problema']), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($t['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </section>
    </section>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="/HelpDesk_EQF/assets/js/script.js"></script>

<script>
function scrollToSection(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.addEventListener('DOMContentLoaded', function () {

    // ---------- DATATABLES ----------
    function initOrGetTable(selector, options) {
        if (!window.jQuery || !$.fn.dataTable || !$(selector).length) return null;
        if ($.fn.dataTable.isDataTable(selector)) {
            return $(selector).DataTable();
        }
        return $(selector).DataTable(options || {});
    }

    const incomingDT = initOrGetTable('#incomingTable', {
        pageLength: 5,
        order: [[1, 'asc']]
    });

    initOrGetTable('#myTicketsTable', {
        pageLength: 5,
        order: [[1, 'desc']]
    });

    initOrGetTable('#historyTable', {
        pageLength: 5,
        order: [[1, 'desc']]
    });

    // ---------- TOASTS ----------
    function showTicketToast(text) {
        const toast = document.createElement('div');
        toast.className = 'eqf-toast-ticket';
        toast.textContent = text;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ---------- ASIGNAR TICKET ----------
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-assign-ticket');
        if (!btn) return;

        // evitar doble click
        if (btn.dataset.loading === '1') return;
        btn.dataset.loading = '1';

        const rowEl = btn.closest('tr');
        const ticketId = btn.dataset.ticketId || (rowEl && rowEl.getAttribute('data-ticket-id'));
        if (!ticketId || !rowEl) {
            btn.dataset.loading = '0';
            return;
        }

        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Asignando...';

        fetch('/HelpDesk_EQF/modules/ticket/assign.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ticket_id=' + encodeURIComponent(ticketId)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                alert(data.msg || 'No se pudo asignar el ticket.');
                btn.disabled = false;
                btn.textContent = originalText;
                btn.dataset.loading = '0';
                return;
            }

            // quitar fila de entrantes
            if (incomingDT) {
                incomingDT.row($(rowEl)).remove().draw(false);
            } else if (rowEl.parentNode) {
                rowEl.parentNode.removeChild(rowEl);
            }

            showTicketToast('Ticket #' + ticketId + ' asignado a ti.');
            btn.dataset.loading = '0';
        })
        .catch(err => {
            console.error(err);
            alert('Error al asignar el ticket.');
            btn.disabled = false;
            btn.textContent = originalText;
            btn.dataset.loading = '0';
        });
    });

    // ---------- NOTIFICACIONES NUEVOS TICKETS ----------
    let lastTicketId = <?php echo (int)$maxIncomingId; ?>;

    // permiso de notificaciones de escritorio
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function showInPageToast(msg) {
        showTicketToast(msg);
    }

    function showDesktopNotification(ticket) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        new Notification('Nuevo ticket entrante (' + ticket.id + ')', {
            body: ticket.problema || '',
            icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png'
        });
    }

    function addIncomingTicketRow(ticket) {
        if (!ticket || !ticket.id) return;

        const rowData = [
            ticket.id,
            ticket.fecha || '',
            ticket.usuario || '',
            ticket.problema || '',
            ticket.descripcion || '',
            `<button type="button"
                     class="btn-assign-ticket"
                     data-ticket-id="${ticket.id}">
                Asignar
             </button>`
        ];

        if (incomingDT) {
            incomingDT.row.add(rowData).draw(false);
        } else {
            const tbody = document.querySelector('#incomingTable tbody');
            if (!tbody) return;

            const tr = document.createElement('tr');
            tr.setAttribute('data-ticket-id', ticket.id);
            tr.innerHTML = `
                <td>${rowData[0]}</td>
                <td>${rowData[1]}</td>
                <td>${rowData[2]}</td>
                <td>${rowData[3]}</td>
                <td>${rowData[4]}</td>
                <td>${rowData[5]}</td>
            `;
            tbody.prepend(tr);
        }
    }

    function pollNewTickets() {
        fetch('/HelpDesk_EQF/modules/ticket/check_new.php?last_id=' + lastTicketId)
            .then(r => r.json())
            .then(data => {
                if (!data || !data.new) return;

                lastTicketId = data.id;
                const msg = 'Nuevo ticket #' + data.id + ' – ' + (data.problema || '');
                showInPageToast(msg);
                showDesktopNotification(data);
                addIncomingTicketRow(data);
            })
            .catch(err => console.error('Error comprobando nuevos tickets:', err));
    }

    // revisar cada 10 segundos
    setInterval(pollNewTickets, 10000);
});
</script>

</body>
</html>
