// Language Manager - Global Language Switching Utility
// File: assets/js/language.js

class LanguageManager {
    constructor() {
        this.currentLanguage = localStorage.getItem('language') || 'en';
        this.translations = {};
        this.categoryTranslations = {
            'Breakfast': { en: 'Breakfast', ar: 'فطور' },
            'Dishes': { en: 'Dishes', ar: 'أطباق' },
            'Bread': { en: 'Bread', ar: 'خبز' },
            'Desserts': { en: 'Desserts', ar: 'حلويات' },
            'Cold Drinks': { en: 'Cold Drinks', ar: 'مشروبات باردة' },
            'Hot Drinks': { en: 'Hot Drinks', ar: 'مشروبات ساخنة' }
        };
        this.initialize();
    }

    initialize() {
        // Apply saved language on page load
        this.updateLanguage(this.currentLanguage);
        
        // Set up automatic button detection and event listeners
        this.setupLanguageButtons();
    }

    // Register translations for a specific page
    registerTranslations(pageTranslations) {
        this.translations = { ...this.translations, ...pageTranslations };
    }

    // Update language across the entire page
    updateLanguage(lang) {
        this.currentLanguage = lang;
        localStorage.setItem('language', lang);
        
        // Update HTML attributes
        const html = document.documentElement;
        if (lang === 'ar') {
            html.setAttribute('dir', 'rtl');
            html.setAttribute('lang', 'ar');
            document.body.classList.add('arabic-font');
        } else {
            html.setAttribute('dir', 'ltr');
            html.setAttribute('lang', 'en');
            document.body.classList.remove('arabic-font');
        }

        // Update all translatable elements with data attributes
        this.updateTranslatableElements();
        
        // Update placeholders
        this.updatePlaceholders();

        // Update language button states
        this.updateLanguageButtons();
        
        // Add visual feedback animation
        document.body.classList.add('language-switch');
        setTimeout(() => {
            document.body.classList.remove('language-switch');
        }, 300);

        // Trigger custom event for pages to listen to
        window.dispatchEvent(new CustomEvent('languageChanged', { 
            detail: { language: lang, translations: this.translations[lang] || {} }
        }));
    }

    // Update all elements with translation data attributes
    updateTranslatableElements() {
        document.querySelectorAll('[data-en]').forEach(element => {
            const enText = element.getAttribute('data-en');
            const arText = element.getAttribute('data-ar');
            
            if (this.currentLanguage === 'ar' && arText) {
                element.textContent = arText;
            } else if (this.currentLanguage === 'en' && enText) {
                element.textContent = enText;
            }
        });
    }

    // Update input placeholders
    updatePlaceholders() {
        document.querySelectorAll('[data-placeholder-en]').forEach(element => {
            const placeholderEn = element.getAttribute('data-placeholder-en');
            const placeholderAr = element.getAttribute('data-placeholder-ar');
            
            if (this.currentLanguage === 'ar' && placeholderAr) {
                element.placeholder = placeholderAr;
            } else if (this.currentLanguage === 'en' && placeholderEn) {
                element.placeholder = placeholderEn;
            }
        });
    }

    // Update language button states across all pages
    updateLanguageButtons() {
        // Find all English language buttons
        const enButtons = document.querySelectorAll('[id*="lang-en"], .lang-en');
        const arButtons = document.querySelectorAll('[id*="lang-ar"], .lang-ar');

        if (this.currentLanguage === 'ar') {
            // Arabic active
            arButtons.forEach(button => {
                button.classList.add('bg-primary', 'text-white', 'border-primary', 'active');
                button.classList.remove('text-gray-600', 'border-gray-200', 'bg-white', 'bg-gray-100');
            });
            
            enButtons.forEach(button => {
                button.classList.remove('bg-primary', 'text-white', 'border-primary', 'active');
                button.classList.add('text-gray-600', 'border-gray-200', 'bg-white');
            });
        } else {
            // English active
            enButtons.forEach(button => {
                button.classList.add('bg-primary', 'text-white', 'border-primary', 'active');
                button.classList.remove('text-gray-600', 'border-gray-200', 'bg-white', 'bg-gray-100');
            });
            
            arButtons.forEach(button => {
                button.classList.remove('bg-primary', 'text-white', 'border-primary', 'active');
                button.classList.add('text-gray-600', 'border-gray-200', 'bg-white');
            });
        }
    }

    // Automatically detect and setup language buttons
    setupLanguageButtons() {
        // Find all language buttons and add event listeners
        const enButtons = document.querySelectorAll('[id*="lang-en"], .lang-en');
        const arButtons = document.querySelectorAll('[id*="lang-ar"], .lang-ar');

        enButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.updateLanguage('en');
            });
        });

        arButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.updateLanguage('ar');
            });
        });
    }

    // Get translation for a key
    getTranslation(key, lang = this.currentLanguage) {
        return this.translations[lang] && this.translations[lang][key] 
            ? this.translations[lang][key] 
            : key;
    }

    // Get category translation
    getCategoryTranslation(category, lang = this.currentLanguage) {
        return this.categoryTranslations[category] && this.categoryTranslations[category][lang]
            ? this.categoryTranslations[category][lang]
            : category;
    }

    // Get current language
    getCurrentLanguage() {
        return this.currentLanguage;
    }

    // Check if current language is RTL
    isRTL() {
        return this.currentLanguage === 'ar';
    }
}

// Create and export singleton instance
const languageManager = new LanguageManager();

// Make it globally available
window.languageManager = languageManager;

// Export for ES6 modules
export default languageManager;