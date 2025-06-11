// assets/js/improved-language-toggle.js - Enhanced bilingual support
class LanguageManager {
    constructor() {
        this.currentLanguage = this.getCurrentLanguage();
        this.supportedLanguages = ['en', 'ar'];
        this.isRTL = this.currentLanguage === 'ar';
        
        this.init();
    }
    
    init() {
        this.applyLanguageSettings();
        this.setupEventListeners();
        this.updatePageContent();
    }
    
    getCurrentLanguage() {
        // Priority: URL param > localStorage > Session > Default
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        
        if (urlLang && this.supportedLanguages.includes(urlLang)) {
            this.saveLanguage(urlLang);
            return urlLang;
        }
        
        return localStorage.getItem('selectedLanguage') || 
               sessionStorage.getItem('language') || 
               'en';
    }
    
    saveLanguage(lang) {
        localStorage.setItem('selectedLanguage', lang);
        sessionStorage.setItem('language', lang);
        this.currentLanguage = lang;
        this.isRTL = lang === 'ar';
    }
    
    applyLanguageSettings() {
        // Update document attributes
        document.documentElement.setAttribute('lang', this.currentLanguage);
        document.documentElement.setAttribute('dir', this.isRTL ? 'rtl' : 'ltr');
        
        // Update body class for styling
        document.body.classList.toggle('rtl', this.isRTL);
        document.body.classList.toggle('ltr', !this.isRTL);
        
        // Update font family
        if (this.isRTL) {
            document.body.style.fontFamily = '"Cairo", "Poppins", sans-serif';
        } else {
            document.body.style.fontFamily = '"Poppins", sans-serif';
        }
    }
    
    setupEventListeners() {
        // Language toggle buttons
        const toggleButtons = document.querySelectorAll('[data-language-toggle]');
        toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleLanguage();
            });
        });
        
        // Auto language links
        const langLinks = document.querySelectorAll('a[href*="lang="]');
        langLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const url = new URL(link.href);
                const lang = url.searchParams.get('lang');
                if (lang && this.supportedLanguages.includes(lang)) {
                    this.saveLanguage(lang);
                }
            });
        });
    }
    
    toggleLanguage() {
        const newLang = this.currentLanguage === 'en' ? 'ar' : 'en';
        this.switchLanguage(newLang);
    }
    
    switchLanguage(newLang) {
        if (!this.supportedLanguages.includes(newLang)) {
            console.warn('Unsupported language:', newLang);
            return;
        }
        
        this.saveLanguage(newLang);
        
        // Update current page URL
        const url = new URL(window.location.href);
        url.searchParams.set('lang', newLang);
        
        // Smooth transition
        document.body.style.opacity = '0.8';
        
        setTimeout(() => {
            window.location.href = url.toString();
        }, 150);
    }
    
    updatePageContent() {
        // Update language toggle displays
        this.updateLanguageToggles();
        
        // Update direction-dependent icons
        this.updateDirectionalIcons();
        
        // Update navigation if needed
        this.updateNavigation();
    }
    
    updateLanguageToggles() {
        const alternativeLang = this.currentLanguage === 'en' ? 'ar' : 'en';
        
        // Update toggle button text
        const toggleTexts = document.querySelectorAll('[data-language-display]');
        toggleTexts.forEach(element => {
            element.textContent = alternativeLang.toUpperCase();
        });
        
        // Update toggle links
        const toggleLinks = document.querySelectorAll('[data-language-toggle]');
        toggleLinks.forEach(link => {
            if (link.tagName === 'A') {
                const url = new URL(link.href, window.location.origin);
                url.searchParams.set('lang', alternativeLang);
                link.href = url.toString();
            }
        });
    }
    
    updateDirectionalIcons() {
        // Update arrow icons for RTL
        const arrows = document.querySelectorAll('[data-feather="arrow-left"], [data-feather="arrow-right"]');
        arrows.forEach(icon => {
            const isBackButton = icon.closest('a, button')?.classList.contains('back-button') ||
                                icon.closest('a')?.href?.includes('menu.php') ||
                                icon.closest('a')?.href?.includes('index.php');
            
            if (isBackButton) {
                icon.setAttribute('data-feather', this.isRTL ? 'arrow-right' : 'arrow-left');
            }
        });
        
        // Re-initialize feather icons if available
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    }
    
    updateNavigation() {
        // Update any navigation elements that need language-specific behavior
        const navItems = document.querySelectorAll('.nav-item, .category-btn');
        navItems.forEach(item => {
            // Add RTL class if needed
            if (this.isRTL) {
                item.classList.add('rtl');
            } else {
                item.classList.remove('rtl');
            }
        });
        
        // Update search input placeholder
        const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="earch"], input[type="search"]');
        searchInputs.forEach(input => {
            const placeholders = {
                'en': 'Search menu items...',
                'ar': 'البحث في عناصر القائمة...'
            };
            input.placeholder = placeholders[this.currentLanguage] || placeholders['en'];
            input.dir = this.isRTL ? 'rtl' : 'ltr';
        });
    }
    
    // Translation helper
    translate(key, fallback = null) {
        const translations = {
            'en': {
                'home': 'Home',
                'menu': 'Menu',
                'popular_items': 'Popular Items',
                'view_all': 'View all',
                'categories': 'Categories',
                'search_placeholder': 'Search menu items...',
                'no_items_found': 'No items found',
                'try_different_search': 'Try a different search or category',
                'reset_search': 'Reset Search',
                'all': 'All',
                'popular': 'Popular',
                'special': 'Special',
                'select_size': 'Select Size',
                'half': 'Half',
                'full': 'Full',
                'spice_level': 'Spice Level',
                'addons': 'Add-ons',
                'price': 'Price',
                'add_to_cart': 'Add to Cart',
                'order_now': 'Order Now',
                'back_to_menu': 'Back to Menu',
                'loading': 'Loading...',
                'error': 'Error',
                'success': 'Success'
            },
            'ar': {
                'home': 'الرئيسية',
                'menu': 'القائمة',
                'popular_items': 'الأصناف الشائعة',
                'view_all': 'عرض الكل',
                'categories': 'الفئات',
                'search_placeholder': 'البحث في عناصر القائمة...',
                'no_items_found': 'لم يتم العثور على أصناف',
                'try_different_search': 'جرب بحثاً أو فئة مختلفة',
                'reset_search': 'إعادة تعيين البحث',
                'all': 'الكل',
                'popular': 'شائع',
                'special': 'مميز',
                'select_size': 'اختر الحجم',
                'half': 'نصف',
                'full': 'كامل',
                'spice_level': 'مستوى الحرارة',
                'addons': 'الإضافات',
                'price': 'السعر',
                'add_to_cart': 'أضف إلى السلة',
                'order_now': 'اطلب الآن',
                'back_to_menu': 'العودة للقائمة',
                'loading': 'جاري التحميل...',
                'error': 'خطأ',
                'success': 'نجح'
            }
        };
        
        return translations[this.currentLanguage]?.[key] || 
               translations['en']?.[key] || 
               fallback || 
               key;
    }
    
    // Format price based on language
    formatPrice(price) {
        const numPrice = parseFloat(price) || 0;
        
        if (this.isRTL) {
            return numPrice.toFixed(0) + ' ريال قطري';
        }
        return 'QAR ' + numPrice.toFixed(0);
    }
    
    // Get localized text from data attributes
    getLocalizedText(element) {
        if (this.isRTL && element.dataset.textAr) {
            return element.dataset.textAr;
        }
        return element.dataset.text || element.textContent;
    }
    
    // Update dynamic content
    updateDynamicContent() {
        // Update elements with data-translate attribute
        const translatableElements = document.querySelectorAll('[data-translate]');
        translatableElements.forEach(element => {
            const key = element.getAttribute('data-translate');
            element.textContent = this.translate(key);
        });
        
        // Update elements with data-text and data-text-ar attributes
        const bilingualElements = document.querySelectorAll('[data-text]');
        bilingualElements.forEach(element => {
            element.textContent = this.getLocalizedText(element);
        });
        
        // Update price displays
        const priceElements = document.querySelectorAll('[data-price]');
        priceElements.forEach(element => {
            const price = element.getAttribute('data-price');
            element.textContent = this.formatPrice(price);
        });
    }
    
    // Utility methods
    isCurrentLanguage(lang) {
        return this.currentLanguage === lang;
    }
    
    getAlternativeLanguage() {
        return this.currentLanguage === 'en' ? 'ar' : 'en';
    }
    
    getDirection() {
        return this.isRTL ? 'rtl' : 'ltr';
    }
    
    // Build URL with current language
    buildUrl(path, params = {}) {
        const url = new URL(path, window.location.origin);
        url.searchParams.set('lang', this.currentLanguage);
        
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                url.searchParams.set(key, value);
            }
        });
        
        return url.toString();
    }
    
    // Show language change notification
    showLanguageChangeNotification() {
        const langName = this.currentLanguage === 'ar' ? 'العربية' : 'English';
        const message = this.isRTL ? `اللغة: ${langName}` : `Language: ${langName}`;
        
        // Use app's toast system if available
        if (window.toast && typeof window.toast.show === 'function') {
            window.toast.show(message, 'success');
            return;
        }
        
        // Fallback notification
        const notification = document.createElement('div');
        notification.className = `fixed top-20 left-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg text-center text-sm z-50 transition-all duration-300 transform translate-y-[-20px] opacity-0`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateY(0)';
            notification.style.opacity = '1';
        }, 10);
        
        // Remove after delay
        setTimeout(() => {
            notification.style.transform = 'translateY(-20px)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Debug info
    getDebugInfo() {
        return {
            currentLanguage: this.currentLanguage,
            isRTL: this.isRTL,
            direction: this.getDirection(),
            alternativeLanguage: this.getAlternativeLanguage(),
            supportedLanguages: this.supportedLanguages,
            url: window.location.href
        };
    }
}

// Auto-initialize when DOM is ready
let languageManager;

document.addEventListener('DOMContentLoaded', function() {
    languageManager = new LanguageManager();
    
    // Make it globally available
    window.languageManager = languageManager;
    
    // Helper functions for global use
    window.__ = function(key, fallback = null) {
        return languageManager.translate(key, fallback);
    };
    
    window.formatPrice = function(price) {
        return languageManager.formatPrice(price);
    };
    
    window.isRTL = function() {
        return languageManager.isRTL;
    };
    
    window.getCurrentLanguage = function() {
        return languageManager.currentLanguage;
    };
    
    window.switchLanguage = function(lang) {
        return languageManager.switchLanguage(lang);
    };
    
    window.buildLanguageUrl = function(path, params = {}) {
        return languageManager.buildUrl(path, params);
    };
    
    // Auto-update content when language changes
    document.addEventListener('languageChanged', function() {
        languageManager.updateDynamicContent();
    });
    
    // Console debug info
    console.log('Language Manager initialized:', languageManager.getDebugInfo());
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageManager;
}

// Update existing language toggle functionality to work with new system
document.addEventListener('DOMContentLoaded', function() {
    // Handle existing language toggle elements
    const existingToggles = document.querySelectorAll('#language-toggle, .language-toggle');
    existingToggles.forEach(toggle => {
        // Remove existing event listeners and add new ones
        toggle.removeEventListener('click', toggle.onclick);
        toggle.onclick = null;
        
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.languageManager) {
                window.languageManager.toggleLanguage();
            }
        });
    });
    
    // Update language display elements
    const langDisplays = document.querySelectorAll('#current-language, .current-language');
    langDisplays.forEach(display => {
        if (window.languageManager) {
            display.textContent = window.languageManager.getAlternativeLanguage().toUpperCase();
        }
    });
    
    // Handle language-specific URLs on page load
    setTimeout(() => {
        if (window.languageManager) {
            languageManager.updateDynamicContent();
        }
    }, 100);
});

// Handle dynamic content updates for SPA-like behavior
function updateLanguageContent() {
    if (window.languageManager) {
        window.languageManager.updateDynamicContent();
        window.languageManager.updatePageContent();
    }
}

// Expose update function globally
window.updateLanguageContent = updateLanguageContent;