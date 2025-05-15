<?php
require_once 'config.php';
require_once 'db/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Initialize base URL for assets
$baseUrl = isset($baseUrl) ? $baseUrl : '';

// Get featured items for hero banner
function getFeaturedItems() {
    // In a real app, you would fetch this from the database
    // For now, returning sample data
    return [
        [
            'id' => 1,
            'name' => 'Special Biryani',
            'short_description' => 'Fragrant basmati rice cooked with aromatic spices',
            'image' => 'biryani.jpg',
            'price' => 249.00
        ],
        [
            'id' => 2,
            'name' => 'Butter Chicken',
            'short_description' => 'Creamy tomato sauce with tender chicken pieces',
            'image' => 'butter-chicken.jpg',
            'price' => 299.00
        ],
        [
            'id' => 3,
            'name' => 'Veg Thali',
            'short_description' => 'Complete meal with variety of vegetarian dishes',
            'image' => 'veg-thali.jpg',
            'price' => 199.00
        ]
    ];
}

// Get categories for quick navigation
function getCategories() {
    // In a real app, you would fetch this from the database
    // For now, returning sample data
    return [
        [
            'id' => 1,
            'name' => 'Starters',
            'icon' => 'starter.png'
        ],
        [
            'id' => 2,
            'name' => 'Main Course',
            'icon' => 'main.png'
        ],
        [
            'id' => 3,
            'name' => 'Desserts',
            'icon' => 'dessert.png'
        ],
        [
            'id' => 4,
            'name' => 'Beverages',
            'icon' => 'beverage.png'
        ],
        [
            'id' => 5,
            'name' => 'Combos',
            'icon' => 'combo.png'
        ]
    ];
}

// Get popular items
function getPopularItems($limit = 4) {
    // In a real app, you would fetch this from the database
    // For now, returning sample data
    return [
        [
            'id' => 1,
            'name' => 'Paneer Butter Masala',
            'image' => 'paneer.jpg',
            'price' => 179.00,
            'rating' => 4.8
        ],
        [
            'id' => 2,
            'name' => 'Chicken Tikka',
            'image' => 'chicken-tikka.jpg',
            'price' => 249.00,
            'rating' => 4.7
        ],
        [
            'id' => 3,
            'name' => 'Veg Pulao',
            'image' => 'veg-pulao.jpg',
            'price' => 149.00,
            'rating' => 4.5
        ],
        [
            'id' => 4,
            'name' => 'Gulab Jamun',
            'image' => 'gulab-jamun.jpg',
            'price' => 99.00,
            'rating' => 4.9
        ]
    ];
}

// Get featured items, categories, and popular items
$featuredItems = getFeaturedItems();
$categories = getCategories();
$popularItems = getPopularItems();

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
                    <img src="<?php echo $baseUrl; ?>assets/images/foods/<?php echo $item['image']; ?>" 
                         alt="<?php echo $item['name']; ?>" 
                         class="w-full h-full object-cover"
                         onerror="this.src='<?php echo $baseUrl; ?>assets/images/placeholder.jpg'">
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
            <h1 class="text-2xl font-bold text-[#3a001e]">Welcome to <span class="text-[#c35331]"><?php echo APP_NAME; ?></span></h1>
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
                    <img src="<?php echo $baseUrl; ?>assets/images/icons/<?php echo $category['icon']; ?>" 
                         alt="<?php echo $category['name']; ?>" 
                         class="w-8 h-8"
                         onerror="this.src='<?php echo $baseUrl; ?>assets/images/icons/default.png'">
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
            <?php foreach ($popularItems as $item): ?>
            <a href="pages/item-detail.php?id=<?php echo $item['id']; ?>" 
               class="bg-white rounded-lg overflow-hidden shadow-sm">
                <div class="h-32 overflow-hidden">
                    <img src="<?php echo $baseUrl; ?>assets/images/foods/<?php echo $item['image']; ?>" 
                         alt="<?php echo $item['name']; ?>" 
                         class="w-full h-full object-cover"
                         onerror="this.src='<?php echo $baseUrl; ?>assets/images/placeholder.jpg'">
                </div>
                <div class="p-2">
                    <h3 class="text-sm font-medium text-[#3a001e] truncate"><?php echo $item['name']; ?></h3>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[#c35331] font-bold">₹<?php echo number_format($item['price'], 2); ?></span>
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
    if (typeof Swiper !== 'undefined') {
        new Swiper('.hero-swiper', {
            loop: true,
            autoplay: {
                delay: 3000,
            },
            pagination: {
                el: '.swiper-pagination',
            },
        });
    } else {
        console.warn('Swiper not loaded. Check if the library is included.');
        // Fallback for missing Swiper
        document.querySelector('.swiper-slide:first-child').style.display = 'block';
    }

    // Add to Home Screen functionality
    const addToHomeBtn = document.getElementById('addToHomeBtn');
    if (addToHomeBtn) {
        addToHomeBtn.addEventListener('click', function() {
            alert('To add this app to your home screen: \n\n1. Tap the share icon in your browser\n2. Scroll down and select "Add to Home Screen"');
        });
    }

    // Hide scrollbar but allow scrolling
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

<?php
require_once 'includes/footer.php';
?>