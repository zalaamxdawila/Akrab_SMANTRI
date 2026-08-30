const CACHE_NAME = 'akrab-static-20260831-v3-safe-install';
const APP_CACHE_PREFIX = 'akrab-static-';
const PRECACHE_ASSETS = [
  '/offline.html',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png'
];
const SAFE_STATIC_PREFIXES = [
  '/assets/css/',
  '/assets/js/',
  '/assets/vendor/',
  '/assets/img/',
  '/assets/icons/'
];
const SAFE_DESTINATIONS = new Set(['style', 'script', 'image', 'font']);

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys
          .filter(key => key.startsWith(APP_CACHE_PREFIX) && key !== CACHE_NAME)
          .map(key => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

function isSafeStaticRequest(request, url) {
  return SAFE_DESTINATIONS.has(request.destination)
    && SAFE_STATIC_PREFIXES.some(prefix => url.pathname.startsWith(prefix));
}

function isCacheableStaticResponse(response) {
  const cacheControl = response.headers.get('Cache-Control') || '';
  return response.ok
    && response.type === 'basic'
    && !cacheControl.toLowerCase().includes('no-store');
}

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || !isSafeStaticRequest(request, url)) return;

  const update = fetch(request).then(async response => {
    if (isCacheableStaticResponse(response)) {
      const cache = await caches.open(CACHE_NAME);
      await cache.put(request, response.clone());
    }
    return response;
  });

  event.waitUntil(update.then(() => undefined).catch(() => undefined));
  event.respondWith(
    caches.match(request).then(cached => cached || update)
  );
});
