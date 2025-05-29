// assets/js/pwa-installer.js

/**
 * PWA Installer Script
 * Adds an "Install App" button for Android and iOS users
 */

class PWAInstaller {
  constructor() {
    this.installPrompt = null;
    this.installButton = null;
    this.bannerElement = null;
    this.isInstalled = false;
    this.init();
  }

  init() {
    // Check if app is already installed in standalone mode
    if (window.matchMedia('(display-mode: standalone)').matches || 
        window.navigator.standalone === true) {
      this.isInstalled = true;
      return; // Already installed, don't show banner
    }

    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
      // Prevent Chrome 67 and earlier from automatically showing the prompt
      e.preventDefault();
      // Stash the event so it can be triggered later
      this.installPrompt = e;
      // Show the install banner
      this.showInstallBanner();
    });

    // Listen for app installed event
    window.addEventListener('appinstalled', () => {
      this.isInstalled = true;
      this.hideInstallBanner();
      console.log('PWA was installed');
      
      // Optionally show a success toast
      if (window.toast && typeof window.toast.show === 'function') {
        window.toast.show('App installed successfully!', 'success');
      }
    });
  }

  showInstallBanner() {
    // If already showing or app is installed, don't show again
    if (this.bannerElement || this.isInstalled) return;

    // Create the banner element
    this.bannerElement = document.createElement('div');
    this.bannerElement.className = 'fixed bottom-20 left-0 right-0 px-4 z-40 mb-2 slide-in';
    this.bannerElement.innerHTML = `
      <div class="bg-white rounded-xl shadow-lg p-4 flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
          </div>
          <div>
            <h3 class="font-medium text-sm">Install Fenyal App</h3>
            <p class="text-xs text-gray-500">Add to your home screen</p>
          </div>
        </div>
        <div class="flex">
          <button class="dismiss-btn text-gray-400 mr-2 text-xs px-2 py-1.5">
            Later
          </button>
          <button class="install-btn bg-primary text-white text-xs font-medium px-3 py-1.5 rounded-lg">
            Install
          </button>
        </div>
      </div>
    `;

    // Add to DOM
    document.body.appendChild(this.bannerElement);

    // Set up event listeners
    this.installButton = this.bannerElement.querySelector('.install-btn');
    const dismissButton = this.bannerElement.querySelector('.dismiss-btn');

    this.installButton.addEventListener('click', () => this.handleInstallClick());
    dismissButton.addEventListener('click', () => this.hideInstallBanner());
    
    // Initialize icons if feather is available
    if (window.feather) {
      window.feather.replace(this.bannerElement.querySelectorAll('[stroke]'));
    }
  }

  hideInstallBanner() {
    if (this.bannerElement) {
      this.bannerElement.style.opacity = '0';
      this.bannerElement.style.transform = 'translateY(20px)';
      this.bannerElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      
      setTimeout(() => {
        if (this.bannerElement && this.bannerElement.parentElement) {
          this.bannerElement.parentElement.removeChild(this.bannerElement);
          this.bannerElement = null;
        }
      }, 300);
    }
  }

  async handleInstallClick() {
    if (!this.installPrompt) return;
    
    // Show the install prompt
    this.installPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await this.installPrompt.userChoice;
    
    // We no longer need the prompt
    this.installPrompt = null;
    
    if (outcome === 'accepted') {
      console.log('User accepted the install prompt');
      this.hideInstallBanner();
    } else {
      console.log('User dismissed the install prompt');
    }
  }
}

// Initialize the PWA installer
window.pwaInstaller = new PWAInstaller();

// For manual triggering later if needed
window.showInstallPrompt = () => {
  if (window.pwaInstaller && window.pwaInstaller.installPrompt) {
    window.pwaInstaller.handleInstallClick();
  } else {
    console.log('Install prompt not available');
    
    // Show instructions for manual installation
    if (window.toast && typeof window.toast.show === 'function') {
      window.toast.show('Add to home screen from your browser menu', 'info');
    }
  }
};