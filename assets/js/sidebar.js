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

  function isVisible(el) {
    if (!el) return false;
    const st = getComputedStyle(el);
    if (st.display === 'none' || st.visibility === 'hidden' || st.opacity === '0') return false;
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  }

  // ✅ Selectores *específicos* del proyecto (evita escanear medio DOM)
  // Nota: .show es la convención usada por openModal()/closeModal().
  const MODAL_SELECTOR = [
    'dialog[open]',
    '[aria-modal="true"]',
    '.user-modal-backdrop.show',
    '.eqf-modal-backdrop.show',
    '.task-modal-backdrop.show',
    '.modal-backdrop.show',
    // fallback por si alguna vista usa id="*Modal" sin clase show (poco común)
    '[id$="Modal"].show',
    '[id$="modal"].show'
  ].join(',');

  function hasOpenModal() {
    const nodes = document.querySelectorAll(MODAL_SELECTOR);
    for (const el of nodes) {
      if (isVisible(el)) return true;
    }
    return false;
  }

  function syncModalLock() {
    const open = hasOpenModal();
    document.documentElement.classList.toggle('sidebar-locked', open);

    // Si hay modal y estás en móvil con sidebar abierto, ciérralo
    if (open) document.body.classList.remove('show-sidebar');
  }

  // Throttle: máx 1 ejecución por frame
  let rafPending = false;
  function scheduleSync() {
    if (rafPending) return;
    rafPending = true;
    requestAnimationFrame(() => {
      rafPending = false;
      syncModalLock();
    });
  }

  function applySavedCollapsedState() {
    const saved = safeGet(STORAGE_KEY);
    document.body.classList.toggle('sidebar-collapsed', saved === '1');
  }

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

  document.addEventListener('DOMContentLoaded', () => {
    applySavedCollapsedState();

    // En móvil arranca cerrado
    if (isMobile()) document.body.classList.remove('show-sidebar');

    scheduleSync();
  });

  // Watchers para cambios de modales (barato + throttled)
  (function watchModals() {
    document.addEventListener('click', scheduleSync, true);
    document.addEventListener('keydown', scheduleSync, true);

    const obs = new MutationObserver(scheduleSync);
    obs.observe(document.body || document.documentElement, {
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style', 'open', 'aria-hidden', 'aria-modal']
    });
  })();

  window.addEventListener('resize', () => {
    if (!isMobile()) {
      document.body.classList.remove('show-sidebar');
    }
    scheduleSync();
  });
})();
