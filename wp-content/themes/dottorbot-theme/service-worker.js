const CACHE_NAME = 'dottorbot-cache-v1';
const ASSETS = [
  '/wp-content/themes/dottorbot-theme/dist/style.css',
  '/wp-content/themes/dottorbot-theme/dist/chat.js',
  '/wp-content/themes/dottorbot-theme/dist/diary.js',
  '/wp-content/themes/dottorbot-theme/dist/pwa.js',
  '/diario',
  '/wp-json/dottorbot/v1/diary',
  '/'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const { pathname } = new URL(event.request.url);

  if (pathname === '/wp-json/dottorbot/v1/diary') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  event.respondWith(
    caches
      .match(event.request)
      .then(
        resp =>
          resp ||
          fetch(event.request)
            .then(response => {
              const clone = response.clone();
              caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
              return response;
            })
            .catch(() => caches.match('/'))
      )
  );
});

self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'DottorBot';
  const options = {
    body: data.body || 'Ricorda di compilare il tuo diario quotidiano.',
    icon: '/wp-content/themes/dottorbot-theme/icon-192.png',
    badge: '/wp-content/themes/dottorbot-theme/icon-192.png',
    data: data.url || '/'
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = event.notification.data || '/';
  event.waitUntil(clients.openWindow(url));
});
