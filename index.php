<?php
require_once 'config.php';
require_once 'db/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Get featured items for hero banner
$featuredItems = [];
try {
    $query = "SELECT * FROM menu_items WHERE is_special = 1 AND is_available = 1 LIMIT 3";
    $result = executeQuery($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $featuredItems[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching featured items: " . $e->getMessage());
}

// Get categories for quick navigation
$categories = [];
try {
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY position ASC LIMIT 6";
    $result = executeQuery($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get popular items
$popularItems = [];
try {
    $query = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY id DESC LIMIT 4";
    $result = executeQuery($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $popularItems[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching popular items: " . $e->getMessage());
}

// Page title
$pageTitle = "Home";

// Include header
require_once 'includes/header.php';
?>

<div class="flex flex-col min-h-screen bg-[#fff6f0]">
    <!-- Hero Banner Section -->
    <section class="w-full relative overflow-hidden">
        <?php if (count($featuredItems) > 0): ?>
        <div class="swiper-container hero-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($featuredItems as $item): ?>
                <div class="swiper-slide relative h-64">
                    <img src="<?php echo $baseUrl; ?>assets/images/foods/<?php echo !empty($item['image']) ? $item['image'] : 'default-food.jpg'; ?>" 
                        alt="<?php echo escapeHtml($item['name']); ?>" 
                        class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#3a001e]/80 to-transparent">
                        <div class="absolute bottom-0 left-0 p-4 text-white">
                            <h2 class="text-2xl font-bold"><?php echo escapeHtml($item['name']); ?></h2>
                            <p class="text-sm opacity-90"><?php echo !empty($item['description']) ? escapeHtml(substr($item['description'], 0, 80)) : ''; ?><?php echo (strlen($item['description']) > 80) ? '...' : ''; ?></p>
                            <a href="<?php echo $baseUrl; ?>pages/item-detail.php?id=<?php echo $item['id']; ?>" 
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
        <?php else: ?>
        <div class="relative h-64 bg-[#3a001e]">
            <div class="absolute inset-0 flex items-center justify-center text-white">
                <div class="text-center">
                    <h2 class="text-2xl font-bold"><?php echo APP_NAME; ?></h2>
                    <p class="mt-2 opacity-90">Delicious food, delivered to your table</p>
                    <a href="<?php echo $baseUrl; ?>pages/menu.php" 
                       class="mt-4 inline-block px-6 py-2 bg-[#c35331] text-white rounded-full text-sm font-medium">
                        View Menu
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Welcome Section -->
    <section class="px-4 py-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[#3a001e]">Welcome to <span class="text-[#c35331]"><?php echo APP_NAME; ?></span></h1>
            <p class="mt-2 text-[#3a001e]/70">Delicious food, delivered to your table</p>
        </div>
    </section>

    <!-- Quick Categories -->
    <?php if (count($categories) > 0): ?>
    <section class="px-4 py-2">
        <h2 class="text-lg font-semibold text-[#3a001e] mb-3">Categories</h2>
        <div class="flex overflow-x-auto pb-2 gap-3 hide-scrollbar">
            <?php foreach ($categories as $category): ?>
            <a href="<?php echo $baseUrl; ?>pages/menu.php?category=<?php echo $category['id']; ?>" 
               class="flex-shrink-0 w-24 flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-[#b98473] flex items-center justify-center mb-1">
                    <?php if (!empty($category['image'])): ?>
                    <img src="<?php echo $baseUrl; ?>assets/images/icons/<?php echo $category['image']; ?>" 
                         alt="<?php echo escapeHtml($category['name']); ?>" 
                         class="w-8 h-8">
                    <?php else: ?>
                    <i class="fas fa-utensils w-8 h-8 flex items-center justify-center text-white"></i>
                    <?php endif; ?>
                </div>
                <span class="text-xs text-center text-[#3a001e] font-medium"><?php echo escapeHtml($category['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular Items -->
    <?php if (count($popularItems) > 0): ?>
    <section class="px-4 py-4">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-[#3a001e]">Popular Items</h2>
            <a href="<?php echo $baseUrl; ?>pages/menu.php" class="text-sm text-[#c35331] font-medium">View All</a>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <?php foreach ($popularItems as $item): ?>
            <a href="<?php echo $baseUrl; ?>pages/item-detail.php?id=<?php echo $item['id']; ?>" 
               class="bg-white rounded-lg overflow-hidden shadow-sm">
                <div class="h-32 overflow-hidden">
                    <img src="<?php echo $baseUrl; ?>assets/images/foods/<?php echo !empty($item['image']) ? $item['image'] : 'default-food.jpg'; ?>" 
                         alt="<?php echo escapeHtml($item['name']); ?>" 
                         class="w-full h-full object-cover">
                    <?php if ($item['is_veg']): ?>
                    <div class="absolute top-1 right-1 bg-green-500 w-4 h-4 rounded-full flex items-center justify-center">
                        <div class="w-2 h-2 bg-white rounded-full"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-2">
                    <h3 class="text-sm font-medium text-[#3a001e] truncate"><?php echo escapeHtml($item['name']); ?></h3>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[#c35331] font-bold"><?php echo formatCurrency($item['price']); ?></span>
                        <button class="text-xs bg-[#3a001e] text-white px-2 py-1 rounded-full">
                            Add
                        </button>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Special Offers -->
    <section class="px-4 py-4">
        <h2 class="text-lg font-semibold text-[#3a001e] mb-3">Special Offers</h2>
        <div class="bg-gradient-to-r from-[#c35331] to-[#b98473] rounded-lg p-4 text-white">
            <h3 class="text-xl font-bold">30% OFF</h3>
            <p class="text-sm">On your first order with code:</p>
            <div class="bg-white text-[#c35331] font-bold text-center py-2 rounded mt-2">WELCOME30</div>
            <p class="text-xs mt-2 opacity-80">Valid until June 30, 2025</p>
        </div>
    </section>

    <!-- App Advertisement -->
    <section class="px-4 py-6 mb-16">
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

<script>
// Initialize Swiper for the hero banner
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.hero-swiper')) {
        new Swiper('.hero-swiper', {
            loop: true,
            autoplay: {
                delay: 3000,
            },
            pagination: {
                el: '.swiper-pagination',
            },
        });
    }

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
// Include footer
require_once 'includes/footer.php';
?>