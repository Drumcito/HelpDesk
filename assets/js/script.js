/* ============================================
   SCRIPTS GLOBALES · MESA DE AYUDA EQF
   Archivo: assets/js/scripts.js
============================================ */

document.addEventListener('DOMContentLoaded', () => {

    /* ============================================
       MODALES GLOBALES
    ============================================ */
    window.openModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('show');
    };

    window.closeModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('show');
    };

    /* ============================================
       DIRECTORIO DE USUARIOS
       (solo si existe la tabla)
    ============================================ */
    const directoryTableEl = document.querySelector('.directory-table');
    let selectedRow = null;
    let currentArea = 'ALL';

    if (directoryTableEl) {
                function sortDirectoryRows(mode) {
            const tbody = directoryTableEl.querySelector('tbody');
            if (!tbody) return;

            const rowsArr = Array.from(tbody.querySelectorAll('.directory-row'));

            rowsArr.sort((a, b) => {
                const aLast  = (a.dataset.last  || '').toLowerCase();
                const bLast  = (b.dataset.last  || '').toLowerCase();
                const aName  = (a.dataset.name  || '').toLowerCase();
                const bName  = (b.dataset.name  || '').toLowerCase();
                const aEmail = (a.dataset.email || '').toLowerCase();
                const bEmail = (b.dataset.email || '').toLowerCase();

                if (mode === 'email') {
                    if (aEmail < bEmail) return -1;
                    if (aEmail > bEmail) return 1;
                    return 0;
                } else {
                    // modo por defecto: Apellido, luego Nombre
                    if (aLast < bLast) return -1;
                    if (aLast > bLast) return 1;
                    if (aName < bName) return -1;
                    if (aName > bName) return 1;
                    return 0;
                }
            });

            // Reinsertar en el DOM en el nuevo orden
            rowsArr.forEach(row => tbody.appendChild(row));
        }

        // -------- Selección de fila (delegado) --------
        const tbody = directoryTableEl.querySelector('tbody');

        if (tbody) {
            tbody.addEventListener('click', (e) => {
                const row = e.target.closest('.directory-row');
                if (!row) return;

                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                }
                selectedRow = row;
                row.classList.add('row-selected');
            });
        }

        // -------- BÚSQUEDA + FILTRO POR ÁREA --------
        const searchInput = document.getElementById('searchUser');
        const filterChips = document.querySelectorAll('.chip-filter');

        function applyFilter() {
            const term = (searchInput && searchInput.value ? searchInput.value : '')
                .trim()
                .toLowerCase();

            document.querySelectorAll('.directory-row').forEach(row => {
                const sap  = (row.dataset.sap  || '').toLowerCase();
                const name = (row.dataset.name || '').toLowerCase();
                const last = (row.dataset.last || '').toLowerCase();
                const area = (row.dataset.area || '').toLowerCase();

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

        if (searchInput) {
            searchInput.addEventListener('input', applyFilter);
        }


        // Filtros por área: TI, MKT, SAP, Sucursal
 filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                filterChips.forEach(c => c.classList.remove('chip-active'));
                chip.classList.add('chip-active');

                currentArea = chip.dataset.area || 'ALL';

                // Orden según filtro:
                // - Si Sucursal -> por correo
                // - En cualquier otro caso -> por Apellido + Nombre
                if (currentArea === 'Sucursal') {
                    sortDirectoryRows('email');
                } else {
                    sortDirectoryRows('name'); // modo por defecto
                }

                // Al cambiar filtro, se deselecciona la fila actual
                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                    selectedRow = null;
                }

                applyFilter();
            });
        });

        /* ============================================
           Acciones CRUD (Agregar, Eliminar, Actualizar)
        ============================================ */

        // --- Eliminar usuario ---
        window.handleDeleteUser = function () {
            if (!selectedRow) {
                alert('Primero selecciona un usuario en la tabla.');
                return;
            }

            const fullName =
                (selectedRow.dataset.name || '') +
                ' ' +
                (selectedRow.dataset.last || '');

            if (!confirm('¿Eliminar al usuario: ' + fullName + '?')) {
                return;
            }

            const id = selectedRow.dataset.id;
            const deleteForm = document.getElementById('deleteForm');
            const deleteInput = document.getElementById('delete_id');

            if (!deleteForm || !deleteInput) {
                console.error('No se encontró el formulario de eliminación.');
                return;
            }

            deleteInput.value = id;
            deleteForm.submit();
        };

        // --- Abrir modal de edición ---
        window.openEditModal = function () {
            if (!selectedRow) {
                alert('Primero selecciona un usuario en la tabla.');
                return;
            }

            const id    = selectedRow.dataset.id;
            const sap   = selectedRow.dataset.sap   || '';
            const name  = selectedRow.dataset.name  || '';
            const last  = selectedRow.dataset.last  || '';
            const area  = selectedRow.dataset.area  || '';
            const email = selectedRow.dataset.email || '';
            const rol   = selectedRow.dataset.rol   || '';

            const idField    = document.getElementById('edit_id');
            const sapField   = document.getElementById('edit_sap');
            const nameField  = document.getElementById('edit_name');
            const lastField  = document.getElementById('edit_last');
            const areaField  = document.getElementById('edit_area');
            const emailField = document.getElementById('edit_email');
            const rolField   = document.getElementById('edit_rol');

            if (!idField || !sapField || !nameField || !lastField || !areaField || !emailField || !rolField) {
                console.error('No se encontraron los campos del formulario de edición.');
                return;
            }

            idField.value    = id;
            sapField.value   = sap;
            nameField.value  = name;
            lastField.value  = last;
            areaField.value  = area;
            emailField.value = email;
            rolField.value   = rol;

            openModal('modal-edit-user');
        };
    }

    /* ============================================
                    ALERTAS CRUD
    ============================================ */
    (function initCrudAlerts() {
        const container = document.getElementById('eqf-alert-container');
        if (!container) return;

        setTimeout(() => {
            container.classList.add('eqf-alert-hide'); 
            setTimeout(() => {
                if (container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            }, 350);
        }, 2000); 
    })();

});
