<?php
// menu.php - Dynamic Bilingual Menu Page
require_once 'admin/config.php';

// Get current language
$language = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
if (in_array($language, ['en', 'ar'])) {
    $_SESSION['language'] = $language;
} else {
    $language = 'en';
}

// Get filters
$selectedCategory = $_GET['category'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Get database connection
$pdo = getConnection();

// Build WHERE clause for filtering
$whereConditions = [];
$params = [];

if (!empty($selectedCategory)) {
    $whereConditions[] = "category = ?";
    $params[] = $selectedCategory;
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(name LIKE ? OR name_ar LIKE ? OR description LIKE ? OR description_ar LIKE ? OR category LIKE ? OR category_ar LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get all categories
$categoriesStmt = $pdo->query("
    SELECT DISTINCT category, category_ar 
    FROM menu_items 
    WHERE category IS NOT NULL 
    ORDER BY 
        CASE category
            WHEN 'Breakfast' THEN 1
            WHEN 'Dishes' THEN 2  
            WHEN 'Bread' THEN 3
            WHEN 'Desserts' THEN 4
            WHEN 'Cold Drinks' THEN 5
            WHEN 'Hot Drinks' THEN 6
            ELSE 7
        END
");
$categories = $categoriesStmt->fetchAll();

// Get popular items (only if no filters applied)
$popularItems = [];
if (empty($selectedCategory) && empty($searchQuery)) {
    $popularStmt = $pdo->prepare("
        SELECT * FROM menu_items 
        WHERE is_popular = 1 
        ORDER BY id DESC 
        LIMIT 6
    ");
    $popularStmt->execute();
    $popularItems = $popularStmt->fetchAll();
}

// Get filtered menu items
$menuStmt = $pdo->prepare("
    SELECT * FROM menu_items 
    $whereClause 
    ORDER BY 
        CASE category
            WHEN 'Breakfast' THEN 1
            WHEN 'Dishes' THEN 2  
            WHEN 'Bread' THEN 3
            WHEN 'Desserts' THEN 4
            WHEN 'Cold Drinks' THEN 5
            WHEN 'Hot Drinks' THEN 6
            ELSE 7
        END,
        name ASC
");
$menuStmt->execute($params);
$menuItems = $menuStmt->fetchAll();

// Translations
$translations = [
    'en' => [
        'menu' => 'Menu',
        'search_placeholder' => 'Search menu items...',
        'all' => 'All',
        'popular_items' => 'Popular Items',
        'all_menu' => 'All Menu',
        'no_items_found' => 'No items found',
        'try_different_search' => 'Try a different search or category',
        'reset_search' => 'Reset Search',
        'home' => 'Home',
        'back' => 'Back',
        'search_results' => 'Search Results',
        'category_results' => 'Category: '
    ],
    'ar' => [
        'menu' => 'القائمة',
        'search_placeholder' => 'البحث في عناصر القائمة...',
        'all' => 'الكل',
        'popular_items' => 'العناصر الشائعة',
        'all_menu' => 'جميع القائمة',
        'no_items_found' => 'لم يتم العثور على عناصر',
        'try_different_search' => 'جرب بحثاً أو فئة مختلفة',
        'reset_search' => 'إعادة تعيين البحث',
        'home' => 'الرئيسية',
        'back' => 'رجوع',
        'search_results' => 'نتائج البحث',
        'category_results' => 'الفئة: '
    ]
];

$t = $translations[$language];

// Helper functions
function getCategoryName($category, $categoryAr, $lang) {
    return ($lang === 'ar' && !empty($categoryAr)) ? $categoryAr : $category;
}

function getItemName($item, $lang) {
    return ($lang === 'ar' && !empty($item['name_ar'])) ? $item['name_ar'] : $item['name'];
}

function getItemDescription($item, $lang) {
    return ($lang === 'ar' && !empty($item['description_ar'])) ? $item['description_ar'] : $item['description'];
}

function formatPrice($price, $lang) {
    return ($lang === 'ar') ? number_format($price, 0) . ' ريال قطري' : 'QAR ' . number_format($price, 0);
}

// Determine page title
$pageTitle = $t['menu'];
if (!empty($searchQuery)) {
    $pageTitle = $t['search_results'];
} elseif (!empty($selectedCategory)) {
    $categoryName = '';
    foreach ($categories as $cat) {
        if ($cat['category'] === $selectedCategory) {
            $categoryName = getCategoryName($cat['category'], $cat['category_ar'], $language);
            break;
        }
    }
    $pageTitle = $t['category_results'] . $categoryName;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>" dir="<?php echo $language === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo $pageTitle; ?> - Fenyal</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Theme configuration -->
    <script src="assets/js/themecolor.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/language.css">
     
    <style>
        /* Arabic font support */
        [lang="ar"] {
            font-family: 'Cairo', 'Poppins', sans-serif;
        }
        
        /* Menu item hover effects */
        .menu-item {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .menu-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .menu-item:active {
            transform: scale(0.98);
        }
        
        /* Category button effects */
        .category-btn {
            transition: all 0.2s ease;
        }
        
        .category-btn:hover {
            transform: translateY(-1px);
        }
        
        .category-btn.active {
            background-color: #c45230;
            color: white;
        }
    </style>
</head>

<body class="bg-background font-sans text-dark">
    <!-- Main app container -->
    <div class="app-container pt-0 pb-30">
        <!-- Header -->
        <header class="sticky-header pt-4 px-4 pb-2">
            <div class="flex items-center justify-between mb-4">
                <a href="index.php?lang=<?php echo $language; ?>" 
                   class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-button">
                    <i data-feather="<?php echo $language === 'ar' ? 'arrow-right' : 'arrow-left'; ?>" 
                       class="h-5 w-5 text-gray-600"></i>
                </a>
                <h1 class="text-xl font-semibold"><?php echo $pageTitle; ?></h1>
                <a href="?lang=<?php echo $language === 'ar' ? 'en' : 'ar'; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-button">
                    <span class="text-sm font-medium text-gray-700">
                        <?php echo strtoupper($language === 'ar' ? 'EN' : 'AR'); ?>
                    </span>
                </a>
            </div>
            
            <!-- Search bar -->
            <form method="GET" class="relative mb-4">
                <input type="hidden" name="lang" value="<?php echo $language; ?>">
                <?php if ($selectedCategory): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">
                <?php endif; ?>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="<?php echo $t['search_placeholder']; ?>" 
                       class="w-full bg-gray-200 rounded-xl py-3 <?php echo $language === 'ar' ? 'pr-10 pl-4' : 'pl-10 pr-4'; ?> text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                <i data-feather="search" 
                   class="absolute <?php echo $language === 'ar' ? 'right-3' : 'left-3'; ?> top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400 search-icon"></i>
            </form>
            
            <!-- Categories -->
            <div class="overflow-x-auto category-scroll">
                <div class="flex space-x-2 py-1 <?php echo $language === 'ar' ? 'space-x-reverse' : ''; ?>" 
                     style="direction: <?php echo $language === 'ar' ? 'rtl' : 'ltr'; ?>">
                    <a href="menu.php?lang=<?php echo $language; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                       class="category-btn <?php echo empty($selectedCategory) ? 'active' : 'bg-gray-100 text-gray-700'; ?> px-4 py-1.5 rounded-full text-sm font-medium scale-button whitespace-nowrap">
                        <?php echo $t['all']; ?>
                    </a>
                    <?php foreach ($categories as $category): ?>
                    <a href="menu.php?category=<?php echo urlencode($category['category']); ?>&lang=<?php echo $language; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                       class="category-btn <?php echo $selectedCategory === $category['category'] ? 'active' : 'bg-gray-100 text-gray-700'; ?> px-4 py-1.5 rounded-full text-sm font-medium scale-button whitespace-nowrap">
                        <?php echo getCategoryName($category['category'], $category['category_ar'], $language); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="px-4 pt-2 pb-4">
            <!-- Popular items section (only show when no filters) -->
            <?php if (!empty($popularItems)): ?>
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3"><?php echo $t['popular_items']; ?></h2>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($popularItems as $item): ?>
                    <div class="menu-item bg-white rounded-xl shadow-sm overflow-hidden flex cursor-pointer"
                         onclick="window.location.href='menu-item-details.php?id=<?php echo $item['id']; ?>&lang=<?php echo $language; ?>'">
                        <div class="w-24 h-24 flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars(getItemName($item, $language)); ?>" 
                                 class="w-full h-full object-cover" 
                                 loading="lazy"
                                 onerror="this.src='uploads/menu/placeholder.jpg'">
                        </div>
                        <div class="p-3 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-medium text-sm mb-0.5">
                                    <?php echo htmlspecialchars(getItemName($item, $language)); ?>
                                </h3>
                                <p class="text-gray-500 text-xs line-clamp-1">
                                    <?php echo htmlspecialchars(getItemDescription($item, $language)); ?>
                                </p>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-primary font-semibold">
                                    <?php 
                                    $displayPrice = $item['is_half_full'] && $item['half_price'] ? $item['half_price'] : $item['price'];
                                    echo formatPrice($displayPrice, $language); 
                                    ?>
                                </span>
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-full">
                                    <?php echo $language === 'ar' ? 'شائع' : 'Popular'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- All menu items -->
            <div>
                <h2 class="text-lg font-semibold mb-3">
                    <?php 
                    if (!empty($searchQuery)) {
                        echo $t['search_results'] . ' (' . count($menuItems) . ')';
                    } elseif (!empty($selectedCategory)) {
                        $categoryName = '';
                        foreach ($categories as $cat) {
                            if ($cat['category'] === $selectedCategory) {
                                $categoryName = getCategoryName($cat['category'], $cat['category_ar'], $language);
                                break;
                            }
                        }
                        echo $categoryName;
                    } else {
                        echo $t['all_menu'];
                    }
                    ?>
                </h2>
                
                <?php if (empty($menuItems)): ?>
                <!-- No results -->
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-feather="search" class="h-8 w-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-1"><?php echo $t['no_items_found']; ?></h3>
                    <p class="text-gray-500 text-sm text-center mb-4"><?php echo $t['try_different_search']; ?></p>
                    <a href="menu.php?lang=<?php echo $language; ?>" 
                       class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium scale-button">
                        <?php echo $t['reset_search']; ?>
                    </a>
                </div>
                <?php else: ?>
                <!-- Menu items grid -->
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($menuItems as $item): ?>
                    <div class="menu-item bg-white rounded-xl shadow-sm overflow-hidden flex cursor-pointer"
                         onclick="window.location.href='menu-item-details.php?id=<?php echo $item['id']; ?>&lang=<?php echo $language; ?>'">
                        <div class="w-24 h-24 flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars(getItemName($item, $language)); ?>" 
                                 class="w-full h-full object-cover" 
                                 loading="lazy"
                                 onerror="this.src='uploads/menu/placeholder.jpg'">
                        </div>
                        <div class="p-3 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-0.5">
                                    <h3 class="font-medium text-sm flex-1">
                                        <?php echo htmlspecialchars(getItemName($item, $language)); ?>
                                    </h3>
                                    <?php if ($item['is_popular'] || $item['is_special']): ?>
                                    <div class="flex gap-1 ml-2">
                                        <?php if ($item['is_popular']): ?>
                                        <span class="text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-full">
                                            <?php echo $language === 'ar' ? 'شائع' : 'Popular'; ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($item['is_special']): ?>
                                        <span class="text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded-full">
                                            <?php echo $language === 'ar' ? 'مميز' : 'Special'; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-500 text-xs line-clamp-1">
                                    <?php echo htmlspecialchars(getItemDescription($item, $language)); ?>
                                </p>
                                <p class="text-gray-400 text-xs mt-0.5">
                                    <?php echo htmlspecialchars(getCategoryName($item['category'], $item['category_ar'], $language)); ?>
                                </p>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-primary font-semibold">
                                    <?php 
                                    $displayPrice = $item['is_half_full'] && $item['half_price'] ? $item['half_price'] : $item['price'];
                                    echo formatPrice($displayPrice, $language); 
                                    ?>
                                </span>
                                <?php if ($item['is_half_full']): ?>
                                <span class="text-xs text-gray-500">
                                    <?php echo $language === 'ar' ? 'نصف/كامل' : 'Half/Full'; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
        
        <!-- Navigation bar -->
        <nav class="bottom-nav fixed bottom-0 left-0 right-0 shadow-lg bottom-safe-area z-40">
            <div class="px-4 py-0">
                <div class="flex justify-around items-center">
                    <!-- Home -->
                    <a href="index.php?lang=<?php echo $language; ?>" 
                       class="nav-item ripple flex flex-col items-center justify-center" data-page="home">
                        <i data-feather="home" class="nav-icon h-5 w-5 mb-1"></i>
                        <span class="nav-text"><?php echo $t['home']; ?></span>
                    </a>
                    
                    <!-- Center Logo -->
                    <div class="center-logo w-16 h-16 rounded-full flex items-center justify-center ripple" onclick="centerLogoAction()">
                        <img src="fenyal-logo-1.png" alt="Fenyal Logo" class="w-10 h-10 rounded-full">
                    </div>
                    
                    <!-- Menu -->
                    <a href="menu.php?lang=<?php echo $language; ?>" 
                       class="nav-item active ripple flex flex-col items-center justify-center" data-page="menu-full">
                        <i data-feather="menu" class="nav-icon h-5 w-5 mb-1"></i>
                        <span class="nav-text"><?php echo $t['menu']; ?></span>
                    </a>
                </div>
            </div>
        </nav>
    </div>
    
    <script>
        // Initialize icons
        feather.replace();
        
        // Set mobile viewport height
        function setViewportHeight() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        
        // Center logo action
        function centerLogoAction() {
            console.log('Center logo clicked');
        }
        
        // Add touch feedback for buttons
        const buttons = document.querySelectorAll('.scale-button, .menu-item');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.97)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
        
        // Auto-submit search form with debounce
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }
    </script>
</body>
</html>