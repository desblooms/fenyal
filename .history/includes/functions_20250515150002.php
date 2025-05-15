<?php
/**
 * Core functions for the Restaurant Menu Application
 * 
 * This file contains utility functions used throughout the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Database connection function
 * 
 * @return PDO Database connection object
 */
function getDbConnection() {
    require_once __DIR__ . '/../db/db.php';
    return $conn;
}

/**
 * Format currency values
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    // Get currency symbol from settings, default to ₹ (INR)
    $symbol = getAppSetting('currency_symbol') ?: '₹';
    return $symbol . ' ' . number_format($amount, 2);
}

/**
 * Escape HTML entities to prevent XSS
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get active theme from database
 * 
 * @return string Theme file name (e.g., 'theme-1.css')
 */
function getActiveTheme() {
    $theme = getAppSetting('active_theme');
    return $theme ?: 'theme-1.css'; // Default theme if none set
}

/**
 * Get application settings from database
 * 
 * @param string $key Setting key to retrieve
 * @return mixed Setting value or null if not found
 */
function getAppSetting($key) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['setting_value'];
        }
        
        return null;
    } catch (PDOException $e) {
        // Log error and return null
        error_log("Database error in getAppSetting: " . $e->getMessage());
        return null;
    }
}

/**
 * Update application setting
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success status
 */
function updateAppSetting($key, $value) {
    try {
        $db = getDbConnection();
        
        // Check if setting exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");
        $checkStmt->bindParam(':key', $key);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            // Update existing setting
            $stmt = $db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
        } else {
            // Insert new setting
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
        }
        
        $stmt->bindParam(':key', $key);
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
        
    } catch (PDOException $e) {
        // Log error and return false
        error_log("Database error in updateAppSetting: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool Login status
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool Admin status
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get menu categories
 * 
 * @return array Array of categories
 */
function getMenuCategories() {
    try {
        $db = getDbConnection();
        $stmt = $db->query("SELECT * FROM categories ORDER BY display_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getMenuCategories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get menu items by category
 * 
 * @param int $categoryId Category ID
 * @return array Array of menu items
 */
function getMenuItemsByCategory($categoryId) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT * FROM menu_items WHERE category_id = :category_id AND is_available = 1 ORDER BY display_order ASC");
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getMenuItemsByCategory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get menu item details
 * 
 * @param int $itemId Item ID
 * @return array|null Item details or null if not found
 */
function getMenuItem($itemId) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = :id");
        $stmt->bindParam(':id', $itemId);
        $stmt->execute();
        
        if ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get add-ons for this item
            $item['addons'] = getItemAddons($itemId);
            return $item;
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Database error in getMenuItem: " . $e->getMessage());
        return null;
    }
}

/**
 * Get item add-ons
 * 
 * @param int $itemId Item ID
 * @return array Array of add-ons
 */
function getItemAddons($itemId) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT * FROM item_addons 
            WHERE item_id = :item_id 
            ORDER BY addon_group, display_order ASC
        ");
        $stmt->bindParam(':item_id', $itemId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getItemAddons: " . $e->getMessage());
        return [];
    }
}

/**
 * Process phone number login
 * 
 * @param string $phone Phone number
 * @return bool|array False on failure, user data on success
 */
function processPhoneLogin($phone) {
    try {
        $db = getDbConnection();
        
        // Clean phone number
        $phone = preg_replace('/\D/', '', $phone);
        
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = :phone");
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Existing user
            return $user;
        } else {
            // Create new user
            $stmt = $db->prepare("INSERT INTO users (phone, created_at) VALUES (:phone, NOW())");
            $stmt->bindParam(':phone', $phone);
            
            if ($stmt->execute()) {
                $userId = $db->lastInsertId();
                $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Database error in processPhoneLogin: " . $e->getMessage());
        return false;
    }
}

/**
 * Create order in database
 * 
 * @param array $orderData Order data
 * @return int|bool Order ID or false on failure
 */
function createOrder($orderData) {
    try {
        $db = getDbConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Insert order header
        $stmt = $db->prepare("
            INSERT INTO orders (
                user_id, order_type, table_number, delivery_address,
                total_amount, payment_method, order_status, order_notes,
                created_at
            ) VALUES (
                :user_id, :order_type, :table_number, :delivery_address,
                :total_amount, :payment_method, 'pending', :order_notes,
                NOW()
            )
        ");
        
        $stmt->bindParam(':user_id', $orderData['user_id']);
        $stmt->bindParam(':order_type', $orderData['order_type']);
        $stmt->bindParam(':table_number', $orderData['table_number']);
        $stmt->bindParam(':delivery_address', $orderData['delivery_address']);
        $stmt->bindParam(':total_amount', $orderData['total_amount']);
        $stmt->bindParam(':payment_method', $orderData['payment_method']);
        $stmt->bindParam(':order_notes', $orderData['order_notes']);
        
        if (!$stmt->execute()) {
            $db->rollBack();
            return false;
        }
        
        $orderId = $db->lastInsertId();
        
        // Insert order items
        foreach ($orderData['items'] as $item) {
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, menu_item_id, quantity, unit_price,
                    item_addons, special_instructions
                ) VALUES (
                    :order_id, :menu_item_id, :quantity, :unit_price,
                    :item_addons, :special_instructions
                )
            ");
            
            $stmt->bindParam(':order_id', $orderId);
            $stmt->bindParam(':menu_item_id', $item['menu_item_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':unit_price', $item['unit_price']);
            $stmt->bindParam(':item_addons', $item['item_addons']);
            $stmt->bindParam(':special_instructions', $item['special_instructions']);
            
            if (!$stmt->execute()) {
                $db->rollBack();
                return false;
            }
        }
        
        // Commit transaction
        $db->commit();
        return $orderId;
        
    } catch (PDOException $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        error_log("Database error in createOrder: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user orders
 * 
 * @param int $userId User ID
 * @return array Array of orders
 */
function getUserOrders($userId) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = :user_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getUserOrders: " . $e->getMessage());
        return [];
    }
}

/**
 * Get order details
 * 
 * @param int $orderId Order ID
 * @return array|null Order details or null if not found
 */
function getOrderDetails($orderId) {
    try {
        $db = getDbConnection();
        
        // Get order header
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->bindParam(':id', $orderId);
        $stmt->execute();
        
        if ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get order items
            $stmt = $db->prepare("
                SELECT oi.*, mi.name as item_name
                FROM order_items oi
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE oi.order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $order;
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Database error in getOrderDetails: " . $e->getMessage());
        return null;
    }
}

/**
 * Update order status
 * 
 * @param int $orderId Order ID
 * @param string $status New status
 * @return bool Success status
 */
function updateOrderStatus($orderId, $status) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("
            UPDATE orders 
            SET order_status = :status, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->bindParam(':id', $orderId);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Database error in updateOrderStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Format WhatsApp order message
 * 
 * @param array $orderData Order data
 * @return string Formatted message
 */
function formatWhatsAppOrderMessage($orderData) {
    $restaurantName = getAppSetting('restaurant_name') ?: 'Our Restaurant';
    
    $message = "*New Order from {$restaurantName}*\n\n";
    $message .= "*Order Type:* " . ucfirst($orderData['order_type']) . "\n";
    
    if ($orderData['order_type'] === 'dine_in') {
        $message .= "*Table:* " . $orderData['table_number'] . "\n";
    } elseif ($orderData['order_type'] === 'delivery') {
        $message .= "*Delivery Address:* " . $orderData['delivery_address'] . "\n";
    }
    
    $message .= "\n*Items:*\n";
    
    $total = 0;
    foreach ($orderData['items'] as $item) {
        $menuItem = getMenuItem($item['menu_item_id']);
        $addons = '';
        
        if (!empty($item['item_addons'])) {
            $addonArray = json_decode($item['item_addons'], true);
            if (is_array($addonArray)) {
                foreach ($addonArray as $addon) {
                    $addons .= "\n   + " . $addon['name'] . ' (' . formatCurrency($addon['price']) . ')';
                }
            }
        }
        
        $itemTotal = ($menuItem['price'] * $item['quantity']);
        $message .= "• {$item['quantity']}x {$menuItem['name']} - " . formatCurrency($itemTotal) . $addons . "\n";
        
        if (!empty($item['special_instructions'])) {
            $message .= "   Note: {$item['special_instructions']}\n";
        }
        
        $total += $itemTotal;
    }
    
    $message .= "\n*Total:* " . formatCurrency($total) . "\n";
    
    if (!empty($orderData['order_notes'])) {
        $message .= "\n*Order Notes:* {$orderData['order_notes']}\n";
    }
    
    $message .= "\nThank you for your order!";
    
    return $message;
}

/**
 * Clean and validate input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if the current device is mobile
 * 
 * @return bool True if mobile device
 */
function isMobileDevice() {
    return preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone)/i', $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Generate a unique order reference number
 * 
 * @return string Order reference
 */
function generateOrderReference() {
    $prefix = 'ORD';
    $timestamp = date('YmdHis');
    $random = mt_rand(100, 999);
    return $prefix . $timestamp . $random;
}