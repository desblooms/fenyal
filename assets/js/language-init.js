// Language initialization script for Fenyal
// This should be included in the head of all pages for consistent language handling

(function() {
    'use strict';
    
    // Default language configuration
    const DEFAULT_LANGUAGE = 'en';
    const SUPPORTED_LANGUAGES = ['en', 'ar'];
    
    // Get language from various sources
    function detectLanguage() {
        // Priority: URL parameter > localStorage > browser language > default
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        
        if (urlLang && SUPPORTED_LANGUAGES.includes(urlLang)) {
            return urlLang;
        }
        
        const storedLang = localStorage.getItem('selectedLanguage');
        if (storedLang && SUPPORTED_LANGUAGES.includes(storedLang)) {
            return storedLang;
        }
        
        // Try to detect from browser language
        const browserLang = navigator.language || navigator.userLanguage;
        if (browserLang && browserLang.startsWith('ar')) {
            return 'ar';
        }
        
        return DEFAULT_LANGUAGE;
    }
    
    // Set language in localStorage
    function setLanguage(lang) {
        if (SUPPORTED_LANGUAGES.includes(lang)) {
            localStorage.setItem('selectedLanguage', lang);
            return lang;
        }
        return DEFAULT_LANGUAGE;
    }
    
    // Update document attributes for the detected language
    function updateDocumentLanguage(lang) {
        document.documentElement.setAttribute('lang', lang);
        document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
        
        // Add language class to body for CSS targeting
        document.body.classList.remove('lang-en', 'lang-ar');
        document.body.classList.add(`lang-${lang}`);
        
        // Update font family for Arabic
        if (lang === 'ar') {
            document.body.style.fontFamily = '"Cairo", "Poppins", sans-serif';
        } else {
            document.body.style.fontFamily = '"Poppins", sans-serif';
        }
    }
    
    // Initialize language on page load
    function initializeLanguage() {
        const detectedLang = detectLanguage();
        const finalLang = setLanguage(detectedLang);
        updateDocumentLanguage(finalLang);
        
        // If URL doesn't have lang parameter but we have a different language, update URL
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        
        if (!urlLang && finalLang !== DEFAULT_LANGUAGE) {
            // Add language parameter to current URL without reloading
            urlParams.set('lang', finalLang);
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, '', newUrl);
        }
        
        return finalLang;
    }
    
    // Language switching function
    function switchLanguage(newLang) {
        if (!SUPPORTED_LANGUAGES.includes(newLang)) {
            console.warn('Unsupported language:', newLang);
            return false;
        }
        
        setLanguage(newLang);
        
        // Update URL with new language
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('lang', newLang);
        const newUrl = window.location.pathname + '?' + urlParams.toString();
        
        // Reload page with new language
        window.location.href = newUrl;
        
        return true;
    }
    
    // Get current language
    function getCurrentLanguage() {
        return localStorage.getItem('selectedLanguage') || DEFAULT_LANGUAGE;
    }
    
    // Check if current language is RTL
    function isRTL() {
        return getCurrentLanguage() === 'ar';
    }
    
    // Build URL with current language parameter
    function buildLangUrl(path, additionalParams = {}) {
        const params = new URLSearchParams();
        params.set('lang', getCurrentLanguage());
        
        // Add additional parameters
        Object.keys(additionalParams).forEach(key => {
            params.set(key, additionalParams[key]);
        });
        
        return path + '?' + params.toString();
    }
    
    // Initialize language when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLanguage);
    } else {
        initializeLanguage();
    }
    
    // Make functions globally available
    window.LanguageManager = {
        initializeLanguage,
        switchLanguage,
        getCurrentLanguage,
        isRTL,
        buildLangUrl,
        detectLanguage,
        setLanguage,
        updateDocumentLanguage,
        SUPPORTED_LANGUAGES,
        DEFAULT_LANGUAGE
    };
    
    // Auto-initialize
    const currentLang = initializeLanguage();
    console.log('Language initialized:', currentLang);
    
})();