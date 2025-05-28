// Enhanced Language Manager - Global Language Switching Utility
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
        this.isInitialized = false;
        this.initialize();
    }

    initialize() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupLanguage();
            });
        } else {
            this.setupLanguage();
        }
    }

    setupLanguage() {
        // Apply saved language on page load
        this.updateLanguage(this.currentLanguage);
        
        // Set up automatic button detection and event listeners
        this.setupLanguageButtons();
        
        // Set up mutation observer to handle dynamically added buttons
        this.setupMutationObserver();
        
        this.isInitialized = true;
    }

    // Set up mutation observer to handle dynamically added language buttons
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check if the added node or its children contain language buttons
                        const newButtons = node.querySelectorAll ? 
                            node.querySelectorAll('[class*="lang-"], [id*="lang-"]') : [];
                        
                        if (newButtons.length > 0) {
                            this.setupLanguageButtons();
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
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
            detail: { 
                language: lang, 
                translations: this.translations[lang] || {},
                isRTL: lang === 'ar'
            }
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
        // Find all language buttons with various selectors
        const enButtons = document.querySelectorAll([
            '[id*="lang-en"]', 
            '.lang-en',
            '[data-lang="en"]',
            'button[data-language="en"]'
        ].join(','));
        
        const arButtons = document.querySelectorAll([
            '[id*="lang-ar"]', 
            '.lang-ar',
            '[data-lang="ar"]',
            'button[data-language="ar"]'
        ].join(','));

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
        // Find all language buttons with various selectors
        const enButtons = document.querySelectorAll([
            '[id*="lang-en"]', 
            '.lang-en',
            '[data-lang="en"]',
            'button[data-language="en"]'
        ].join(','));
        
        const arButtons = document.querySelectorAll([
            '[id*="lang-ar"]', 
            '.lang-ar',
            '[data-lang="ar"]',
            'button[data-language="ar"]'
        ].join(','));

        // Remove existing listeners by cloning and replacing elements
        [...enButtons, ...arButtons].forEach(button => {
            if (!button.hasAttribute('data-lang-listener')) {
                button.setAttribute('data-lang-listener', 'true');
                
                const newButton = button.cloneNode(true);
                if (button.parentNode) {
                    button.parentNode.replaceChild(newButton, button);
                }
            }
        });

        // Re-query after cloning
        const newEnButtons = document.querySelectorAll([
            '[id*="lang-en"]', 
            '.lang-en',
            '[data-lang="en"]',
            'button[data-language="en"]'
        ].join(','));
        
        const newArButtons = document.querySelectorAll([
            '[id*="lang-ar"]', 
            '.lang-ar',
            '[data-lang="ar"]',
            'button[data-language="ar"]'
        ].join(','));

        // Add event listeners
        newEnButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.updateLanguage('en');
            });
        });

        newArButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.updateLanguage('ar');
            });
        });

        // Update button states immediately
        setTimeout(() => {
            this.updateLanguageButtons();
        }, 50);
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

    // Force refresh language buttons (useful for dynamically added content)
    refreshLanguageButtons() {
        setTimeout(() => {
            this.setupLanguageButtons();
        }, 100);
    }

    // Method to programmatically switch language
    switchLanguage(lang) {
        if (lang === 'en' || lang === 'ar') {
            this.updateLanguage(lang);
        }
    }
}

// Create and export singleton instance
const languageManager = new LanguageManager();

// Make it globally available
window.languageManager = languageManager;

// Export for ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = languageManager;
}

// Auto-setup for immediate use
if (typeof window !== 'undefined') {
    // Ensure it works even if script is loaded after DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            languageManager.refreshLanguageButtons();
        });
    } else {
        languageManager.refreshLanguageButtons();
    }
}