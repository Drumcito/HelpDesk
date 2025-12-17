/* ============================================
   SCRIPTS GLOBALES · MESA DE AYUDA EQF
   Archivo: assets/js/script.js
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
           Acciones CRUD 
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


/* ========== DASHBOARD USUARIO / CAPSULia ========== */

document.addEventListener('DOMContentLoaded', () => {
    const problemSelect   = document.getElementById('problema');
    const detalleTextarea = document.getElementById('detalle');
    const qaButtons       = document.querySelectorAll('.user-capsulia-qa-btn');
    const chatLog         = document.getElementById('capsuliaChatLog');
    const input           = document.getElementById('capsuliaInput');
    const sendBtn         = document.getElementById('capsuliaSend');
    const closeBtn        = document.querySelector('.user-capsulia-close');
    const capsuliaPanel   = document.querySelector('.user-capsulia');

    function addChatMessage(text, from = 'user') {
        if (!chatLog) return;
        const row = document.createElement('div');
        row.className = 'user-capsulia-chat-msg ' + from;
        const span = document.createElement('span');
        span.textContent = text;
        row.appendChild(span);
        chatLog.appendChild(row);
        chatLog.scrollTop = chatLog.scrollHeight;
    }

    qaButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.problem || btn.textContent.trim();
            if (problemSelect) {
                problemSelect.value = val;
            }
            addChatMessage(val, 'user');
        });
    });

    if (sendBtn && input) {
        const send = () => {
            const text = input.value.trim();
            if (!text) return;
            addChatMessage(text, 'user');
            input.value = '';

            // Respuesta dummy
            setTimeout(() => {
                addChatMessage(
                    'He recibido tu mensaje. Si lo deseas, describe más a detalle y crea el ticket con el botón "Enviar ticket".',
                    'bot'
                );
            }, 400);
        };

        sendBtn.addEventListener('click', send);
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                send();
            }
        });
    }

    if (closeBtn && capsuliaPanel) {
        closeBtn.addEventListener('click', () => {
            capsuliaPanel.classList.toggle('is-closed');
        });
    }
});


/* ============================================
            MODAL CREAR TICKET (USUARIO)
============================================ */
document.addEventListener('DOMContentLoaded', function () {
    const noJefeCheckbox    = document.getElementById('noJefe');
    const sapDisplay        = document.getElementById('sapDisplay');
    const nombreDisplay     = document.getElementById('nombreDisplay');
    const emailDisplay      = document.getElementById('emailDisplay');
    const sapValueHidden    = document.getElementById('sapValue');
    const nombreValueHidden = document.getElementById('nombreValue');
    const problemaSelect    = document.getElementById('problemaSelect');
    const adjuntoContainer  = document.getElementById('adjuntoContainer');
    const ticketForm        = document.getElementById('ticketForm');
    const areaSoporte       = document.getElementById('areaSoporte');

    const prioridadDisplay  = document.getElementById('prioridadDisplay');
    const prioridadHidden   = document.getElementById('prioridadValue');

    // Si no está el formulario de ticket, no hacemos nada (este JS se usa en otras vistas)
    if (!sapDisplay || !nombreDisplay || !emailDisplay || !sapValueHidden || !nombreValueHidden || !ticketForm) {
        return;
    }
    if (adjuntoContainer) adjuntoContainer.style.display = 'block';

    const originalSap    = sapDisplay.value;
    const originalNombre = nombreDisplay.value;
    const originalEmail  = emailDisplay.value;

    sapDisplay.disabled = true;
    nombreDisplay.disabled = true;
    sapDisplay.style.backgroundColor = '#e5e5e5';
    nombreDisplay.style.backgroundColor = '#e5e5e5';
    emailDisplay.style.backgroundColor = '#e5e5e5';

    // -----------------------------
    // Checkbox "No soy jefe de sucursal"
    // -----------------------------
    if (noJefeCheckbox) {
        noJefeCheckbox.addEventListener('change', function () {
            if (this.checked) {
                sapDisplay.disabled = false;
                nombreDisplay.disabled = false;

                sapDisplay.value = '';
                nombreDisplay.value = '';

                sapDisplay.style.backgroundColor = '#ffffff';
                nombreDisplay.style.backgroundColor = '#ffffff';
            } else {
                sapDisplay.disabled = true;
                nombreDisplay.disabled = true;

                sapDisplay.value = originalSap;
                nombreDisplay.value = originalNombre;

                sapDisplay.style.backgroundColor = '#e5e5e5';
                nombreDisplay.style.backgroundColor = '#e5e5e5';

                sapValueHidden.value    = originalSap;
                nombreValueHidden.value = originalNombre;
            }
        });
    }

 // ---------- CAMBIO DE ESTATUS EN MIS TICKETS ----------
document.addEventListener('change', function (e) {
    const select = e.target.closest('.ticket-status-select');
    if (!select) return;

    const ticketId  = select.dataset.ticketId;
    const newStatus = select.value;
    if (!ticketId || !newStatus) return;


  const classes = select.className.split(' ').filter(c => !c.startsWith('status-'));
    classes.push('status-' + newStatus);           // agregamos la nueva
    select.className = classes.join(' ');

    
    fetch('/HelpDesk_EQF/modules/ticket/update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ticket_id=' + encodeURIComponent(ticketId) +
              '&estado='   + encodeURIComponent(newStatus)
    })
    .then(async (r) => {
        const raw = await r.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Respuesta no es JSON válido en update_status:', raw);
            alert('Error al actualizar el estatus.');
            return;
        }

        if (!data.ok) {
            alert(data.msg || 'No se pudo actualizar el estatus.');
            return;
        }

        // Actualizar valor y clases de color
        select.value = data.estado;

        // quitar clases anteriores status-*
        select.className = select.className
            .split(' ')
            .filter(c => !c.startsWith('status-'))
            .join(' ');
        // agregar la nueva
        select.classList.add('status-' + data.estado);

        showTicketToast(
            'Estatus del ticket #' + ticketId +
            ' actualizado a "' + data.estado_label + '".'
        );
    })
    .catch(err => {
        console.error('Error actualizando estatus:', err);
        alert('Error al actualizar el estatus.');
    });
});
function addIncomingTicketRow(ticket) {
    if (!ticket || !ticket.id) return;

    const prioridadRaw   = (ticket.prioridad || 'media').toLowerCase();
    const prioridadLabel = prioridadRaw === 'alta'   ? 'Alta'
                         : prioridadRaw === 'baja'   ? 'Baja'
                         : prioridadRaw === 'critica' || prioridadRaw === 'crítica' ? 'Crítica'
                         : 'Media';

    const prioridadHtml = `
        <span class="priority-pill priority-${prioridadRaw}">
            ${prioridadLabel}
        </span>
    `;

    const rowData = [
        ticket.id,
        ticket.fecha || '',
        ticket.usuario || '',
        ticket.problema || '',
        prioridadHtml,
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
            <td>${rowData[6]}</td>
        `;
        tbody.prepend(tr);
    }
}

    // -----------------------------
    // Área de soporte → lista de problemas
    // -----------------------------
if (areaSoporte && problemaSelect) {

        async function fetchProblemas(areaRaw) {
            const area = (areaRaw || '').toUpperCase().trim();
            if (!area) return [];

            const res = await fetch(
                `/HelpDesk_EQF/modules/dashboard/user/get_problems.php?area=${encodeURIComponent(area)}`
            );
            const data = await res.json();
            if (!data.ok) return [];
            return Array.isArray(data.items) ? data.items : [];
        }

        function resetProblemas() {
            problemaSelect.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Selecciona primero un área de soporte';
            problemaSelect.appendChild(opt);
            problemaSelect.value = '';

            if (prioridadDisplay && prioridadHidden) {
                prioridadDisplay.value = 'Media';
                prioridadHidden.value  = 'media';
            }
        }

        async function fillProblemas(areaRaw) {
            const area = (areaRaw || '').toUpperCase().trim();

            problemaSelect.innerHTML = '';
            problemaSelect.disabled = true;

            if (!area) {
                resetProblemas();
                problemaSelect.disabled = false;
                return;
            }

            // placeholder loading
            let loading = document.createElement('option');
            loading.value = '';
            loading.textContent = 'Cargando...';
            problemaSelect.appendChild(loading);

            const items = await fetchProblemas(area);

            problemaSelect.innerHTML = '';

            if (!items.length) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'No hay problemas para esta área';
                problemaSelect.appendChild(opt);
                problemaSelect.value = '';
                problemaSelect.disabled = false;

                if (prioridadDisplay && prioridadHidden) {
                    prioridadDisplay.value = 'Media';
                    prioridadHidden.value  = 'media';
                }
                return;
            }

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Selecciona un problema';
            problemaSelect.appendChild(placeholder);

            items.forEach(p => {
                const opt = document.createElement('option');
                // OJO: mandamos value como id o "otro"
                opt.value = String(p.id);
                opt.textContent = p.label;
                problemaSelect.appendChild(opt);
            });

            problemaSelect.value = '';
            problemaSelect.disabled = false;

            if (prioridadDisplay && prioridadHidden) {
                prioridadDisplay.value = 'Media';
                prioridadHidden.value  = 'media';
            }
        }

        areaSoporte.addEventListener('change', function () {
            fillProblemas(this.value);
        });

        // Problema cambia prioridad y adjuntos (TU MISMA LÓGICA, intacta)
        problemaSelect.addEventListener('change', function () {
            const value = this.value;

            if (!prioridadDisplay || !prioridadHidden) return;

            if (value === 'otro' || value === '') {
                prioridadDisplay.value = 'Media';
                prioridadHidden.value  = 'media';
            } else {
                prioridadDisplay.value = 'Alta';
                prioridadHidden.value  = 'alta';
            }
        });

        // si el área ya viene seleccionada al abrir
        if (areaSoporte.value) {
            fillProblemas(areaSoporte.value);
        } else {
            resetProblemas();
        }
    }
        });

    // -----------------------------
    // Antes de enviar, sincronizar los hidden con lo que esté en pantalla
    // -----------------------------
    ticketForm.addEventListener('submit', function () {
        const sapFinal    = sapDisplay.value.trim();
        const nombreFinal = nombreDisplay.value.trim();

        sapValueHidden.value    = sapFinal;
        nombreValueHidden.value = nombreFinal;

        // Por si acaso, aseguramos prioridadHidden
        if (prioridadDisplay && prioridadHidden && !prioridadHidden.value) {
            const txt = (prioridadDisplay.value || '').toLowerCase();
            if (txt.includes('alta'))      prioridadHidden.value = 'alta';
            else if (txt.includes('baja')) prioridadHidden.value = 'baja';
            else                           prioridadHidden.value = 'media';
        }
    });


/*------------------
Colapsar Sidebar
-------------------*/
function toggleSidebar() {
  document.body.classList.toggle('sidebar-collapsed');
}

window.toggleSidebar = toggleSidebar;
