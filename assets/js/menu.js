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

// Helper function to format price in Qatari Riyals
export function formatPrice(price) {
    return 'QAR ' + price.toFixed(0);
}