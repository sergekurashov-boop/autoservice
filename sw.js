// autoservice/sw.js
const CACHE_NAME = 'autoservice-v1';
const urlsToCache = [
  '/autoservice/inspection_mobile.php',
  '/autoservice/assets/css/services.css',
  '/autoservice/manifest.json'
];

// Установка
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Перехват запросов
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});