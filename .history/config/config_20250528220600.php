<?php
/**
 * Fenyal Restaurant Menu System - Main Configuration
 * 
 * This file loads all configuration settings, initializes the application,
 * and sets up the environment for the digital menu system.
 * 
 * @package Fenyal Restaurant Menu
 * @version 2.0.0
 * @author Fenyal Development Team
 */

// Prevent direct access
if (!defined('FENYAL_ACCESS')) {
    define('FENYAL_ACCESS', true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting based on environment
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Load configuration files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/constants.php';

// Check if .env file exists and load environment variables
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    loadEnvironmentVariables($envFile);
}

/**
 * Load environment variables from .env file
 * 
 * @param string $file Path to .env file
 */
function loadEnvironmentVariables($file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match('/^(["\']).*\1$/', $value)) {
            $value = substr($value, 1, -1);
        }
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

/**
 * Get environment variable with default fallback
 * 
 * @param string $key Environment variable key
 * @param mixed $default Default value if key doesn't exist
 * @return mixed
 */
function env($key, $default = null) {
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

// Override database constants with environment variables if they exist
if (env('DB_HOST')) {
    define('DB_HOST_ENV', env('DB_HOST'));
} else {
    define('DB_HOST_ENV', DB_HOST);
}

if (env('DB_NAME')) {
    define('DB_NAME_ENV', env('DB_NAME'));
} else {
    define('DB_NAME_ENV', DB_NAME);
}

if (env('DB_USER')) {
    define('DB_USER_ENV', env('DB_USER'));
} else {
    define('DB_USER_ENV', DB_USER);
}

if (env('DB_PASS')) {
    define('DB_PASS_ENV', env('DB_PASS'));
} else {
    define('DB_PASS_ENV', DB_PASS);
}

// Application URL configuration
if (env('APP_URL')) {
    define('APP_URL', rtrim(env('APP_URL'), '/'));
} else {
    // Auto-detect application URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    define('APP_URL', $protocol . '://' . $host . $scriptPath);
}

// Upload configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'webp']);
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Image settings
define('IMAGE_MAX_WIDTH', 800);
define('IMAGE_MAX_HEIGHT', 600);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);

// Pagination settings
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'fenyal_csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour

// Language settings (from constants.php)
if (!defined('CURRENT_LANGUAGE')) {
    $currentLang = getCurrentLanguage();
    define('CURRENT_LANGUAGE', $currentLang);
}

// Set text direction for current language
define('TEXT_DIRECTION', CURRENT_LANGUAGE === 'ar' ? 'rtl' : 'ltr');

// Error logging
define('LOG_ENABLED', env('LOG_ENABLED', true));
define('LOG_PATH', dirname(__DIR__) . '/logs/');

// Create necessary directories if they don't exist
createRequiredDirectories();

/**
 * Get current language from session, URL parameter, or default
 * 
 * @return string Language code
 */
function getCurrentLanguage() {
    // Check URL parameter first
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
        $_SESSION['language'] = $_GET['lang'];
        return $_GET['lang'];
    }
    
    // Check session
    if (isset($_SESSION['language']) && in_array($_SESSION['language'], SUPPORTED_LANGUAGES)) {
        return $_SESSION['language'];
    }
    
    // Check browser language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browserLangs as $lang) {
            $lang = substr(trim($lang), 0, 2);
            if (in_array($lang, SUPPORTED_LANGUAGES)) {
                $_SESSION['language'] = $lang;
                return $lang;
            }
        }
    }
    
    // Return default language
    $_SESSION['language'] = DEFAULT_LANGUAGE;
    return DEFAULT_LANGUAGE;
}

/**
 * Create required directories if they don't exist
 */
function createRequiredDirectories() {
    $directories = [
        dirname(__DIR__) . '/uploads/',
        dirname(__DIR__) . '/uploads/menu-items/',
        dirname(__DIR__) . '/uploads/categories/',
        dirname(__DIR__) . '/uploads/temp/',
        dirname(__DIR__) . '/logs/',
        dirname(__DIR__) . '/cache/'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            
            // Create .htaccess for upload directories to prevent direct PHP execution
            if (strpos($dir, '/uploads/') !== false) {
                file_put_contents($dir . '.htaccess', "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n");
            }
        }
    }
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Log application events
 * 
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 */
function logEvent($message, $level = 'info') {
    if (!LOG_ENABLED) {
        return;
    }
    
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Format price for display
 * 
 * @param float $price Price value
 * @param string $currency Currency symbol
 * @return string Formatted price
 */
function formatPrice($price, $currency = 'ر.س') {
    if (CURRENT_LANGUAGE === 'ar') {
        return number_format($price, 2) . ' ' . $currency;
    }
    return $currency . ' ' . number_format($price, 2);
}

/**
 * Get file extension from filename
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate unique filename
 * 
 * @param string $originalName Original filename
 * @return string Unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = getFileExtension($originalName);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user has specific role
 * 
 * @param string $role Required role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin or super admin
 * 
 * @return bool
 */
function isAdmin() {
    return hasRole('admin') || hasRole('super_admin');
}

/**
 * Check if user is super admin
 * 
 * @return bool
 */
function isSuperAdmin() {
    return hasRole('super_admin');
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Get current page URL
 * 
 * @return string Current page URL
 */
function getCurrentURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Include language file and return translations
 * 
 * @param string $lang Language code
 * @return array Translations
 */
function loadLanguage($lang = null) {
    if ($lang === null) {
        $lang = CURRENT_LANGUAGE;
    }
    
    $langFile = dirname(__DIR__) . '/languages/' . $lang . '.php';
    if (file_exists($langFile)) {
        return include $langFile;
    }
    
    // Fallback to English
    $fallbackFile = dirname(__DIR__) . '/languages/en.php';
    if (file_exists($fallbackFile)) {
        return include $fallbackFile;
    }
    
    return [];
}

// Load translations for current language
$GLOBALS['translations'] = loadLanguage();

/**
 * Get translation for key
 * 
 * @param string $key Translation key
 * @param string $default Default value if key not found
 * @return string Translation
 */
function __($key, $default = '') {
    return $GLOBALS['translations'][$key] ?? $default ?: $key;
}

// Initialize application
try {
    // Check if database connection is working
    require_once dirname(__DIR__) . '/classes/Database.php';
    $db = Database::getInstance();
    
    // Log successful initialization
    logEvent('Application initialized successfully');
    
} catch (Exception $e) {
    // Log initialization error
    logEvent('Application initialization failed: ' . $e->getMessage(), 'error');
    
    // Show user-friendly error in production
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        die('Application temporarily unavailable. Please try again later.');
    } else {
        die('Configuration Error: ' . $e->getMessage());
    }
}

// Set content type for proper UTF-8 handling
header('Content-Type: text/html; charset=UTF-8');

// Application successfully configured
define('FENYAL_CONFIGURED', true);
?>