// Enhanced Menu Data Handler with Caching and Performance Improvements
class MenuManager {
    constructor() {
        this.menuData = [];
        this.categories = [];
        this.initialized = false;
        this.cache = new Map();
        this.cacheExpiry = 30 * 60 * 1000; // 30 minutes
        this.lastFetch = null;
    }

    async loadMenuData() {
        if (this.initialized && this.isCacheValid()) {
            return this.menuData;
        }
        
        try {
            // Check if we have cached data
            const cachedData = this.getCachedData();
            if (cachedData && this.isCacheValid()) {
                this.menuData = cachedData.data;
                this.categories = cachedData.categories;
                this.initialized = true;
                return this.menuData;
            }

            // Show loading indicator
            this.showLoadingIndicator();

            const response = await fetch('data/menu.json');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            this.menuData = await response.json();
            this.initialized = true;
            this.lastFetch = Date.now();
            
            // Extract unique categories with sorting
            const categorySet = new Set();
            this.menuData.forEach(item => categorySet.add(item.category));
            this.categories = Array.from(categorySet).sort();
            
            // Cache the data
            this.setCachedData({
                data: this.menuData,
                categories: this.categories,
                timestamp: this.lastFetch
            });

            // Hide loading indicator
            this.hideLoadingIndicator();
            
            // Preload images for better performance
            this.preloadImages();
            
            return this.menuData;
        } catch (error) {
            console.error('Error loading menu data:', error);
            this.hideLoadingIndicator();
            
            // Try to use cached data even if expired
            const cachedData = this.getCachedData();
            if (cachedData) {
                console.warn('Using expired cached data due to network error');
                this.menuData = cachedData.data;
                this.categories = cachedData.categories;
                this.initialized = true;
                return this.menuData;
            }
            
            // Show error to user
            this.showErrorMessage('Failed to load menu. Please check your connection and try again.');
            return [];
        }
    }

    getCachedData() {
        try {
            const cached = localStorage.getItem('menuData');
            return cached ? JSON.parse(cached) : null;
        } catch (error) {
            console.error('Error reading cached data:', error);
            return null;
        }
    }

    setCachedData(data) {
        try {
            localStorage.setItem('menuData', JSON.stringify(data));
        } catch (error) {
            console.error('Error caching data:', error);
        }
    }

    isCacheValid() {
        const cachedData = this.getCachedData();
        if (!cachedData || !cachedData.timestamp) return false;
        
        const now = Date.now();
        return (now - cachedData.timestamp) < this.cacheExpiry;
    }

    showLoadingIndicator() {
        // Remove existing indicators
        document.querySelectorAll('.menu-loading-indicator').forEach(el => el.remove());
        
        const indicator = document.createElement('div');
        indicator.className = 'menu-loading-indicator fixed top-0 left-0 right-0 bg-primary text-white text-center py-2 text-sm z-50';
        indicator.innerHTML = '📱 Loading delicious menu items...';
        document.body.appendChild(indicator);
    }

    hideLoadingIndicator() {
        document.querySelectorAll('.menu-loading-indicator').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        });
    }

    showErrorMessage(message) {
        if (window.toast) {
            window.toast.error(message);
        } else {
            alert(message);
        }
    }

    preloadImages() {
        // Preload first 6 images for faster display
        const imagesToPreload = this.menuData.slice(0, 6);
        imagesToPreload.forEach(item => {
            const img = new Image();
            img.src = item.image;
        });
    }
    
    getItemById(id) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return null;
        }
        
        // Use cache for faster lookups
        const cacheKey = `item_${id}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        const item = this.menuData.find(item => item.id === parseInt(id));
        if (item) {
            this.cache.set(cacheKey, item);
        }
        
        return item;
    }
    
    getItemsByCategory(category) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        const cacheKey = `category_${category}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        const items = this.menuData.filter(item => item.category === category);
        this.cache.set(cacheKey, items);
        
        return items;
    }
    
    getSpecialItems() {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        if (this.cache.has('special_items')) {
            return this.cache.get('special_items');
        }
        
        const items = this.menuData.filter(item => item.isSpecial);
        this.cache.set('special_items', items);
        
        return items;
    }
    
    getPopularItems() {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        if (this.cache.has('popular_items')) {
            return this.cache.get('popular_items');
        }
        
        const items = this.menuData
            .filter(item => item.isPopular)
            .sort((a, b) => (b.rating || 0) - (a.rating || 0)); // Sort by rating if available
        
        this.cache.set('popular_items', items);
        
        return items;
    }
    
    getAllCategories() {
        return this.categories;
    }

    // Enhanced search functionality
    searchItems(query, options = {}) {
        if (!this.initialized || !query.trim()) {
            return [];
        }

        const searchTerm = query.toLowerCase().trim();
        const {
            includeCategory = true,
            includeDescription = true,
            includeName = true,
            fuzzyMatch = false
        } = options;

        let results = this.menuData.filter(item => {
            let matches = false;
            
            if (includeName && item.name.toLowerCase().includes(searchTerm)) {
                matches = true;
            }
            
            if (includeDescription && item.description.toLowerCase().includes(searchTerm)) {
                matches = true;
            }
            
            if (includeCategory && item.category.toLowerCase().includes(searchTerm)) {
                matches = true;
            }

            // Search in Arabic names/descriptions if available
            if (item.nameAr && item.nameAr.includes(searchTerm)) {
                matches = true;
            }
            
            if (item.descriptionAr && item.descriptionAr.includes(searchTerm)) {
                matches = true;
            }

            return matches;
        });

        // Sort results by relevance (name matches first, then description, then category)
        results.sort((a, b) => {
            const aNameMatch = a.name.toLowerCase().includes(searchTerm);
            const bNameMatch = b.name.toLowerCase().includes(searchTerm);
            
            if (aNameMatch && !bNameMatch) return -1;
            if (!aNameMatch && bNameMatch) return 1;
            
            // If both or neither match names, sort by popularity
            return (b.isPopular ? 1 : 0) - (a.isPopular ? 1 : 0);
        });

        return results;
    }

    // Get dietary information
    getDietaryInfo(item) {
        const dietary = [];
        
        // Simple dietary detection based on ingredients/category
        if (item.category === 'Desserts') {
            dietary.push('Sweet');
        }
        
        if (item.name.toLowerCase().includes('chicken') || 
            item.description.toLowerCase().includes('chicken')) {
            dietary.push('Chicken');
        }
        
        if (item.name.toLowerCase().includes('beef') || 
            item.description.toLowerCase().includes('beef')) {
            dietary.push('Beef');  
        }
        
        if (item.spiceLevelOptions && item.spiceLevelOptions.length > 0) {
            dietary.push('Spicy Options');
        }

        return dietary;
    }

    // Clear cache
    clearCache() {
        this.cache.clear();
        localStorage.removeItem('menuData');
        console.log('Menu cache cleared');
    }

    // Get statistics
    getStatistics() {
        if (!this.initialized) return null;

        return {
            totalItems: this.menuData.length,
            categoriesCount: this.categories.length,
            popularItemsCount: this.menuData.filter(item => item.isPopular).length,
            specialItemsCount: this.menuData.filter(item => item.isSpecial).length,
            averagePrice: this.menuData.reduce((sum, item) => sum + item.price, 0) / this.menuData.length,
            priceRange: {
                min: Math.min(...this.menuData.map(item => item.price)),
                max: Math.max(...this.menuData.map(item => item.price))
            }
        };
    }
}

// Initialize and export singleton instance
const menuManager = new MenuManager();
export default menuManager;

// Enhanced price formatting with currency conversion support
export function formatPrice(price, currency = 'QAR') {
    const formatted = price.toFixed(0);
    
    // Add currency symbol based on type
    switch (currency) {
        case 'QAR':
            return `QAR ${formatted}`;
        case 'USD':
            return `${formatted}`;
        case 'EUR':
            return `€${formatted}`;
        default:
            return `${currency} ${formatted}`;
    }
}

// Enhanced Cart functionality with persistence and sync
class CartManager {
    constructor() {
        this.cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
        this.cartHistory = JSON.parse(localStorage.getItem('cartHistory')) || [];
        this.isOnline = navigator.onLine;
        this.observers = [];
        this.maxHistoryItems = 50;
        
        this.init();
    }

    init() {
        // Listen for online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncCart();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });

        // Auto-save cart periodically
        setInterval(() => {
            this.saveCart();
        }, 30000); // Every 30 seconds

        // Save cart before page unload
        window.addEventListener('beforeunload', () => {
            this.saveCart();
        });

        // Initial cart update
        this.updateCartBadge();
    }

    // Observer pattern for cart changes
    addObserver(callback) {
        this.observers.push(callback);
    }

    removeObserver(callback) {
        this.observers = this.observers.filter(obs => obs !== callback);
    }

    notifyObservers(event, data) {
        this.observers.forEach(callback => {
            try {
                callback(event, data);
            } catch (error) {
                console.error('Error in cart observer:', error);
            }
        });
    }
    
    getCartItems() {
        return this.cartItems;
    }
    
    addToCart(item, quantity = 1, variants = {}) {
        const existingItemIndex = this.findExistingItem(item.id, variants);
        
        if (existingItemIndex >= 0) {
            this.cartItems[existingItemIndex].quantity += quantity;
            this.cartItems[existingItemIndex].updatedAt = new Date().toISOString();
        } else {
            const cartItem = {
                ...item,
                quantity,
                variants,
                addedAt: new Date().toISOString(),
                cartId: this.generateCartId()
            };
            this.cartItems.push(cartItem);
        }
        
        this.saveCart();
        this.updateCartBadge();
        this.addToHistory('add', item, quantity);
        this.notifyObservers('itemAdded', { item, quantity, variants });
        
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
            this.addToHistory('remove', removedItem.item || removedItem, removedItem.quantity);
            this.notifyObservers('itemRemoved', { item: removedItem, index });
        }
        return this.cartItems;
    }
    
    updateQuantity(index, quantity) {
        if (index >= 0 && index < this.cartItems.length) {
            const oldQuantity = this.cartItems[index].quantity;
            
            if (quantity <= 0) {
                this.removeFromCart(index);
            } else {
                this.cartItems[index].quantity = quantity;
                this.cartItems[index].updatedAt = new Date().toISOString();
                this.saveCart();
                this.notifyObservers('quantityUpdated', { 
                    index, 
                    oldQuantity, 
                    newQuantity: quantity 
                });
            }
        }
        return this.cartItems;
    }
    
    clearCart() {
        const oldItems = [...this.cartItems];
        this.cartItems = [];
        this.saveCart();
        this.updateCartBadge();
        this.addToHistory('clear', null, 0);
        this.notifyObservers('cartCleared', { previousItems: oldItems });
    }
    
    saveCart() {
        try {
            localStorage.setItem('cartItems', JSON.stringify(this.cartItems));
            localStorage.setItem('cartLastSaved', new Date().toISOString());
        } catch (error) {
            console.error('Failed to save cart:', error);
            
            // Try to free up space by removing old history
            if (error.name === 'QuotaExceededError') {
                this.cleanupHistory();
                // Try saving again
                try {
                    localStorage.setItem('cartItems', JSON.stringify(this.cartItems));
                } catch (secondError) {
                    console.error('Failed to save cart even after cleanup:', secondError);
                }
            }
        }
    }

    addToHistory(action, item, quantity) {
        const historyEntry = {
            action,
            item: item ? {
                id: item.id,
                name: item.name,
                price: item.price
            } : null,
            quantity,
            timestamp: new Date().toISOString()
        };

        this.cartHistory.unshift(historyEntry);
        
        // Keep only recent history
        if (this.cartHistory.length > this.maxHistoryItems) {
            this.cartHistory = this.cartHistory.slice(0, this.maxHistoryItems);
        }

        localStorage.setItem('cartHistory', JSON.stringify(this.cartHistory));
    }

    cleanupHistory() {
        this.cartHistory = this.cartHistory.slice(0, Math.floor(this.maxHistoryItems / 2));
        localStorage.setItem('cartHistory', JSON.stringify(this.cartHistory));
    }

    getCartHistory() {
        return this.cartHistory;
    }
    
    getCartTotal() {
        return this.cartItems.reduce((total, item) => {
            let itemPrice = 0;
            
            // Handle half/full pricing
            if (item.variants && item.variants.size === 'half') {
                itemPrice = item.halfPrice || item.price;
            } else if (item.variants && item.variants.size === 'full') {
                itemPrice = item.fullPrice || item.price;
            } else {
                itemPrice = item.price;
            }
            
            // Add addons cost
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
            
            // Add animation for count changes
            if (itemCount > 0) {
                badge.classList.add('animate-bounce');
                setTimeout(() => {
                    badge.classList.remove('animate-bounce');
                }, 1000);
            }
        });

        // Update page title with cart count
        if (itemCount > 0) {
            document.title = `(${itemCount}) Fenyal - Delicious Food`;
        } else {
            document.title = 'Fenyal - Delicious Food';
        }
    }

    // Sync cart with backend (placeholder for future implementation)
    async syncCart() {
        if (!this.isOnline) return;
        
        try {
            // This would sync with your backend API
            console.log('Syncing cart with server...');
            // const response = await fetch('/api/cart/sync', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(this.cartItems)
            // });
        } catch (error) {
            console.error('Failed to sync cart:', error);
        }
    }

    // Get cart summary for checkout
    getCartSummary() {
        const subtotal = this.getCartTotal();
        const tax = Math.round(subtotal * 0.05); // 5% tax
        const deliveryFee = subtotal > 100 ? 0 : 30; // Free delivery over QAR 100
        const total = subtotal + tax + deliveryFee;

        return {
            subtotal,
            tax,
            deliveryFee,
            total,
            itemCount: this.getItemCount(),
            items: this.cartItems
        };
    }

    // Import/Export cart (for sharing or backup)
    exportCart() {
        return {
            items: this.cartItems,
            timestamp: new Date().toISOString(),
            version: '1.0'
        };
    }

    importCart(cartData) {
        if (cartData && cartData.items && Array.isArray(cartData.items)) {
            this.cartItems = cartData.items;
            this.saveCart();
            this.updateCartBadge();
            this.notifyObservers('cartImported', { cartData });
            return true;
        }
        return false;
    }
}

// Initialize and export singleton cart instance
export const cartManager = new CartManager();

// Language-aware helper functions with caching
const translationCache = new Map();

export function getItemName(item, language = 'en') {
    const cacheKey = `name_${item.id}_${language}`;
    
    if (translationCache.has(cacheKey)) {
        return translationCache.get(cacheKey);
    }
    
    const name = language === 'ar' && item.nameAr ? item.nameAr : item.name;
    translationCache.set(cacheKey, name);
    
    return name;
}

export function getItemDescription(item, language = 'en') {
    const cacheKey = `desc_${item.id}_${language}`;
    
    if (translationCache.has(cacheKey)) {
        return translationCache.get(cacheKey);
    }
    
    const description = language === 'ar' && item.descriptionAr ? item.descriptionAr : item.description;
    translationCache.set(cacheKey, description);
    
    return description;
}

export function getCategoryName(item, language = 'en') {
    const cacheKey = `cat_${item.category}_${language}`;
    
    if (translationCache.has(cacheKey)) {
        return translationCache.get(cacheKey);
    }
    
    const category = language === 'ar' && item.categoryAr ? item.categoryAr : item.category;
    translationCache.set(cacheKey, category);
    
    return category;
}

// Update cart badge on page load and setup global error handling
document.addEventListener('DOMContentLoaded', function() {
    cartManager.updateCartBadge();
    
    // Global error handling for menu operations
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && event.reason.message && event.reason.message.includes('menu')) {
            console.error('Menu-related error:', event.reason);
            
            if (window.toast) {
                window.toast.error('Unable to load menu data. Please refresh the page.');
            }
        }
    });
});

// Make instances globally available
window.menuManager = menuManager;
window.cartManager = cartManager;