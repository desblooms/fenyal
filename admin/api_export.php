<?php
// api_export.php - API endpoint to export menu data as JSON
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = getConnection();
    
    // Get all menu items with their related data
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
        
        // Add half/full prices if applicable
        if ($row['is_half_full']) {
            $item['halfPrice'] = $row['half_price'] ? (float)$row['half_price'] : null;
            $item['fullPrice'] = $row['full_price'] ? (float)$row['full_price'] : null;
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
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $menuItems,
        'count' => count($menuItems),
        'generated_at' => date('Y-m-d H:i:s'),
        'version' => '1.0'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to export menu data',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>