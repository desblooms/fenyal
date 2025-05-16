/**
 * Update Notification Module
 * 
 * This module adds a notification when a new version of the PWA is available.
 * It checks for service worker updates and prompts the user to refresh the page.
 */

// Configuration
const CHECK_INTERVAL = 30 * 60 * 1000; // Check for updates every 30 minutes

/**
 * Initialize the update notification functionality
 */
export function initUpdateNotification() {
  // Check if service workers are supported
  if (!('serviceWorker' in navigator)) {
    return;
  }
  
  // Check for updates immediately and then periodically
  checkForUpdates();
  setInterval(checkForUpdates, CHECK_INTERVAL);
  
  // Also listen for controlling service worker changes
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    // Controller changed, which means new service worker took over
    console.log('New service worker controller');
  });
}

/**
 * Check for service worker updates
 */
async function checkForUpdates() {
  try {
    // Get the registration
    const registration = await navigator.serviceWorker.getRegistration();
    
    if (!registration) return;
    
    // Check if there's a new service worker waiting
    if (registration.waiting) {
      const currentVersion = localStorage.getItem('appVersion') || '1.0.0';
      const lastDismissedVersion = localStorage.getItem('dismissedUpdateVersion') || '';
      
      // Only show notification if this version hasn't been dismissed
      if (lastDismissedVersion !== currentVersion) {
        showUpdateNotification(registration);
      }
    }
    
    // Add listener for future updates
    registration.addEventListener('updatefound', () => {
      const newWorker = registration.installing;
      
      // Track progress
      newWorker.addEventListener('statechange', () => {
        // When the service worker is installed, show notification
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          const currentVersion = localStorage.getItem('appVersion') || '1.0.0';
          const lastDismissedVersion = localStorage.getItem('dismissedUpdateVersion') || '';
          
          if (lastDismissedVersion !== currentVersion) {
            showUpdateNotification(registration);
          }
        }
      });
    });
    
  } catch (error) {
    console.error('Error checking for updates:', error);
  }
}

/**
 * Show the update notification
 * @param {ServiceWorkerRegistration} registration - The service worker registration
 */
function showUpdateNotification(registration) {
  // Check if notification already exists
  if (document.getElementById('update-notification')) {
    return;
  }
  
  // Create notification element
  const notificationEl = document.createElement('div');
  notificationEl.id = 'update-notification';
  notificationEl.className = 'fixed bottom-20 left-0 right-0 mx-auto w-11/12 max-w-sm bg-white rounded-lg shadow-lg p-4 z-50 border border-primary/20';
  
  // Add notification content
  notificationEl.innerHTML = `
    <div class="flex items-start">
      <div class="flex-shrink-0 bg-primary/10 rounded-full p-2 mr-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
          <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
        </svg>
      </div>
      <div class="flex-1">
        <h3 class="font-medium text-gray-900">Update Available</h3>
        <p class="text-sm text-gray-600 mt-1 mb-3">
          A new version of Fenyal app is available. Update now for the latest features and improvements.
        </p>
        <div class="flex space-x-2">
          <button id="dismiss-update" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700">
            Later
          </button>
          <button id="apply-update" class="px-3 py-1.5 text-sm bg-primary text-white rounded-md">
            Update Now
          </button>
        </div>
      </div>
    </div>
  `;
  
  // Add to the DOM
  document.body.appendChild(notificationEl);
  
  // Add event listeners
  document.getElementById('dismiss-update').addEventListener('click', () => {
    const currentVersion = localStorage.getItem('appVersion') || '1.0.0';
    localStorage.setItem('dismissedUpdateVersion', currentVersion);
    notificationEl.remove();
  });
  
  document.getElementById('apply-update').addEventListener('click', () => {
    if (registration && registration.waiting) {
      // Send message to service worker to skip waiting
      registration.waiting.postMessage({ type: 'SKIP_WAITING' });
      
      // Reload the page to apply updates
      window.location.reload();
    }
  });
  
  // Add animation
  setTimeout(() => {
    notificationEl.style.transition = 'transform 0.3s ease-out, opacity 0.3s ease-out';
    notificationEl.style.transform = 'translateY(0)';
    notificationEl.style.opacity = '1';
  }, 10);
}

// Listen for messages from the service worker
navigator.serviceWorker.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'UPDATE_AVAILABLE') {
    // Show notification when service worker tells us an update is available
    navigator.serviceWorker.getRegistration().then(registration => {
      if (registration) {
        showUpdateNotification(registration);
      }
    });
  }
});

// Export the init function
export default { initUpdateNotification };