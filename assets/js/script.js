/* ============================================
   SCRIPTS GLOBALES · MESA DE AYUDA EQF
   Archivo: assets/js/scripts.js
============================================ */

document.addEventListener('DOMContentLoaded', function () {

    /* ============================================
       MODALES GLOBALES
       (openModal / closeModal) – reutilizables
    ============================================ */

    window.openModal = function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('show');
        }
    };

    window.closeModal = function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('show');
        }
    };

    /* ============================================
       DIRECTORIO DE USUARIOS
       (solo se ejecuta si existe la tabla)
    ============================================ */

    const directoryTable = document.querySelector('.directory-table');

    if (directoryTable) {
        // -------- Selección de fila --------
        let selectedRow = null;
        const rows = directoryTable.querySelectorAll('.directory-row');

        rows.forEach(row => {
            row.addEventListener('click', () => {
                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                }
                selectedRow = row;
                row.classList.add('row-selected');
            });
        });

        // -------- Búsqueda + filtros --------
        const searchInput = document.getElementById('searchUser');
        const filterChips = document.querySelectorAll('.chip-filter');
        let currentArea = 'ALL';

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

        window.triggerSearch = function () {
            applyFilter();
        };

        // Filtros por área: TI, MKT, SAP, Sucursales, etc.
        filterChips.forEach(chip => {
            chip.addEventListener('click', () => {
                filterChips.forEach(c => c.classList.remove('chip-active'));
                chip.classList.add('chip-active');

                currentArea = chip.dataset.area || 'ALL';

                // Al cambiar filtro, se deselecciona la fila
                if (selectedRow) {
                    selectedRow.classList.remove('row-selected');
                    selectedRow = null;
                }

                applyFilter();
            });
        });

        /* ============================================
           Acciones CRUD desde los botones laterales
           (Agregar, Eliminar, Actualizar)
        ============================================ */

        // --- Eliminar usuario seleccionado ---
        window.handleDeleteUser = function () {
            if (!selectedRow) {
                alert('Primero selecciona un usuario en la tabla.');
                return;
            }

            const fullName =
                (selectedRow.dataset.name || '') +
                ' ' +
                (selectedRow.dataset.last || '');

            const confirmDelete = confirm(
                '¿Eliminar al usuario: ' + fullName + '?'
            );

            if (!confirmDelete) {
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

        // --- Abrir modal de edición y rellenar campos ---
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
       Aquí después puedes agregar más secciones JS
       (por ejemplo para el dashboard SA, tickets, etc.)
    ============================================ */

});
