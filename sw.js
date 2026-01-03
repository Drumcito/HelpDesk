self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data.json(); } catch (e) {}

  const title = data.title || 'HelpDesk EQF';
  const body  = data.body  || 'Tienes un aviso nuevo.';
  const url   = data.url   || '/HelpDesk_EQF/modules/dashboard/user/user.php';
  const level = data.level || 'INFO';

  const options = {
    body,
    data: { url },
    badge: '/HelpDesk_EQF/assets/img/icon_helpdesk.png',
    icon: '/HelpDesk_EQF/assets/img/icon_helpdesk.png',
    tag: 'helpdesk-announcement-' + (data.id || ''),
    renotify: true,
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/HelpDesk_EQF/';
  event.waitUntil(clients.openWindow(url));
});
