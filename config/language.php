<?php
// config/language.php - Simple Language Configuration Helper
// This file provides basic language switching functionality

if (!defined('LANG_CONFIG_LOADED')) {
    define('LANG_CONFIG_LOADED', true);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Supported languages
    define('SUPPORTED_LANGUAGES', ['en', 'ar']);
    define('DEFAULT_LANGUAGE', 'en');
    
    /**
     * Get current language from URL, session, or default
     */
    function getCurrentLanguage() {
        static $currentLang = null;
        
        if ($currentLang === null) {
            // Priority: URL param > Session > Default
            $requestedLang = $_GET['lang'] ?? $_SESSION['language'] ?? DEFAULT_LANGUAGE;
            
            if (in_array($requestedLang, SUPPORTED_LANGUAGES)) {
                $currentLang = $requestedLang;
                $_SESSION['language'] = $requestedLang;
            } else {
                $currentLang = DEFAULT_LANGUAGE;
            }
        }
        
        return $currentLang;
    }
    
    /**
     * Check if current language is RTL
     */
    function isRTL() {
        return getCurrentLanguage() === 'ar';
    }
    
    /**
     * Get text direction
     */
    function getDirection() {
        return isRTL() ? 'rtl' : 'ltr';
    }
    
    /**
     * Get alternative language for toggle
     */
    function getAlternativeLanguage() {
        return getCurrentLanguage() === 'ar' ? 'en' : 'ar';
    }
    
    /**
     * Build URL with language parameter
     */
    function buildLangUrl($path, $params = []) {
        $params['lang'] = getCurrentLanguage();
        $queryString = http_build_query($params);
        return $path . ($queryString ? '?' . $queryString : '');
    }
    
    /**
     * Get localized text from item array
     */
    function getLocalizedText($item, $field) {
        $currentLang = getCurrentLanguage();
        $arField = $field . '_ar';
        
        if ($currentLang === 'ar' && isset($item[$arField]) && !empty($item[$arField])) {
            return $item[$arField];
        }
        
        return $item[$field] ?? '';
    }
    
    /**
     * Format price based on language
     */
    function formatPrice($price) {
        $currentLang = getCurrentLanguage();
        return ($currentLang === 'ar') ? number_format($price, 0) . ' QAR ' : 'QAR ' . number_format($price, 0);
    }
    
    /**
     * Basic translations array
     */
    function getTranslations() {
        return [
            'en' => [
                'home' => 'Home',
                'menu' => 'Menu',
                'popular_items' => 'Popular Items',
                'view_all' => 'View all',
                'categories' => 'Categories',
                'no_items_found' => 'No items found',
                'popular' => 'Popular',
                'special' => 'Special',
                'search_placeholder' => 'Search menu items...',
                'all' => 'All',
                'all_menu' => 'All Menu',
                'try_different_search' => 'Try a different search or category',
                'reset_search' => 'Reset Search',
                'search_results' => 'Search Results',
                'category_results' => 'Category: ',
                'select_size' => 'Select Size',
                'half' => 'Half',
                'full' => 'Full',
                'spice_level' => 'Spice Level',
                'addons' => 'Add-ons',
                'price' => 'Price',
                'add_to_cart' => 'Add to Cart',
                'order_now' => 'Order Now',
                'back_to_menu' => 'Back to Menu'
            ],
            'ar' => [
                'home' => 'الرئيسية',
                'menu' => 'القائمة',
                'popular_items' => 'الأصناف الشائعة',
                'view_all' => 'عرض الكل',
                'categories' => 'الفئات',
                'no_items_found' => 'لم يتم العثور على أصناف',
                'popular' => 'شائع',
                'special' => 'مميز',
                'search_placeholder' => 'البحث في عناصر القائمة...',
                'all' => 'الكل',
                'all_menu' => 'جميع القائمة',
                'try_different_search' => 'جرب بحثاً أو فئة مختلفة',
                'reset_search' => 'إعادة تعيين البحث',
                'search_results' => 'نتائج البحث',
                'category_results' => 'الفئة: ',
                'select_size' => 'اختر الحجم',
                'half' => 'نصف',
                'full' => 'كامل',
                'spice_level' => 'مستوى الحرارة',
                'addons' => 'الإضافات',
                'price' => 'السعر',
                'add_to_cart' => 'أضف إلى السلة',
                'order_now' => 'اطلب الآن',
                'back_to_menu' => 'العودة للقائمة'
            ]
        ];
    }
    
    /**
     * Get translated text
     */
    function __($key) {
        static $translations = null;
        
        if ($translations === null) {
            $translations = getTranslations();
        }
        
        $currentLang = getCurrentLanguage();
        return $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $key;
    }
    
    /**
     * Redirect to language-specific URL
     */
    function redirectWithLanguage($url, $params = []) {
        header('Location: ' . buildLangUrl($url, $params));
        exit;
    }
    
    // Auto-set language on every page load
    $GLOBALS['currentLang'] = getCurrentLanguage();
    $GLOBALS['isRTL'] = isRTL();
    $GLOBALS['direction'] = getDirection();
    $GLOBALS['alternativeLang'] = getAlternativeLanguage();
}
?>