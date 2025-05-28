<?php
/**
 * Application Constants
 * Fenyal Restaurant Menu System
 * 
 * This file contains all the application-wide constants
 * including app info, language settings, user roles, and system configurations
 */

// =============================================================================
// APPLICATION INFORMATION
// =============================================================================
define('APP_NAME', 'Fenyal Restaurant');
define('APP_NAME_AR', 'مطعم فنيال');
define('APP_VERSION', '2.0.0');
define('APP_DESCRIPTION', 'Modern Bilingual Restaurant Menu System');
define('APP_AUTHOR', 'Fenyal Development Team');
define('APP_URL', 'http://localhost/fenyal-menu-php');

// =============================================================================
// LANGUAGE CONFIGURATION
// =============================================================================
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', ['en', 'ar']);
define('RTL_LANGUAGES', ['ar']);

// Language names
define('LANGUAGE_NAMES', [
    'en' => 'English',
    'ar' => 'العربية'
]);

// =============================================================================
// USER ROLES & PERMISSIONS
// =============================================================================
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');

define('USER_ROLES', [
    ROLE_SUPER_ADMIN => 'Super Administrator',
    ROLE_ADMIN => 'Administrator'
]);

// Role permissions
define('PERMISSIONS', [
    ROLE_SUPER_ADMIN => [
        'manage_users',
        'manage_menu',
        'manage_categories',
        'manage_settings',
        'view_analytics',
        'system_config'
    ],
    ROLE_ADMIN => [
        'manage_menu',
        'manage_categories',
        'view_analytics'
    ]
]);

// =============================================================================
// FILE UPLOAD CONFIGURATION
// =============================================================================
define('UPLOAD_DIR', 'uploads/');
define('MENU_IMAGES_DIR', UPLOAD_DIR . 'menu-items/');
define('CATEGORY_IMAGES_DIR', UPLOAD_DIR . 'categories/');
define('TEMP_DIR', UPLOAD_DIR . 'temp/');

// Maximum file sizes (in bytes)
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Allowed file types
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/jpg', 
    'image/png',
    'image/webp',
    'image/gif'
]);

define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Image dimensions
define('MAX_IMAGE_WIDTH', 1920);
define('MAX_IMAGE_HEIGHT', 1080);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);

// =============================================================================
// PAGINATION SETTINGS
// =============================================================================
define('DEFAULT_ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);
define('MAX_ITEMS_PER_PAGE', 50);

// =============================================================================
// CACHE SETTINGS
// =============================================================================
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour in seconds
define('CACHE_DIR', 'cache/');

// =============================================================================
// SESSION CONFIGURATION
// =============================================================================
define('SESSION_NAME', 'FENYAL_SESSION');
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('REMEMBER_ME_DURATION', 30 * 24 * 3600); // 30 days

// =============================================================================
// SECURITY SETTINGS
// =============================================================================
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 minutes in seconds
define('CSRF_TOKEN_NAME', 'csrf_token');

// Security headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin'
]);

// =============================================================================
// API CONFIGURATION
// =============================================================================
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per hour
define('API_ENABLED', true);

// API Response formats
define('API_SUCCESS_CODE', 200);
define('API_ERROR_CODE', 400);
define('API_UNAUTHORIZED_CODE', 401);
define('API_NOT_FOUND_CODE', 404);
define('API_SERVER_ERROR_CODE', 500);

// =============================================================================
// MENU ITEM SETTINGS
// =============================================================================
define('DEFAULT_MENU_SORT', 'sort_order ASC, name_en ASC');
define('FEATURED_ITEMS_LIMIT', 6);
define('MENU_CARD_IMAGE_SIZE', 'medium'); // small, medium, large

// Menu item status
define('MENU_STATUS_AVAILABLE', 'available');
define('MENU_STATUS_UNAVAILABLE', 'unavailable');
define('MENU_STATUS_SEASONAL', 'seasonal');

define('MENU_STATUSES', [
    MENU_STATUS_AVAILABLE => 'Available',
    MENU_STATUS_UNAVAILABLE => 'Unavailable',  
    MENU_STATUS_SEASONAL => 'Seasonal'
]);

// =============================================================================
// CATEGORY SETTINGS
// =============================================================================
define('DEFAULT_CATEGORY_SORT', 'sort_order ASC, name_en ASC');
define('MAX_CATEGORY_DEPTH', 3);

// =============================================================================
// DATETIME FORMATS
// =============================================================================
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Timezone
define('DEFAULT_TIMEZONE', 'Asia/Kuwait');

// =============================================================================
// CURRENCY SETTINGS
// =============================================================================
define('DEFAULT_CURRENCY', 'KWD');
define('CURRENCY_SYMBOL', 'د.ك');
define('CURRENCY_POSITION', 'after'); // before or after
define('DECIMAL_PLACES', 3);
define('DECIMAL_SEPARATOR', '.');
define('THOUSANDS_SEPARATOR', ',');

// =============================================================================
// EMAIL CONFIGURATION (if needed for notifications)
// =============================================================================
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls'); // tls or ssl

define('FROM_EMAIL', 'noreply@fenyal-restaurant.com');
define('FROM_NAME', APP_NAME);
define('ADMIN_EMAIL', 'admin@fenyal-restaurant.com');

// =============================================================================
// LOG SETTINGS
// =============================================================================
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_DIR', 'logs/');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_MAX_FILES', 5);

// =============================================================================
// DEVELOPMENT SETTINGS
// =============================================================================
define('DEBUG_MODE', false);
define('DISPLAY_ERRORS', DEBUG_MODE);
define('ERROR_REPORTING_LEVEL', DEBUG_MODE ? E_ALL : E_ERROR);

// =============================================================================
// SOCIAL MEDIA & CONTACT (for display purposes)
// =============================================================================
define('RESTAURANT_PHONE', '+965 XXXX XXXX');
define('RESTAURANT_EMAIL', 'info@fenyal-restaurant.com');
define('RESTAURANT_ADDRESS_EN', 'Kuwait City, Kuwait');
define('RESTAURANT_ADDRESS_AR', 'مدينة الكويت، الكويت');

define('SOCIAL_MEDIA', [
    'facebook' => 'https://facebook.com/fenyal',
    'instagram' => 'https://instagram.com/fenyal',
    'twitter' => 'https://twitter.com/fenyal'
]);

// =============================================================================
// SEO SETTINGS
// =============================================================================
define('DEFAULT_META_TITLE_EN', 'Fenyal Restaurant - Authentic Middle Eastern Cuisine');
define('DEFAULT_META_TITLE_AR', 'مطعم فنيال - المأكولات الشرق أوسطية الأصيلة');
define('DEFAULT_META_DESCRIPTION_EN', 'Experience authentic Middle Eastern cuisine at Fenyal Restaurant. Browse our menu of traditional dishes made with fresh ingredients.');
define('DEFAULT_META_DESCRIPTION_AR', 'استمتع بالمأكولات الشرق أوسطية الأصيلة في مطعم فنيال. تصفح قائمتنا من الأطباق التقليدية المحضرة بمكونات طازجة.');
define('DEFAULT_META_KEYWORDS', 'restaurant, middle eastern food, arabic cuisine, kuwait restaurant, halal food');

// =============================================================================
// THEME SETTINGS
// =============================================================================
define('DEFAULT_THEME', 'modern');
define('SUPPORTED_THEMES', ['modern', 'classic', 'minimal']);

// Color scheme
define('PRIMARY_COLOR', '#1F2937'); // Dark gray
define('SECONDARY_COLOR', '#F59E0B'); // Amber
define('ACCENT_COLOR', '#10B981'); // Emerald
define('ERROR_COLOR', '#EF4444'); // Red
define('SUCCESS_COLOR', '#10B981'); // Green
define('WARNING_COLOR', '#F59E0B'); // Amber

// =============================================================================
// HELPER FUNCTIONS FOR CONSTANTS
// =============================================================================

/**
 * Check if current language is RTL
 * @return bool
 */
function isRTL() {
    $currentLang = $_SESSION['language'] ?? DEFAULT_LANGUAGE;
    return in_array($currentLang, RTL_LANGUAGES);
}

/**
 * Get localized app name
 * @return string
 */
function getAppName() {
    $currentLang = $_SESSION['language'] ?? DEFAULT_LANGUAGE;
    return $currentLang === 'ar' ? APP_NAME_AR : APP_NAME;
}

/**
 * Get formatted currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    $formatted = number_format($amount, DECIMAL_PLACES, DECIMAL_SEPARATOR, THOUSANDS_SEPARATOR);
    
    if (CURRENCY_POSITION === 'before') {
        return CURRENCY_SYMBOL . ' ' . $formatted;
    } else {
        return $formatted . ' ' . CURRENCY_SYMBOL;
    }
}

/**
 * Check if user has permission
 * @param string $permission
 * @return bool
 */
function hasPermission($permission) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    return in_array($permission, PERMISSIONS[$userRole] ?? []);
}

/**
 * Get upload directory path
 * @param string $type
 * @return string
 */
function getUploadDir($type = 'general') {
    switch ($type) {
        case 'menu':
            return MENU_IMAGES_DIR;
        case 'category':
            return CATEGORY_IMAGES_DIR;
        case 'temp':
            return TEMP_DIR;
        default:
            return UPLOAD_DIR;
    }
}

// =============================================================================
// VALIDATION RULES
// =============================================================================
define('VALIDATION_RULES', [
    'menu_name' => [
        'required' => true,
        'min_length' => 2,
        'max_length' => 100
    ],
    'menu_description' => [
        'required' => false,
        'max_length' => 500
    ],
    'menu_price' => [
        'required' => true,
        'type' => 'decimal',
        'min' => 0.001,
        'max' => 9999.999
    ],
    'category_name' => [
        'required' => true,
        'min_length' => 2,
        'max_length' => 100
    ],
    'username' => [
        'required' => true,
        'min_length' => 3,
        'max_length' => 50,
        'pattern' => '/^[a-zA-Z0-9_]+$/'
    ],
    'email' => [
        'required' => true,
        'type' => 'email',
        'max_length' => 100
    ],
    'password' => [
        'required' => true,
        'min_length' => PASSWORD_MIN_LENGTH,
        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/' // At least one lowercase, uppercase, and digit
    ]
]);

// =============================================================================
// STATUS MESSAGES
// =============================================================================
define('SUCCESS_MESSAGES', [
    'item_created' => 'Menu item created successfully',
    'item_updated' => 'Menu item updated successfully',
    'item_deleted' => 'Menu item deleted successfully',
    'category_created' => 'Category created successfully',
    'category_updated' => 'Category updated successfully',
    'category_deleted' => 'Category deleted successfully',
    'user_created' => 'User created successfully',
    'user_updated' => 'User updated successfully',
    'login_success' => 'Login successful',
    'logout_success' => 'Logout successful',
    'settings_saved' => 'Settings saved successfully'
]);

define('ERROR_MESSAGES', [
    'invalid_credentials' => 'Invalid username or password',
    'access_denied' => 'Access denied',
    'item_not_found' => 'Item not found',
    'category_not_found' => 'Category not found',
    'user_not_found' => 'User not found',
    'file_upload_error' => 'File upload failed',
    'invalid_file_type' => 'Invalid file type',
    'file_too_large' => 'File size too large',
    'database_error' => 'Database connection error',
    'validation_error' => 'Please check your input and try again',
    'session_expired' => 'Your session has expired. Please login again.',
    'csrf_token_mismatch' => 'Security token mismatch. Please try again.'
]);

// =============================================================================
// END OF CONSTANTS
// =============================================================================
?>