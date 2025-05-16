// assets/js/manual-install-button.js

/**
 * Manual PWA Install Button
 * Create a button that users can click to install the PWA
 * Place this on settings or profile pages
 */

class ManualInstallButton {
  constructor(containerId = 'pwa-install-container') {
    this.containerId = containerId;
    this.container = null;
    this.isInstallable = false;
    this.isInstalled = false;
    this.init();
  }

  init() {
    // Find or create container
    this.container = document.getElementById(this.containerId);
    if (!this.container) {
      console.warn(`Container with ID "${this.containerId}" not found.`);
      return;
    }

    // Check if app is already installed
    if (window.matchMedia('(display-mode: standalone)').matches || 
        window.navigator.standalone === true) {
      this.isInstalled = true;
      this.renderInstalledState();
      return;
    }

    // Check if installation is available
    if (window.pwaInstaller && window.pwaInstaller.installPrompt) {
      this.isInstallable = true;
    }

    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', () => {
      this.isInstallable = true;
      this.renderButton();
    });

    // Listen for app installed event
    window.addEventListener('appinstalled', () => {
      this.isInstalled = true;
      this.isInstallable = false;
      this.renderInstalledState();
    });

    // Render the initial button
    this.renderButton();
  }

  renderButton() {
    if (!this.container) return;

    this.container.innerHTML = `
      <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
        <div class="flex flex-col">
          <div class="flex items-center mb-3">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mr-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
            </div>
            <div>
              <h3 class="font-medium">Install Fenyal App</h3>
              <p class="text-sm text-gray-600">Get better performance and offline access</p>
            </div>
          </div>
          <div class="flex flex-col space-y-3 ml-15">
            <div class="flex items-center text-xs text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              Works offline
            </div>
            <div class="flex items-center text-xs text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              Faster loading times
            </div>
            <div class="flex items-center text-xs text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              App-like experience
            </div>
          </div>
          <button 
            id="manual-install-btn"
            class="mt-4 py-3 rounded-xl font-medium text-center ${
              this.isInstallable 
                ? 'bg-primary text-white' 
                : 'bg-gray-100 text-gray-400'
            }"
            ${!this.isInstallable ? 'disabled' : ''}
          >
            ${this.isInstallable ? 'Install Now' : 'Not Available on this Browser'}
          </button>
          ${!this.isInstallable ? `
            <p class="text-xs text-gray-500 text-center mt-2">
              Try using Chrome on Android or Safari on iOS
            </p>
          ` : ''}
        </div>
      </div>
    `;

    // Add event listener to the button
    const installButton = document.getElementById('manual-install-btn');
    if (installButton) {
      installButton.addEventListener('click', () => this.handleInstallClick());
    }

    // Initialize icons if feather is available
    if (window.feather) {
      window.feather.replace(this.container.querySelectorAll('[stroke]'));
    }
  }

  renderInstalledState() {
    if (!this.container) return;

    this.container.innerHTML = `
      <div class="bg-green-50 rounded-xl p-4 shadow-sm mb-4 flex items-center">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <div>
          <h3 class="font-medium text-green-800">App Installed</h3>
          <p class="text-sm text-green-700">You're using the installed version of Fenyal</p>
        </div>
      </div>
    `;

    // Initialize icons if feather is available
    if (window.feather) {
      window.feather.replace(this.container.querySelectorAll('[stroke]'));
    }
  }

  handleInstallClick() {
    if (window.showInstallPrompt) {
      window.showInstallPrompt();
    } else {
      // Fallback instructions
      alert("To install this app: tap the browser menu button and select 'Add to Home Screen' or 'Install App'.");
    }
  }
  
  // Static method to initialize the button
  static init(containerId) {
    return new ManualInstallButton(containerId);
  }
}

// Automatically initialize if this script is loaded after DOM content is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Look for containers with the data-pwa-install attribute
  const containers = document.querySelectorAll('[data-pwa-install]');
  containers.forEach(container => {
    new ManualInstallButton(container.id);
  });
});

// Make it globally available
window.ManualInstallButton = ManualInstallButton;