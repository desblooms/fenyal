<?php
// admin/config.php - Database configuration and setup with enhanced categories support

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'qpzkdqupex');
define('DB_USER', 'qpzkdqupex');
define('DB_PASS', 'X9Vx6nyC9B');

// Admin credentials (in production, use proper authentication)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'fenyal2024'); // Change this!

// Create database connection
function getConnection() {
    try {
        //$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";qpzkdqupex", qpzkdqupex, X9Vx6nyC9B);
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS); 
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database and tables
function initializeDatabase() {
    try {
        // First, create database if it doesn't exist
        //$pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", qpzkdqupex, X9Vx6nyC9B);
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Create menu_items table
        $createMenuTable = "
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255),
            description TEXT,
            description_ar TEXT,
            price DECIMAL(10, 2) NOT NULL,
            category VARCHAR(100) NOT NULL,
            category_ar VARCHAR(100),
            image VARCHAR(500),
            is_popular BOOLEAN DEFAULT FALSE,
            is_special BOOLEAN DEFAULT FALSE,
            is_half_full BOOLEAN DEFAULT FALSE,
            half_price DECIMAL(10, 2) NULL,
            full_price DECIMAL(10, 2) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_popular (is_popular),
            INDEX idx_special (is_special)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createMenuTable);
        
        // Create addons table
        $createAddonsTable = "
        CREATE TABLE IF NOT EXISTS menu_addons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_item_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            name_ar VARCHAR(255),
            price DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
            INDEX idx_menu_item (menu_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createAddonsTable);
        
        // Create spice levels table
        $createSpiceLevelsTable = "
        CREATE TABLE IF NOT EXISTS menu_spice_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_item_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            name_ar VARCHAR(100),
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
            INDEX idx_menu_item (menu_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createSpiceLevelsTable);
        
        // Create enhanced categories table with image support
        $createCategoriesTable = "
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            name_ar VARCHAR(100),
            display_order INT DEFAULT 0,
            image VARCHAR(500) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_display_order (display_order),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createCategoriesTable);
        
        // Check if image column exists, if not add it (for existing installations)
        $checkImageColumn = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'");
        if ($checkImageColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(500) NULL AFTER display_order");
        }
        
        // Check if updated_at column exists, if not add it
        $checkUpdatedAtColumn = $pdo->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
        if ($checkUpdatedAtColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }
        
        // Insert default categories if they don't exist (with default images)
        $defaultCategories = [
            [
                'name' => 'Breakfast', 
                'name_ar' => 'فطور', 
                'display_order' => 1,
                'image' => 'uploads/categories/breakfast.png'
            ],
            [
                'name' => 'Dishes', 
                'name_ar' => 'أطباق', 
                'display_order' => 2,
                'image' => 'uploads/categories/dishes.png'
            ],
            [
                'name' => 'Bread', 
                'name_ar' => 'خبز', 
                'display_order' => 3,
                'image' => 'uploads/categories/bread.png'
            ],
            [
                'name' => 'Desserts', 
                'name_ar' => 'حلويات', 
                'display_order' => 4,
                'image' => 'uploads/categories/desserts.png'
            ],
            [
                'name' => 'Cold Drinks', 
                'name_ar' => 'مشروبات باردة', 
                'display_order' => 5,
                'image' => 'uploads/categories/cold-drinks.png'
            ],
            [
                'name' => 'Hot Drinks', 
                'name_ar' => 'مشروبات ساخنة', 
                'display_order' => 6,
                'image' => 'uploads/categories/hot-drinks.png'
            ]
        ];
        
        foreach ($defaultCategories as $category) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, name_ar, display_order, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category['name'], $category['name_ar'], $category['display_order'], $category['image']]);
        }
        
        return true;
        
    } catch(PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

// Import JSON data to database
function importJSONData($jsonFile) {
    $pdo = getConnection();
    
    if (!file_exists($jsonFile)) {
        throw new Exception("JSON file not found: $jsonFile");
    }
    
    $jsonData = file_get_contents($jsonFile);
    $menuItems = json_decode($jsonData, true);
    
    if (!$menuItems) {
        throw new Exception("Invalid JSON data");
    }
    
    $pdo->beginTransaction();
    
    try {
        // Clear existing data
        $pdo->exec("DELETE FROM menu_spice_levels");
        $pdo->exec("DELETE FROM menu_addons");
        $pdo->exec("DELETE FROM menu_items");
        
        foreach ($menuItems as $item) {
            // Insert menu item
            $stmt = $pdo->prepare("
                INSERT INTO menu_items 
                (id, name, name_ar, description, description_ar, price, category, category_ar, 
                 image, is_popular, is_special, is_half_full, half_price, full_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $item['id'],
                $item['name'],
                $item['nameAr'] ?? null,
                $item['description'],
                $item['descriptionAr'] ?? null,
                $item['price'],
                $item['category'],
                $item['categoryAr'] ?? null,
                $item['image'],
                $item['isPopular'] ? 1 : 0,
                $item['isSpecial'] ? 1 : 0,
                $item['isHalfFull'] ? 1 : 0,
                $item['halfPrice'] ?? null,
                $item['fullPrice'] ?? null
            ]);
            
            // Insert addons
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    $addonStmt = $pdo->prepare("
                        INSERT INTO menu_addons (menu_item_id, name, name_ar, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $addonStmt->execute([
                        $item['id'],
                        $addon['name'],
                        $addon['nameAr'] ?? null,
                        $addon['price']
                    ]);
                }
            }
            
            // Insert spice levels
            if (!empty($item['spiceLevelOptions'])) {
                foreach ($item['spiceLevelOptions'] as $spiceLevel) {
                    $spiceStmt = $pdo->prepare("
                        INSERT INTO menu_spice_levels (menu_item_id, name, name_ar) 
                        VALUES (?, ?, ?)
                    ");
                    $spiceStmt->execute([
                        $item['id'],
                        $spiceLevel['name'],
                        $spiceLevel['nameAr'] ?? null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

// Export database data to JSON
function exportToJSON() {
    $pdo = getConnection();
    
    $stmt = $pdo->query("
        SELECT m.*, 
               GROUP_CONCAT(DISTINCT CONCAT(a.name, '|', COALESCE(a.name_ar, ''), '|', a.price) SEPARATOR ';;') as addons,
               GROUP_CONCAT(DISTINCT CONCAT(s.name, '|', COALESCE(s.name_ar, '')) SEPARATOR ';;') as spice_levels
        FROM menu_items m
        LEFT JOIN menu_addons a ON m.id = a.menu_item_id
        LEFT JOIN menu_spice_levels s ON m.id = s.menu_item_id
        GROUP BY m.id
        ORDER BY m.id
    ");
    
    $menuItems = [];
    
    while ($row = $stmt->fetch()) {
        $item = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'nameAr' => $row['name_ar'],
            'description' => $row['description'],
            'descriptionAr' => $row['description_ar'],
            'price' => (float)$row['price'],
            'category' => $row['category'],
            'categoryAr' => $row['category_ar'],
            'image' => $row['image'],
            'isPopular' => (bool)$row['is_popular'],
            'isSpecial' => (bool)$row['is_special'],
            'isHalfFull' => (bool)$row['is_half_full']
        ];
        
        if ($row['is_half_full']) {
            $item['halfPrice'] = (float)$row['half_price'];
            $item['fullPrice'] = (float)$row['full_price'];
        }
        
        // Parse addons
        $item['addons'] = [];
        if ($row['addons']) {
            $addons = explode(';;', $row['addons']);
            foreach ($addons as $addon) {
                $parts = explode('|', $addon);
                if (count($parts) >= 3) {
                    $item['addons'][] = [
                        'name' => $parts[0],
                        'nameAr' => $parts[1] ?: null,
                        'price' => (float)$parts[2]
                    ];
                }
            }
        }
        
        // Parse spice levels
        $item['spiceLevelOptions'] = [];
        if ($row['spice_levels']) {
            $spiceLevels = explode(';;', $row['spice_levels']);
            foreach ($spiceLevels as $spiceLevel) {
                $parts = explode('|', $spiceLevel);
                if (count($parts) >= 2) {
                    $item['spiceLevelOptions'][] = [
                        'name' => $parts[0],
                        'nameAr' => $parts[1] ?: null
                    ];
                }
            }
        }
        
        $menuItems[] = $item;
    }
    
    return json_encode($menuItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// Get categories with images for frontend
function getCategoriesWithImages() {
    $pdo = getConnection();
    
    $stmt = $pdo->query("
        SELECT c.*, COUNT(m.id) as item_count
        FROM categories c
        LEFT JOIN menu_items m ON c.name = m.category
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.display_order, c.name
    ");
    
    return $stmt->fetchAll();
}

// Simple authentication function
function checkAuth() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Login function
function adminLogin($username, $password) {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        session_start();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

// Initialize database when this file is included
try {
    initializeDatabase();
} catch (Exception $e) {
    // Log error but don't stop execution
    error_log("Database initialization error: " . $e->getMessage());
}
?>
