<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/functions.php';

// Set base URL for assets and links
$baseUrl = isset($baseUrl) ? $baseUrl : '';

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get cart count
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Get active theme
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'theme-1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Swiper JS for sliders (optional) -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/main.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/themes/<?php echo $theme; ?>.css" id="theme-css">
    
    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo $baseUrl; ?>manifest.json">
    <meta name="theme-color" content="#3a001e">
    <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>assets/images/icons/icon-192x192.png">
    
    <!-- Additional styles -->
    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        /* Status bar styling */
        .status-bar {
            height: 20px;
            background-color: #3a001e;
        }
    </style>
</head>
<body class="bg-[#fff6f0] text-[#3a001e] min-h-screen flex flex-col">
    <!-- Mobile status bar (app-like appearance) -->
    <div class="status-bar flex justify-between items-center px-4 text-white text-xs">
        <div><?php echo date('g:i A'); ?></div>
        <div>
            <i class="fas fa-signal"></i>
            <i class="fas fa-wifi ml-1"></i>
            <i class="fas fa-battery-full ml-1"></i>
        </div>
    </div>
    
    <!-- Top header bar -->
    <header class="bg-[#3a001e] text-white shadow-md z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Left side: Back button or logo -->
            <div class="flex items-center">
                <?php if ($currentPage !== 'index.php' && !isset($hideBackButton)): ?>
                <a href="javascript:history.back()" class="mr-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo $baseUrl; ?>index.php" class="font-bold text-xl">
                    <?php echo APP_NAME; ?>
                </a>
            </div>
            
            <!-- Right side: Action buttons -->
            <div class="flex items-center space-x-4">
                <a href="<?php echo $baseUrl; ?>pages/cart.php" class="relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-[#c35331] text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?php echo $cartCount; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $baseUrl; ?>pages/my-orders.php">
                    <i class="fas fa-receipt text-xl"></i>
                </a>
                <?php else: ?>
                <a href="<?php echo $baseUrl; ?>pages/login.php">
                    <i class="fas fa-user-circle text-xl"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($currentPage === 'menu.php' && isset($categories)): ?>
        <!-- Category navigation for menu page -->
        <div class="bg-[#b98473] overflow-x-auto hide-scrollbar">
            <div class="flex py-2 px-4 space-x-4 whitespace-nowrap">
                <?php foreach ($categories as $category): ?>
                <a href="#category-<?php echo $category['id']; ?>" 
                   class="px-4 py-1 text-white text-sm font-medium rounded-full bg-[#3a001e] hover:bg-[#c35331] transition">
                    <?php echo $category['name']; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </header>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="flash-message container mx-auto px-4 mt-3 <?php echo $_SESSION['flash_message']['type']; ?>">
        <div class="rounded-md p-3 <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <p><?php echo $_SESSION['flash_message']['message']; ?></p>
        </div>
    </div>
    <?php 
    // Clear the message after displaying
    unset($_SESSION['flash_message']);
    endif; 
    ?>
    
    <!-- Main content begins (will be closed in footer) -->
    <main class="flex-grow">