<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/functions.php';

// Get active theme from database or use default
$theme = "theme-1"; // Default theme
$query = "SELECT value FROM settings WHERE setting_key = 'active_theme'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $theme = $row['value'];
}

// Get cart count if user is logged in
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = $userId";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $cartCount = $row['cart_count'] ? $row['cart_count'] : 0;
    }
}

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Food App</title>
    
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- App CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/main.css">
    
    <!-- Active Theme CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/themes/<?php echo $theme; ?>.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Manifest for PWA support (optional) -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
</head>
<body class="bg-app-bg text-app-text min-h-screen flex flex-col">
    <!-- Status bar (mobile-app style) -->
    <div class="app-status-bar bg-primary text-white py-1 px-4 flex justify-between items-center">
        <div class="text-sm">
            <i class="fas fa-signal"></i>
            <i class="fas fa-wifi ml-1"></i>
        </div>
        <div class="text-sm font-semibold">
            <?php echo date('g:i A'); ?>
        </div>
        <div class="text-sm">
            <i class="fas fa-battery-full"></i>
        </div>
    </div>
    
    <!-- Top Navigation -->
    <header class="sticky top-0 z-50">
        <nav class="bg-primary text-white p-4 shadow-md">
            <div class="container mx-auto flex justify-between items-center">
                <!-- Left: Logo/Back button -->
                <div class="flex items-center">
                    <?php if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false): ?>
                        <a href="javascript:history.back()" class="mr-3 text-white">
                            <i class="fas fa-chevron-left text-xl"></i>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo BASE_URL; ?>/" class="text-xl font-bold">
                        FoodApp
                    </a>
                </div>
                
                <!-- Right: Action buttons -->
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/cart.php" class="relative">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo $cartCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/my-orders.php">
                            <i class="fas fa-clipboard-list text-xl"></i>
                        </a>
                        <div class="relative group">
                            <button class="focus:outline-none">
                                <i class="fas fa-user-circle text-xl"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                                <div class="px-4 py-2 text-sm text-gray-700 border-b">
                                    Hello, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'; ?>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/pages/my-orders.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    My Orders
                                </a>
                                <a href="<?php echo BASE_URL; ?>/pages/contact.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Contact Us
                                </a>
                                <a href="<?php echo BASE_URL; ?>/includes/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/pages/login.php">
                            <i class="fas fa-sign-in-alt text-xl"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        
        <!-- Category tabs (if on menu page) -->
        <?php if ($currentPage === 'menu.php'): ?>
            <div class="bg-white shadow-md overflow-x-auto">
                <div class="flex py-2 px-1 space-x-2 whitespace-nowrap">
                    <?php
                    // Fetch menu categories
                    $query = "SELECT * FROM categories WHERE status = 1 ORDER BY display_order";
                    $result = mysqli_query($conn, $query);
                    
                    if ($result && mysqli_num_rows($result) > 0):
                        while ($category = mysqli_fetch_assoc($result)):
                    ?>
                        <a href="#category-<?php echo $category['id']; ?>" class="category-tab px-4 py-2 rounded-full text-sm font-medium">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php
                        endwhile;
                    endif;
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </header>
    
    <!-- Main Content Container -->
    <main class="flex-grow container mx-auto p-4">