const CACHE_NAME = 'fermento-admin-v1';
const urlsToCache = [
  'pedidos.php',
  'api_detalle_stats.php',
  'assets/css/admin-new.css',
  'assets/js/hoja_produccion.js',
  '../assets/icons/icon-192.png'
];

// Instalar SW y cachear recursos básicos
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Activar y limpiar cachés viejas
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Estrategia Stale-while-revalidate (Carga rápido de caché, actualiza en segundo plano)
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        const fetchPromise = fetch(event.request).then(networkResponse => {
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, networkResponse.clone());
          });
          return networkResponse;
        });
        return response || fetchPromise;
      })
  );
});

// --- NOTIFICACIONES --- (Preparamos el manejo para cuando implementemos el backend de notificaciones)
self.addEventListener('push', event => {
  let data = { title: 'Nuevo Pedido 🍞', body: 'Tienes un nuevo pedido esperando en Fermento.' };
  if (event.data) {
    try { data = event.data.json(); } catch (e) { data.body = event.data.text(); }
  }

  const options = {
    body: data.body,
    icon: '../assets/icons/icon-192.png',
    badge: '../assets/icons/icon-192.png',
    vibrate: [200, 100, 200],
    data: { url: 'pedidos.php' }
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});
