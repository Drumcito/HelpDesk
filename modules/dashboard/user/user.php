<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$pdo = Database::getConnection();

$userId    = (int)($_SESSION['user_id'] ?? 0);
$userName  = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
$userEmail = $_SESSION['user_email'] ?? '';
$userArea  = $_SESSION['user_area'] ?? '';
$userSap   = $_SESSION['user_sap'] ?? '';


$profileImg = ($userArea === 'Sucursal')
    ? '/HelpDesk_EQF/assets/img/pp/pp_sucursal.jpg'
    : '/HelpDesk_EQF/assets/img/pp/pp_corporativo.jpg';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>User | HELP DESK EQF</title>
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">
</head>

<body class="user-body">

    <!-- SIDEBAR IZQUIERDO -->
    <aside class="user-sidebar">
        <div class="user-sidebar-profile">
            <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Foto de perfil"
                 class="user-sidebar-avatar">

            <div class="user-sidebar-info">
                <p class="user-sidebar-name">
                    <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <p class="user-sidebar-area">
                    <?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        </div>

        <nav class="user-sidebar-menu">
            <button type="button" class="user-menu-item" onclick="scrollToTickets()">
                <span class="user-menu-icon">📄</span>
                <span>Tickets</span>
            </button>

            <button type="button" class="user-menu-item" onclick="openTicketModal()">
                <span class="user-menu-icon">➕</span>
                <span>Crear ticket</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/auth/logout.php'">
                <span class="user-menu-icon">🚪</span>
                <span>Cerrar sesión</span>
            </button>

            <button type="button" class="user-menu-item" onclick="window.location.href='/HelpDesk_EQF/modules/docs/important.php'">
                <span class="user-menu-icon">📎</span>
                <span>Documentos importantes</span>
            </button>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="user-main">

        <!-- HEADER YA VIENE ARRIBA (header.php) -->

        <section class="user-main-inner">

            <!-- TITULO / BIENVENIDA -->
            <header class="user-main-header">
                <div>
                    <h1 class="user-main-title">HelpDesk EQF</h1>
                    <p class="user-main-subtitle">
                        Bienvenido, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>.
                    </p>
                </div>
            </header>

            <!-- BLOQUE CENTRAL (puedes mostrar algo sencillo por ahora) -->
            <section class="user-main-content">
                <div class="user-info-card">
                    <h2>Resumen rápido</h2>
                    <p>
                        Desde aquí puedes crear nuevos tickets, consultar el historial de los que has levantado
                        y acceder a documentos importantes para la operación de tu sucursal o área.
                    </p>
                    <button type="button" class="btn-primary user-main-cta" onclick="openTicketModal()">
                        Crear nuevo ticket
                    </button>
                </div>

                <!-- Sección de tickets como ancla -->
                <div id="tickets-section" class="user-tickets-placeholder">
                    <h3>Tus tickets</h3>
                    <p>
                        Próximamente aquí listaremos tus tickets recientes.  
                        Por ahora, utiliza el botón <strong>“Crear ticket”</strong> del menú lateral o el de arriba.
                    </p>
                </div>
            </section>

        </section>

    </main>

    <!-- CHATBOT FLOtANTE (OCULTO HASTA CLIC) -->
    <div class="chatbot-container" id="chatbot">
        <button type="button" class="chatbot-toggle" onclick="toggleChatbot()">
            💬
        </button>

        <div class="chatbot-panel">
            <header class="chatbot-header">
                <div class="chatbot-header-info">
                    <span class="chatbot-avatar">🤖</span>
                    <div>
                        <p class="chatbot-title">CAPSULia</p>
                        <p class="chatbot-subtitle">Asistente HelpDesk EQF</p>
                    </div>
                </div>
                <button type="button" class="chatbot-close" onclick="toggleChatbot()">×</button>
            </header>

            <div class="chatbot-body">
                <div class="chatbot-message chatbot-message-bot">
                    <p>
                        ¡Hola! Soy <strong>CAPSULia</strong>.  
                        Cuéntame tu problema y te ayudaré a resolverlo o a crear un ticket.
                    </p>
                </div>

                <div class="chatbot-quick-actions">
                    <button type="button" class="chatbot-chip">Cierre del día</button>
                    <button type="button" class="chatbot-chip">No tengo acceso</button>
                    <button type="button" class="chatbot-chip">No tengo internet</button>
                    <button type="button" class="chatbot-chip chatbot-chip-outline">Otro</button>
                </div>
            </div>

            <form class="chatbot-input-row" onsubmit="event.preventDefault();">
                <button type="button" class="chatbot-attach">📎</button>
                <input type="text" class="chatbot-input" placeholder="Escribe tu mensaje...">
                <button type="submit" class="chatbot-send">Enviar</button>
            </form>
        </div>
    </div>

    <!-- MODAL CREAR TICKET -->
    <div class="user-modal-backdrop" id="ticketModal">
        <div class="user-modal">
            <header class="user-modal-header">
                <h2>Crear ticket</h2>
                <button type="button" class="user-modal-close" onclick="closeTicketModal()">×</button>
            </header>

            <p class="user-modal-description">
                Completa la información para registrar tu incidencia en el HelpDesk EQF.
            </p>

            <form method="POST" action="/HelpDesk_EQF/modules/ticket/create.php" class="user-modal-form">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="sap" value="<?php echo htmlspecialchars($userSap, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="area" value="<?php echo htmlspecialchars($userArea, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="user-modal-grid">
                    <div class="form-group">
                        <label>Problema</label>
                        <select name="problema" required>
                            <option value="">Selecciona una opción</option>
                            <option value="cierre_dia">Cierre del día</option>
                            <option value="no_tengo_acceso">No tengo acceso</option>
                            <option value="no_tengo_internet">No tengo internet</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group form-group-full">
                        <label>Descripción</label>
                        <textarea name="descripcion" rows="3" placeholder="Describe brevemente el problema..." required></textarea>
                    </div>
                </div>

                <div class="user-modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeTicketModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Enviar ticket</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTicketModal() {
            document.getElementById('ticketModal').classList.add('is-visible');
        }

        function closeTicketModal() {
            document.getElementById('ticketModal').classList.remove('is-visible');
        }

        function toggleChatbot() {
            const c = document.getElementById('chatbot');
            c.classList.toggle('is-open');
        }

        function scrollToTickets() {
            const section = document.getElementById('tickets-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    </script>

    <script src="/HelpDesk_EQF/assets/js/script.js"></script>
<?php include __DIR__ . '/../../../template/footer.php'; ?>

</body>
</html>
