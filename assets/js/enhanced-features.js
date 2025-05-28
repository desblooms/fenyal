// Enhanced App Features - Cart Persistence, Favorites, and More
// File: assets/js/enhanced-features.js

// Enhanced Favorites Manager
class FavoritesManager {
    constructor() {
        this.favorites = JSON.parse(localStorage.getItem('favorites')) || [];
        this.init();
    }

    init() {
        // Listen for favorite button clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('.favorite-btn, .favorite-btn *')) {
                const button = e.target.closest('.favorite-btn');
                const itemId = button.dataset.itemId || this.extractItemIdFromUrl();
                
                if (itemId) {
                    this.toggleFavorite(itemId, button);
                }
            }
        });

        // Update favorite buttons on page load
        this.updateFavoriteButtons();
    }

    extractItemIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }

    toggleFavorite(itemId, button) {
        const isCurrentlyFavorite = this.isFavorite(itemId);
        
        if (isCurrentlyFavorite) {
            this.removeFavorite(itemId);
        } else {
            this.addFavorite(itemId);
        }

        this.updateFavoriteButton(button, !isCurrentlyFavorite);
        this.saveFavorites();

        // Show toast notification
        const message = !isCurrentlyFavorite ? 'Added to favorites' : 'Removed from favorites';
        this.showToast(message, !isCurrentlyFavorite ? 'success' : 'info');
    }

    addFavorite(itemId) {
        if (!this.isFavorite(itemId)) {
            this.favorites.push(parseInt(itemId));
        }
    }

    removeFavorite(itemId) {
        this.favorites = this.favorites.filter(id => id !== parseInt(itemId));
    }

    isFavorite(itemId) {
        return this.favorites.includes(parseInt(itemId));
    }

    getFavorites() {
        return this.favorites;
    }

    updateFavoriteButtons() {
        document.querySelectorAll('.favorite-btn').forEach(button => {
            const itemId = button.dataset.itemId || this.extractItemIdFromUrl();
            if (itemId) {
                this.updateFavoriteButton(button, this.isFavorite(itemId));
            }
        });
    }

    updateFavoriteButton(button, isFavorite) {
        const icon = button.querySelector('i');
        
        if (isFavorite) {
            button.classList.add('active');
            icon.style.fill = 'currentColor';
            icon.style.color = '#ef4444';
        } else {
            button.classList.remove('active');
            icon.style.fill = 'none';
            icon.style.color = '';
        }
    }

    saveFavorites() {
        localStorage.setItem('favorites', JSON.stringify(this.favorites));
    }

    showToast(message, type = 'info') {
        if (window.toast && typeof window.toast.show === 'function') {
            window.toast.show(message, type);
        }
    }
}

// Enhanced Cart Persistence with Auto-Save
class EnhancedCartManager {
    constructor() {
        this.cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
        this.isOnline = navigator.onLine;
        this.syncQueue = JSON.parse(localStorage.getItem('cartSyncQueue')) || [];
        this.init();
    }

    init() {
        // Auto-save cart every 30 seconds
        setInterval(() => {
            this.autoSave();
        }, 30000);

        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncCartWhenOnline();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
        });

        // Save cart when page is about to unload
        window.addEventListener('beforeunload', () => {
            this.saveCart();
        });
    }

    autoSave() {
        this.saveCart();
        console.log('Cart auto-saved at', new Date().toLocaleTimeString());
    }

    addToCart(item, quantity = 1, variants = {}) {
        // Add timestamp and unique identifier
        const cartItem = {
            ...item,
            quantity,
            variants,
            addedAt: new Date().toISOString(),
            cartId: this.generateCartId()
        };

        const existingIndex = this.findExistingItem(item.id, variants);
        
        if (existingIndex >= 0) {
            this.cartItems[existingIndex].quantity += quantity;
            this.cartItems[existingIndex].updatedAt = new Date().toISOString();
        } else {
            this.cartItems.push(cartItem);
        }

        this.saveCart();
        this.updateCartBadge();
        
        // Add to sync queue if offline
        if (!this.isOnline) {
            this.addToSyncQueue('add', cartItem);
        }

        return this.cartItems;
    }

    findExistingItem(itemId, variants) {
        return this.cartItems.findIndex(
            cartItem => cartItem.id === itemId && 
                       JSON.stringify(cartItem.variants) === JSON.stringify(variants)
        );
    }

    generateCartId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }

    removeFromCart(index) {
        if (index >= 0 && index < this.cartItems.length) {
            const removedItem = this.cartItems[index];
            this.cartItems.splice(index, 1);
            this.saveCart();
            this.updateCartBadge();
            
            // Add to sync queue if offline
            if (!this.isOnline) {
                this.addToSyncQueue('remove', removedItem);
            }
        }
        return this.cartItems;
    }

    updateQuantity(index, quantity) {
        if (index >= 0 && index < this.cartItems.length) {
            if (quantity <= 0) {
                this.removeFromCart(index);
            } else {
                this.cartItems[index].quantity = quantity;
                this.cartItems[index].updatedAt = new Date().toISOString();
                this.saveCart();
            }
        }
        return this.cartItems;
    }

    clearCart() {
        this.cartItems = [];
        this.saveCart();
        this.updateCartBadge();
    }

    saveCart() {
        try {
            localStorage.setItem('cartItems', JSON.stringify(this.cartItems));
            localStorage.setItem('cartLastSaved', new Date().toISOString());
        } catch (error) {
            console.error('Failed to save cart:', error);
        }
    }

    addToSyncQueue(action, item) {
        this.syncQueue.push({
            action,
            item,
            timestamp: new Date().toISOString()
        });
        localStorage.setItem('cartSyncQueue', JSON.stringify(this.syncQueue));
    }

    syncCartWhenOnline() {
        if (this.syncQueue.length > 0) {
            console.log('Syncing cart changes...', this.syncQueue);
            // Here you would sync with your backend API
            this.syncQueue = [];
            localStorage.removeItem('cartSyncQueue');
        }
    }

    getCartItems() {
        return this.cartItems;
    }

    getCartTotal() {
        return this.cartItems.reduce((total, item) => {
            let itemPrice = 0;
            
            if (item.variants && item.variants.size === 'half') {
                itemPrice = item.halfPrice;
            } else if (item.variants && item.variants.size === 'full') {
                itemPrice = item.fullPrice;
            } else {
                itemPrice = item.price;
            }
            
            if (item.variants && item.variants.addons) {
                itemPrice += item.variants.addons.reduce((addonTotal, addon) => {
                    return addonTotal + addon.price;
                }, 0);
            }
            
            return total + (itemPrice * item.quantity);
        }, 0);
    }

    getItemCount() {
        return this.cartItems.reduce((count, item) => count + item.quantity, 0);
    }

    updateCartBadge() {
        const cartBadges = document.querySelectorAll('#cart-badge, .cart-badge');
        const itemCount = this.getItemCount();
        
        cartBadges.forEach(badge => {
            badge.textContent = itemCount;
            badge.style.display = itemCount > 0 ? 'flex' : 'none';
            
            // Add animation when count changes
            if (itemCount > 0) {
                badge.classList.add('animate-pulse');
                setTimeout(() => {
                    badge.classList.remove('animate-pulse');
                }, 600);
            }
        });
    }
}

// Enhanced Toast Notification System
class EnhancedToastManager {
    constructor() {
        this.toastContainer = null;
        this.activeToasts = [];
        this.init();
    }

    init() {
        this.createToastContainer();
    }

    createToastContainer() {
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.className = 'fixed top-4 right-4 z-50 pointer-events-none space-y-2';
            this.toastContainer.id = 'toast-container';
            document.body.appendChild(this.toastContainer);
        }
    }

    show(message, type = 'info', duration = 4000) {
        const toast = this.createToast(message, type);
        this.toastContainer.appendChild(toast);
        this.activeToasts.push(toast);

        // Trigger entrance animation
        setTimeout(() => {
            toast.classList.add('translate-x-0', 'opacity-100');
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 10);

        // Auto-remove after duration
        setTimeout(() => {
            this.removeToast(toast);
        }, duration);

        return toast;
    }

    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `transform transition-all duration-300 ease-in-out translate-x-full opacity-0 
                          max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden`;

        const colors = {
            success: { bg: 'bg-green-50', icon: 'text-green-400', text: 'text-green-800' },
            error: { bg: 'bg-red-50', icon: 'text-red-400', text: 'text-red-800' },
            warning: { bg: 'bg-yellow-50', icon: 'text-yellow-400', text: 'text-yellow-800' },
            info: { bg: 'bg-blue-50', icon: 'text-blue-400', text: 'text-blue-800' }
        };

        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'alert-triangle',
            info: 'info'
        };

        const color = colors[type] || colors.info;
        const icon = icons[type] || icons.info;

        toast.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i data-feather="${icon}" class="h-5 w-5 ${color.icon}"></i>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium ${color.text}">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="toast-close bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i data-feather="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Initialize feather icons
        if (window.feather) {
            window.feather.replace(toast.querySelectorAll('[data-feather]'));
        }

        // Add close functionality
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            this.removeToast(toast);
        });

        return toast;
    }

    removeToast(toast) {
        if (toast && toast.parentNode) {
            toast.classList.add('translate-x-full', 'opacity-0');
            toast.classList.remove('translate-x-0', 'opacity-100');

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                this.activeToasts = this.activeToasts.filter(t => t !== toast);
            }, 300);
        }
    }

    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration) {
        return this.show(message, 'info', duration);
    }

    clearAll() {
        this.activeToasts.forEach(toast => {
            this.removeToast(toast);
        });
    }
}

// Enhanced Search with History and Suggestions
class EnhancedSearchManager {
    constructor() {
        this.searchHistory = JSON.parse(localStorage.getItem('searchHistory')) || [];
        this.searchSuggestions = [];
        this.currentQuery = '';
        this.init();
    }

    init() {
        this.setupSearchInputs();
        this.loadSearchSuggestions();
    }

    setupSearchInputs() {
        document.addEventListener('input', (e) => {
            if (e.target.matches('input[type="search"], .search-input, #search-input')) {
                this.handleSearchInput(e.target);
            }
        });

        document.addEventListener('focus', (e) => {
            if (e.target.matches('input[type="search"], .search-input, #search-input')) {
                this.showSearchSuggestions(e.target);
            }
        });
    }

    handleSearchInput(input) {
        const query = input.value.trim();
        this.currentQuery = query;

        if (query.length >= 2) {
            this.showSearchSuggestions(input, query);
        } else {
            this.hideSearchSuggestions();
        }
    }

    showSearchSuggestions(input, query = '') {
        let suggestions = [];

        if (query) {
            // Filter suggestions based on query
            suggestions = this.searchSuggestions.filter(suggestion =>
                suggestion.toLowerCase().includes(query.toLowerCase())
            );
        } else {
            // Show recent searches
            suggestions = this.searchHistory.slice(0, 5);
        }

        this.renderSuggestions(input, suggestions, query ? 'Suggestions' : 'Recent Searches');
    }

    renderSuggestions(input, suggestions, title) {
        // Remove existing suggestions
        this.hideSearchSuggestions();

        if (suggestions.length === 0) return;

        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'absolute top-full left-0 right-0 bg-white shadow-lg rounded-lg mt-1 z-50 max-h-60 overflow-y-auto search-suggestions';
        suggestionsContainer.innerHTML = `
            <div class="p-2 border-b border-gray-100">
                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">${title}</h4>
            </div>
            <div class="py-1">
                ${suggestions.map(suggestion => `
                    <div class="suggestion-item px-3 py-2 hover:bg-gray-50 cursor-pointer flex items-center">
                        <i data-feather="search" class="h-4 w-4 text-gray-400 mr-2"></i>
                        <span class="text-sm">${suggestion}</span>
                    </div>
                `).join('')}
            </div>
        `;

        // Position relative to input
        const inputRect = input.getBoundingClientRect();
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(suggestionsContainer);

        // Initialize feather icons
        if (window.feather) {
            window.feather.replace(suggestionsContainer.querySelectorAll('[data-feather]'));
        }

        // Add click handlers
        suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const suggestion = item.querySelector('span').textContent;
                input.value = suggestion;
                this.addToSearchHistory(suggestion);
                this.hideSearchSuggestions();
                
                // Trigger search
                const event = new Event('input', { bubbles: true });
                input.dispatchEvent(event);
            });
        });
    }

    hideSearchSuggestions() {
        document.querySelectorAll('.search-suggestions').forEach(container => {
            container.remove();
        });
    }

    addToSearchHistory(query) {
        if (!query || this.searchHistory.includes(query)) return;

        this.searchHistory.unshift(query);
        this.searchHistory = this.searchHistory.slice(0, 10); // Keep only last 10
        localStorage.setItem('searchHistory', JSON.stringify(this.searchHistory));
    }

    async loadSearchSuggestions() {
        try {
            // Load menu data to generate suggestions
            if (window.menuManager) {
                await window.menuManager.loadMenuData();
                const menuData = window.menuManager.menuData;
                
                // Create suggestions from menu items and categories
                const suggestions = new Set();
                
                menuData.forEach(item => {
                    suggestions.add(item.name);
                    suggestions.add(item.category);
                    
                    // Add words from description
                    const words = item.description.split(' ').filter(word => word.length > 3);
                    words.forEach(word => suggestions.add(word));
                });

                this.searchSuggestions = Array.from(suggestions).slice(0, 50);
            }
        } catch (error) {
            console.error('Failed to load search suggestions:', error);
        }
    }

    clearSearchHistory() {
        this.searchHistory = [];
        localStorage.removeItem('searchHistory');
    }
}

// Initialize all enhanced features
document.addEventListener('DOMContentLoaded', () => {
    // Initialize enhanced features
    window.enhancedFavorites = new FavoritesManager();
    window.enhancedCart = new EnhancedCartManager();
    window.enhancedToast = new EnhancedToastManager();
    window.enhancedSearch = new EnhancedSearchManager();

    // Override the original toast with enhanced version
    window.toast = window.enhancedToast;
    
    console.log('Enhanced features initialized successfully');
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        FavoritesManager,
        EnhancedCartManager,
        EnhancedToastManager,
        EnhancedSearchManager
    };
}