<?php
session_start();
require_once __DIR__ . '/../../../config/connectionBD.php';
include __DIR__ . '/../../../template/header.php';
$activePage = 'directorio';
include __DIR__ . '/../../../template/navbar.php'; 


if (!isset($_SESSION['user_id'])) {
    header('Location: /HelpDesk_EQF/auth/login.php');
    exit;
}

$rol = (int)($_SESSION['user_rol'] ?? 0);
if ($rol !== 1) {
    header('Location: /../../../modules/dashboard/sa/sa.php');
    exit;
}
$pdo = Database::getConnection();

$nombreCompleto = $_SESSION['user_name'] . ' ' . $_SESSION['user_last'];

$stmt = $pdo->query('
    SELECT id, number_sap, name, last_name, email, rol, area
    FROM users
    ORDER BY last_name ASC, name ASC
');


$usuarios = $stmt->fetchAll();

function rolLabel(int $rol): string {
    return match ($rol) {
        1 => 'SA',
        2 => 'Admin',
        3 => 'Analista',
        4 => 'Usuario',
        default => '—',
    };
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directorio | HELP DESK EQF</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="/HelpDesk_EQF/assets/css/style.css">

</head>
<body class="directory-body">

        <!-- ALERTAS -->
<?php
$alerts = [];

if (isset($_GET['created'])) {
    $alerts[] = [
        'type' => 'success',
        'icon' => 'capsulin_add.png',   // ícono verde (Capsulín feliz +)
        'text' => 'USUARIO REGISTRADO EXITOSAMENTE'
    ];
}

if (isset($_GET['deleted'])) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => 'capsulin_delete.png', // ícono rojo (Capsulín triste -)
        'text' => 'USUARIO ELIMINADO EXITOSAMENTE'
    ];
}

if (isset($_GET['updated'])) {
    $alerts[] = [
        'type' => 'info',
        'icon' => 'capsulin_update.png', // ícono azul (Capsulín actualizando)
        'text' => 'USUARIO ACTUALIZADO EXITOSAMENTE'
    ];
}
?>

<?php if (!empty($alerts)): ?>
    <div id="eqf-alert-container">
        <?php foreach ($alerts as $a): ?>
            <div class="eqf-alert eqf-alert-<?php echo $a['type']; ?>">
                <img
                    class="eqf-alert-icon"
                    src="/HelpDesk_EQF/assets/img/icons/<?php echo htmlspecialchars($a['icon'], ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Notificación EQF"
                >
                <p class="eqf-alert-text">
                    <?php echo htmlspecialchars($a['text'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


    <!-- CONTENIDO PRINCIPAL -->
    <main class="directory-main">
        <header class="directory-header-main">

            <div>
                <h1>Directorio de Usuarios</h1>
            </div>
            <div class="dashboard-user-pill">
               <b> <span ><?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?></span></b>
            </div>
        </header>

        <!-- BARRA DE BÚSQUEDA -->
        <section class="directory-search-row">
            <div class="search-input-wrap">
                <input
                    type="text"
                    id="searchUser"
                    class="search-big"
                    placeholder="BUSCAR POR #SAP O NOMBRE"
                    autocomplete="off"
                >
            </div>
        </section>
<!-- linea de botones FIltro && CRUD-->
 <div class="directory-filter-actions-row">
        <!-- FILTRO -->
        <div class="directory-filter-chips">
            <button class="chip-filter chip-active" data-area="ALL">Todos</button>
            <button class="chip-filter" data-area="TI">TI</button>
            <button class="chip-filter" data-area="MKT">MKT</button>
            <button class="chip-filter" data-area="SAP">SAP</button>
            <button class="chip-filter" data-area="Sucursal">Sucursales</button>
        </div>

        <!-- BOTONES CRUD -->
            <div class="directory-actions">
                <button type="button" class="action-btn action-add" title="Agregar usuario" onclick="openModal('modal-create-user')">
                    <img src="/HelpDesk_EQF/assets/img/icons/icon_add.png" class="action-icon" alt="Agregar">
                </button>
                <button type="button" class="action-btn action-delete" title="Eliminar usuario" onclick="handleDeleteUser()">
                    <img src="/HelpDesk_EQF/assets/img/icons/icon_delete.png" class="action-icon" alt="Eliminar">
                </button>
                <button type="button" class="action-btn action-edit" title="Actualizar usuario" onclick="openEditModal()">
                    <img src="/HelpDesk_EQF/assets/img/icons/icon_update.png" class="action-icon" alt="Actualizar">
                </button>
            </div>
        </div>

        <!-- TABLA -->
        <section class="directory-table-row">
            <div class="directory-table-wrapper">
                <table class="data-table directory-table" id="directoryTable">
                    <thead>
                        <tr>
                            <th style="color: white;"># SAP</th>
                            <th style="color: white;">Nombre</th>
                            <th style="color: white;">Apellido</th>
                            <th style="color: white;">Área</th>
                            <th style="color: white;">Rol</th>
                            <th style="color: white;">Correo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($usuarios) === 0): ?>
                        <tr>
                            <td colspan="6" class="table-empty">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr class="directory-row"
                                data-id="<?php echo (int)$u['id']; ?>"
                                data-sap="<?php echo htmlspecialchars($u['number_sap'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-name="<?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-last="<?php echo htmlspecialchars($u['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-area="<?php echo htmlspecialchars($u['area'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-rol="<?php echo (int)$u['rol']; ?>"
                            >
                                <td><?php echo htmlspecialchars($u['number_sap'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($u['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($u['area'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(rolLabel((int)$u['rol']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL: NUEVO USUARIO -->
        <div class="modal-backdrop" id="modal-create-user">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Registrar nuevo usuario</h3>
                    <button type="button" class="modal-close" onclick="closeModal('modal-create-user')">✕</button>
                </div>
                <p class="modal-description">
                    La contraseña será temporal. Al iniciar sesión por primera vez,
                    el sistema le pedirá al usuario que la cambie.
                </p>

                    <form method="POST" action="../../../auth/users.php" class="modal-form">
                            <input type="hidden" name="action" value="create">

                    <div class="modal-grid">
                        <div class="form-group">
                            <label>No. SAP</label>
                            <input type="text" name="number_sap" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido(s)</label>
                            <input type="text" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Correo</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Área</label>
                             <select name="area" required>
                                <option value="">Selecciona...</option>
                                <option value="TI">TI</option>
                                <option value="MKT">MKT</option>
                                <option value="SAP">SAP</option>
                                <option value="Corporativo">Corporativo</option>  
                                <option value="Sucursal">Sucursales</option>  
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rol</label>
                            <select name="rol" required>
                                <option value="">Selecciona...</option>
                                <option value="1">SA</option>
                                <option value="2">Administrador</option>
                                <option value="3">Analista</option>
                                <option value="4">Usuario</option>
                            </select>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; margin-top: 15px;">
                            <input type="checkbox" name="assign_password" id="assign_password"
                             style="width: 18px; height: 18px; margin: 0; cursor: pointer;" checked>
                            <label for="assign_password" style="cursor: pointer;">Asignar contraseña</label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('modal-create-user')">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-login" style="width:auto;">
                            Guardar usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDITAR USUARIO -->
        <div class="modal-backdrop" id="modal-edit-user">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Editar usuario</h3>
                    <button type="button" class="modal-close" onclick="closeModal('modal-edit-user')">✕</button>
                </div>
                <p class="modal-description">
                    Actualiza los datos del usuario seleccionado.
                </p>
<form method="POST" action="/HelpDesk_EQF/auth/users.php" class="modal-form">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" id="edit_id">

                    <div class="modal-grid">
                        <div class="form-group">
                            <label>No. SAP</label>
                            <input type="text" name="number_sap" id="edit_sap" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="name" id="edit_name" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido(s)</label>
                            <input type="text" name="last_name" id="edit_last" required>
                        </div>
                        <div class="form-group">
                            <label>Correo</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label>Área</label>
                            <select name="area" id="edit_area" required>
                                <option value="">Selecciona...</option>
                                <option value="TI">TI</option>
                                <option value="MKT">MKT</option>
                                <option value="SAP">SAP</option>
                                <option value="Corporativo">Corporativo</option>
                                <option value="Sucursales">Sucursales</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rol</label>
                            <select name="rol" id="edit_rol" required>
                                <option value="">Selecciona...</option>
                                <option value="1">SA</option>
                                <option value="2">Administrador</option>
                                <option value="3">Analista</option>
                                <option value="4">Usuario</option>
                            </select>
                        </div>
                            <div style="display:flex; align-items:center; gap:8px; margin-top: 15px;">
                                <input type="checkbox" name="reset_password" id="reset"
                                        style="width: 18px; height: 18px; margin: 0; cursor: pointer;">
                                    <label for="reset" style="cursor: pointer;">Reiniciar contraseña</label>
                            </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('modal-edit-user')">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-login" style="width:auto;">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- FORMULARIO OCULTO PARA ELIMINAR -->
       <form id="deleteForm" method="POST" action="/HelpDesk_EQF/auth/users.php" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>
    </main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="/HelpDesk_EQF/assets/js/script.js"></script>

</body>
</html>