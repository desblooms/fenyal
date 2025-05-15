<?php
// Start session if not already started
if (session_status() === PHP_SESSION_INACTIVE) {
    session_start();
}

// Include configuration and database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db/db.php';

// Get current theme from settings (default to theme-1 if not set)
$query = "SELECT value FROM settings WHERE setting_name = 'active_theme'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $activeTheme = $row['value'];
} else {
    $activeTheme = 'theme-1';
}

// Get cart count if user is logged in
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $cartQuery = "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$userId'";
    $cartResult = mysqli_query($conn, $cartQuery);
    
    if ($cartResult && mysqli_num_rows($cartResult) > 0) {
        $cartRow = mysqli_fetch_assoc($cartResult);
        $cartCount = $cartRow['total'] ? $cartRow['total'] : 0;
    }
}

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flavors Hub - Digital Menu</title>
    
    <!-- Tailwind CSS CDN (you can replace with local installation) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/main.css">
    
    <!-- Active Theme CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/themes/<?php echo $activeTheme; ?>.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Optional: PWA Support -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#c35331">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-192x192.png">
</head>
<body class="bg-light-bg text-dark-text min-h-screen flex flex-col">
    <!-- Header Bar -->
    <header class="bg-primary text-white sticky top-0 z-50 shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo and Back Button -->
                <div class="flex items-center space-x-2">
                    <?php if ($currentPage !== 'index.php'): ?>
                    <a href="javascript:history.back()" class="text-white p-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo BASE_URL; ?>" class="font-bold text-xl flex items-center">
                        <img src="<?php echo BASE_URL; ?>/assets/images/icons/logo.png" alt="Logo" class="h-8 mr-2">
                        Flavors Hub
                    </a>
                </div>
                
                <!-- Right Side Navigation -->
                <div class="flex items-center space-x-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Cart Icon with Badge -->
                        <a href="<?php echo BASE_URL; ?>/pages/cart.php" class="relative p-2">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <?php if ($cartCount > 0): ?>
                            <span class="absolute top-0 right-0 bg-accent text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $cartCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- User Account Icon -->
                        <div class="relative group">
                            <button class="p-2 focus:outline-none">
                                <i class="fas fa-user-circle text-xl"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                                <a href="<?php echo BASE_URL; ?>/pages/my-orders.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    My Orders
                                </a>
                                <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login Button -->
                        <a href="<?php echo BASE_URL; ?>/pages/login.php" class="p-2">
                            <i class="fas fa-sign-in-alt text-xl"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Menu Button for Mobile -->
                    <button id="mobile-menu-button" class="p-2 focus:outline-none lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Navigation Menu (hidden by default) -->
            <div id="mobile-menu" class="hidden lg:hidden mt-2 pb-2">
                <nav class="flex flex-col space-y-2">
                    <a href="<?php echo BASE_URL; ?>" class="<?php echo $currentPage === 'index.php' ? 'font-bold' : ''; ?> py-2 px-4 hover:bg-primary-dark rounded">Home</a>
                    <a href="<?php echo BASE_URL; ?>/pages/menu.php" class="<?php echo $currentPage === 'menu.php' ? 'font-bold' : ''; ?> py-2 px-4 hover:bg-primary-dark rounded">Menu</a>
                    <a href="<?php echo BASE_URL; ?>/pages/my-orders.php" class="<?php echo $currentPage === 'my-orders.php' ? 'font-bold' : ''; ?> py-2 px-4 hover:bg-primary-dark rounded">My Orders</a>
                    <a href="<?php echo BASE_URL; ?>/pages/contact.php" class="<?php echo $currentPage === 'contact.php' ? 'font-bold' : ''; ?> py-2 px-4 hover:bg-primary-dark rounded">Contact</a>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- Desktop Navigation (hidden on mobile) -->
    <nav class="bg-secondary text-white hidden lg:block shadow-md">
        <div class="container mx-auto px-4">
            <ul class="flex justify-center space-x-8">
                <li>
                    <a href="<?php echo BASE_URL; ?>" class="<?php echo $currentPage === 'index.php' ? 'border-b-2 border-accent' : ''; ?> py-3 px-2 inline-block hover:text-accent transition">Home</a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/menu.php" class="<?php echo $currentPage === 'menu.php' ? 'border-b-2 border-accent' : ''; ?> py-3 px-2 inline-block hover:text-accent transition">Menu</a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/my-orders.php" class="<?php echo $currentPage === 'my-orders.php' ? 'border-b-2 border-accent' : ''; ?> py-3 px-2 inline-block hover:text-accent transition">My Orders</a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/contact.php" class="<?php echo $currentPage === 'contact.php' ? 'border-b-2 border-accent' : ''; ?> py-3 px-2 inline-block hover:text-accent transition">Contact</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main class="flex-grow container mx-auto px-4 py-6">
    <!-- Header ends here, main content will be placed below -->