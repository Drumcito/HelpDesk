(function () {
const SNAPSHOT_URL = '/HelpDesk_EQF/modules/dashboard/common/ajax/announcements_snapshot.php?mode=all';
  const POLL_MS = 8000;

  const listEl = document.getElementById('annList');
  const badgeEl = document.getElementById('annBadge');

  if (!listEl) return;

  try {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission().catch(() => {});
    }
  } catch (_) {}

  const storageKey = 'lastAnnIdSeen';
  let lastSeen = parseInt(localStorage.getItem(storageKey) || '0', 10) || 0;

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
  }
function fmtDT(v) {
  if (!v) return '';
  // "2026-01-22 08:00:00" -> "2026-01-22T08:00:00"
  const iso = String(v).trim().replace(' ', 'T');
  const d = new Date(iso);
  if (isNaN(d.getTime())) return String(v);

  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yy = d.getFullYear();
  const hh = String(d.getHours()).padStart(2,'0');
  const mi = String(d.getMinutes()).padStart(2,'0');
  return `${dd}/${mm}/${yy} ${hh}:${mi}`;
}


function renderSchedule(a) {
  const s = a.starts_at ? fmtDT(a.starts_at) : '';
  const e = a.ends_at ? fmtDT(a.ends_at) : '';
  if (!s && !e) return '';
  return `
    <div class="announcement__when" style="margin-top:6px;opacity:.9;font-weight:400;">
      ${s ? `Hora de inicio: ${esc(s)}` : ''}
      ${s && e ? `<br>` : ''}
      ${e ? `Hora de fin: ${esc(e)}` : ''}
    </div>
  `;
}

  function canDeactivate() {
    return !!window.EQF_CAN_DEACTIVATE_ANN;
  }

  // Mantener el mismo mapeo que en PHP (annClass / annLabel)
  function normLevel(level) {
    const x = String(level ?? '').trim().toUpperCase();
    if (x === 'CRITICAL' || x === 'CRITICO' || x === 'CRÍTICO') return 'CRITICAL';
    if (x === 'WARN' || x === 'WARNING' || x === 'AVISO') return 'WARN';
    return 'INFO';
  }

  function levelClass(level) {
    const x = normLevel(level);
    if (x === 'CRITICAL') return 'announcement--critical';
    if (x === 'WARN') return 'announcement--warn';
    return 'announcement--info';
  }

  function levelLabel(level) {
    const x = normLevel(level);
    if (x === 'CRITICAL') return 'Crítico';
    if (x === 'WARN') return 'Aviso';
    return 'Info';
  }

  function render(items) {
    if (badgeEl) badgeEl.textContent = String(items.length);

    let html = '';
    items.forEach(a => {
      const lvl = normLevel(a.level);
      const cls = levelClass(lvl);
      const pill = levelLabel(lvl);

      html += `
        <div class="announcement ${cls}" data-id="${esc(a.id)}">
          <div class="announcement__top">
            <div>
              <p class="announcement__h">${esc(a.title)}</p>
            ${renderSchedule(a)}

              </div>

            <div style="display:flex;gap:10px;align-items:center;">
              <span class="announcement__pill">${pill}</span>

              ${canDeactivate() ? `
                <button type="button" class="ann-deactivate-btn" data-id="${esc(a.id)}"
                style="background:none; border:none; padding:0; margin:0; color:#C8002D; font-weight:800; cursor:pointer; ">
                  Desactivar
                </button>` : ''
              }
            </div>
          </div>

<div class="announcement__body" style="font-weight:800;">${esc(a.body)}</div>
        </div>
      `;
    });

    listEl.innerHTML = html || `<p style="opacity:.8;">No hay avisos activos.</p>`;
  }

async function deactivate(id) {
  const url = '/HelpDesk_EQF/modules/dashboard/common/ajax/announcements_toggle.php';

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: Number(id) })
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok || !data.ok) {
    alert(data.msg || 'No se pudo desactivar');
    return false;
  }
  return true;
}


  async function poll() {
    try {
      const res = await fetch(SNAPSHOT_URL, { cache: 'no-store' });
      const data = await res.json();
      if (!data.ok) return;

      const items = Array.isArray(data.items) ? data.items : [];
      render(items);

      // Notificación si llega uno nuevo
      let maxId = 0;
      for (const it of items) {
        const id = parseInt(it.id, 10) || 0;
        if (id > maxId) maxId = id;
      }

      if (maxId > lastSeen) {
        // Evitar notificar al cargar por primera vez
        if (lastSeen > 0) {
          const newest = items.find(x => (parseInt(x.id, 10) || 0) === maxId) || items[0];
          if ('Notification' in window && Notification.permission === 'granted' && newest) {
            new Notification('Nuevo aviso', { body: newest.title || 'Tienes un aviso nuevo' });
          }
        }
        lastSeen = maxId;
        localStorage.setItem(storageKey, String(lastSeen));
      }
    } catch (e) {
      // silenciar
    }
  }

  // Clicks
  document.addEventListener('click', async (ev) => {
const deBtn = ev.target.closest('.ann-deactivate-btn');
    if (deBtn) {
      const id = deBtn.getAttribute('data-id');
      if (!id) return;
      if (!confirm('¿Desactivar este aviso?')) return;
      await deactivate(id);
      await poll();
    }
  });

  poll();
  setInterval(poll, POLL_MS);
})();
