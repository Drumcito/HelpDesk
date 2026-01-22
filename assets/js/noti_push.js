/* ============================================
   NOTIFICACIONES GLOBALES · HELPDESK EQF
   Archivo: assets/js/noti_push.js
   - Poll unificado (tickets + tareas si vienen en notifications)
   - Badge + mark_read
   - Notificación nativa con icono + click abre link
============================================ */

(function () {
  if (typeof window.HELPDESK_USER_ID === 'undefined' || !window.HELPDESK_USER_ID) return;

  // 🔁 Reusar misma llave que tu script.js para NO perder estado
  let lastNotifId = parseInt(localStorage.getItem('lastNotifId') || '0', 10) || 0;

  function setBadge(n) {
    const el = document.getElementById('notifBadge');
    if (!el) return;
    const x = parseInt(n || 0, 10) || 0;
    el.textContent = x > 99 ? '99+' : String(x);
    el.style.display = x > 0 ? 'inline-flex' : 'none';
  }

  async function ensurePermission() {
    if (!('Notification' in window)) return false;
    if (Notification.permission === 'granted') return true;
    if (Notification.permission === 'denied') return false;

    // pedir permiso al cargar
    try {
      const p = await Notification.requestPermission();
      return p === 'granted';
    } catch (e) {
      return false;
    }
  }

  async function showDesktop(title, body, link, tag) {
    const ok = await ensurePermission();
    if (!ok) return;

    // Intentar SW (mejor comportamiento). Si no hay, fallback a Notification normal.
    if ('serviceWorker' in navigator) {
      try {
        const reg = await navigator.serviceWorker.getRegistration('/HelpDesk_EQF/');
        if (reg && reg.showNotification) {
          await reg.showNotification(title || 'HelpDesk EQF', {
            body: body || '',
            icon: '/HelpDesk_EQF/assets/img/icon_desktop.png',
            badge: '/HelpDesk_EQF/assets/img/icon_desktop.png',
            tag: tag || undefined,
            data: { url: link || '/HelpDesk_EQF/' },
            renotify: false
          });
          return;
        }
      } catch (e) {}
    }

    const n = new Notification(title || 'HelpDesk EQF', {
      body: body || '',
      icon: '/HelpDesk_EQF/assets/img/icon_desktop.png',
      tag: tag || undefined
    });

    if (link) {
      n.onclick = () => {
        try { window.open(link, '_blank'); } catch (e) {}
      };
    }
  }

  async function markRead(ids) {
    if (!ids.length) return;
    try {
      await fetch('/HelpDesk_EQF/modules/notifications/mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
      });
    } catch (e) {}
  }

  let running = false;

  async function poll() {
    if (running) return;
    running = true;

    try {
      const r = await fetch('/HelpDesk_EQF/modules/notifications/poll.php?since_id=' + encodeURIComponent(lastNotifId), {
        cache: 'no-store'
      });

      const ct = (r.headers.get('content-type') || '').toLowerCase();
      if (!ct.includes('application/json')) return;

      const data = await r.json();
      if (!data || !data.ok) return;

      setBadge(data.unread);

      const notifs = Array.isArray(data.notifications) ? data.notifications : [];
      if (!notifs.length) return;

      const ids = [];

      for (const n of notifs) {
        const id = parseInt(n.id, 10);
        if (!isNaN(id)) {
          lastNotifId = Math.max(lastNotifId, id);
          ids.push(id);
        }

        // Notificación nativa (unificada)
        await showDesktop(
          n.title || 'HelpDesk EQF',
          n.body || '',
          n.link || '/HelpDesk_EQF/',
          'helpdesk-' + (id || Date.now())
        );
      }

      localStorage.setItem('lastNotifId', String(lastNotifId));
      await markRead(ids);

    } catch (e) {
      // console.warn('noti_push poll error', e);
    } finally {
      running = false;
    }
  }

  poll();
  setInterval(poll, 7000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

})();
