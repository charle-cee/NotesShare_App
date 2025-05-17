const CACHE_NAME = 'notes-share-cache-v2';
const FILES_TO_CACHE = [
  '/',
  '/index.php',
  '/offline.php',   // Ensure this matches your offline page filename
  '/manifest.php',
  '/manifest.webmanifest',
  '/logo.png',
  '/style.css',
  '/script.js'
];

// INSTALL: Caches the essential files when service worker is installed
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Caching core files...');
      return cache.addAll(FILES_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// ACTIVATE: Cleans up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// FETCH: Responds with cache or network request, falling back to offline page
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      // Fetch from network
      return fetch(event.request)
        .then((networkResponse) => {
          return caches.open(CACHE_NAME).then((cache) => {
            // Only cache GET requests
            if (event.request.method === 'GET' && event.request.url.startsWith('http')) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          });
        })
        .catch(() => {
          // Fallback for navigation requests (e.g., HTML pages)
          if (event.request.mode === 'navigate') {
            return caches.match('/offline.php');  // Ensure this is the correct fallback page
          }
        });
    })
  );
});
