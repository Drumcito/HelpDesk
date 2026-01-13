/* =========================================================
   /HelpDesk_EQF/assets/js/sidebar.js
   SIDEBAR EQF (user-sidebar)
   - Desktop: colapsa y persiste (localStorage eqf_sidebar_collapsed)
   - Mobile (<900px): usa body.show-sidebar (drawer)
   - Si hay modal abierto: bloquea interacción con sidebar y lo manda atrás (html.sidebar-locked)
========================================================= */

(function () {
  const STORAGE_KEY = 'eqf_sidebar_collapsed';

  function isMobile() {
    return window.matchMedia('(max-width: 900px)').matches;
  }

  function safeGet(key) {
    try { return localStorage.getItem(key); } catch (e) { return null; }
  }
  function safeSet(key, val) {
    try { localStorage.setItem(key, val); } catch (e) {}
  }

  // --- MODALS: detección universal ---
  function isVisible(el) {
    if (!el) return false;
    const st = getComputedStyle(el);
    if (st.display === 'none' || st.visibility === 'hidden' || st.opacity === '0') return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  }

  function hasOpenModal() {
    const nodes = document.querySelectorAll(`
      dialog[open],
      [aria-modal="true"],
      [role="dialog"],
      .modal-backdrop,
      .user-modal-backdrop,
      .eqf-modal-backdrop,
      .task-modal-backdrop,
      [class*="modal"],
      [id*="modal"],
      [class*="backdrop"],
      [id*="backdrop"]
    `);

    for (const el of nodes) {
      const cls = (el.className || '').toString().toLowerCase();
      const id  = (el.id || '').toLowerCase();

      const looksLikeModal =
        el.matches('dialog[open]') ||
        el.getAttribute('aria-modal') === 'true' ||
        el.getAttribute('role') === 'dialog' ||
        cls.includes('modal') ||
        cls.includes('backdrop') ||
        id.includes('modal') ||
        id.includes('backdrop');

      if (looksLikeModal && isVisible(el)) return true;
    }
    return false;
  }

  function syncModalLock() {
    const open = hasOpenModal();
    document.documentElement.classList.toggle('sidebar-locked', open);

    // Si hay modal y estás en móvil con sidebar abierto, ciérralo
    if (open) document.body.classList.remove('show-sidebar');
  }

  // --- Persistencia: aplicar estado guardado ---
  function applySavedCollapsedState() {
    const saved = safeGet(STORAGE_KEY);
    document.body.classList.toggle('sidebar-collapsed', saved === '1');
  }

  // --- API global para tu onclick="toggleSidebar()" ---
  function toggleSidebar() {
    // Si hay modal abierto, bloquea interacción
    if (document.documentElement.classList.contains('sidebar-locked')) return;

    if (isMobile()) {
      document.body.classList.toggle('show-sidebar');
      return;
    }

    const collapsed = document.body.classList.toggle('sidebar-collapsed');
    safeSet(STORAGE_KEY, collapsed ? '1' : '0');
  }

  // Exponer global (porque en tu HTML lo llamas con onclick)
  window.toggleSidebar = toggleSidebar;

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    applySavedCollapsedState();

    // En móvil arranca cerrado
    if (isMobile()) document.body.classList.remove('show-sidebar');

    syncModalLock();
  });

  // Watchers para cambios de modales
  (function watchModals() {
    document.addEventListener('click', () => setTimeout(syncModalLock, 0), true);
    document.addEventListener('keydown', () => setTimeout(syncModalLock, 0), true);

    const obs = new MutationObserver(() => syncModalLock());
    obs.observe(document.documentElement, {
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style', 'open', 'aria-hidden', 'aria-modal']
    });
  })();

  // Evitar estados raros al cambiar tamaño
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      document.body.classList.remove('show-sidebar'); // salir de móvil => cerrar drawer
    }
  });
})();
