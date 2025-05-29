// service-worker.js - Fenyal Food Ordering PWA

const CACHE_NAME = 'fenyal-cache-v1';
const URLS_TO_CACHE = [
  '/',
  '/index.html',
  '/login.html',
  '/otp.html',
  '/menu.html',
  '/menu-item-details.html',
  '/cart.html',
  '/takeaway.html',
  '/dine-in.html',
  '/home-delivery.html',
  '/contact-us.html',
  '/checkout/checkout.html',
  '/checkout/checkout-whatsapp.html',
  '/checkout/checkout-payment.html',
  '/checkout/checkout-cod.html',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/js/menu.js',
  '/data/menu.json',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js',
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'
];

// Install event - cache assets
self.addEventListener('install', event => {
  console.log('[Service Worker] Installing Service Worker...', event);
  
  // Perform install steps
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Caching app shell');
        return cache.addAll(URLS_TO_CACHE);
      })
      .then(() => {
        console.log('[Service Worker] Successfully cached app shell');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activating Service Worker...', event);
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[Service Worker] Claiming clients');
      return self.clients.claim();
    })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin) && 
      !event.request.url.includes('cdn.') && 
      !event.request.url.includes('fonts.')) {
    return;
  }
  
  // Handle API requests - network first
  if (event.request.url.includes('/api/') || event.request.url.includes('menu.json')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Clone the response for cache and return
          let responseToCache = response.clone();
          
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });
            
          return response;
        })
        .catch(() => {
          // If fetch fails, try to get from cache
          return caches.match(event.request);
        })
    );
    return;
  }
  
  // Handle static assets - cache first, network fallback
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        
        // Clone the request to ensure it's safe to read when passed to fetch
        const fetchRequest = event.request.clone();
        
        return fetch(fetchRequest)
          .then(response => {
            // Check if valid response
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Clone the response for cache and return
            const responseToCache = response.clone();
            
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
              
            return response;
          })
          .catch(error => {
            console.log('[Service Worker] Fetch failed; returning offline page instead.', error);
            
            // For HTML requests, return the index page when offline
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/index.html');
            }
          });
      })
  );
});

// Background sync for offline form submissions
self.addEventListener('sync', event => {
  if (event.tag === 'order-sync') {
    event.waitUntil(syncOrders());
  }
});

function syncOrders() {
  // Get stored orders from IndexedDB or localStorage
  return self.clients.matchAll().then(clients => {
    return clients.map(client => {
      // Send message to client
      return client.postMessage({
        msg: 'Syncing your orders in the background'
      });
    });
  });
}

// Push notifications
self.addEventListener('push', event => {
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/assets/icons/icon-512x512.png',
    badge: '/assets/icons/badge-128x128.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification click event
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});