// Menu Data Handler
class MenuManager {
    constructor() {
        this.menuData = [];
        this.categories = [];
        this.initialized = false;
    }

    async loadMenuData() {
        if (this.initialized) return this.menuData;
        
        try {
            const response = await fetch('data/menu.json');
            if (!response.ok) {
                throw new Error('Failed to load menu data');
            }
            
            this.menuData = await response.json();
            this.initialized = true;
            
            // Extract unique categories
            const categorySet = new Set();
            this.menuData.forEach(item => categorySet.add(item.category));
            this.categories = Array.from(categorySet);
            
            return this.menuData;
        } catch (error) {
            console.error('Error loading menu data:', error);
            return [];
        }
    }
    
    getItemById(id) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return null;
        }
        
        return this.menuData.find(item => item.id === parseInt(id));
    }
    
    getItemsByCategory(category) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        return this.menuData.filter(item => item.category === category);
    }
    
    getSpecialItems() {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        return this.menuData.filter(item => item.isSpecial);
    }
    
    getPopularItems() {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        return this.menuData.filter(item => item.isPopular);
    }
    
    getAllCategories() {
        return this.categories;
    }
}

// Initialize and export singleton instance
const menuManager = new MenuManager();
export default menuManager;

// Helper function to format price in Indian Rupees
export function formatPrice(price) {
    return 'QAR ' + price.toFixed(0);
}

// Cart functionality
class CartManager {
    constructor() {
        this.cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
    }
    
    getCartItems() {
        return this.cartItems;
    }
    
    addToCart(item, quantity = 1, variants = {}) {
        const existingItemIndex = this.cartItems.findIndex(
            cartItem => cartItem.id === item.id && 
                        JSON.stringify(cartItem.variants) === JSON.stringify(variants)
        );
        
        if (existingItemIndex >= 0) {
            this.cartItems[existingItemIndex].quantity += quantity;
        } else {
            this.cartItems.push({
                ...item,
                quantity,
                variants
            });
        }
        
        this.saveCart();
        this.updateCartBadge();
        return this.cartItems;
    }
    
    removeFromCart(index) {
        if (index >= 0 && index < this.cartItems.length) {
            this.cartItems.splice(index, 1);
            this.saveCart();
            this.updateCartBadge();
        }
        return this.cartItems;
    }
    
    updateQuantity(index, quantity) {
        if (index >= 0 && index < this.cartItems.length) {
            if (quantity <= 0) {
                this.removeFromCart(index);
            } else {
                this.cartItems[index].quantity = quantity;
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
        localStorage.setItem('cartItems', JSON.stringify(this.cartItems));
    }
    
    getCartTotal() {
        return this.cartItems.reduce((total, item) => {
            let itemPrice = 0;
            
            // Handle half/full pricing
            if (item.variants && item.variants.size === 'half') {
                itemPrice = item.halfPrice;
            } else if (item.variants && item.variants.size === 'full') {
                itemPrice = item.fullPrice;
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
        const cartBadges = document.querySelectorAll('#cart-badge');
        const itemCount = this.getItemCount();
        
        cartBadges.forEach(badge => {
            badge.textContent = itemCount;
            badge.style.display = itemCount > 0 ? 'flex' : 'none';
        });
    }
}

// Initialize and export singleton cart instance
export const cartManager = new CartManager();

// Update cart badge on page load
document.addEventListener('DOMContentLoaded', function() {
    cartManager.updateCartBadge();
});



// Add language-aware helper functions
export function getItemName(item, language = 'en') {
    return language === 'ar' && item.nameAr ? item.nameAr : item.name;
}

export function getItemDescription(item, language = 'en') {
    return language === 'ar' && item.descriptionAr ? item.descriptionAr : item.description;
}

export function getCategoryName(item, language = 'en') {
    return language === 'ar' && item.categoryAr ? item.categoryAr : item.category;
}