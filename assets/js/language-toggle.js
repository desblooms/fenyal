// Simple language toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const languageToggle = document.getElementById('language-toggle');
    const currentLanguageSpan = document.getElementById('current-language');
    
    // Get current language from localStorage or default to 'en'
    let currentLanguage = localStorage.getItem('selectedLanguage') || 'en';
    updateLanguageDisplay(currentLanguage);
    
    // Toggle between languages when button is clicked
    languageToggle.addEventListener('click', function() {
        // Switch language
        currentLanguage = currentLanguage === 'en' ? 'ar' : 'en';
        
        // Save selected language
        localStorage.setItem('selectedLanguage', currentLanguage);
        
        // Update display
        updateLanguageDisplay(currentLanguage);
        
        // Apply language changes
        applyLanguage(currentLanguage);
        
        // Show success message
        if (window.toast && typeof window.toast.show === 'function') {
            const langName = currentLanguage === 'ar' ? 'العربية' : 'English';
            window.toast.show(`Language: ${langName}`, 'success');
        }
    });
    
    function updateLanguageDisplay(lang) {
        currentLanguageSpan.textContent = lang.toUpperCase();
    }
    
    function applyLanguage(lang) {
        // Apply RTL/LTR direction
        if (lang === 'ar') {
            document.documentElement.setAttribute('dir', 'rtl');
            document.documentElement.setAttribute('lang', 'ar');
        } else {
            document.documentElement.setAttribute('dir', 'ltr');
            document.documentElement.setAttribute('lang', 'en');
        }
    }
    
    // Apply saved language on page load
    applyLanguage(currentLanguage);
});