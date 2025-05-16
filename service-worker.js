<<<<<<< HEAD
// service-worker.js - Enhanced PWA Capabilities for Fenyal Food Ordering

const CACHE_NAME = 'fenyal-cache-v2';
const ASSETS_CACHE = 'fenyal-assets-v2';
const DYNAMIC_CACHE = 'fenyal-dynamic-v2';

// Assets to cache immediately on service worker installation
const STATIC_ASSETS = [
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
  '/checkout/checkout.html',
  '/checkout/checkout-whatsapp.html',
  '/checkout/checkout-payment.html',
  '/checkout/checkout-cod.html',
  '/contact-us.html',
  '/about-us.html',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/js/menu.js',
  '/assets/js/global-search.js',
  '/data/menu.json',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js',
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'
];

// Additional paths to cache but not block installation if they fail
const OPTIONAL_ASSETS = [
  '/assets/icons/icon-72x72.png',
  '/assets/icons/icon-96x96.png',
  '/assets/icons/icon-128x128.png',
  '/assets/icons/icon-144x144.png',
  '/assets/icons/icon-152x152.png',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-384x384.png',
  '/assets/icons/icon-512x512.png',
  '/assets/icons/favicon.ico',
  '/assets/icons/apple-icon-180x180.png',
  '/assets/images/veggie-burger.jpg',
  '/assets/images/butter-chicken.jpg',
  '/manifest.json'
];

// Function to clean up old caches
const cleanupCaches = async () => {
  const cacheKeys = await caches.keys();
  const deletionPromises = cacheKeys
    .filter(key => key !== CACHE_NAME && key !== ASSETS_CACHE && key !== DYNAMIC_CACHE)
    .map(key => caches.delete(key));
  
  return Promise.all(deletionPromises);
};

// Install event handler - cache core assets
self.addEventListener('install', event => {
  console.log('[Service Worker] Installing...');
  
  // Cache critical assets that should block installation if they fail
  const cacheStaticAssets = async () => {
    const cache = await caches.open(CACHE_NAME);
    console.log('[Service Worker] Caching core app shell...');
    return cache.addAll(STATIC_ASSETS);
  };
  
  // Cache optional assets that shouldn't block installation
  const cacheOptionalAssets = async () => {
    const cache = await caches.open(ASSETS_CACHE);
    console.log('[Service Worker] Caching optional assets...');
    
    // Map each URL to a Promise that resolves even if the cache operation fails
    const optionalCaching = OPTIONAL_ASSETS.map(url => 
      cache.add(url).catch(error => {
        console.log(`[Service Worker] Optional caching failed for: ${url}`, error);
        return null; // Resolve anyway to not block installation
      })
    );
    
    return Promise.all(optionalCaching);
  };
  
  // Wait for the critical assets to be cached
  event.waitUntil(
    cacheStaticAssets()
      .then(() => self.skipWaiting()) // Take control immediately
      .then(() => cacheOptionalAssets()) // Then try to cache optional assets
  );
});

// Activate event handler - cleanup old caches and claim clients
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    cleanupCaches()
      .then(() => {
        console.log('[Service Worker] Claiming clients');
        return self.clients.claim(); // Take control of all clients
      })
  );
});

// Helper function to determine if a request is for an API or external resource
const isApiOrExternal = request => {
  const url = new URL(request.url);
  return url.pathname.startsWith('/api/') || 
         !url.hostname.includes(self.location.hostname) ||
         request.method !== 'GET';
};

// Helper to determine if a response should be cached dynamically
const shouldCacheDynamically = (url, response) => {
  // Only cache successful responses
  if (!response || response.status !== 200) return false;
  
  // Don't cache API responses or non-GET requests
  if (url.pathname.startsWith('/api/')) return false;
  
  // Only cache same-origin requests except for allowed CDNs
  if (!url.hostname.includes(self.location.hostname) && 
      !url.hostname.includes('cdn.') && 
      !url.hostname.includes('fonts.googleapis.com')) return false;
  
  return true;
};

// Fetch event handler - serve from cache, fall back to network with dynamic caching
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Skip cross-origin requests except for CDNs we want to cache
  if (!url.hostname.includes(self.location.hostname) && 
      !url.hostname.includes('cdn.') && 
      !url.hostname.includes('fonts.googleapis.com')) {
    return;
  }
  
  // For API requests or non-GET requests, use network first with fallback to cache
  if (isApiOrExternal(event.request)) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Don't cache API responses
          return response;
        })
        .catch(() => {
          console.log('[Service Worker] Falling back to cache for:', event.request.url);
          return caches.match(event.request);
        })
    );
    return;
  }
  
  // For menu.json, always try network first, then cache
  if (url.pathname.includes('menu.json')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Clone the response to cache it and return it
          const responseToCache = response.clone();
          caches.open(DYNAMIC_CACHE).then(cache => {
            cache.put(event.request, responseToCache);
          });
          return response;
        })
        .catch(() => {
          return caches.match(event.request);
        })
    );
    return;
  }
  
  // For everything else, try cache first, fall back to network
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // Return cached response if found
        if (cachedResponse) {
          return cachedResponse;
        }
        
        // If not in cache, fetch from network
        return fetch(event.request)
          .then(networkResponse => {
            // Return the network response immediately
            if (!networkResponse || networkResponse.status !== 200) {
              return networkResponse;
            }
            
            // Clone the response to cache it and return the original
            const responseToCache = networkResponse.clone();
            
            // Cache dynamically in the background
            if (shouldCacheDynamically(url, networkResponse)) {
              caches.open(DYNAMIC_CACHE).then(cache => {
                cache.put(event.request, responseToCache);
              });
            }
            
            return networkResponse;
          })
          .catch(error => {
            console.log('[Service Worker] Fetch failed completely:', error);
            
            // For HTML pages, fallback to the app shell (index.html)
            if (event.request.headers.get('Accept').includes('text/html')) {
              return caches.match('/index.html');
            }
            
            // For everything else, just propagate the error
            throw error;
          });
      })
  );
});

// Background sync for offline order submissions
self.addEventListener('sync', event => {
  if (event.tag === 'sync-pending-orders') {
    console.log('[Service Worker] Syncing pending orders');
    event.waitUntil(syncPendingOrders());
  }
});

// Function to sync pending orders when coming back online
async function syncPendingOrders() {
  try {
    // Get pending orders from IndexedDB
    const pendingOrders = await getPendingOrdersFromDB();
    
    if (pendingOrders && pendingOrders.length > 0) {
      console.log(`[Service Worker] Found ${pendingOrders.length} pending orders to sync`);
      
      // Process each pending order
      const syncResults = await Promise.all(
        pendingOrders.map(async order => {
          try {
            // Attempt to send the order to the server
            const response = await fetch('/api/orders', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(order)
            });
            
            if (response.ok) {
              // If successful, remove from pending orders
              await removePendingOrder(order.id);
              return { success: true, order };
            } else {
              return { success: false, order, error: 'Server returned error' };
            }
          } catch (error) {
            return { success: false, order, error };
          }
        })
      );
      
      // Notify the clients about the sync results
      const clients = await self.clients.matchAll();
      clients.forEach(client => {
        client.postMessage({
          type: 'ORDER_SYNC_COMPLETED',
          syncResults
        });
      });
      
      return syncResults;
    }
    
    return [];
  } catch (error) {
    console.error('[Service Worker] Error syncing pending orders:', error);
    throw error;
  }
}

// These functions would interact with IndexedDB - implementation depends on how you store pending orders
function getPendingOrdersFromDB() {
  // Example implementation - replace with your actual IndexedDB code
  return new Promise((resolve) => {
    // For now, just check localStorage as a simple example
    const pendingOrders = localStorage.getItem('pendingOrders');
    resolve(pendingOrders ? JSON.parse(pendingOrders) : []);
  });
}

function removePendingOrder(orderId) {
  // Example implementation - replace with your actual IndexedDB code
  return new Promise((resolve) => {
    // For now, just update localStorage as a simple example
    const pendingOrders = JSON.parse(localStorage.getItem('pendingOrders') || '[]');
    const updatedOrders = pendingOrders.filter(order => order.id !== orderId);
    localStorage.setItem('pendingOrders', JSON.stringify(updatedOrders));
    resolve();
  });
}

// Push notification handler
self.addEventListener('push', event => {
  console.log('[Service Worker] Push notification received');
  
  try {
    // Try to parse the data
    const data = event.data ? event.data.json() : { 
      title: 'Fenyal',
      message: 'New notification from Fenyal'
    };
    
    // Default notification options
    const options = {
      body: data.message || data.body || 'You have a new notification',
      icon: '/assets/icons/icon-192x192.png',
      badge: '/assets/icons/icon-72x72.png',
      vibrate: [100, 50, 100],
      data: {
        url: data.url || '/'
      },
      actions: []
    };
    
    // Add actions if provided
    if (data.actions) {
      options.actions = data.actions;
    }
    
    // Show the notification
    event.waitUntil(
      self.registration.showNotification(data.title || 'Fenyal', options)
    );
  } catch (error) {
    console.error('[Service Worker] Error showing push notification:', error);
    
    // Show a generic notification as fallback
    event.waitUntil(
      self.registration.showNotification('Fenyal', {
        body: 'You have a new notification',
        icon: '/assets/icons/icon-192x192.png',
        badge: '/assets/icons/icon-72x72.png'
      })
    );
  }
});

// Notification click handler
self.addEventListener('notificationclick', event => {
  console.log('[Service Worker] Notification clicked');
  
  // Close the notification
  event.notification.close();
  
  // Get the URL to open (from the notification data)
  const urlToOpen = event.notification.data && event.notification.data.url
    ? new URL(event.notification.data.url, self.location.origin).href
    : '/';
  
  // Focus on existing tab if open, otherwise open a new window
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(clientList => {
        // Check if a window with the URL is already open
        for (const client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        
        // If no window is open, open a new one
        if (self.clients.openWindow) {
          return self.clients.openWindow(urlToOpen);
        }
      })
  );
});
=======
>>>>>>> parent of 2bb6e88 (PWA)
