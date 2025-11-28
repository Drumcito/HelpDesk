<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';

// Header + navbar
include __DIR__ . '/../../../template/header.php';
$activePage = 'inicio'; // por si lo usas en el navbar
include __DIR__ . '/../../../template/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

// Rol (opcional: restringir a rol de usuario final)
$rol = (int)($_SESSION['user_rol'] ?? 0);
// if ($rol !== 4) {
//     header('Location: /HelpDesk_EQF/modules/dashboard/sa/sa.php');
//     exit;
// }

$pdo = Database::getConnection();

// Datos del usuario en sesión (ajusta a como los guardas)
$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';
$userSap   = $_SESSION['user_sap'] ?? '';

// ====== MIS TICKETS (ejemplo de consulta) ======
// Ajusta nombres de tabla/columnas a tu modelo real
$tickets = [];

try {
    $stmt = $pdo->prepare('
        SELECT id, status, created_at, analyst_name
        FROM tickets
        WHERE created_by = :user_id
        ORDER BY created_at DESC
        LIMIT 20
    ');
    $stmt->execute([':user_id' => $userId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si hay error, simplemente dejamos $tickets vacío.
    $tickets = [];
}
?>

<main class="main-content user-dashboard">
    <div class="user-dashboard-layout">

        <!-- COLUMNA IZQUIERDA: FORMULARIO + MIS TICKETS -->
        <section class="user-panel">

            <div class="user-title-row">
                <h1 class="user-page-title">HelpDesk EQF <span>— Usuario</span></h1>
            </div>

            <!-- CARD: CREAR TICKET -->
            <section class="card card-ticket">
                <h2 class="card-title">Crear Ticket</h2>

                <form action="/HelpDesk_EQF/modules/ticket/create.php" method="POST" class="ticket-form">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="form-control"
                                value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
                                readonly
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>"
                                readonly
                            >
                        </div>

                        <div class="form-group">
                            <label for="area">Área</label>
                            <input
                                type="text"
                                id="area"
                                name="area"
                                class="form-control"
                                value="<?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>"
                                readonly
                            >
                        </div>

                        <div class="form-group">
                            <label for="sap">#SAP</label>
                            <input
                                type="text"
                                id="sap"
                                name="sap"
                                class="form-control"
                                value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>"
                                readonly
                            >
                        </div>
                    </div>

                    <div class="form-checkbox-row">
                        <input type="checkbox" id="no_jefe" name="no_jefe">
                        <label for="no_jefe">No soy jefe de sucursal</label>
                    </div>

                    <div class="form-group">
                        <label for="problema">Problema</label>
                        <select id="problema" name="problema" class="form-control" required>
                            <option value="">Selecciona una opción</option>
                            <option value="cierre_dia">Cierre del día</option>
                            <option value="no_tengo_acceso">No tengo acceso</option>
                            <option value="no_tengo_internet">No tengo internet</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            class="form-control"
                            rows="3"
                            placeholder="Cuéntanos más sobre tu problema..."
                        ></textarea>
                    </div>

                    <div class="form-actions-right">
                        <button type="submit" class="btn btn-primary">
                            Enviar ticket
                        </button>
                    </div>
                </form>
            </section>

            <!-- CARD: MIS TICKETS -->
            <section class="card card-tickets-list">
                <h2 class="card-title">Mis Tickets</h2>

                <div class="table-wrapper">
                    <table class="table-tickets">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Analista</th>
                                <th>Ver / Reabrir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="5">Aún no tienes tickets registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ticket['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php
                                                $status = $ticket['status'] ?? '';
                                                $badgeClass = 'badge-open';
                                                $statusLabel = $status;

                                                switch ($status) {
                                                    case 'open':
                                                    case 'abierto':
                                                        $badgeClass = 'badge-open';
                                                        $statusLabel = 'Abierto';
                                                        break;
                                                    case 'closed':
                                                    case 'cerrado':
                                                        $badgeClass = 'badge-closed';
                                                        $statusLabel = 'Cerrado';
                                                        break;
                                                    case 'pending':
                                                    case 'en_proceso':
                                                        $badgeClass = 'badge-pending';
                                                        $statusLabel = 'En proceso';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                                // Ajusta el nombre de la columna de fecha si es diferente
                                                $date = $ticket['created_at'] ?? '';
                                                if ($date !== '') {
                                                    $dt = new DateTime($date);
                                                    echo $dt->format('d/m/Y H:i');
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($ticket['analyst_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <a
                                                href="/HelpDesk_EQF/modules/ticket/view.php?id=<?php echo urlencode($ticket['id']); ?>"
                                                class="link-action"
                                            >
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </section>

        <!-- COLUMNA DERECHA: CAPSULia -->
        <aside class="capsulia-panel">

            <section class="card capsulia-card">
                <header class="capsulia-header">
                    <div class="capsulia-header-left">
                        <div class="capsulia-avatar">
                            <span>🤖</span>
                        </div>
                        <div>
                            <p class="capsulia-name">CAPSULia</p>
                            <p class="capsulia-subtitle">Asistente HelpDesk EQF</p>
                        </div>
                    </div>
                    <button type="button" class="capsulia-close">×</button>
                </header>

                <div class="capsulia-body">

                    <div class="capsulia-message capsulia-message-bot">
                        <p class="capsulia-message-text">
                            👋 ¡Hola! Soy <strong>CAPSULia</strong>, tu asistente del HelpDesk EQF.<br>
                            Puedo ayudarte a resolver tu problema paso a paso o crear un ticket automáticamente si lo necesitas.
                        </p>
                    </div>

                    <div class="capsulia-quick-actions">
                        <button type="button" class="btn-chip">Cierre del día</button>
                        <button type="button" class="btn-chip">No tengo acceso</button>
                        <button type="button" class="btn-chip">No tengo internet</button>
                        <button type="button" class="btn-chip btn-chip-outline">Otro</button>
                    </div>

                    <form class="capsulia-input-row" action="#" method="POST">
                        <button type="button" class="capsulia-attach-btn">📎</button>
                        <input
                            type="text"
                            class="capsulia-input"
                            name="mensaje_capsulia"
                            placeholder="Escribe tu mensaje..."
                        >
                        <button type="submit" class="btn btn-primary btn-send">Enviar</button>
                    </form>

                </div>
            </section>

        </aside>

    </div>
</main>

<?php
include __DIR__ . '/../../../template/footer.php';
