<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/functions.php';

// Get base URL for assets
$baseUrl = isset($baseUrl) ? $baseUrl : '';

// Get active theme
$theme = getActiveTheme();

// Get cart count
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
} elseif (isset($_SESSION['guest_cart'])) {
    foreach ($_SESSION['guest_cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Current page for highlighting active menu items
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/main.css">
    
    <!-- Theme CSS -->
    <link id="theme-css" rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/themes/<?php echo $theme; ?>.css">
    
    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo $baseUrl; ?>manifest.json">
    <meta name="theme-color" content="#3a001e">
    <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>assets/images/icons/logo-192.png">
    
    <!-- Swiper for sliders if needed -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
</head>
<body class="bg-[#fff6f0] text-[#3a001e] min-h-screen flex flex-col">
    <!-- Status Bar (App-like appearance) -->
    <div class="bg-[#3a001e] text-white py-1 px-4 flex justify-between items-center text-xs">
        <div>
            <i class="fas fa-signal"></i>
            <i class="fas fa-wifi ml-1"></i>
        </div>
        <div class="font-medium">
            <?php echo date('g:i A'); ?>
        </div>
        <div>
            <i class="fas fa-battery-full"></i>
        </div>
    </div>

    <!-- Main Header -->
    <header class="bg-[#3a001e] text-white sticky top-0 z-50 shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Left side: Back button or Logo -->
                <div class="flex items-center">
                    <?php if ($currentPage !== 'index.php'): ?>
                    <a href="javascript:history.back()" class="mr-3 text-white">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo $baseUrl; ?>index.php" class="text-xl font-bold flex items-center">
                        <img src="<?php echo $baseUrl; ?>assets/images/icons/logo.png" alt="<?php echo APP_NAME; ?>" class="h-8 mr-2">
                        <?php echo APP_NAME; ?>
                    </a>
                </div>
                
                <!-- Right side: Action buttons -->
                <div class="flex items-center space-x-4">
                    <!-- Search button -->
                    <button id="search-toggle" class="text-white focus:outline-none">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                    
                    <!-- Cart icon with badge -->
                    <a href="<?php echo $baseUrl; ?>pages/cart.php" class="text-white relative">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-[#c35331] text-white rounded-full h-5 w-5 flex items-center justify-center text-xs">
                            <?php echo $cartCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Account / Login -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="text-white focus:outline-none">
                            <i class="fas fa-user-circle text-xl"></i>
                        </button>
                        <div class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <div class="px-4 py-2 text-sm text-[#3a001e] border-b">
                                Hello, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'; ?>
                            </div>
                            <a href="<?php echo $baseUrl; ?>pages/my-orders.php" class="block px-4 py-2 text-sm text-[#3a001e] hover:bg-gray-100">
                                My Orders
                            </a>
                            <a href="<?php echo $baseUrl; ?>pages/my-account.php" class="block px-4 py-2 text-sm text-[#3a001e] hover:bg-gray-100">
                                My Account
                            </a>
                            <a href="<?php echo $baseUrl; ?>pages/logout.php" class="block px-4 py-2 text-sm text-[#3a001e] hover:bg-gray-100">
                                Logout
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>pages/login.php" class="text-white">
                        <i class="fas fa-sign-in-alt text-xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Search Bar (hidden by default, toggle with JS) -->
        <div id="search-bar" class="bg-[#b98473] py-2 px-4 hidden">
            <form action="<?php echo $baseUrl; ?>pages/menu.php" method="get" class="flex">
                <input type="text" name="search" placeholder="Search menu..." 
                    class="w-full py-2 px-3 rounded-l-full text-[#3a001e] focus:outline-none">
                <button type="submit" class="bg-[#c35331] text-white px-4 rounded-r-full">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <!-- Category Navigation (visible on menu page) -->
        <?php if ($currentPage === 'menu.php'): ?>
        <div class="bg-[#b98473] overflow-x-auto whitespace-nowrap py-2 px-1 hide-scrollbar">
            <?php 
            // Fetch categories
            $categories = getMenuCategories();
            foreach ($categories as $category): 
            ?>
            <a href="#category-<?php echo $category['id']; ?>" 
               class="inline-block px-4 py-1 mx-1 rounded-full bg-[#fff6f0] text-[#3a001e] text-sm font-medium">
                <?php echo $category['name']; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>

    <!-- Flash Messages -->
    <?php $flashMessage = getFlashMessage(); ?>
    <?php if ($flashMessage): ?>
    <div class="flash-message container mx-auto px-4 py-2 mt-2">
        <div class="rounded px-4 py-3 <?php 
            if ($flashMessage['type'] === 'success') echo 'bg-green-100 text-green-800'; 
            elseif ($flashMessage['type'] === 'error') echo 'bg-red-100 text-red-800';
            elseif ($flashMessage['type'] === 'warning') echo 'bg-yellow-100 text-yellow-800';
            else echo 'bg-blue-100 text-blue-800';
        ?>">
            <?php echo $flashMessage['message']; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <main class="flex-grow container mx-auto px-4 py-4">
    <!-- Header ends here, main content of each page goes below -->

<script>
    // Toggle search bar visibility
    document.getElementById('search-toggle').addEventListener('click', function() {
        const searchBar = document.getElementById('search-bar');
        searchBar.classList.toggle('hidden');
        if (!searchBar.classList.contains('hidden')) {
            searchBar.querySelector('input').focus();
        }
    });
    
    // Hide scrollbar but allow scrolling for category nav
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
            .hide-scrollbar::-webkit-scrollbar {
                display: none;
            }
            .hide-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        `;
        document.head.appendChild(style);
    });
</script>