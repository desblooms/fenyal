// Enhanced service worker for PWA installation on Android
// Add this to your existing service-worker.js file

// App shell URLs to cache
const APP_SHELL_URLS = [
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
  '/about-us.html',
  '/profile.html',
  '/app-installed.html',
  '/checkout/checkout.html',
  '/checkout/checkout-whatsapp.html',
  '/checkout/checkout-payment.html',
  '/checkout/checkout-cod.html',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/js/menu.js',
  '/assets/js/pwa-installer.js',
  '/assets/js/manual-install-button.js',
  '/assets/js/global-search.js',
  '/data/menu.json',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js',
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'
];

// Cache name definitions
const CACHE_NAMES = {
  static: 'fenyal-static-v1',
  dynamic: 'fenyal-dynamic-v1',
  images: 'fenyal-images-v1'
};

// Install event - cache App Shell
self.addEventListener('install', event => {
  console.log('[Service Worker] Installing Service Worker...');
  
  // Skip waiting to activate immediately
  self.skipWaiting();
  
  // Cache app shell resources
  event.waitUntil(
    caches.open(CACHE_NAMES.static)
      .then(cache => {
        console.log('[Service Worker] Caching App Shell');
        return cache.addAll(APP_SHELL_URLS);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activating Service Worker...');
  
  // Claim clients immediately
  event.waitUntil(self.clients.claim());
  
  // Clean up old caches
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (
            cacheName !== CACHE_NAMES.static && 
            cacheName !== CACHE_NAMES.dynamic &&
            cacheName !== CACHE_NAMES.images
          ) {
            console.log('[Service Worker] Removing old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - serve from cache, fallback to network with cache
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Skip cross-origin requests
  if (url.origin !== self.location.origin && 
      !url.href.includes('cdn.') && 
      !url.href.includes('fonts.googleapis')) {
    return;
  }
  
  // Handle API requests (menu.json) - Network first, fallback to cache
  if (url.pathname.includes('/data/menu.json')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Clone the response for cache and return
          const clonedResponse = response.clone();
          
          caches.open(CACHE_NAMES.dynamic)
            .then(cache => {
              cache.put(event.request, clonedResponse);
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
  
  // Handle image requests - Cache first, fallback to network with cache
  if (
    event.request.url.match(/\.(jpg|jpeg|png|gif|svg|webp)$/) ||
    event.request.url.includes('/assets/images/') ||
    event.request.url.includes('/assets/icons/')
  ) {
    event.respondWith(
      caches.match(event.request)
        .then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          return fetch(event.request)
            .then(response => {
              // Cache the fetched image
              const clonedResponse = response.clone();
              
              caches.open(CACHE_NAMES.images)
                .then(cache => {
                  cache.put(event.request, clonedResponse);
                });
                
              return response;
            })
            .catch(error => {
              console.error('[Service Worker] Fetch image failed:', error);
            });
        })
    );
    return;
  }
  
  // Handle HTML documents - Network first with cache fallback
  if (event.request.headers.get('accept').includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Clone the response for cache and return
          const clonedResponse = response.clone();
          
          caches.open(CACHE_NAMES.dynamic)
            .then(cache => {
              cache.put(event.request, clonedResponse);
            });
            
          return response;
        })
        .catch(() => {
          // If fetch fails, try to get from cache
          return caches.match(event.request)
            .then(cachedResponse => {
              if (cachedResponse) {
                return cachedResponse;
              }
              
              // If no cache match, return the offline fallback page
              return caches.match('/index.html');
            });
        })
    );
    return;
  }
  
  // Default strategy - Stale while revalidate
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // Return cached response if available
        if (cachedResponse) {
          // In background, fetch from network and update cache
          fetch(event.request)
            .then(networkResponse => {
              caches.open(CACHE_NAMES.dynamic)
                .then(cache => {
                  cache.put(event.request, networkResponse.clone());
                });
            })
            .catch(() => {
              // Silently fail if background update fails
            });
            
          return cachedResponse;
        }
        
        // No cache match, fetch from network
        return fetch(event.request)
          .then(response => {
            // Clone response for cache
            const clonedResponse = response.clone();
            
            // Cache response for future
            caches.open(CACHE_NAMES.dynamic)
              .then(cache => {
                cache.put(event.request, clonedResponse);
              });
              
            return response;
          })
          .catch(error => {
            console.error('[Service Worker] Fetch failed:', error);
            
            // For non-HTML requests, return nothing when offline
            if (!event.request.headers.get('accept').includes('text/html')) {
              return;
            }
            
            // For HTML pages, return the offline fallback page
            return caches.match('/index.html');
          });
      })
  );
});

// Listen for messages from client
self.addEventListener('message', event => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});

// Add custom app installation event handling
self.addEventListener('appinstalled', event => {
  console.log('[Service Worker] App installed');
  
  // Send message to clients that app was installed
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage({
        type: 'APP_INSTALLED'
      });
    });
  });
});

// Push notification support
self.addEventListener('push', event => {
  let notification = {
    title: 'New message from Fenyal',
    body: 'You have a new update from Fenyal',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/badge-72x72.png',
    data: {
      url: '/'
    }
  };

  if (event.data) {
    try {
      notification = Object.assign(notification, event.data.json());
    } catch (e) {
      console.error('Could not parse push notification data', e);
    }
  }

  event.waitUntil(
    self.registration.showNotification(notification.title, {
      body: notification.body,
      icon: notification.icon,
      badge: notification.badge,
      vibrate: [100, 50, 100],
      data: notification.data
    })
  );
});

// Notification click event
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  const urlToOpen = event.notification.data && event.notification.data.url 
    ? event.notification.data.url 
    : '/';
  
  event.waitUntil(
    clients.matchAll({type: 'window'})
      .then(clientList => {
        // Check if there is already a window/tab open with the target URL
        for (const client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        
        // If no window/tab is open, open a new one
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Background sync for offline support
self.addEventListener('sync', event => {
  if (event.tag === 'sync-orders') {
    console.log('[Service Worker] Syncing orders');
    event.waitUntil(syncOrders());
  }
});

// Function to sync pending orders when back online
function syncOrders() {
  return new Promise((resolve, reject) => {
    // Get pending orders from IndexedDB
    const request = indexedDB.open('fenyal-db', 1);
    
    request.onerror = event => {
      console.error('Error opening IndexedDB:', event.target.error);
      reject(event.target.error);
    };
    
    request.onsuccess = event => {
      const db = event.target.result;
      const transaction = db.transaction(['pendingOrders'], 'readwrite');
      const store = transaction.objectStore('pendingOrders');
      
      const getAllRequest = store.getAll();
      
      getAllRequest.onsuccess = () => {
        const pendingOrders = getAllRequest.result;
        
        if (pendingOrders.length === 0) {
          resolve();
          return;
        }
        
        // Process each pending order
        const syncPromises = pendingOrders.map(order => {
          return fetch('/api/orders', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(order)
          })
          .then(response => {
            if (response.ok) {
              // If order is submitted successfully, remove from pending
              const deleteRequest = store.delete(order.id);
              
              // Notify user
              return self.registration.showNotification('Order Synced', {
                body: 'Your order has been submitted successfully',
                icon: '/assets/icons/icon-192x192.png',
                badge: '/assets/icons/badge-72x72.png'
              });
            }
          })
          .catch(error => {
            console.error('Error syncing order:', error);
          });
        });
        
        Promise.all(syncPromises)
          .then(() => resolve())
          .catch(error => reject(error));
      };
      
      getAllRequest.onerror = event => {
        console.error('Error getting pending orders:', event.target.error);
        reject(event.target.error);
      };
    };
    
    // Create object store if it doesn't exist
    request.onupgradeneeded = event => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pendingOrders')) {
        db.createObjectStore('pendingOrders', { keyPath: 'id' });
      }
    };
  });
}