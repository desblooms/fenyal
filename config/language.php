<?php
// config/language.php - Enhanced Language Configuration Helper with Better Bilingual Support
// This file provides comprehensive language switching functionality

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
            // Priority: URL param > localStorage preference > Session > Default
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
        $formattedPrice = number_format($price, 0);
        
        if ($currentLang === 'ar') {
            return $formattedPrice . ' ر.ق'; // Qatari Riyal in Arabic
        }
        return 'QAR ' . $formattedPrice;
    }
    
    /**
     * Enhanced translations array with all required strings
     */
    function getTranslations() {
        return [
            'en' => [
                // Navigation & UI
                'home' => 'Home',
                'menu' => 'Menu',
                'about' => 'About',
                'contact' => 'Contact',
                'categories' => 'Categories',
                'language' => 'Language',
                
                // Menu & Items
                'popular_items' => 'Popular Items',
                'view_all' => 'View all',
                'all_menu' => 'All Menu',
                'no_items_found' => 'No items found',
                'popular' => 'Popular',
                'special' => 'Special',
                'new' => 'New',
                'featured' => 'Featured',
                
                // Search & Filter
                'search_placeholder' => 'Search menu items...',
                'all' => 'All',
                'try_different_search' => 'Try a different search or category',
                'reset_search' => 'Reset Search',
                'search_results' => 'Search Results',
                'category_results' => 'Category: ',
                'filter_by' => 'Filter by',
                'sort_by' => 'Sort by',
                
                // Item Details
                'select_size' => 'Select Size',
                'half' => 'Half',
                'full' => 'Full',
                'small' => 'Small',
                'medium' => 'Medium',
                'large' => 'Large',
                'spice_level' => 'Spice Level',
                'addons' => 'Add-ons',
                'extras' => 'Extras',
                'price' => 'Price',
                'total_price' => 'Total Price',
                'add_to_cart' => 'Add to Cart',
                'order_now' => 'Order Now',
                'quantity' => 'Quantity',
                'ingredients' => 'Ingredients',
                'nutrition' => 'Nutrition Info',
                
                // Spice Levels
                'mild' => 'Mild',
                'spicy' => 'Spicy',
                'hot' => 'Hot',
                'extra_hot' => 'Extra Hot',
                
                // Common Add-ons (for fallback)
                'extra_cheese' => 'Extra Cheese',
                'extra_sauce' => 'Extra Sauce',
                'avocado' => 'Avocado',
                'olives' => 'Olives',
                'pickles' => 'Pickles',
                
                // Categories (fallback if not in database)
                'breakfast' => 'Breakfast',
                'lunch' => 'Lunch',
                'dinner' => 'Dinner',
                'dishes' => 'Dishes',
                'main_courses' => 'Main Courses',
                'appetizers' => 'Appetizers',
                'bread' => 'Bread & Bakery',
                'desserts' => 'Desserts',
                'beverages' => 'Beverages',
                'cold_drinks' => 'Cold Drinks',
                'hot_drinks' => 'Hot Drinks',
                'coffee' => 'Coffee',
                'tea' => 'Tea',
                'juices' => 'Fresh Juices',
                
                // Actions & Buttons
                'back' => 'Back',
                'close' => 'Close',
                'continue' => 'Continue',
                'cancel' => 'Cancel',
                'save' => 'Save',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'confirm' => 'Confirm',
                'yes' => 'Yes',
                'no' => 'No',
                'ok' => 'OK',
                'retry' => 'Retry',
                'refresh' => 'Refresh',
                'load_more' => 'Load More',
                'show_more' => 'Show More',
                'show_less' => 'Show Less',
                
                // Status Messages
                'loading' => 'Loading...',
                'loading_menu' => 'Loading menu...',
                'loading_items' => 'Loading items...',
                'error' => 'Error',
                'success' => 'Success',
                'warning' => 'Warning',
                'info' => 'Information',
                'not_found' => 'Not Found',
                'not_available' => 'Not Available',
                'coming_soon' => 'Coming Soon',
                'out_of_stock' => 'Out of Stock',
                'in_stock' => 'In Stock',
                
                // Error Messages
                'connection_error' => 'Connection error. Please check your internet.',
                'server_error' => 'Server error. Please try again later.',
                'item_not_found' => 'Item not found',
                'item_not_found_desc' => 'The requested menu item could not be found.',
                'back_to_menu' => 'Back to Menu',
                'something_went_wrong' => 'Something went wrong',
                'please_try_again' => 'Please try again',
                
                // Time & Date
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'tomorrow' => 'Tomorrow',
                'now' => 'Now',
                'recently' => 'Recently',
                'minutes' => 'minutes',
                'hours' => 'hours',
                'days' => 'days',
                
                // Order & Cart
                'cart' => 'Cart',
                'checkout' => 'Checkout',
                'order_summary' => 'Order Summary',
                'total' => 'Total',
                'subtotal' => 'Subtotal',
                'tax' => 'Tax',
                'delivery' => 'Delivery',
                'pickup' => 'Pickup',
                'delivery_fee' => 'Delivery Fee',
                'free_delivery' => 'Free Delivery',
                
                // Contact & Location
                'phone' => 'Phone',
                'email' => 'Email',
                'address' => 'Address',
                'location' => 'Location',
                'hours' => 'Hours',
                'open' => 'Open',
                'closed' => 'Closed',
                
                // App Features
                'favorites' => 'Favorites',
                'reviews' => 'Reviews',
                'rating' => 'Rating',
                'share' => 'Share',
                'feedback' => 'Feedback',
                'help' => 'Help',
                'settings' => 'Settings',
                'profile' => 'Profile',
                'logout' => 'Logout',
                'login' => 'Login'
            ],
            'ar' => [
                // Navigation & UI
                'home' => 'الرئيسية',
                'menu' => 'القائمة',
                'about' => 'حولنا',
                'contact' => 'اتصل بنا',
                'categories' => 'الفئات',
                'language' => 'اللغة',
                
                // Menu & Items
                'popular_items' => 'الأصناف الشائعة',
                'view_all' => 'عرض الكل',
                'all_menu' => 'جميع الأصناف',
                'no_items_found' => 'لم يتم العثور على أصناف',
                'popular' => 'شائع',
                'special' => 'مميز',
                'new' => 'جديد',
                'featured' => 'مُختار',
                
                // Search & Filter
                'search_placeholder' => 'البحث في عناصر القائمة...',
                'all' => 'الكل',
                'try_different_search' => 'جرب بحثاً أو فئة مختلفة',
                'reset_search' => 'إعادة تعيين البحث',
                'search_results' => 'نتائج البحث',
                'category_results' => 'الفئة: ',
                'filter_by' => 'تصفية حسب',
                'sort_by' => 'ترتيب حسب',
                
                // Item Details
                'select_size' => 'اختر الحجم',
                'half' => 'نصف',
                'full' => 'كامل',
                'small' => 'صغير',
                'medium' => 'متوسط',
                'large' => 'كبير',
                'spice_level' => 'مستوى الحرارة',
                'addons' => 'الإضافات',
                'extras' => 'إضافات',
                'price' => 'السعر',
                'total_price' => 'السعر الإجمالي',
                'add_to_cart' => 'أضف إلى السلة',
                'order_now' => 'اطلب الآن',
                'quantity' => 'الكمية',
                'ingredients' => 'المكونات',
                'nutrition' => 'معلومات غذائية',
                
                // Spice Levels
                'mild' => 'خفيف',
                'spicy' => 'حار',
                'hot' => 'حار جداً',
                'extra_hot' => 'حار للغاية',
                
                // Common Add-ons (for fallback)
                'extra_cheese' => 'جبنة إضافية',
                'extra_sauce' => 'صلصة إضافية',
                'avocado' => 'أفوكادو',
                'olives' => 'زيتون',
                'pickles' => 'مخلل',
                
                // Categories (fallback if not in database)
                'breakfast' => 'فطور',
                'lunch' => 'غداء',
                'dinner' => 'عشاء',
                'dishes' => 'أطباق',
                'main_courses' => 'الأطباق الرئيسية',
                'appetizers' => 'المقبلات',
                'bread' => 'خبز ومخبوزات',
                'desserts' => 'حلويات',
                'beverages' => 'مشروبات',
                'cold_drinks' => 'مشروبات باردة',
                'hot_drinks' => 'مشروبات ساخنة',
                'coffee' => 'قهوة',
                'tea' => 'شاي',
                'juices' => 'عصائر طازجة',
                
                // Actions & Buttons
                'back' => 'رجوع',
                'close' => 'إغلاق',
                'continue' => 'متابعة',
                'cancel' => 'إلغاء',
                'save' => 'حفظ',
                'edit' => 'تعديل',
                'delete' => 'حذف',
                'confirm' => 'تأكيد',
                'yes' => 'نعم',
                'no' => 'لا',
                'ok' => 'موافق',
                'retry' => 'إعادة المحاولة',
                'refresh' => 'تحديث',
                'load_more' => 'تحميل المزيد',
                'show_more' => 'عرض المزيد',
                'show_less' => 'عرض أقل',
                
                // Status Messages
                'loading' => 'جاري التحميل...',
                'loading_menu' => 'جاري تحميل القائمة...',
                'loading_items' => 'جاري تحميل الأصناف...',
                'error' => 'خطأ',
                'success' => 'نجح',
                'warning' => 'تحذير',
                'info' => 'معلومات',
                'not_found' => 'غير موجود',
                'not_available' => 'غير متوفر',
                'coming_soon' => 'قريباً',
                'out_of_stock' => 'نفد المخزون',
                'in_stock' => 'متوفر',
                
                // Error Messages
                'connection_error' => 'خطأ في الاتصال. يرجى التحقق من الإنترنت.',
                'server_error' => 'خطأ في الخادم. يرجى المحاولة لاحقاً.',
                'item_not_found' => 'الصنف غير موجود',
                'item_not_found_desc' => 'لم يتم العثور على الصنف المطلوب في القائمة.',
                'back_to_menu' => 'العودة للقائمة',
                'something_went_wrong' => 'حدث خطأ ما',
                'please_try_again' => 'يرجى المحاولة مرة أخرى',
                
                // Time & Date
                'today' => 'اليوم',
                'yesterday' => 'أمس',
                'tomorrow' => 'غداً',
                'now' => 'الآن',
                'recently' => 'مؤخراً',
                'minutes' => 'دقائق',
                'hours' => 'ساعات',
                'days' => 'أيام',
                
                // Order & Cart
                'cart' => 'السلة',
                'checkout' => 'الدفع',
                'order_summary' => 'ملخص الطلب',
                'total' => 'المجموع',
                'subtotal' => 'المجموع الفرعي',
                'tax' => 'الضريبة',
                'delivery' => 'التوصيل',
                'pickup' => 'الاستلام',
                'delivery_fee' => 'رسوم التوصيل',
                'free_delivery' => 'توصيل مجاني',
                
                // Contact & Location
                'phone' => 'الهاتف',
                'email' => 'البريد الإلكتروني',
                'address' => 'العنوان',
                'location' => 'الموقع',
                'hours' => 'ساعات العمل',
                'open' => 'مفتوح',
                'closed' => 'مغلق',
                
                // App Features
                'favorites' => 'المفضلة',
                'reviews' => 'التقييمات',
                'rating' => 'التقييم',
                'share' => 'مشاركة',
                'feedback' => 'التعليقات',
                'help' => 'المساعدة',
                'settings' => 'الإعدادات',
                'profile' => 'الملف الشخصي',
                'logout' => 'تسجيل الخروج',
                'login' => 'تسجيل الدخول'
            ]
        ];
    }
    
    /**
     * Get translated text with fallback support
     */
    function __($key, $fallback = null) {
        static $translations = null;
        
        if ($translations === null) {
            $translations = getTranslations();
        }
        
        $currentLang = getCurrentLanguage();
        return $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $fallback ?? $key;
    }
    
    /**
     * Get localized item name with fallback
     */
    function getItemName($item) {
        $currentLang = getCurrentLanguage();
        if ($currentLang === 'ar' && !empty($item['name_ar'])) {
            return $item['name_ar'];
        }
        return $item['name'] ?? '';
    }
    
    /**
     * Get localized item description with fallback
     */
    function getItemDescription($item) {
        $currentLang = getCurrentLanguage();
        if ($currentLang === 'ar' && !empty($item['description_ar'])) {
            return $item['description_ar'];
        }
        return $item['description'] ?? '';
    }
    
    /**
     * Get localized category name with fallback
     */
    function getCategoryName($item) {
        $currentLang = getCurrentLanguage();
        if ($currentLang === 'ar' && !empty($item['category_ar'])) {
            return $item['category_ar'];
        }
        return $item['category'] ?? '';
    }
    
    /**
     * Redirect to language-specific URL
     */
    function redirectWithLanguage($url, $params = []) {
        header('Location: ' . buildLangUrl($url, $params));
        exit;
    }
    
    /**
     * Set HTML attributes for current language
     */
    function getLanguageAttributes() {
        $currentLang = getCurrentLanguage();
        $direction = getDirection();
        
        return [
            'lang' => $currentLang,
            'dir' => $direction,
            'class' => $currentLang === 'ar' ? 'rtl arabic-font' : 'ltr'
        ];
    }
    
    /**
     * Generate language toggle URL for current page
     */
    function getLanguageToggleUrl() {
        $alternativeLang = getAlternativeLanguage();
        $currentUrl = $_SERVER['REQUEST_URI'];
        $parsedUrl = parse_url($currentUrl);
        
        // Parse existing query parameters
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        
        // Update language parameter
        $queryParams['lang'] = $alternativeLang;
        
        // Rebuild URL
        $newQuery = http_build_query($queryParams);
        $newUrl = $parsedUrl['path'] . ($newQuery ? '?' . $newQuery : '');
        
        return $newUrl;
    }
    
    /**
     * Format numbers for current locale
     */
    function formatNumber($number, $decimals = 0) {
        $currentLang = getCurrentLanguage();
        
        if ($currentLang === 'ar') {
            // Arabic-Indic numerals conversion
            $arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            
            $formatted = number_format($number, $decimals);
            return str_replace($englishNumerals, $arabicNumerals, $formatted);
        }
        
        return number_format($number, $decimals);
    }
    
    /**
     * Get currency symbol based on language
     */
    function getCurrencySymbol() {
        $currentLang = getCurrentLanguage();
        return $currentLang === 'ar' ? 'ر.ق' : 'QAR';
    }
    
    /**
     * Enhanced price formatting with currency placement
     */
    function formatPriceAdvanced($price, $showCurrency = true) {
        $currentLang = getCurrentLanguage();
        $formattedPrice = formatNumber($price, 0);
        
        if (!$showCurrency) {
            return $formattedPrice;
        }
        
        $currency = getCurrencySymbol();
        
        if ($currentLang === 'ar') {
            return $formattedPrice . ' ' . $currency;
        }
        
        return $currency . ' ' . $formattedPrice;
    }
    
    // Auto-set language on every page load
    $GLOBALS['currentLang'] = getCurrentLanguage();
    $GLOBALS['isRTL'] = isRTL();
    $GLOBALS['direction'] = getDirection();
    $GLOBALS['alternativeLang'] = getAlternativeLanguage();
    
    // Set language attributes for HTML
    $GLOBALS['langAttributes'] = getLanguageAttributes();
}
?>


