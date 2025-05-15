<?php
require_once 'config.php';
require_once 'db/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Get active theme from database or use default
$activeTheme = getActiveTheme();

// Get featured items for hero banner
$featuredItems = getFeaturedItems();

// Get categories for quick navigation
$categories = getCategories();

// Page title
$pageTitle = "Home";

require_once 'includes/header.php';
?>

<div class="flex flex-col min-h-screen bg-[#fff6f0]">
    <!-- Hero Banner Section -->
    <section class="w-full relative overflow-hidden">
        <div class="swiper-container hero-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($featuredItems as $item): ?>
                <div class="swiper-slide relative h-64">
                    <img src="assets/images/foods/<?php echo $item['image']; ?>" 
                        alt="<?php echo $item['name']; ?>" 
                        class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#3a001e]/80 to-transparent">
                        <div class="absolute bottom-0 left-0 p-4 text-white">
                            <h2 class="text-2xl font-bold"><?php echo $item['name']; ?></h2>
                            <p class="text-sm opacity-90"><?php echo $item['short_description']; ?></p>
                            <a href="pages/item-detail.php?id=<?php echo $item['id']; ?>" 
                               class="mt-2 inline-block px-4 py-2 bg-[#c35331] text-white rounded-full text-sm font-medium">
                                Order Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <!-- Welcome Section -->
    <section class="px-4 py-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[#3a001e]">Welcome to <span class="text-[#c35331]">FoodHub</span></h1>
            <p class="mt-2 text-[#3a001e]/70">Delicious food, delivered to your table</p>
        </div>
    </section>

    <!-- Quick Categories -->
    <section class="px-4 py-2">
        <h2 class="text-lg font-semibold text-[#3a001e] mb-3">Categories</h2>
        <div class="flex overflow-x-auto pb-2 gap-3 hide-scrollbar">
            <?php foreach ($categories as $category): ?>
            <a href="pages/menu.php?category=<?php echo $category['id']; ?>" 
               class="flex-shrink-0 w-24 flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-[#b98473] flex items-center justify-center mb-1">
                    <img src="assets/images/icons/<?php echo $category['icon']; ?>" 
                         alt="<?php echo $category['name']; ?>" 
                         class="w-8 h-8">
                </div>
                <span class="text-xs text-center text-[#3a001e] font-medium"><?php echo $category['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Popular Items -->
    <section class="px-4 py-4">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-[#3a001e]">Popular Items</h2>
            <a href="pages/menu.php" class="text-sm text-[#c35331] font-medium">View All</a>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <?php 
            $popularItems = getPopularItems(4);
            foreach ($popularItems as $item): 
            ?>
            <a href="pages/item-detail.php?id=<?php echo $item['id']; ?>" 
               class="bg-white rounded-lg overflow-hidden shadow-sm">
                <div class="h-32 overflow-hidden">
                    <img src="assets/images/foods/<?php echo $item['image']; ?>" 
                         alt="<?php echo $item['name']; ?>" 
                         class="w-full h-full object-cover">
                </div>
                <div class="p-2">
                    <h3 class="text-sm font-medium text-[#3a001e] truncate"><?php echo $item['name']; ?></h3>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[#c35331] font-bold">₹<?php echo $item['price']; ?></span>
                        <div class="flex items-center">
                            <span class="text-xs text-[#3a001e]/70">⭐ <?php echo $item['rating']; ?></span>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Special Offers -->
    <section class="px-4 py-4">
        <h2 class="text-lg font-semibold text-[#3a001e] mb-3">Special Offers</h2>
        <div class="bg-gradient-to-r from-[#c35331] to-[#b98473] rounded-lg p-4 text-white">
            <h3 class="text-xl font-bold">30% OFF</h3>
            <p class="text-sm">On your first order with code:</p>
            <div class="bg-white text-[#c35331] font-bold text-center py-2 rounded mt-2">WELCOME30</div>
            <p class="text-xs mt-2 opacity-80">Valid until May 30, 2025</p>
        </div>
    </section>

    <!-- App Advertisement -->
    <section class="px-4 py-6 mb-4">
        <div class="bg-[#3a001e] rounded-lg p-4 text-white flex items-center">
            <div class="flex-1">
                <h3 class="text-lg font-bold">Enjoy Our App?</h3>
                <p class="text-xs opacity-90 mt-1">Add to home screen for the best experience!</p>
            </div>
            <button id="addToHomeBtn" class="bg-[#c35331] text-white px-3 py-2 rounded-full text-xs font-medium">
                Add Now
            </button>
        </div>
    </section>
</div>

<!-- Mobile Navigation -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex justify-around py-2 z-50">
    <a href="index.php" class="flex flex-col items-center text-[#c35331]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span class="text-xs">Home</span>
    </a>
    <a href="pages/menu.php" class="flex flex-col items-center text-[#3a001e]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <span class="text-xs">Menu</span>
    </a>
    <a href="pages/cart.php" class="flex flex-col items-center text-[#3a001e]">
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
            <span class="absolute -top-1 -right-1 bg-[#c35331] text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                <?php echo count($_SESSION['cart']); ?>
            </span>
            <?php endif; ?>
        </div>
        <span class="text-xs">Cart</span>
    </a>
    <a href="pages/my-orders.php" class="flex flex-col items-center text-[#3a001e]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <span class="text-xs">Orders</span>
    </a>
    <a href="<?php echo isset($_SESSION['user_id']) ? 'pages/profile.php' : 'pages/login.php'; ?>" class="flex flex-col items-center text-[#3a001e]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span class="text-xs">Account</span>
    </a>
</div>

<script>
// Initialize Swiper for the hero banner
document.addEventListener('DOMContentLoaded', function() {
    new Swiper('.hero-swiper', {
        loop: true,
        autoplay: {
            delay: 3000,
        },
        pagination: {
            el: '.swiper-pagination',
        },
    });

    // Add to Home Screen functionality
    const addToHomeBtn = document.getElementById('addToHomeBtn');
    if (addToHomeBtn) {
        addToHomeBtn.addEventListener('click', function() {
            alert('To add this app to your home screen: \n\n1. Tap the share icon in your browser\n2. Scroll down and select "Add to Home Screen"');
        });
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>