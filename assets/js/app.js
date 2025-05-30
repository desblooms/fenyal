// Common app functionality

// Calculate viewport height for mobile browsers
function setViewportHeight() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

// Toast notification system
class ToastNotification {
    constructor() {
        this.toastContainer = null;
        this.initialize();
    }
    
    initialize() {
        // Create toast container if it doesn't exist
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.className = 'fixed bottom-20 left-0 right-0 flex flex-col items-center justify-center z-50 pointer-events-none';
            document.body.appendChild(this.toastContainer);
        }
    }
    
    show(message, type = 'success', duration = 3000) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `mb-2 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 ease-in-out opacity-0 translate-y-4 max-w-xs text-center pointer-events-auto`;
        
        // Set background color based on type
        if (type === 'success') {
            toast.classList.add('bg-green-500');
        } else if (type === 'error') {
            toast.classList.add('bg-red-500');
        } else if (type === 'warning') {
            toast.classList.add('bg-yellow-500');
        } else {
            toast.classList.add('bg-primary');
        }
        
        // Set message
        toast.textContent = message;
        
        // Add to container
        this.toastContainer.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.remove('opacity-0', 'translate-y-4');
        }, 10);
        
        // Remove after duration
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => {
                this.toastContainer.removeChild(toast);
            }, 300);
        }, duration);
    }
}

// Create and export toast notification system
export const toast = new ToastNotification();

// Page transition effect
export function pageTransition(url) {
    // Apply fade out effect to main container
    const mainApp = document.querySelector('.app-container');
    
    if (mainApp) {
        mainApp.classList.add('opacity-0');
        mainApp.style.transition = 'opacity 0.3s ease-out';
        
        setTimeout(() => {
            window.location.href = url;
        }, 300);
    } else {
        // If no main container found, just redirect
        window.location.href = url;
    }
}

// Loading spinner
export function showLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'fixed inset-0 bg-dark/30 flex items-center justify-center z-50 loading-overlay';
    
    const spinner = document.createElement('div');
    spinner.className = 'w-12 h-12 rounded-full border-4 border-primary border-t-transparent animate-spin';
    
    loadingOverlay.appendChild(spinner);
    document.body.appendChild(loadingOverlay);
}

export function hideLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('opacity-0');
        loadingOverlay.style.transition = 'opacity 0.3s ease-out';
        
        setTimeout(() => {
            document.body.removeChild(loadingOverlay);
        }, 300);
    }
}

// Common initialization
export function initApp() {
    // Set viewport height
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    
    // Show main app if user has visited before
    const welcomeOverlay = document.getElementById('welcome-overlay');
    const mainApp = document.getElementById('main-app');
    
    if (welcomeOverlay && mainApp) {
        const hasVisited = localStorage.getItem('hasVisited');
        
        if (hasVisited) {
            welcomeOverlay.classList.add('hidden');
            mainApp.classList.remove('hidden');
        }
    }
    
    // Add class to body when page is loaded
    document.body.classList.add('page-loaded');
}

// Initialize app on DOMContentLoaded
document.addEventListener('DOMContentLoaded', initApp);

// Handle form validation
export function validateForm(formElement) {
    const inputs = formElement.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('border-red-500');
            
            // Add error message if it doesn't exist
            let errorMessage = input.parentElement.querySelector('.error-message');
            if (!errorMessage) {
                errorMessage = document.createElement('p');
                errorMessage.className = 'text-red-500 text-xs mt-1 error-message';
                errorMessage.textContent = 'This field is required';
                input.parentElement.appendChild(errorMessage);
            }
        } else {
            input.classList.remove('border-red-500');
            
            // Remove error message if it exists
            const errorMessage = input.parentElement.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        }
    });
    
    return isValid;
}

// Format date and time
export function formatDateTime(date) {
    return new Intl.DateTimeFormat('en-IN', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit', 
        minute: '2-digit'
    }).format(date);
}

// Get URL parameters
export function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const paramObj = {};
    
    for (const [key, value] of params.entries()) {
        paramObj[key] = value;
    }
    
    return paramObj;
}


// Enhanced Bottom Navigation Script
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const centerLogo = document.querySelector('.center-logo');

    // Handle nav item clicks
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't prevent default for actual navigation
            // Remove active class from all items
            navItems.forEach(navItem => {
                navItem.classList.remove('active');
                const icon = navItem.querySelector('.nav-icon');
                const text = navItem.querySelector('.nav-text');
                if (icon) icon.classList.add('text-gray-500');
                if (text) text.classList.add('text-gray-500');
            });
            
            // Add active class to clicked item
            this.classList.add('active');
            const activeIcon = this.querySelector('.nav-icon');
            const activeText = this.querySelector('.nav-text');
            if (activeIcon) activeIcon.classList.remove('text-gray-500');
            if (activeText) activeText.classList.remove('text-gray-500');
        });
    });

    // Add touch feedback for mobile
    const allInteractiveElements = [...navItems, centerLogo];
    allInteractiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = this.classList.contains('center-logo') 
                ? 'translateY(-6px) scale(1.05)' 
                : 'translateY(-2px) scale(0.98)';
        });

        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Set active nav item based on current page
    function setActiveNavItem() {
        const currentPath = window.location.pathname;
        const fileName = currentPath.split('/').pop() || 'index.html';
        
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href.includes(fileName)) {
                item.classList.add('active');
                const icon = item.querySelector('.nav-icon');
                const text = item.querySelector('.nav-text');
                if (icon) icon.classList.remove('text-gray-500');
                if (text) text.classList.remove('text-gray-500');
            }
        });
    }

    // Initialize active state
    setActiveNavItem();
});

// Center logo action
function centerLogoAction() {
    const centerLogo = document.querySelector('.center-logo');
    
    // Add special animation
    centerLogo.style.transform = 'translateY(-8px) scale(1.1) rotate(360deg)';
    setTimeout(() => {
        centerLogo.style.transform = '';
    }, 600);
    
    // You can add your custom action here
    // For example: open WhatsApp, show special menu, etc.
    if (typeof toast !== 'undefined') {
        toast.show('Welcome to Fenyal! 🍽️', 'success');
    }
    
    // Optional: Navigate to a special page or trigger an action
    // window.open('https://wa.me/+1234567890', '_blank');
}