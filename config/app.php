<?php
// config/app.php - Application Configuration and Optimization with Dynamic Categories
session_start();

// Application Constants
define('APP_NAME', 'Fenyal');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://fenyal.orderwithmenu.com/');
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', ['en', 'ar']);

// Performance Settings
define('ENABLE_CACHE', true);
define('CACHE_DURATION', 10); // 1 hour
define('ENABLE_COMPRESSION', true);

// Database Configuration
require_once __DIR__ . '/../admin/config.php';

// Language Management Class
class LanguageManager {
    private static $instance = null;
    private $currentLanguage;
    private $translations = [];
    
    private function __construct() {
        $this->initializeLanguage();
        $this->loadTranslations();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeLanguage() {
        // Priority: URL param > Session > Default
        $requestedLang = $_GET['lang'] ?? $_SESSION['language'] ?? DEFAULT_LANGUAGE;
        
        if (in_array($requestedLang, SUPPORTED_LANGUAGES)) {
            $this->currentLanguage = $requestedLang;
            $_SESSION['language'] = $requestedLang;
        } else {
            $this->currentLanguage = DEFAULT_LANGUAGE;
        }
    }
    
    private function loadTranslations() {
        $this->translations = [
            'en' => [
                // Navigation
                'home' => 'Home',
                'menu' => 'Menu',
                'about' => 'About',
                'contact' => 'Contact',
                'categories' => 'Categories',
                
                // Menu Items
                'popular_items' => 'Popular Items',
                'view_all' => 'View all',
                'all_menu' => 'All Menu',
                'search_placeholder' => 'Search menu items...',
                'no_items_found' => 'No items found',
                'try_different_search' => 'Try a different search or category',
                'reset_search' => 'Reset Search',
                
                // Categories
                'all_categories' => 'All',
                'breakfast' => 'Breakfast',
                'dishes' => 'Dishes',
                'bread' => 'Bread',
                'desserts' => 'Desserts',
                'cold_drinks' => 'Cold Drinks',
                'hot_drinks' => 'Hot Drinks',
                
                // Item Details
                'select_size' => 'Select Size',
                'half' => 'Half',
                'full' => 'Full',
                'spice_level' => 'Spice Level',
                'addons' => 'Add-ons',
                'price' => 'Price',
                'add_to_cart' => 'Add to Cart',
                'order_now' => 'Order Now',
                
                // Status
                'popular' => 'Popular',
                'special' => 'Special',
                'new' => 'New',
                'loading' => 'Loading...',
                'error' => 'Error',
                'success' => 'Success',
                
                // Actions
                'back' => 'Back',
                'close' => 'Close',
                'continue' => 'Continue',
                'cancel' => 'Cancel',
                'save' => 'Save',
                'edit' => 'Edit',
                'delete' => 'Delete',
                
                // Spice Levels
                'mild' => 'Mild',
                'medium' => 'Medium',
                'spicy' => 'Spicy',
                'hot' => 'Hot'
            ],
            'ar' => [
                // Navigation
                'home' => 'الرئيسية',
                'menu' => 'القائمة',
                'about' => 'حولنا',
                'contact' => 'اتصل بنا',
                'categories' => 'الفئات',
                
                // Menu Items
                'popular_items' => 'الأصناف الشائعة',
                'view_all' => 'عرض الكل',
                'all_menu' => 'جميع الأصناف',
                'search_placeholder' => 'البحث في أصناف القائمة...',
                'no_items_found' => 'لم يتم العثور على أصناف',
                'try_different_search' => 'جرب بحثاً أو فئة مختلفة',
                'reset_search' => 'إعادة تعيين البحث',
                
                // Categories
                'all_categories' => 'الكل',
                'breakfast' => 'فطور',
                'dishes' => 'أطباق',
                'bread' => 'خبز',
                'desserts' => 'حلويات',
                'cold_drinks' => 'مشروبات باردة',
                'hot_drinks' => 'مشروبات ساخنة',
                
                // Item Details
                'select_size' => 'اختر الحجم',
                'half' => 'نصف',
                'full' => 'كامل',
                'spice_level' => 'مستوى الحرارة',
                'addons' => 'الإضافات',
                'price' => 'السعر',
                'add_to_cart' => 'أضف إلى السلة',
                'order_now' => 'اطلب الآن',
                
                // Status
                'popular' => 'شائع',
                'special' => 'مميز',
                'new' => 'جديد',
                'loading' => 'جاري التحميل...',
                'error' => 'خطأ',
                'success' => 'نجح',
                
                // Actions
                'back' => 'رجوع',
                'close' => 'إغلاق',
                'continue' => 'متابعة',
                'cancel' => 'إلغاء',
                'save' => 'حفظ',
                'edit' => 'تعديل',
                'delete' => 'حذف',
                
                // Spice Levels
                'mild' => 'خفيف',
                'medium' => 'متوسط',
                'spicy' => 'حار',
                'hot' => 'حار جداً'
            ]
        ];
    }
    
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    public function translate($key, $fallback = null) {
        $translation = $this->translations[$this->currentLanguage][$key] ?? 
                      $this->translations[DEFAULT_LANGUAGE][$key] ?? 
                      $fallback ?? 
                      $key;
        return $translation;
    }
    
    public function isRTL() {
        return $this->currentLanguage === 'ar';
    }
    
    public function getDirection() {
        return $this->isRTL() ? 'rtl' : 'ltr';
    }
    
    public function getAlternativeLanguage() {
        return $this->currentLanguage === 'ar' ? 'en' : 'ar';
    }
}

// Cache Management Class
class CacheManager {
    private static $instance = null;
    private $cacheDir;
    
    private function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        if (!ENABLE_CACHE) return null;
        
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cacheData = file_get_contents($cacheFile);
        $data = unserialize($cacheData);
        
        if ($data['expires'] < time()) {
            unlink($cacheFile);
            return null;
        }
        
        return $data['content'];
    }
    
    public function set($key, $content, $duration = CACHE_DURATION) {
        if (!ENABLE_CACHE) return false;
        
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        $data = [
            'content' => $content,
            'expires' => time() + $duration,
            'created' => time()
        ];
        
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }
    
    public function delete($key) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return true;
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
}

// Database Helper Class
class DatabaseHelper {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $this->pdo = getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getMenuItems($filters = []) {
        $cacheKey = 'menu_items_' . md5(serialize($filters));
        $cache = CacheManager::getInstance();
        
        // Try to get from cache first
        if ($cached = $cache->get($cacheKey)) {
            return $cached;
        }
        
        // Build query
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['category'])) {
            $whereConditions[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = "(name LIKE ? OR name_ar LIKE ? OR description LIKE ? OR description_ar LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (isset($filters['popular']) && $filters['popular']) {
            $whereConditions[] = "is_popular = 1";
        }
        
        if (isset($filters['special']) && $filters['special']) {
            $whereConditions[] = "is_special = 1";
        }
        
        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $limit = "LIMIT " . (int)$filters['limit'];
        } else {
            $limit = "";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT * FROM menu_items $whereClause ORDER BY 
                CASE category
                    WHEN 'Breakfast' THEN 1
                    WHEN 'Dishes' THEN 2  
                    WHEN 'Bread' THEN 3
                    WHEN 'Desserts' THEN 4
                    WHEN 'Cold Drinks' THEN 5
                    WHEN 'Hot Drinks' THEN 6
                    ELSE 7
                END, name ASC $limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        // Cache the result
        $cache->set($cacheKey, $items);
        
        return $items;
    }
    
    public function getMenuItem($id) {
        $cacheKey = "menu_item_$id";
        $cache = CacheManager::getInstance();
        
        if ($cached = $cache->get($cacheKey)) {
            return $cached;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if ($item) {
            // Get addons
            $addonsStmt = $this->pdo->prepare("SELECT * FROM menu_addons WHERE menu_item_id = ? ORDER BY name");
            $addonsStmt->execute([$id]);
            $item['addons'] = $addonsStmt->fetchAll();
            
            // Get spice levels
            $spiceLevelsStmt = $this->pdo->prepare("SELECT * FROM menu_spice_levels WHERE menu_item_id = ? ORDER BY name");
            $spiceLevelsStmt->execute([$id]);
            $item['spiceLevels'] = $spiceLevelsStmt->fetchAll();
            
            $cache->set($cacheKey, $item);
        }
        
        return $item;
    }
    
    public function getCategories() {
        $cacheKey = 'categories_with_images';
        $cache = CacheManager::getInstance();
        
        if ($cached = $cache->get($cacheKey)) {
            return $cached;
        }
        
        $stmt = $this->pdo->query("
            SELECT c.*, COUNT(m.id) as item_count
            FROM categories c
            LEFT JOIN menu_items m ON c.name = m.category
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.display_order, c.name
        ");
        $categories = $stmt->fetchAll();
        
        $cache->set($cacheKey, $categories);
        return $categories;
    }
    
    public function getCategoryByName($categoryName) {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE name = ? AND is_active = 1");
        $stmt->execute([$categoryName]);
        return $stmt->fetch();
    }
}

// Utility Functions
function formatPrice($price, $language = null) {
    $lang = $language ?? LanguageManager::getInstance()->getCurrentLanguage();
    return ($lang === 'ar') ? number_format($price, 0) . ' ريال قطري' : 'QAR ' . number_format($price, 0);
}

function getLocalizedText($item, $field, $language = null) {
    $lang = $language ?? LanguageManager::getInstance()->getCurrentLanguage();
    $arField = $field . '_ar';
    
    if ($lang === 'ar' && isset($item[$arField]) && !empty($item[$arField])) {
        return $item[$arField];
    }
    
    return $item[$field] ?? '';
}

function buildUrl($path, $params = []) {
    $currentLang = LanguageManager::getInstance()->getCurrentLanguage();
    $params['lang'] = $currentLang;
    
    $queryString = http_build_query($params);
    return $path . ($queryString ? '?' . $queryString : '');
}

function redirect($url, $params = []) {
    header('Location: ' . buildUrl($url, $params));
    exit;
}

// Get category image with fallback
function getCategoryImage($category) {
    $db = DatabaseHelper::getInstance();
    $categoryData = $db->getCategoryByName($category);
    
    if ($categoryData && !empty($categoryData['image'])) {
        return $categoryData['image'];
    }
    
    // Fallback to default images
    $defaultImages = [
        'Breakfast' => 'uploads/menu/1.png',
        'Dishes' => 'uploads/menu/2.png',
        'Bread' => 'uploads/menu/3.png',
        'Desserts' => 'uploads/menu/4.png',
        'Cold Drinks' => 'uploads/menu/5.png',
        'Hot Drinks' => 'uploads/menu/6.png'
    ];
    
    return $defaultImages[$category] ?? 'uploads/menu/placeholder.jpg';
}

// Initialize global instances
$lang = LanguageManager::getInstance();
$db = DatabaseHelper::getInstance();
$cache = CacheManager::getInstance();

// Enable compression if configured
if (ENABLE_COMPRESSION && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

// Function to get translation (global helper)
function __($key, $fallback = null) {
    global $lang;
    return $lang->translate($key, $fallback);
}

// Function to check if current language is RTL
function isRTL() {
    global $lang;
    return $lang->isRTL();
}

// Function to get current language
function getCurrentLanguage() {
    global $lang;
    return $lang->getCurrentLanguage();
}

// Function to get alternative language
function getAlternativeLanguage() {
    global $lang;
    return $lang->getAlternativeLanguage();
}
?>