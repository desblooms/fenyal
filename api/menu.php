<?php
// api/menu.php - Fast API endpoint for menu data retrieval
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../admin/config.php';

try {
    $pdo = getConnection();
    
    // Get request parameters
    $action = $_GET['action'] ?? 'menu';
    $language = $_GET['lang'] ?? 'en';
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $popular = $_GET['popular'] ?? '';
    $itemId = $_GET['id'] ?? '';
    
    // Validate language
    if (!in_array($language, ['en', 'ar'])) {
        $language = 'en';
    }
    
    switch ($action) {
        case 'menu':
            handleMenuRequest($pdo, $language, $category, $search, $popular);
            break;
            
        case 'item':
            handleItemRequest($pdo, $language, $itemId);
            break;
            
        case 'categories':
            handleCategoriesRequest($pdo, $language);
            break;
            
        case 'search':
            handleSearchRequest($pdo, $language, $search);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function handleMenuRequest($pdo, $language, $category, $search, $popular) {
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    if (!empty($category)) {
        $whereConditions[] = "category = ?";
        $params[] = $category;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(name LIKE ? OR name_ar LIKE ? OR description LIKE ? OR description_ar LIKE ? OR category LIKE ? OR category_ar LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($popular === '1' || $popular === 'true') {
        $whereConditions[] = "is_popular = 1";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get menu items
    $sql = "SELECT * FROM menu_items $whereClause ORDER BY 
            CASE category
                WHEN 'Breakfast' THEN 1
                WHEN 'Dishes' THEN 2  
                WHEN 'Bread' THEN 3
                WHEN 'Desserts' THEN 4
                WHEN 'Cold Drinks' THEN 5
                WHEN 'Hot Drinks' THEN 6
                ELSE 7
            END, name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    // Format items for response
    $formattedItems = [];
    foreach ($items as $item) {
        $formattedItems[] = formatMenuItem($item, $language);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedItems,
        'count' => count($formattedItems),
        'language' => $language,
        'filters' => [
            'category' => $category,
            'search' => $search,
            'popular' => $popular
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function handleItemRequest($pdo, $language, $itemId) {
    if (empty($itemId) || !is_numeric($itemId)) {
        throw new Exception('Invalid item ID');
    }
    
    // Get menu item
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // Get addons
    $addonsStmt = $pdo->prepare("SELECT * FROM menu_addons WHERE menu_item_id = ? ORDER BY name");
    $addonsStmt->execute([$itemId]);
    $addons = $addonsStmt->fetchAll();
    
    // Get spice levels
    $spiceLevelsStmt = $pdo->prepare("SELECT * FROM menu_spice_levels WHERE menu_item_id = ? ORDER BY name");
    $spiceLevelsStmt->execute([$itemId]);
    $spiceLevels = $spiceLevelsStmt->fetchAll();
    
    // Format item
    $formattedItem = formatMenuItem($item, $language);
    
    // Add addons
    $formattedItem['addons'] = [];
    foreach ($addons as $addon) {
        $formattedItem['addons'][] = [
            'id' => (int)$addon['id'],
            'name' => $addon['name'],
            'nameAr' => $addon['name_ar'],
            'localizedName' => ($language === 'ar' && !empty($addon['name_ar'])) ? $addon['name_ar'] : $addon['name'],
            'price' => (float)$addon['price'],
            'formattedPrice' => formatPrice($addon['price'], $language)
        ];
    }
    
    // Add spice levels
    $formattedItem['spiceLevels'] = [];
    foreach ($spiceLevels as $spiceLevel) {
        $formattedItem['spiceLevels'][] = [
            'id' => (int)$spiceLevel['id'],
            'name' => $spiceLevel['name'],
            'nameAr' => $spiceLevel['name_ar'],
            'localizedName' => ($language === 'ar' && !empty($spiceLevel['name_ar'])) ? $spiceLevel['name_ar'] : $spiceLevel['name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedItem,
        'language' => $language,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function handleCategoriesRequest($pdo, $language) {
    $stmt = $pdo->query("
        SELECT DISTINCT category, category_ar, COUNT(*) as item_count
        FROM menu_items 
        WHERE category IS NOT NULL 
        GROUP BY category, category_ar
        ORDER BY 
            CASE category
                WHEN 'Breakfast' THEN 1
                WHEN 'Dishes' THEN 2  
                WHEN 'Bread' THEN 3
                WHEN 'Desserts' THEN 4
                WHEN 'Cold Drinks' THEN 5
                WHEN 'Hot Drinks' THEN 6
                ELSE 7
            END
    ");
    $categories = $stmt->fetchAll();
    
    $formattedCategories = [];
    foreach ($categories as $category) {
        $formattedCategories[] = [
            'name' => $category['category'],
            'nameAr' => $category['category_ar'],
            'localizedName' => ($language === 'ar' && !empty($category['category_ar'])) ? $category['category_ar'] : $category['category'],
            'itemCount' => (int)$category['item_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedCategories,
        'language' => $language,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function handleSearchRequest($pdo, $language, $search) {
    if (empty($search)) {
        throw new Exception('Search query is required');
    }
    
    // Perform search
    $searchTerm = "%$search%";
    $stmt = $pdo->prepare("
        SELECT * FROM menu_items 
        WHERE name LIKE ? OR name_ar LIKE ? OR description LIKE ? OR description_ar LIKE ? OR category LIKE ? OR category_ar LIKE ?
        ORDER BY 
            CASE 
                WHEN name LIKE ? OR name_ar LIKE ? THEN 1
                WHEN description LIKE ? OR description_ar LIKE ? THEN 2
                ELSE 3
            END,
            name ASC
        LIMIT 50
    ");
    
    $stmt->execute([
        $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
        $searchTerm, $searchTerm, $searchTerm, $searchTerm
    ]);
    $items = $stmt->fetchAll();
    
    // Format items
    $formattedItems = [];
    foreach ($items as $item) {
        $formattedItems[] = formatMenuItem($item, $language);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedItems,
        'count' => count($formattedItems),
        'query' => $search,
        'language' => $language,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function formatMenuItem($item, $language) {
    return [
        'id' => (int)$item['id'],
        'name' => $item['name'],
        'nameAr' => $item['name_ar'],
        'localizedName' => ($language === 'ar' && !empty($item['name_ar'])) ? $item['name_ar'] : $item['name'],
        'description' => $item['description'],
        'descriptionAr' => $item['description_ar'],
        'localizedDescription' => ($language === 'ar' && !empty($item['description_ar'])) ? $item['description_ar'] : $item['description'],
        'price' => (float)$item['price'],
        'formattedPrice' => formatPrice($item['price'], $language),
        'category' => $item['category'],
        'categoryAr' => $item['category_ar'],
        'localizedCategory' => ($language === 'ar' && !empty($item['category_ar'])) ? $item['category_ar'] : $item['category'],
        'image' => $item['image'],
        'isPopular' => (bool)$item['is_popular'],
        'isSpecial' => (bool)$item['is_special'],
        'isHalfFull' => (bool)$item['is_half_full'],
        'halfPrice' => $item['half_price'] ? (float)$item['half_price'] : null,
        'fullPrice' => $item['full_price'] ? (float)$item['full_price'] : null,
        'formattedHalfPrice' => $item['half_price'] ? formatPrice($item['half_price'], $language) : null,
        'formattedFullPrice' => $item['full_price'] ? formatPrice($item['full_price'], $language) : null,
        'defaultPrice' => (float)(($item['is_half_full'] && $item['half_price']) ? $item['half_price'] : $item['price']),
        'formattedDefaultPrice' => formatPrice(($item['is_half_full'] && $item['half_price']) ? $item['half_price'] : $item['price'], $language)
    ];
}

function formatPrice($price, $language) {
    return ($language === 'ar') ? number_format($price, 0) . ' ريال قطري' : 'QAR ' . number_format($price, 0);
}
?>