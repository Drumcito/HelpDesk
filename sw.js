

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});


self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {}

  const title = data.title || 'HelpDesk EQF';
  const body  = data.body  || 'Tienes una notificación nueva.';
  const url   = data.url   || '/HelpDesk_EQF/';
  const tag   = data.tag   || ('helpdesk-push-' + (data.id || Date.now()));

  const options = {
    body,
    data: { url },
    tag,
    renotify: false,
    badge: '/HelpDesk_EQF/assets/img/icon_desktop.png',
    icon:  '/HelpDesk_EQF/assets/img/icon_desktop.png'
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});


self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url =
    (event.notification.data && event.notification.data.url)
      ? event.notification.data.url
      : '/HelpDesk_EQF/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Si ya hay una ventana abierta con esa URL, enfócarla
        for (const client of clientList) {
          if (client.url === url && 'focus' in client) {
            return client.focus();
          }
        }
        // Si no, abrir nueva
        if (self.clients.openWindow) {
          return self.clients.openWindow(url);
        }
      })
  );
});
