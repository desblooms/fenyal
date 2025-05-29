// Menu Data Handler with Bilingual Support
class MenuManager {
    constructor() {
        this.menuData = [];
        this.categories = [];
        this.categoriesAr = [];
        this.initialized = false;
        this.currentLanguage = localStorage.getItem('selectedLanguage') || 'en';
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
            
            // Extract unique categories for both languages
            const categorySet = new Set();
            const categoryArSet = new Set();
            
            this.menuData.forEach(item => {
                categorySet.add(item.category);
                if (item.categoryAr) {
                    categoryArSet.add(item.categoryAr);
                }
            });
            
            this.categories = Array.from(categorySet);
            this.categoriesAr = Array.from(categoryArSet);
            
            return this.menuData;
        } catch (error) {
            console.error('Error loading menu data:', error);
            return [];
        }
    }
    
    // Set current language
    setLanguage(language) {
        this.currentLanguage = language;
        localStorage.setItem('selectedLanguage', language);
    }
    
    // Get current language
    getCurrentLanguage() {
        return this.currentLanguage;
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
        
        // Handle both English and Arabic category names
        return this.menuData.filter(item => {
            if (this.currentLanguage === 'ar') {
                return item.categoryAr === category || item.category === category;
            } else {
                return item.category === category || item.categoryAr === category;
            }
        });
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
        if (this.currentLanguage === 'ar') {
            return this.categoriesAr;
        }
        return this.categories;
    }
    
    // Get localized item name
    getItemName(item) {
        if (this.currentLanguage === 'ar' && item.nameAr) {
            return item.nameAr;
        }
        return item.name;
    }
    
    // Get localized item description
    getItemDescription(item) {
        if (this.currentLanguage === 'ar' && item.descriptionAr) {
            return item.descriptionAr;
        }
        return item.description;
    }
    
    // Get localized category name
    getItemCategory(item) {
        if (this.currentLanguage === 'ar' && item.categoryAr) {
            return item.categoryAr;
        }
        return item.category;
    }
    
    // Get localized addon name
    getAddonName(addon) {
        if (this.currentLanguage === 'ar' && addon.nameAr) {
            return addon.nameAr;
        }
        return addon.name;
    }
    
    // Search items with bilingual support
    searchItems(searchTerm) {
        if (!this.initialized) {
            console.error('Menu data not initialized');
            return [];
        }
        
        const term = searchTerm.toLowerCase().trim();
        
        return this.menuData.filter(item => {
            const name = this.getItemName(item).toLowerCase();
            const description = this.getItemDescription(item).toLowerCase();
            const category = this.getItemCategory(item).toLowerCase();
            
            // Search in both English and Arabic if available
            const nameEn = item.name.toLowerCase();
            const descriptionEn = item.description.toLowerCase();
            const categoryEn = item.category.toLowerCase();
            
            const nameAr = item.nameAr ? item.nameAr.toLowerCase() : '';
            const descriptionAr = item.descriptionAr ? item.descriptionAr.toLowerCase() : '';
            const categoryAr = item.categoryAr ? item.categoryAr.toLowerCase() : '';
            
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
}

// Initialize and export singleton instance
const menuManager = new MenuManager();
export default menuManager;

// Helper function to format price in Qatari Riyals with Arabic support
export function formatPrice(price, language = null) {
    const lang = language || localStorage.getItem('selectedLanguage') || 'en';
    
    if (lang === 'ar') {
        return price.toFixed(0) + ' ريال قطري';
    }
    return 'QAR ' + price.toFixed(0);
}

// Translation helper functions
export const translations = {
    en: {
        home: 'Home',
        menu: 'Menu',
        popular: 'Popular Items',
        viewAll: 'View all',
        allMenu: 'All Menu',
        searchPlaceholder: 'Search menu items...',
        noItemsFound: 'No items found',
        tryDifferentSearch: 'Try a different search or category',
        resetSearch: 'Reset Search',
        searchResults: 'Search Results',
        selectSize: 'Select Size',
        half: 'Half',
        full: 'Full',
        spiceLevel: 'Spice Level',
        addons: 'Add-ons',
        price: 'Price',
        mild: 'Mild',
        medium: 'Medium',
        spicy: 'Spicy',
        extraMozzarella: 'Extra Mozzarella',
        avocadoSlice: 'Avocado Slice',
        extraOliveOil: 'Extra Olive Oil',
        extraCardamom: 'Extra Cardamom',
        saffron: 'Saffron'
    },
    ar: {
        home: 'الرئيسية',
        menu: 'القائمة',
        popular: 'الأصناف الشائعة',
        viewAll: 'عرض الكل',
        allMenu: 'جميع الأصناف',
        searchPlaceholder: 'البحث في أصناف القائمة...',
        noItemsFound: 'لم يتم العثور على أصناف',
        tryDifferentSearch: 'جرب بحثاً أو فئة مختلفة',
        resetSearch: 'إعادة تعيين البحث',
        searchResults: 'نتائج البحث',
        selectSize: 'اختر الحجم',
        half: 'نصف',
        full: 'كامل',
        spiceLevel: 'مستوى الحار',
        addons: 'الإضافات',
        price: 'السعر',
        mild: 'خفيف',
        medium: 'متوسط',
        spicy: 'حار',
        extraMozzarella: 'موزاريلا إضافية',
        avocadoSlice: 'شريحة أفوكادو',
        extraOliveOil: 'زيت زيتون إضافي',
        extraCardamom: 'هيل إضافي',
        saffron: 'زعفران'
    }
};

export function getTranslation(key, language = null) {
    const lang = language || localStorage.getItem('selectedLanguage') || 'en';
    return translations[lang][key] || translations.en[key] || key;
}