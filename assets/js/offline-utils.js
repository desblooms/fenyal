/**
 * Offline Functionality Module for Fenyal App
 * 
 * This module contains functions to handle offline-related functionality such as:
 * - Detecting offline/online status
 * - Queue operations for later sync
 * - Storing data in IndexedDB for offline access
 * - Managing the offline UI state
 */

// IndexedDB setup for offline data storage
const DB_NAME = 'FenyalOfflineDB';
const DB_VERSION = 1;
const STORES = {
  PENDING_ORDERS: 'pendingOrders',
  CART_ITEMS: 'cartItems',
  FAVORITE_ITEMS: 'favoriteItems',
  APP_STATE: 'appState'
};

// Initialize the database
async function initDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    
    request.onerror = (event) => {
      console.error('IndexedDB error:', event.target.error);
      reject(event.target.error);
    };
    
    request.onsuccess = (event) => {
      const db = event.target.result;
      console.log('IndexedDB initialized successfully');
      resolve(db);
    };
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      
      // Create object stores if they don't exist
      if (!db.objectStoreNames.contains(STORES.PENDING_ORDERS)) {
        const pendingOrdersStore = db.createObjectStore(STORES.PENDING_ORDERS, { keyPath: 'id' });
        pendingOrdersStore.createIndex('timestamp', 'timestamp', { unique: false });
      }
      
      if (!db.objectStoreNames.contains(STORES.CART_ITEMS)) {
        const cartItemsStore = db.createObjectStore(STORES.CART_ITEMS, { keyPath: 'id' });
      }
      
      if (!db.objectStoreNames.contains(STORES.FAVORITE_ITEMS)) {
        const favoritesStore = db.createObjectStore(STORES.FAVORITE_ITEMS, { keyPath: 'id' });
      }
      
      if (!db.objectStoreNames.contains(STORES.APP_STATE)) {
        const appStateStore = db.createObjectStore(STORES.APP_STATE, { keyPath: 'key' });
      }
      
      console.log('IndexedDB setup complete');
    };
  });
}

// Generic function to add an item to any store
async function addItem(storeName, item) {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, 'readwrite');
    const store = transaction.objectStore(storeName);
    const request = store.put(item);
    
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

// Generic function to get all items from a store
async function getAllItems(storeName) {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, 'readonly');
    const store = transaction.objectStore(storeName);
    const request = store.getAll();
    
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

// Generic function to get an item by ID
async function getItemById(storeName, id) {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, 'readonly');
    const store = transaction.objectStore(storeName);
    const request = store.get(id);
    
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

// Generic function to delete an item by ID
async function deleteItem(storeName, id) {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, 'readwrite');
    const store = transaction.objectStore(storeName);
    const request = store.delete(id);
    
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

// Generic function to clear all items from a store
async function clearStore(storeName) {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, 'readwrite');
    const store = transaction.objectStore(storeName);
    const request = store.clear();
    
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

// Add a new pending order
export async function addPendingOrder(order) {
  // Add timestamp to track when the order was created
  const orderWithTimestamp = {
    ...order,
    id: order.id || Date.now().toString(),
    timestamp: Date.now(),
    status: 'pending'
  };
  
  // Save to IndexedDB
  await addItem(STORES.PENDING_ORDERS, orderWithTimestamp);
  
  // Register for background sync if available
  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    try {
      const registration = await navigator.serviceWorker.ready;
      await registration.sync.register('sync-pending-orders');
      console.log('Background sync registered for pending orders');
    } catch (error) {
      console.error('Failed to register background sync:', error);
    }
  }
  
  return orderWithTimestamp;
}

// Get all pending orders
export async function getPendingOrders() {
  return await getAllItems(STORES.PENDING_ORDERS);
}

// Remove a pending order (after successful sync)
export async function removePendingOrder(orderId) {
  return await deleteItem(STORES.PENDING_ORDERS, orderId);
}

// Save cart items for offline access
export async function saveCartItems(items) {
  // First clear existing cart items
  await clearStore(STORES.CART_ITEMS);
  
  // Then add all current items
  const promises = items.map(item => {
    return addItem(STORES.CART_ITEMS, {
      ...item,
      id: item.id.toString() // Ensure ID is a string for IndexedDB
    });
  });
  
  return Promise.all(promises);
}

// Load cart items from offline storage
export async function loadCartItems() {
  return await getAllItems(STORES.CART_ITEMS);
}

// Add or update a favorite item
export async function toggleFavoriteItem(item) {
  // Check if item is already a favorite
  const existingItem = await getItemById(STORES.FAVORITE_ITEMS, item.id.toString());
  
  if (existingItem) {
    // Remove from favorites
    await deleteItem(STORES.FAVORITE_ITEMS, item.id.toString());
    return false; // Indicates item was removed from favorites
  } else {
    // Add to favorites
    await addItem(STORES.FAVORITE_ITEMS, {
      ...item,
      id: item.id.toString(),
      addedAt: Date.now()
    });
    return true; // Indicates item was added to favorites
  }
}

// Get all favorite items
export async function getFavoriteItems() {
  return await getAllItems(STORES.FAVORITE_ITEMS);
}

// Check if an item is favorited
export async function isItemFavorite(itemId) {
  const item = await getItemById(STORES.FAVORITE_ITEMS, itemId.toString());
  return !!item;
}

// Save app state for offline persistence
export async function saveAppState(key, value) {
  return await addItem(STORES.APP_STATE, { key, value });
}

// Load app state
export async function getAppState(key) {
  const state = await getItemById(STORES.APP_STATE, key);
  return state ? state.value : null;
}

// Network status detection
export function isOnline() {
  return navigator.onLine;
}

// Setup network status listeners
export function setupNetworkListeners(callbacks) {
  const { onOnline, onOffline } = callbacks || {};
  
  window.addEventListener('online', () => {
    console.log('Network connection restored');
    
    // Trigger background sync if available
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
      navigator.serviceWorker.ready.then(registration => {
        registration.sync.register('sync-pending-orders');
      });
    }
    
    // Call the onOnline callback if provided
    if (typeof onOnline === 'function') {
      onOnline();
    }
  });
  
  window.addEventListener('offline', () => {
    console.log('Network connection lost');
    
    // Call the onOffline callback if provided
    if (typeof onOffline === 'function') {
      onOffline();
    }
  });
}

// Cache menu data for offline use
export async function cacheMenuData(menuData) {
  return await saveAppState('menuData', menuData);
}

// Get cached menu data
export async function getCachedMenuData() {
  return await getAppState('menuData');
}

// Initialize the offline functionality
export async function initOfflineSupport() {
  try {
    // Initialize the database
    await initDB();
    
    // Setup network listeners with default empty callbacks
    setupNetworkListeners();
    
    // Register the service worker if not already registered
    if ('serviceWorker' in navigator) {
      try {
        const registration = await navigator.serviceWorker.register('/service-worker.js');
        console.log('ServiceWorker registered with scope:', registration.scope);
      } catch (error) {
        console.error('ServiceWorker registration failed:', error);
      }
    }
    
    return true;
  } catch (error) {
    console.error('Failed to initialize offline support:', error);
    return false;
  }
}

// Export a function to check if the app is installed (standalone mode)
export function isAppInstalled() {
  return window.matchMedia('(display-mode: standalone)').matches || 
         navigator.standalone === true;
}

// Export all functions as a module
export default {
  initOfflineSupport,
  isOnline,
  setupNetworkListeners,
  addPendingOrder,
  getPendingOrders,
  removePendingOrder,
  saveCartItems,
  loadCartItems,
  toggleFavoriteItem,
  getFavoriteItems,
  isItemFavorite,
  saveAppState,
  getAppState,
  cacheMenuData,
  getCachedMenuData,
  isAppInstalled
};