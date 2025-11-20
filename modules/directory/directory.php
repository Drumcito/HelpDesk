<?php
session_start();
require_once __DIR__ . '/../../config/connectionBD.php';

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
    <title>Directorio | Mesa de Ayuda EQF</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="directory-body">

    <!-- MENÚ PASTILLA LATERAL -->
    <aside class="sidebar-pill">
        <div class="sidebar-pill-inner">
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
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="directory-main">
        <header class="directory-header-main">
            <div>
                <h1>Directorio de Usuarios</h1>
                <p class="directory-subtitle">
                    Busca, filtra y administra usuarios de todas las áreas.
                </p>
            </div>
            <div class="dashboard-user-pill">
                <span><?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </header>

        <!-- BARRA DE BÚSQUEDA -->
        <section class="directory-search-row">
            <div class="search-input-wrap">
                <input
                    type="text"
                    id="searchUser"
                    class="search-big"
                    placeholder="BUSCAR POR # SAP O NOMBRE"
                    autocomplete="off"
                >
            </div>
            <button type="button" class="btn-search-big" onclick="triggerSearch()">
                BUSCAR
            </button>
        </section>

        <!-- CHIPS DE FILTRO -->
        <div class="directory-filter-chips">
            <button class="chip-filter chip-active" data-area="ALL">Todos</button>
            <button class="chip-filter" data-area="TI">TI</button>
            <button class="chip-filter" data-area="MKT">MKT</button>
            <button class="chip-filter" data-area="SAP">SAP</button>
            <button class="chip-filter" data-area="SUCURSALES">Sucursales</button>
        </div>

        <!-- TABLA + BOTONES LATERALES -->
        <section class="directory-table-row">
            <div class="directory-table-wrapper">
                <table class="data-table directory-table" id="directoryTable">
                    <thead>
                        <tr>
                            <th># SAP</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Área</th>
                            <th>Rol</th>
                            <th>Correo</th>
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

            <!-- BOTONES DE ACCIÓN (VERDE, ROJO, AMARILLO) -->
            <div class="directory-actions">
                <button type="button" class="action-btn action-add" title="Agregar usuario" onclick="openModal('modal-create-user')">
                    ➕
                </button>
                <button type="button" class="action-btn action-delete" title="Eliminar usuario" onclick="handleDeleteUser()">
                    🗑
                </button>
                <button type="button" class="action-btn action-edit" title="Actualizar usuario" onclick="openEditModal()">
                    ♻️
                </button>
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

                <form method="POST" action="../../auth/users.php" class="modal-form">
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
                            <input type="text" name="area" required>
                        </div>
                        <div class="form-group">
                            <label>Rol</label>
                            <select name="rol" required>
                                <option value="">Selecciona...</option>
                                <option value="1">Super Administrador</option>
                                <option value="2">Administrador</option>
                                <option value="3">Analista</option>
                                <option value="4">Usuario</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Contraseña temporal</label>
                            <input type="text" name="temp_password" required>
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

                <form method="POST" action="../../auth/users.php" class="modal-form">
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
                            <input type="text" name="area" id="edit_area" required>
                        </div>
                        <div class="form-group">
                            <label>Rol</label>
                            <select name="rol" id="edit_rol" required>
                                <option value="1">Super Administrador</option>
                                <option value="2">Administrador</option>
                                <option value="3">Analista</option>
                                <option value="4">Usuario</option>
                            </select>
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
        <form id="deleteForm" method="POST" action="../../auth/users.php" style="display:none;">
            <input type="hidden" name="id" id="delete_id">
        </form>

    </main>

    <script>
        // --- selección de fila ---
        let selectedRow = null;

        const rows = document.querySelectorAll('.directory-row');
        rows.forEach(row => {
            row.addEventListener('click', () => {
                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                }
                selectedRow = row;
                row.classList.add('row-selected');
            });
        });

        // --- búsqueda + filtro combinados ---
        const searchInput = document.getElementById('searchUser');
        const filterChips = document.querySelectorAll('.chip-filter');
        let currentArea = 'ALL';

        function applyFilter() {
            const term = (searchInput.value || '').trim().toLowerCase();

            document.querySelectorAll('.directory-row').forEach(row => {
                const sap  = row.dataset.sap.toLowerCase();
                const name = row.dataset.name.toLowerCase();
                const last = row.dataset.last.toLowerCase();
                const area = row.dataset.area.toLowerCase();

                const matchTerm =
                    term === '' ||
                    sap.includes(term) ||
                    name.includes(term) ||
                    last.includes(term);

                const matchArea =
                    currentArea === 'ALL' ||
                    area === currentArea.toLowerCase();

                row.style.display = (matchTerm && matchArea) ? '' : 'none';
            });
        }

        function triggerSearch() {
            applyFilter();
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilter);
        }

        // --- filtro por chips ---
        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                filterChips.forEach(c => c.classList.remove('chip-active'));
                chip.classList.add('chip-active');
                currentArea = chip.dataset.area || 'ALL';
                selectedRow = null;
                applyFilter();
            });
        });

        // --- modales ---
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('show');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('show');
        }

        // --- eliminar usuario ---
        function handleDeleteUser() {
            if (!selectedRow) {
                alert('Primero selecciona un usuario en la tabla.');
                return;
            }
            const name = selectedRow.dataset.name + ' ' + selectedRow.dataset.last;
            if (!confirm('¿Eliminar al usuario: ' + name + '?')) {
                return;
            }
            const id = selectedRow.dataset.id;
            const deleteInput = document.getElementById('delete_id');
            deleteInput.value = id;
            document.getElementById('deleteForm').submit();
        }

        // --- editar usuario ---
        function openEditModal() {
            if (!selectedRow) {
                alert('Primero selecciona un usuario en la tabla.');
                return;
            }
            document.getElementById('edit_id').value   = selectedRow.dataset.id;
            document.getElementById('edit_sap').value  = selectedRow.dataset.sap;
            document.getElementById('edit_name').value = selectedRow.dataset.name;
            document.getElementById('edit_last').value = selectedRow.dataset.last;
            document.getElementById('edit_area').value = selectedRow.dataset.area;
            document.getElementById('edit_email').value = selectedRow.dataset.email;
            document.getElementById('edit_rol').value  = selectedRow.dataset.rol;

            openModal('modal-edit-user');
        }
    </script>
    <script src="../../assets/js/scripts.js"></script>

</body>
</html>