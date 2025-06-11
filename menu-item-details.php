<?php
// menu-item-details.php - Complete Bilingual Menu Item Details
session_start();

// Include admin config for database connection
require_once 'admin/config.php';

// Language Management
$supportedLanguages = ['en', 'ar'];
$defaultLanguage = 'en';

// Get current language from URL parameter, session, or default
$currentLang = $_GET['lang'] ?? $_SESSION['language'] ?? $defaultLanguage;

// Validate and set language
if (!in_array($currentLang, $supportedLanguages)) {
    $currentLang = $defaultLanguage;
}

// Store in session
$_SESSION['language'] = $currentLang;

// Get item ID
$itemId = $_GET['id'] ?? null;

if (!$itemId || !is_numeric($itemId)) {
    header('Location: menu.php?lang=' . $currentLang);
    exit;
}

// Get database connection
$pdo = getConnection();

// Get menu item with addons and spice levels
$itemStmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
$itemStmt->execute([$itemId]);
$item = $itemStmt->fetch();

if (!$item) {
    header('Location: menu.php?lang=' . $currentLang);
    exit;
}

// Get addons for this item
$addonsStmt = $pdo->prepare("SELECT * FROM menu_addons WHERE menu_item_id = ? ORDER BY name");
$addonsStmt->execute([$itemId]);
$addons = $addonsStmt->fetchAll();

// Get spice levels for this item
$spiceLevelsStmt = $pdo->prepare("SELECT * FROM menu_spice_levels WHERE menu_item_id = ? ORDER BY name");
$spiceLevelsStmt->execute([$itemId]);
$spiceLevels = $spiceLevelsStmt->fetchAll();

// Translation array
$translations = [
    'en' => [
        'loading_item_details' => 'Loading item details...',
        'select_size' => 'Select Size',
        'half' => 'Half',
        'full' => 'Full',
        'spice_level' => 'Spice Level',
        'addons' => 'Add-ons',
        'price' => 'Price',
        'popular' => 'Popular',
        'special' => 'Special',
        'item_not_found' => 'Item Not Found',
        'item_not_found_desc' => 'The requested menu item could not be found.',
        'back_to_menu' => 'Back to Menu',
        'add_to_cart' => 'Add to Cart',
        'order_now' => 'Order Now',
        'home' => 'Home',
        'menu' => 'Menu',
        // Spice levels
        'mild' => 'Mild',
        'medium' => 'Medium',
        'spicy' => 'Spicy',
        'hot' => 'Hot'
    ],
    'ar' => [
        'loading_item_details' => 'جاري تحميل تفاصيل الصنف...',
        'select_size' => 'اختر الحجم',
        'half' => 'نصف',
        'full' => 'كامل',
        'spice_level' => 'مستوى الحرارة',
        'addons' => 'الإضافات',
        'price' => 'السعر',
        'popular' => 'شائع',
        'special' => 'مميز',
        'item_not_found' => 'الصنف غير موجود',
        'item_not_found_desc' => 'لم يتم العثور على الصنف المطلوب في القائمة.',
        'back_to_menu' => 'العودة للقائمة',
        'add_to_cart' => 'أضف إلى السلة',
        'order_now' => 'اطلب الآن',
        'home' => 'الرئيسية',
        'menu' => 'القائمة',
        // Spice levels
        'mild' => 'خفيف',
        'medium' => 'متوسط',
        'spicy' => 'حار',
        'hot' => 'حار جداً'
    ]
];

// Helper functions
function __($key) {
    global $translations, $currentLang;
    return $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $key;
}

function isRTL() {
    global $currentLang;
    return $currentLang === 'ar';
}

function getItemName($item) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($item['name_ar'])) ? $item['name_ar'] : $item['name'];
}

function getItemDescription($item) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($item['description_ar'])) ? $item['description_ar'] : $item['description'];
}

function getCategoryName($item) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($item['category_ar'])) ? $item['category_ar'] : $item['category'];
}

function getAddonName($addon) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($addon['name_ar'])) ? $addon['name_ar'] : $addon['name'];
}

function getSpiceLevelName($spiceLevel) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($spiceLevel['name_ar'])) ? $spiceLevel['name_ar'] : $spiceLevel['name'];
}

function formatPrice($price) {
    global $currentLang;
    return ($currentLang === 'ar') ? number_format($price, 0) . ' ريال قطري' : 'QAR ' . number_format($price, 0);
}

function buildUrl($path, $params = []) {
    global $currentLang;
    $params['lang'] = $currentLang;
    $queryString = http_build_query($params);
    return $path . ($queryString ? '?' . $queryString : '');
}

$alternativeLang = $currentLang === 'ar' ? 'en' : 'ar';
$direction = isRTL() ? 'rtl' : 'ltr';

// Calculate default price
$defaultPrice = $item['is_half_full'] && $item['half_price'] ? $item['half_price'] : $item['price'];
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo htmlspecialchars(getItemName($item)); ?> - Fenyal</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Theme configuration -->
    <script src="assets/js/themecolor.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($currentLang === 'ar'): ?>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/language.css">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

    <style>
        /* Arabic font support */
        [lang="ar"] {
            font-family: 'Cairo', 'Poppins', sans-serif;
        }
        
        /* Smooth transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Price update animation */
        .price-update {
            transition: all 0.3s ease;
        }
        
        .price-highlight {
            animation: priceHighlight 0.5s ease;
        }
        
        @keyframes priceHighlight {
            0% { background-color: rgba(196, 82, 48, 0.1); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body class="bg-background font-sans text-dark">
    <!-- Main app container -->
    <div class="app-container h-full overflow-y-auto scroll-touch pb-8 fade-in">
        <!-- Item image (sticky at top) -->
        <div class="relative item-img-container">
            <div class="w-full h-full bg-gray-200 object-cover !bg-bottom" 
                 style="background-image: url('<?php echo htmlspecialchars($item['image']); ?>'); background-size: cover; background-position: center;">
            </div>
            <div class="absolute inset-0 bg-gradient-to-b from-black/50 to-transparent"></div>
            
            <!-- Back button -->
            <a href="<?php echo buildUrl('menu.php'); ?>" 
               class="absolute top-4 <?php echo $direction === 'rtl' ? 'right-4' : 'left-4'; ?> w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center shadow-lg">
                <i data-feather="<?php echo $direction === 'rtl' ? 'arrow-right' : 'arrow-left'; ?>" 
                   class="h-5 w-5 text-white"></i>
            </a>
            
            <!-- Language toggle button -->
            <a href="?id=<?php echo $itemId; ?>&lang=<?php echo $alternativeLang; ?>" 
               class="absolute top-4 <?php echo $direction === 'rtl' ? 'left-4' : 'right-4'; ?> w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center shadow-lg">
                <span class="text-sm font-medium text-white">
                    <?php echo strtoupper($alternativeLang); ?>
                </span>
            </a>
        </div>
        
        <!-- Item details -->
        <div class="px-4 pt-4 pb-8">
            <!-- Item title and category -->
            <div class="mb-3">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h1 class="text-xl font-bold mb-1"><?php echo htmlspecialchars(getItemName($item)); ?></h1>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars(getCategoryName($item)); ?></p>
                    </div>
                    <div class="flex items-center gap-2 ml-3">
                        <?php if ($item['is_popular']): ?>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-medium">
                            <?php echo __('popular'); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($item['is_special']): ?>
                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium">
                            <?php echo __('special'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Item description -->
            <p class="text-gray-600 text-sm mb-6 leading-relaxed">
                <?php echo htmlspecialchars(getItemDescription($item)); ?>
            </p>
            
            <form id="item-form">
                <!-- Size options (if half/full available) -->
                <?php if ($item['is_half_full']): ?>
                <div class="mb-6">
                    <h3 class="text-base font-semibold mb-3"><?php echo __('select_size'); ?></h3>
                    <div class="flex space-x-4 radio-container">
                        <div>
                            <input type="radio" name="size" id="size-half" value="half" checked 
                                   data-price="<?php echo $item['half_price']; ?>">
                            <label for="size-half" class="text-sm">
                                <?php echo __('half'); ?> - <?php echo formatPrice($item['half_price']); ?>
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="size" id="size-full" value="full"
                                   data-price="<?php echo $item['full_price']; ?>">
                            <label for="size-full" class="text-sm">
                                <?php echo __('full'); ?> - <?php echo formatPrice($item['full_price']); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Spice level -->
                <?php if (!empty($spiceLevels)): ?>
                <div class="mb-6">
                    <h3 class="text-base font-semibold mb-3"><?php echo __('spice_level'); ?></h3>
                    <div class="flex flex-wrap gap-3 radio-container">
                        <?php foreach ($spiceLevels as $index => $spiceLevel): ?>
                        <div>
                            <input type="radio" name="spice-level" id="spice-<?php echo $index; ?>" 
                                   value="<?php echo htmlspecialchars($spiceLevel['name']); ?>" 
                                   <?php echo $index === 0 ? 'checked' : ''; ?>>
                            <label for="spice-<?php echo $index; ?>" class="text-sm">
                                <?php echo htmlspecialchars(getSpiceLevelName($spiceLevel)); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Add-ons -->
                <?php if (!empty($addons)): ?>
                <div class="mb-6">
                    <h3 class="text-base font-semibold mb-3"><?php echo __('addons'); ?></h3>
                    <div class="space-y-3 checkbox-container">
                        <?php foreach ($addons as $addon): ?>
                        <div class="flex justify-between items-center">
                            <div>
                                <input type="checkbox" id="addon-<?php echo $addon['id']; ?>" 
                                       name="addons" value="<?php echo $addon['id']; ?>" 
                                       data-price="<?php echo $addon['price']; ?>">
                                <label for="addon-<?php echo $addon['id']; ?>" class="text-sm">
                                    <?php echo htmlspecialchars(getAddonName($addon)); ?>
                                </label>
                            </div>
                            <span class="text-sm text-gray-600">
                                <?php echo formatPrice($addon['price']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Price display -->
                <div class="mb-8">
                    <div>
                        <p class="text-sm text-gray-500"><?php echo __('price'); ?></p>
                        <p id="total-price" class="text-xl font-bold text-primary price-update" 
                           data-base-price="<?php echo $defaultPrice; ?>">
                            <?php echo formatPrice($defaultPrice); ?>
                        </p>
                    </div>
                </div>
            </form>
            
            <!-- Action buttons -->
            <!-- <div class="flex gap-3">
                <button type="button" id="add-to-cart-btn" 
                        class="flex-1 bg-gray-100 text-gray-700 font-medium py-3 px-6 rounded-xl transition-colors hover:bg-gray-200">
                    <?php echo __('add_to_cart'); ?>
                </button>
                <button type="button" id="order-now-btn" 
                        class="flex-1 bg-primary text-white font-medium py-3 px-6 rounded-xl transition-colors hover:bg-primary/90">
                    <?php echo __('order_now'); ?>
                </button>
            </div> -->
        </div>
    </div>
    
    <!-- JavaScript -->
     <script src="assets/js/improved-language-toggle.js"></script>
    <script>
        // Initialize icons
        feather.replace();
        
        // Price calculation variables
        const language = '<?php echo $currentLang; ?>';
        const basePrice = <?php echo $defaultPrice; ?>;
        const halfPrice = <?php echo $item['half_price'] ?: $item['price']; ?>;
        const fullPrice = <?php echo $item['full_price'] ?: $item['price']; ?>;
        const totalPriceElement = document.getElementById('total-price');
        
        // Format price function
        function formatPrice(price) {
            if (language === 'ar') {
                return Math.round(price) + ' ريال قطري';
            }
            return 'QAR ' + Math.round(price);
        }
        
        // Calculate total price
        function calculateTotalPrice() {
            let total = basePrice;
            
            // Check size selection
            const sizeInputs = document.querySelectorAll('input[name="size"]');
            sizeInputs.forEach(input => {
                if (input.checked) {
                    total = parseFloat(input.dataset.price);
                }
            });
            
            // Add addon prices
            const addonInputs = document.querySelectorAll('input[name="addons"]:checked');
            addonInputs.forEach(input => {
                total += parseFloat(input.dataset.price);
            });
            
            return total;
        }
        
        // Update price display
        function updatePriceDisplay() {
            const newPrice = calculateTotalPrice();
            totalPriceElement.textContent = formatPrice(newPrice);
            totalPriceElement.classList.add('price-highlight');
            
            setTimeout(() => {
                totalPriceElement.classList.remove('price-highlight');
            }, 500);
        }
        
        // Add event listeners for price updates
        document.querySelectorAll('input[name="size"], input[name="addons"]').forEach(input => {
            input.addEventListener('change', updatePriceDisplay);
        });
        
        // Button actions
        document.getElementById('add-to-cart-btn').addEventListener('click', function() {
            // Add to cart functionality
            alert('Added to cart! (Feature to be implemented)');
        });
        
        document.getElementById('order-now-btn').addEventListener('click', function() {
            // Order now functionality
            alert('Order placed! (Feature to be implemented)');
        });
        
        // Set mobile viewport height
        function setViewportHeight() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        
        // Add touch feedback
        document.querySelectorAll('button, a').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.97)';
            });
            
            element.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>