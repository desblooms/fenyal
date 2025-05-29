// Enhanced language toggle functionality with menu integration
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
        
        // Update menu manager language if available
        if (window.menuManager) {
            window.menuManager.setLanguage(currentLanguage);
        }
        
        // Refresh content on current page
        refreshPageContent(currentLanguage);
        
        // Show success message
        if (window.toast && typeof window.toast.show === 'function') {
            const langName = currentLanguage === 'ar' ? 'العربية' : 'English';
            const message = currentLanguage === 'ar' ? `اللغة: ${langName}` : `Language: ${langName}`;
            window.toast.show(message, 'success');
        }
    });
    
    function updateLanguageDisplay(lang) {
        if (currentLanguageSpan) {
            currentLanguageSpan.textContent = lang.toUpperCase();
        }
    }
    
    function applyLanguage(lang) {
        // Apply RTL/LTR direction
        if (lang === 'ar') {
            document.documentElement.setAttribute('dir', 'rtl');
            document.documentElement.setAttribute('lang', 'ar');
            document.body.style.fontFamily = '"Cairo", "Poppins", sans-serif';
        } else {
            document.documentElement.setAttribute('dir', 'ltr');
            document.documentElement.setAttribute('lang', 'en');
            document.body.style.fontFamily = '"Poppins", sans-serif';
        }
    }
    
    function refreshPageContent(lang) {
        // Update static text elements based on current page
        const currentPage = getCurrentPageType();
        
        switch (currentPage) {
            case 'home':
                updateHomePageContent(lang);
                break;
            case 'menu':
                updateMenuPageContent(lang);
                break;
            case 'menu-item-details':
                updateMenuItemDetailsContent(lang);
                break;
        }
        
        // Update navigation labels
        updateNavigationLabels(lang);
    }
    
    function getCurrentPageType() {
        const path = window.location.pathname;
        const filename = path.substring(path.lastIndexOf('/') + 1);
        
        if (filename === 'index.html' || filename === '') {
            return 'home';
        } else if (filename === 'menu.html') {
            return 'menu';
        } else if (filename === 'menu-item-details.html') {
            return 'menu-item-details';
        }
        return 'unknown';
    }
    
    function updateHomePageContent(lang) {
        // Update home page specific elements
        const popularTitle = document.querySelector('h2');
        const viewAllLink = document.querySelector('a[href="menu.html"]');
        
        if (popularTitle && popularTitle.textContent.includes('Popular')) {
            popularTitle.textContent = getTranslation('popular', lang);
        }
        
        if (viewAllLink && viewAllLink.textContent.includes('View all')) {
            viewAllLink.textContent = getTranslation('viewAll', lang);
        }
        
        // Update category names
        updateCategoryLabels(lang);
        
        // Reload popular items with new language
        if (window.loadSpecialItems && typeof window.loadSpecialItems === 'function') {
            setTimeout(() => {
                window.loadSpecialItems();
            }, 100);
        }
    }
    
    function updateMenuPageContent(lang) {
        // Update menu page specific elements
        const pageTitle = document.querySelector('h1');
        const searchInput = document.getElementById('search-input');
        const sectionTitles = document.querySelectorAll('h2');
        const noResultsElements = document.getElementById('no-results');
        
        if (pageTitle) {
            pageTitle.textContent = getTranslation('menu', lang);
        }
        
        if (searchInput) {
            searchInput.placeholder = getTranslation('searchPlaceholder', lang);
        }
        
        // Update section titles
        sectionTitles.forEach(title => {
            if (title.textContent.includes('Popular')) {
                title.textContent = getTranslation('popular', lang);
            } else if (title.textContent.includes('All Menu')) {
                title.textContent = getTranslation('allMenu', lang);
            }
        });
        
        // Update no results content
        if (noResultsElements) {
            const noResultsTitle = noResultsElements.querySelector('h3');
            const noResultsText = noResultsElements.querySelector('p');
            const resetButton = noResultsElements.querySelector('button');
            
            if (noResultsTitle) {
                noResultsTitle.textContent = getTranslation('noItemsFound', lang);
            }
            if (noResultsText) {
                noResultsText.textContent = getTranslation('tryDifferentSearch', lang);
            }
            if (resetButton) {
                resetButton.textContent = getTranslation('resetSearch', lang);
            }
        }
        
        // Trigger menu content refresh
        if (window.refreshMenuContent && typeof window.refreshMenuContent === 'function') {
            setTimeout(() => {
                window.refreshMenuContent();
            }, 100);
        }
    }
    
    function updateMenuItemDetailsContent(lang) {
        // Update menu item details page
        const sizeSectionTitle = document.querySelector('h3');
        const priceLabel = document.querySelector('p');
        
        // Update form labels
        const labels = document.querySelectorAll('label');
        labels.forEach(label => {
            const text = label.textContent.trim();
            if (text === 'Half') {
                label.textContent = getTranslation('half', lang);
            } else if (text === 'Full') {
                label.textContent = getTranslation('full', lang);
            }
        });
        
        // Update section titles
        if (sizeSectionTitle && sizeSectionTitle.textContent.includes('Select Size')) {
            sizeSectionTitle.textContent = getTranslation('selectSize', lang);
        }
        
        if (priceLabel && priceLabel.textContent === 'Price') {
            priceLabel.textContent = getTranslation('price', lang);
        }
        
        // Refresh item details if function exists
        if (window.refreshItemDetails && typeof window.refreshItemDetails === 'function') {
            setTimeout(() => {
                window.refreshItemDetails();
            }, 100);
        }
    }
    
    function updateNavigationLabels(lang) {
        // Update bottom navigation labels
        const navTexts = document.querySelectorAll('.nav-text');
        navTexts.forEach(navText => {
            const text = navText.textContent.trim();
            if (text === 'Home') {
                navText.textContent = getTranslation('home', lang);
            } else if (text === 'Menu') {
                navText.textContent = getTranslation('menu', lang);
            }
        });
    }
    
    function updateCategoryLabels(lang) {
        // Update category labels on home page
        const categoryLabels = [
            { en: 'Breakfast', ar: 'الإفطار' },
            { en: 'Dishes', ar: 'الأطباق' },
            { en: 'Bread', ar: 'الخبز' },
            { en: 'Desserts', ar: 'الحلويات' },
            { en: 'Cold Drinks', ar: 'المشروبات الباردة' },
            { en: 'Hot Drinks', ar: 'المشروبات الساخنة' }
        ];
        
        const categoryElements = document.querySelectorAll('a[href*="category"] span');
        categoryElements.forEach(span => {
            const currentText = span.textContent.trim();
            const category = categoryLabels.find(cat => 
                cat.en === currentText || cat.ar === currentText
            );
            
            if (category) {
                span.textContent = lang === 'ar' ? category.ar : category.en;
            }
        });
    }
    
    // Helper function to get translations
    function getTranslation(key, lang) {
        const translations = {
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
                selectSize: 'Select Size',
                half: 'Half',
                full: 'Full',
                spiceLevel: 'Spice Level',
                addons: 'Add-ons',
                price: 'Price'
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
                selectSize: 'اختر الحجم',
                half: 'نصف',
                full: 'كامل',
                spiceLevel: 'مستوى الحار',
                addons: 'الإضافات',
                price: 'السعر'
            }
        };
        
        return translations[lang][key] || translations.en[key] || key;
    }
    
    // Apply saved language on page load
    applyLanguage(currentLanguage);
    
    // Apply initial content updates
    setTimeout(() => {
        refreshPageContent(currentLanguage);
    }, 500);
});