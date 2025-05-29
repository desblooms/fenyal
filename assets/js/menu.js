// assets/js/menu.js - Complete Bilingual Menu Data Handler
class MenuManager {
    constructor() {
        this.menuData = [];
        this.categories = [];
        this.categoriesAr = [];
        this.initialized = false;
        this.currentLanguage = localStorage.getItem('selectedLanguage') || 'en';
        this.apiEndpoint = '/admin/api_export.php'; 
       
    }

    async loadMenuData() {
        if (this.initialized) return this.menuData;
        
        try {
            console.log('Loading menu data...');
            
            // Try loading from admin API first
            let response;
            let data;
            
            try {
                response = await fetch(this.apiEndpoint);
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        data = result.data;
                        console.log('Menu data loaded from admin API');
                    } else {
                        throw new Error('Invalid API response format');
                    }
                } else {
                    throw new Error(`API returned ${response.status}`);
                }
            } catch (apiError) {
                console.warn('Admin API not available, falling back to static JSON:', apiError.message);
                
                // Fallback to static JSON file
                response = await fetch(this.fallbackEndpoint);
                if (!response.ok) {
                    throw new Error(`Failed to load fallback data: ${response.status} ${response.statusText}`);
                }
                data = await response.json();
                console.log('Menu data loaded from static JSON');
            }
            
            // Validate data format
            if (!Array.isArray(data)) {
                throw new Error('Menu data is not in the expected format (should be an array)');
            }
            
            this.menuData = data;
            this.initialized = true;
            
            // Extract unique categories for both languages
            const categorySet = new Set();
            const categoryArSet = new Set();
            
            this.menuData.forEach(item => {
                if (item.category) {
                    categorySet.add(item.category);
                }
                if (item.categoryAr) {
                    categoryArSet.add(item.categoryAr);
                }
            });
            
            this.categories = Array.from(categorySet);
            this.categoriesAr = Array.from(categoryArSet);
            
            console.log(`Menu loaded successfully: ${this.menuData.length} items, ${this.categories.length} categories`);
            console.log('Categories EN:', this.categories);
            console.log('Categories AR:', this.categoriesAr);
            
            return this.menuData;
            
        } catch (error) {
            console.error('Error loading menu data:', error);
            this.showLoadingError(error.message);
            return [];
        }
    }
    
    showLoadingError(errorMessage) {
        console.error('Menu loading error:', errorMessage);
        // This method can be called by UI components to show user-friendly errors
        if (window.toast && typeof window.toast.show === 'function') {
            window.toast.show('Failed to load menu data. Please refresh the page.', 'error');
        }
    }
    
    // Set current language
    setLanguage(language) {
        console.log('Setting menu language to:', language);
        this.currentLanguage = language;
        localStorage.setItem('selectedLanguage', language);
    }
    
    // Get current language
    getCurrentLanguage() {
        return this.currentLanguage;
    }
    
    // Get item by ID
    getItemById(id) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return null;
        }
        
        const itemId = parseInt(id);
        const item = this.menuData.find(item => item.id === itemId);
        
        if (!item) {
            console.warn(`Menu item with ID ${itemId} not found`);
        }
        
        return item;
    }
    
    // Get items by category
    getItemsByCategory(category) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        // Handle both English and Arabic category names
        return this.menuData.filter(item => {
            return item.category === category || item.categoryAr === category;
        });
    }
    
    // Get special items (marked as special)
    getSpecialItems() {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        return this.menuData.filter(item => item.isSpecial === true);
    }
    
    // Get popular items (marked as popular)
    getPopularItems() {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        return this.menuData.filter(item => item.isPopular === true);
    }
    
    // Get all categories based on current language
    getAllCategories() {
        if (this.currentLanguage === 'ar') {
            // For Arabic, return a mapped array of Arabic category names
            return this.categories.map(category => {
                // Find an item with this category to get the Arabic equivalent
                const item = this.menuData.find(item => item.category === category);
                return item?.categoryAr || category;
            });
        }
        return this.categories;
    }
    
    // Get localized item name
    getItemName(item) {
        if (!item) return '';
        
        if (this.currentLanguage === 'ar' && item.nameAr) {
            return item.nameAr;
        }
        return item.name || '';
    }
    
    // Get localized item description
    getItemDescription(item) {
        if (!item) return '';
        
        if (this.currentLanguage === 'ar' && item.descriptionAr) {
            return item.descriptionAr;
        }
        return item.description || '';
    }
    
    // Get localized category name
    getItemCategory(item) {
        if (!item) return '';
        
        if (this.currentLanguage === 'ar' && item.categoryAr) {
            return item.categoryAr;
        }
        return item.category || '';
    }
    
    // Get localized addon name
    getAddonName(addon) {
        if (!addon) return '';
        
        if (this.currentLanguage === 'ar' && addon.nameAr) {
            return addon.nameAr;
        }
        return addon.name || '';
    }
    
    // Get localized spice level name
    getSpiceLevelName(spiceLevel) {
        if (!spiceLevel) return '';
        
        if (this.currentLanguage === 'ar' && spiceLevel.nameAr) {
            return spiceLevel.nameAr;
        }
        return spiceLevel.name || '';
    }
    
    // Search items with bilingual support
    searchItems(searchTerm) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        if (!searchTerm || typeof searchTerm !== 'string') {
            return this.menuData;
        }
        
        const term = searchTerm.toLowerCase().trim();
        
        if (term === '') {
            return this.menuData;
        }
        
        return this.menuData.filter(item => {
            // Search in current language
            const name = this.getItemName(item).toLowerCase();
            const description = this.getItemDescription(item).toLowerCase();
            const category = this.getItemCategory(item).toLowerCase();
            
            // Also search in both English and Arabic regardless of current language
            const nameEn = (item.name || '').toLowerCase();
            const descriptionEn = (item.description || '').toLowerCase();
            const categoryEn = (item.category || '').toLowerCase();
            
            const nameAr = (item.nameAr || '').toLowerCase();
            const descriptionAr = (item.descriptionAr || '').toLowerCase();
            const categoryAr = (item.categoryAr || '').toLowerCase();
            
            return (
                name.includes(term) ||
                description.includes(term) ||
                category.includes(term) ||
                nameEn.includes(term) ||
                descriptionEn.includes(term) ||
                categoryEn.includes(term) ||
                nameAr.includes(term) ||
                descriptionAr.includes(term) ||
                categoryAr.includes(term)
            );
        });
    }
    
    // Filter items by multiple criteria
    filterItems(filters = {}) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        let filteredItems = [...this.menuData];
        
        // Filter by category
        if (filters.category) {
            filteredItems = filteredItems.filter(item => 
                item.category === filters.category || item.categoryAr === filters.category
            );
        }
        
        // Filter by popular
        if (filters.popular === true) {
            filteredItems = filteredItems.filter(item => item.isPopular === true);
        }
        
        // Filter by special
        if (filters.special === true) {
            filteredItems = filteredItems.filter(item => item.isSpecial === true);
        }
        
        // Filter by price range
        if (filters.minPrice !== undefined) {
            filteredItems = filteredItems.filter(item => item.price >= filters.minPrice);
        }
        
        if (filters.maxPrice !== undefined) {
            filteredItems = filteredItems.filter(item => item.price <= filters.maxPrice);
        }
        
        // Search filter
        if (filters.search) {
            filteredItems = this.searchItems(filters.search);
        }
        
        return filteredItems;
    }
    
    // Get price for item (considering half/full options)
    getItemPrice(item, size = 'default') {
        if (!item) return 0;
        
        if (item.isHalfFull) {
            switch (size) {
                case 'half':
                    return item.halfPrice || item.price;
                case 'full':
                    return item.fullPrice || item.price;
                default:
                    return item.halfPrice || item.price; // Default to half price
            }
        }
        
        return item.price || 0;
    }
    
    // Check if item has half/full options
    hasHalfFullOptions(item) {
        return item && item.isHalfFull === true;
    }
    
    // Check if item has add-ons
    hasAddons(item) {
        return item && Array.isArray(item.addons) && item.addons.length > 0;
    }
    
    // Check if item has spice level options
    hasSpiceLevelOptions(item) {
        return item && Array.isArray(item.spiceLevelOptions) && item.spiceLevelOptions.length > 0;
    }
    
    // Get category translation mapping
    getCategoryTranslation(category, targetLanguage = null) {
        const lang = targetLanguage || this.currentLanguage;
        
        // Find an item with this category
        const item = this.menuData.find(item => 
            item.category === category || item.categoryAr === category
        );
        
        if (!item) return category;
        
        if (lang === 'ar') {
            return item.categoryAr || item.category;
        } else {
            return item.category || category;
        }
    }
    
    // Translate category name based on mapping
    translateCategoryName(categoryName) {
        // Predefined category mappings
        const categoryMap = {
            'en': {
                'Breakfast': 'Breakfast',
                'Dishes': 'Dishes',
                'Bread': 'Bread',
                'Desserts': 'Desserts',
                'Cold Drinks': 'Cold Drinks',
                'Hot Drinks': 'Hot Drinks',
                // Arabic to English
                'فطور': 'Breakfast',
                'أطباق': 'Dishes',
                'خبز': 'Bread',
                'حلويات': 'Desserts',
                'مشروبات باردة': 'Cold Drinks',
                'مشروبات ساخنة': 'Hot Drinks'
            },
            'ar': {
                'Breakfast': 'فطور',
                'Dishes': 'أطباق',
                'Bread': 'خبز',
                'Desserts': 'حلويات',
                'Cold Drinks': 'مشروبات باردة',
                'Hot Drinks': 'مشروبات ساخنة',
                // Arabic to Arabic (identity)
                'فطور': 'فطور',
                'أطباق': 'أطباق',
                'خبز': 'خبز',
                'حلويات': 'حلويات',
                'مشروبات باردة': 'مشروبات باردة',
                'مشروبات ساخنة': 'مشروبات ساخنة'
            }
        };
        
        const mapping = categoryMap[this.currentLanguage];
        return mapping?.[categoryName] || categoryName;
    }
    
    // Get statistics
    getStatistics() {
        if (!this.initialized) {
            return {
                totalItems: 0,
                popularItems: 0,
                specialItems: 0,
                categories: 0,
                categoriesBreakdown: {}
            };
        }
        
        const categoriesBreakdown = {};
        this.menuData.forEach(item => {
            const category = this.getItemCategory(item);
            categoriesBreakdown[category] = (categoriesBreakdown[category] || 0) + 1;
        });
        
        return {
            totalItems: this.menuData.length,
            popularItems: this.menuData.filter(item => item.isPopular).length,
            specialItems: this.menuData.filter(item => item.isSpecial).length,
            categories: this.categories.length,
            categoriesBreakdown
        };
    }
    
    // Refresh menu data (useful for admin updates)
    async refreshMenuData() {
        this.initialized = false;
        this.menuData = [];
        this.categories = [];
        this.categoriesAr = [];
        
        return await this.loadMenuData();
    }
    
    // Debug helper
    debugInfo() {
        return {
            initialized: this.initialized,
            currentLanguage: this.currentLanguage,
            totalItems: this.menuData.length,
            categories: this.categories,
            categoriesAr: this.categoriesAr,
            apiEndpoint: this.apiEndpoint,
            fallbackEndpoint: this.fallbackEndpoint
        };
    }
}

// Initialize and export singleton instance
const menuManager = new MenuManager();

// Make it globally available
window.menuManager = menuManager;

export default menuManager;

// Helper function to format price in Qatari Riyals with Arabic support
export function formatPrice(price, language = null) {
    if (typeof price !== 'number' || isNaN(price)) {
        price = 0;
    }
    
    const lang = language || localStorage.getItem('selectedLanguage') || 'en';
    
    if (lang === 'ar') {
        return price.toFixed(0) + ' QAR';
    }
    return 'QAR ' + price.toFixed(0);
}

// Translation system
export const translations = {
    en: {
        // Navigation & UI
        home: 'Home',
        menu: 'Menu',
        popular: 'Popular Items',
        viewAll: 'View all',
        allMenu: 'All Menu',
        
        // Search & Filter
        searchPlaceholder: 'Search menu items...',
        noItemsFound: 'No items found',
        tryDifferentSearch: 'Try a different search or category',
        resetSearch: 'Reset Search',
        searchResults: 'Search Results',
        
        // Item Details
        selectSize: 'Select Size',
        half: 'Half',
        full: 'Full',
        spiceLevel: 'Spice Level',
        addons: 'Add-ons',
        price: 'Price',
        
        // Spice Levels
        mild: 'Mild',
        medium: 'Medium',
        spicy: 'Spicy',
        
        // Common Add-ons
        extraMozzarella: 'Extra Mozzarella',
        avocadoSlice: 'Avocado Slice',
        extraOliveOil: 'Extra Olive Oil',
        extraCardamom: 'Extra Cardamom',
        saffron: 'Saffron',
        
        // Categories
        breakfast: 'Breakfast',
        dishes: 'Dishes',
        bread: 'Bread',
        desserts: 'Desserts',
        coldDrinks: 'Cold Drinks',
        hotDrinks: 'Hot Drinks',
        
        // Status Messages
        loading: 'Loading...',
        error: 'Error loading data',
        retry: 'Try Again',
        
        // Actions
        addToCart: 'Add to Cart',
        order: 'Order',
        back: 'Back',
        close: 'Close'
    },
    ar: {
        // Navigation & UI
        home: 'الرئيسية',
        menu: 'القائمة',
        popular: 'الأصناف الشائعة',
        viewAll: 'عرض الكل',
        allMenu: 'جميع الأصناف',
        
        // Search & Filter
        searchPlaceholder: 'البحث في أصناف القائمة...',
        noItemsFound: 'لم يتم العثور على أصناف',
        tryDifferentSearch: 'جرب بحثاً أو فئة مختلفة',
        resetSearch: 'إعادة تعيين البحث',
        searchResults: 'نتائج البحث',
        
        // Item Details
        selectSize: 'اختر الحجم',
        half: 'نصف',
        full: 'كامل',
        spiceLevel: 'مستوى الحرارة',
        addons: 'الإضافات',
        price: 'السعر',
        
        // Spice Levels
        mild: 'خفيف',
        medium: 'متوسط',
        spicy: 'حار',
        
        // Common Add-ons
        extraMozzarella: 'موزاريلا إضافية',
        avocadoSlice: 'شريحة أفوكادو',
        extraOliveOil: 'زيت زيتون إضافي',
        extraCardamom: 'هيل إضافي',
        saffron: 'زعفران',
        
        // Categories
        breakfast: 'فطور',
        dishes: 'أطباق',
        bread: 'خبز',
        desserts: 'حلويات',
        coldDrinks: 'مشروبات باردة',
        hotDrinks: 'مشروبات ساخنة',
        
        // Status Messages
        loading: 'جاري التحميل...',
        error: 'خطأ في تحميل البيانات',
        retry: 'حاول مرة أخرى',
        
        // Actions
        addToCart: 'أضف إلى السلة',
        order: 'اطلب',
        back: 'رجوع',
        close: 'إغلاق'
    }
};

// Get translation helper function
export function getTranslation(key, language = null) {
    const lang = language || localStorage.getItem('selectedLanguage') || 'en';
    return translations[lang]?.[key] || translations.en[key] || key;
}

// Additional utility functions
export function isRTL(language = null) {
    const lang = language || localStorage.getItem('selectedLanguage') || 'en';
    return lang === 'ar';
}

export function getTextDirection(language = null) {
    return isRTL(language) ? 'rtl' : 'ltr';
}

// Price comparison helper
export function comparePrices(item1, item2, size = 'default') {
    const price1 = menuManager.getItemPrice(item1, size);
    const price2 = menuManager.getItemPrice(item2, size);
    return price1 - price2;
}

// Category sorting helper
export function sortItemsByCategory(items) {
    const categoryOrder = [
        'Breakfast', 'فطور',
        'Dishes', 'أطباق', 
        'Bread', 'خبز',
        'Desserts', 'حلويات',
        'Cold Drinks', 'مشروبات باردة',
        'Hot Drinks', 'مشروبات ساخنة'
    ];
    
    return items.sort((a, b) => {
        const categoryA = menuManager.getItemCategory(a);
        const categoryB = menuManager.getItemCategory(b);
        
        const indexA = categoryOrder.indexOf(categoryA);
        const indexB = categoryOrder.indexOf(categoryB);
        
        // If category not found in order, put it at the end
        const orderA = indexA === -1 ? categoryOrder.length : indexA;
        const orderB = indexB === -1 ? categoryOrder.length : indexB;
        
        return orderA - orderB;
    });
}

// Console log for debugging
console.log('Menu.js loaded successfully with bilingual support');