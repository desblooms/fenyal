// Enhanced language toggle functionality with comprehensive menu integration
document.addEventListener('DOMContentLoaded', function() {
    const languageToggle = document.getElementById('language-toggle');
    const currentLanguageSpan = document.getElementById('current-language');
    
    // Get current language from localStorage or default to 'en'
    let currentLanguage = localStorage.getItem('selectedLanguage') || 'en';
    
    // Initialize language display and apply language settings
    updateLanguageDisplay(currentLanguage);
    applyLanguage(currentLanguage);
    
    // Toggle between languages when button is clicked
    if (languageToggle) {
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
            showLanguageChangeToast(currentLanguage);
        });
    }
    
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
        
        // Update back button icon for RTL support
        updateBackButtonIcon(lang);
    }
    
    function updateBackButtonIcon(lang) {
        const backBtns = document.querySelectorAll('[data-feather="arrow-left"], [data-feather="arrow-right"]');
        backBtns.forEach(icon => {
            if (lang === 'ar') {
                icon.setAttribute('data-feather', 'arrow-right');
            } else {
                icon.setAttribute('data-feather', 'arrow-left');
            }
        });
        
        // Re-initialize feather icons if available
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
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
        const popularTitle = document.querySelector('h2#popular-title');
        const viewAllLink = document.querySelector('a#view-all-link');
        
        if (popularTitle) {
            popularTitle.textContent = getTranslation('popular', lang);
        }
        
        if (viewAllLink) {
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
        const pageTitle = document.querySelector('h1#page-title');
        const searchInput = document.getElementById('search-input');
        const popularTitle = document.getElementById('popular-title');
        const menuSectionTitle = document.getElementById('menu-section-title');
        const allCategoryBtn = document.getElementById('all-category-btn');
        const noResultsElements = document.getElementById('no-results');
        
        if (pageTitle) {
            pageTitle.textContent = getTranslation('menu', lang);
        }
        
        if (searchInput) {
            searchInput.placeholder = getTranslation('searchPlaceholder', lang);
        }
        
        if (popularTitle) {
            popularTitle.textContent = getTranslation('popular', lang);
        }
        
        if (menuSectionTitle) {
            menuSectionTitle.textContent = getTranslation('allMenu', lang);
        }
        
        if (allCategoryBtn) {
            allCategoryBtn.textContent = getTranslation('all', lang);
        }
        
        // Update no results content
        if (noResultsElements) {
            const noResultsTitle = noResultsElements.querySelector('h3#no-results-title');
            const noResultsText = noResultsElements.querySelector('p#no-results-text');
            const resetButton = noResultsElements.querySelector('button#reset-search');
            
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
        // Update menu item details page elements
        const loadingText = document.getElementById('loading-text');
        const sizeTitle = document.getElementById('size-title');
        const sizeHalfLabel = document.getElementById('size-half-label');
        const sizeFullLabel = document.getElementById('size-full-label');
        const spiceLevelTitle = document.getElementById('spice-level-title');
        const addonsTitle = document.getElementById('addons-title');
        const priceLabel = document.getElementById('price-label');
        const popularBadge = document.getElementById('popular-badge');
        const errorTitle = document.getElementById('error-title');
        const errorText = document.getElementById('error-text');
        const backToMenuBtn = document.getElementById('back-to-menu');
        
        // Update all text elements
        if (loadingText) {
            loadingText.textContent = getTranslation('loadingItemDetails', lang);
        }
        if (sizeTitle) {
            sizeTitle.textContent = getTranslation('selectSize', lang);
        }
        if (sizeHalfLabel) {
            sizeHalfLabel.textContent = getTranslation('half', lang);
        }
        if (sizeFullLabel) {
            sizeFullLabel.textContent = getTranslation('full', lang);
        }
        if (spiceLevelTitle) {
            spiceLevelTitle.textContent = getTranslation('spiceLevel', lang);
        }
        if (addonsTitle) {
            addonsTitle.textContent = getTranslation('addons', lang);
        }
        if (priceLabel) {
            priceLabel.textContent = getTranslation('price', lang);
        }
        if (popularBadge && !popularBadge.classList.contains('hidden')) {
            popularBadge.textContent = getTranslation('popular', lang);
        }
        if (errorTitle) {
            errorTitle.textContent = getTranslation('itemNotFound', lang);
        }
        if (errorText) {
            errorText.textContent = getTranslation('itemNotFoundDesc', lang);
        }
        if (backToMenuBtn) {
            backToMenuBtn.textContent = getTranslation('backToMenu', lang);
        }
        
        // Update spice level options if they exist
        updateSpiceLevelLabels(lang);
        
        // Update addon labels if they exist
        updateAddonLabels(lang);
        
        // Refresh item details if function exists
        if (window.refreshItemDetails && typeof window.refreshItemDetails === 'function') {
            setTimeout(() => {
                window.refreshItemDetails();
            }, 100);
        }
    }
    
    function updateSpiceLevelLabels(lang) {
        const spiceLevelInputs = document.querySelectorAll('input[name="spice-level"]');
        spiceLevelInputs.forEach(input => {
            const label = document.querySelector(`label[for="${input.id}"]`);
            if (label) {
                const spiceLevel = input.value;
                label.textContent = getSpiceLevelTranslation(spiceLevel, lang);
            }
        });
    }
    
    function updateAddonLabels(lang) {
        const addonInputs = document.querySelectorAll('input[name="addons"]');
        addonInputs.forEach(input => {
            const label = document.querySelector(`label[for="${input.id}"]`);
            if (label && window.currentItem && window.currentItem.addons) {
                const addonName = input.value;
                const addon = window.currentItem.addons.find(a => a.name === addonName);
                if (addon) {
                    const localizedName = lang === 'ar' && addon.nameAr ? addon.nameAr : addon.name;
                    label.textContent = localizedName;
                }
            }
        });
    }
    
    function updateNavigationLabels(lang) {
        // Update bottom navigation labels
        const navHome = document.getElementById('nav-home');
        const navMenu = document.getElementById('nav-menu');
        
        if (navHome) {
            navHome.textContent = getTranslation('home', lang);
        }
        if (navMenu) {
            navMenu.textContent = getTranslation('menu', lang);
        }
    }
    
    function updateCategoryLabels(lang) {
        // Update category labels on home page
        const categoryLabels = [
            { key: 'Breakfast', en: 'Breakfast', ar: 'فطور' },
            { key: 'Dishes', en: 'Dishes', ar: 'أطباق' },
            { key: 'Bread', en: 'Bread', ar: 'خبز' },
            { key: 'Desserts', en: 'Desserts', ar: 'حلويات' },
            { key: 'Cold Drinks', en: 'Cold Drinks', ar: 'مشروبات باردة' },
            { key: 'Hot Drinks', en: 'Hot Drinks', ar: 'مشروبات ساخنة' }
        ];
        
        const categoryElements = document.querySelectorAll('.category-label');
        categoryElements.forEach(span => {
            const categoryKey = span.getAttribute('data-category');
            const category = categoryLabels.find(cat => cat.key === categoryKey);
            
            if (category) {
                span.textContent = lang === 'ar' ? category.ar : category.en;
            }
        });
    }
    
    function getSpiceLevelTranslation(spiceLevel, lang) {
        const spiceLevelMap = {
            'Mild': { en: 'Mild', ar: 'خفيف' },
            'Medium': { en: 'Medium', ar: 'متوسط' },
            'Spicy': { en: 'Spicy', ar: 'حار' },
            'Hot': { en: 'Hot', ar: 'حار جداً' }
        };
        
        return spiceLevelMap[spiceLevel]?.[lang] || spiceLevel;
    }
    
    function showLanguageChangeToast(lang) {
        if (window.toast && typeof window.toast.show === 'function') {
            const langName = lang === 'ar' ? 'العربية' : 'English';
            const message = lang === 'ar' ? `اللغة: ${langName}` : `Language: ${langName}`;
            window.toast.show(message, 'success');
        } else {
            // Fallback toast implementation
            const toast = document.createElement('div');
            toast.className = `fixed bottom-24 left-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg text-center text-sm z-50 transition-opacity`;
            toast.textContent = lang === 'ar' ? `اللغة: العربية` : `Language: English`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }
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
                all: 'All',
                searchPlaceholder: 'Search menu items...',
                noItemsFound: 'No items found',
                tryDifferentSearch: 'Try a different search or category',
                resetSearch: 'Reset Search',
                selectSize: 'Select Size',
                half: 'Half',
                full: 'Full',
                spiceLevel: 'Spice Level',
                addons: 'Add-ons',
                price: 'Price',
                popular: 'Popular',
                loadingItemDetails: 'Loading item details...',
                itemNotFound: 'Item Not Found',
                itemNotFoundDesc: 'The requested menu item could not be found.',
                backToMenu: 'Back to Menu'
            },
            ar: {
                home: 'الرئيسية',
                menu: 'القائمة',
                popular: 'الأصناف الشائعة',
                viewAll: 'عرض الكل',
                allMenu: 'جميع الأصناف',
                all: 'الكل',
                searchPlaceholder: 'البحث في أصناف القائمة...',
                noItemsFound: 'لم يتم العثور على أصناف',
                tryDifferentSearch: 'جرب بحثاً أو فئة مختلفة',
                resetSearch: 'إعادة تعيين البحث',
                selectSize: 'اختر الحجم',
                half: 'نصف',
                full: 'كامل',
                spiceLevel: 'مستوى الحار',
                addons: 'الإضافات',
                price: 'السعر',
                popular: 'شائع',
                loadingItemDetails: 'جاري تحميل تفاصيل الصنف...',
                itemNotFound: 'الصنف غير موجود',
                itemNotFoundDesc: 'لم يتم العثور على الصنف المطلوب في القائمة.',
                backToMenu: 'العودة للقائمة'
            }
        };
        
        return translations[lang][key] || translations.en[key] || key;
    }
    
    // Apply saved language on page load
    setTimeout(() => {
        refreshPageContent(currentLanguage);
    }, 500);
    
    // Make language functions available globally for other scripts
    window.languageToggle = {
        getCurrentLanguage: () => currentLanguage,
        setLanguage: (lang) => {
            currentLanguage = lang;
            localStorage.setItem('selectedLanguage', lang);
            updateLanguageDisplay(lang);
            applyLanguage(lang);
            refreshPageContent(lang);
        },
        getTranslation: getTranslation,
        refreshContent: () => refreshPageContent(currentLanguage)
    };
});