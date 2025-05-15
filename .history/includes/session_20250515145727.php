<?php
/**
 * Session management for restaurant ordering app
 * Handles user sessions, authentication, and cart data
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get user ID if logged in
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get user's phone number if logged in
 * 
 * @return string|null Phone number if logged in, null otherwise
 */
function getUserPhone() {
    return isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : null;
}

/**
 * Get user's name if logged in
 * 
 * @return string|null User name if logged in, null otherwise
 */
function getUserName() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
}

/**
 * Log user in
 * 
 * @param int $userId User ID
 * @param string $phone User phone number
 * @param string $name User name
 * @param string $role User role (user/admin)
 * @return void
 */
function loginUser($userId, $phone, $name, $role = 'user') {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
    $_SESSION['logged_in_time'] = time();
    
    // If there was a guest cart, associate it with the user
    if (isset($_SESSION['guest_cart'])) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = $_SESSION['guest_cart'];
        }
        unset($_SESSION['guest_cart']);
    }
}

/**
 * Log user out
 * 
 * @return void
 */
function logoutUser() {
    // Keep cart data when logging out, if desired
    $cartData = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    
    // Keep theme preference
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'theme-1';
    
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Start a new session
    session_start();
    
    // Restore cart data to guest cart if desired
    $_SESSION['guest_cart'] = $cartData;
    
    // Restore theme preference
    $_SESSION['theme'] = $theme;
}

/**
 * Get current active theme
 * 
 * @return string Theme name (e.g., 'theme-1')
 */
function getCurrentTheme() {
    return isset($_SESSION['theme']) ? $_SESSION['theme'] : 'theme-1';
}

/**
 * Set current active theme
 * 
 * @param string $theme Theme name (e.g., 'theme-1')
 * @return void
 */
function setCurrentTheme($theme) {
    $_SESSION['theme'] = $theme;
}

/**
 * Get cart items
 * 
 * @return array Cart items
 */
function getCartItems() {
    if (isLoggedIn()) {
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    } else {
        return isset($_SESSION['guest_cart']) ? $_SESSION['guest_cart'] : [];
    }
}

/**
 * Add item to cart
 * 
 * @param array $item Item data including id, name, price, quantity, addons
 * @return void
 */
function addToCart($item) {
    if (isLoggedIn()) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if item already exists (with same add-ons)
        $found = false;
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['id'] === $item['id']) {
                // Check if add-ons match
                $addonsMatch = true;
                
                if (isset($cartItem['addons']) && isset($item['addons'])) {
                    if (count($cartItem['addons']) !== count($item['addons'])) {
                        $addonsMatch = false;
                    } else {
                        foreach ($cartItem['addons'] as $key => $addon) {
                            if (!isset($item['addons'][$key]) || $item['addons'][$key] !== $addon) {
                                $addonsMatch = false;
                                break;
                            }
                        }
                    }
                } else if ((isset($cartItem['addons']) && !isset($item['addons'])) || 
                           (!isset($cartItem['addons']) && isset($item['addons']))) {
                    $addonsMatch = false;
                }
                
                if ($addonsMatch) {
                    $cartItem['quantity'] += $item['quantity'];
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
    } else {
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        
        // Check if item already exists (with same add-ons)
        $found = false;
        foreach ($_SESSION['guest_cart'] as &$cartItem) {
            if ($cartItem['id'] === $item['id']) {
                // Check if add-ons match
                $addonsMatch = true;
                
                if (isset($cartItem['addons']) && isset($item['addons'])) {
                    if (count($cartItem['addons']) !== count($item['addons'])) {
                        $addonsMatch = false;
                    } else {
                        foreach ($cartItem['addons'] as $key => $addon) {
                            if (!isset($item['addons'][$key]) || $item['addons'][$key] !== $addon) {
                                $addonsMatch = false;
                                break;
                            }
                        }
                    }
                } else if ((isset($cartItem['addons']) && !isset($item['addons'])) || 
                           (!isset($cartItem['addons']) && isset($item['addons']))) {
                    $addonsMatch = false;
                }
                
                if ($addonsMatch) {
                    $cartItem['quantity'] += $item['quantity'];
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            $_SESSION['guest_cart'][] = $item;
        }
    }
}

/**
 * Update cart item quantity
 * 
 * @param int $index Item index in cart array
 * @param int $quantity New quantity
 * @return void
 */
function updateCartItemQuantity($index, $quantity) {
    if (isLoggedIn()) {
        if (isset($_SESSION['cart'][$index])) {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                removeCartItem($index);
            } else {
                $_SESSION['cart'][$index]['quantity'] = $quantity;
            }
        }
    } else {
        if (isset($_SESSION['guest_cart'][$index])) {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                removeCartItem($index);
            } else {
                $_SESSION['guest_cart'][$index]['quantity'] = $quantity;
            }
        }
    }
}

/**
 * Remove item from cart
 * 
 * @param int $index Item index in cart array
 * @return void
 */
function removeCartItem($index) {
    if (isLoggedIn()) {
        if (isset($_SESSION['cart'][$index])) {
            array_splice($_SESSION['cart'], $index, 1);
        }
    } else {
        if (isset($_SESSION['guest_cart'][$index])) {
            array_splice($_SESSION['guest_cart'], $index, 1);
        }
    }
}

/**
 * Clear cart
 * 
 * @return void
 */
function clearCart() {
    if (isLoggedIn()) {
        $_SESSION['cart'] = [];
    } else {
        $_SESSION['guest_cart'] = [];
    }
}

/**
 * Get cart total
 * 
 * @return float Cart total price
 */
function getCartTotal() {
    $total = 0;
    $items = getCartItems();
    
    foreach ($items as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        
        // Add add-ons prices if present
        if (isset($item['addons'])) {
            foreach ($item['addons'] as $addon) {
                if (isset($addon['price'])) {
                    $itemTotal += $addon['price'] * $item['quantity'];
                }
            }
        }
        
        $total += $itemTotal;
    }
    
    return $total;
}

/**
 * Get cart item count
 * 
 * @return int Number of items in cart
 */
function getCartItemCount() {
    $count = 0;
    $items = getCartItems();
    
    foreach ($items as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

/**
 * Set a flash message to be displayed on the next page load
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message text
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'time' => time()
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null if no message
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
 * Store order data in session (before checkout completion)
 * 
 * @param array $orderData Order data including items, total, delivery type, etc.
 * @return void
 */
function setCurrentOrder($orderData) {
    $_SESSION['current_order'] = $orderData;
}

/**
 * Get current order data
 * 
 * @return array|null Order data or null if no current order
 */
function getCurrentOrder() {
    return isset($_SESSION['current_order']) ? $_SESSION['current_order'] : null;
}

/**
 * Clear current order data after checkout completion
 * 
 * @return void
 */
function clearCurrentOrder() {
    if (isset($_SESSION['current_order'])) {
        unset($_SESSION['current_order']);
    }
}