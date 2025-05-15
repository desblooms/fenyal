<?php
/**
 * Application Configuration
 */

// Database settings
define('DB_HOST', 'localhost');     // Database host
define('DB_NAME', 'u345095192_menudb'); // Database name
define('DB_USER', 'u345095192_menu');       // Database username
define('DB_PASS', 'db_password');   // Database password

// Application settings
define('APP_NAME', 'Digital Menu');
define('APP_URL', 'https://digitalmenu.desblooms.in/');
define('APP_VERSION', '1.0.0');

// Path settings
define('BASE_PATH', __DIR__);
define('ASSETS_PATH', '/assets');
define('UPLOADS_PATH', '/uploads');

// WhatsApp integration
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_NUMBER', '911234567890'); // With country code

// Debug mode (set to false in production)
define('DEBUG_MODE', true);

// Set error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set('Asia/Kolkata');