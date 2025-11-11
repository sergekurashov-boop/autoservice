const CACHE_NAME = 'crm-cache-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/tasks.php',
  '/css/bootstrap.min.css', // если есть локально
  // добавьте сюда другие важные файлы
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});