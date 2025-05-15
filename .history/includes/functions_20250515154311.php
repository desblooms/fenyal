<?php
/**
 * Core functions for the Restaurant Menu Application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get active theme from database or session
 * 
 * @return string Theme name (e.g., 'theme-1')
 */
function getActiveTheme() {
    global $conn;
    
    // First check session for theme preference
    if (isset($_SESSION['theme'])) {
        return $_SESSION['theme'];
    }
    
    // If not in session, check database
    try {
        $query = "SELECT setting_value FROM settings WHERE setting_key = 'active_theme'";
        $result = executeQuery($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Save to session for future use
            $_SESSION['theme'] = $row['setting_value'];
            return $row['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Error fetching active theme: " . $e->getMessage());
    }
    
    // Default theme if nothing found
    return 'theme-1';
}

/**
 * Format currency values
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    // Get currency symbol (default to ₹ for Indian Rupees)
    $symbol = '₹';
    
    try {
        $query = "SELECT setting_value FROM settings WHERE setting_key = 'currency_symbol'";
        $result = executeQuery($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['setting_value'])) {
                $symbol = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching currency symbol: " . $e->getMessage());
    }
    
    return $symbol . ' ' . number_format($amount, 2);
}

/**
 * Escape HTML to prevent XSS
 * 
 * @param string $text Text to escape
 * @return string Escaped text
 */
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Get menu categories
 * 
 * @return array Array of categories
 */
function getMenuCategories() {
    $categories = [];
    try {
        $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY position ASC";
        $result = executeQuery($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching menu categories: " . $e->getMessage());
    }
    
    return $categories;
}

/**
 * Get menu items by category
 * 
 * @param int $categoryId Category ID
 * @return array Array of menu items
 */
function getMenuItemsByCategory($categoryId) {
    $items = [];
    try {
        $query = "SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY position ASC";
        $result = executeQuery($query, [$categoryId], 'i');
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching menu items: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get featured items for hero banner
 * 
 * @param int $limit Number of items to return
 * @return array Array of featured items
 */
function getFeaturedItems($limit = 3) {
    $items = [];
    try {
        $query = "SELECT * FROM menu_items WHERE is_special = 1 AND is_available = 1 ORDER BY id DESC LIMIT ?";
        $result = executeQuery($query, [$limit], 'i');
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching featured items: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get popular items
 * 
 * @param int $limit Number of items to return
 * @return array Array of popular items
 */
function getPopularItems($limit = 4) {
    $items = [];
    try {
        $query = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY id DESC LIMIT ?";
        $result = executeQuery($query, [$limit], 'i');
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching popular items: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get categories for navigation
 * 
 * @return array Array of categories
 */
function getCategories() {
    return getMenuCategories();
}

/**
 * Get flash message from session
 * 
 * @return array|null Flash message or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Set flash message in session
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message text
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get menu item by ID
 * 
 * @param int $id Item ID
 * @return array|null Item details or null if not found
 */
function getMenuItem($id) {
    try {
        $query = "SELECT * FROM menu_items WHERE id = ?";
        $result = executeQuery($query, [$id], 'i');
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    } catch (Exception $e) {
        error_log("Error fetching menu item: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Get item add-ons
 * 
 * @param int $itemId Item ID
 * @return array Array of add-ons grouped by category
 */
function getItemAddons($itemId) {
    $addonCategories = [];
    try {
        // Get addon categories for this item
        $query = "SELECT ac.* FROM addon_categories ac
                  JOIN menu_item_addon_categories miac ON ac.id = miac.addon_category_id
                  WHERE miac.menu_item_id = ?";
        $result = executeQuery($query, [$itemId], 'i');
        
        if ($result && $result->num_rows > 0) {
            while ($category = $result->fetch_assoc()) {
                $category['addons'] = [];
                
                // Get addons for this category
                $addonQuery = "SELECT * FROM addons WHERE category_id = ? AND is_available = 1";
                $addonResult = executeQuery($addonQuery, [$category['id']], 'i');
                
                if ($addonResult) {
                    while ($addon = $addonResult->fetch_assoc()) {
                        $category['addons'][] = $addon;
                    }
                }
                
                $addonCategories[] = $category;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching item addons: " . $e->getMessage());
    }
    
    return $addonCategories;
}
?>