<?php
// index.php - Bilingual Home Page with Dynamic Categories
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

// Get database connection
$pdo = getConnection();

// Get categories with images
$categoriesStmt = $pdo->query("
    SELECT c.*, COUNT(m.id) as item_count
    FROM categories c
    LEFT JOIN menu_items m ON c.name = m.category
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.display_order, c.name
");
$categories = $categoriesStmt->fetchAll();

// Get popular items
$popularStmt = $pdo->prepare("
    SELECT * FROM menu_items 
    WHERE is_popular = 1 
    ORDER BY id DESC 
    LIMIT 6
");
$popularStmt->execute();
$popularItems = $popularStmt->fetchAll();

// Translation arrays
$translations = [
    'en' => [
        'home' => 'Home',
        'menu' => 'Menu',
        'popular_items' => 'Popular Items',
        'view_all' => 'View all',
        'categories' => 'Categories',
        'no_items_found' => 'No items found',
        'popular' => 'Popular',
        'special' => 'Special'
    ],
    'ar' => [
        'home' => 'الرئيسية',
        'menu' => 'القائمة',
        'popular_items' => 'الأصناف الشائعة',
        'view_all' => 'عرض الكل',
        'categories' => 'الفئات',
        'no_items_found' => 'لم يتم العثور على أصناف',
        'popular' => 'شائع',
        'special' => 'مميز'
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

function getCategoryName($category) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($category['name_ar'])) ? $category['name_ar'] : $category['name'];
}

function getItemName($item) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($item['name_ar'])) ? $item['name_ar'] : $item['name'];
}

function getItemCategory($item) {
    global $currentLang;
    return ($currentLang === 'ar' && !empty($item['category_ar'])) ? $item['category_ar'] : $item['category'];
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

function getCategoryImage($category) {
    // Check if category has custom image
    if (!empty($category['image']) && file_exists($category['image'])) {
        return $category['image'];
    }
    
    // Fallback to default images based on category name
    $defaultImages = [
        'Breakfast' => 'uploads/menu/1.png',
        'Dishes' => 'uploads/menu/2.png',
        'Bread' => 'uploads/menu/3.png',
        'Desserts' => 'uploads/menu/4.png',
        'Cold Drinks' => 'uploads/menu/5.png',
        'Hot Drinks' => 'uploads/menu/6.png'
    ];
    
    if (isset($defaultImages[$category['name']])) {
        return $defaultImages[$category['name']];
    }
    
    // Final fallback
    return 'uploads/menu/placeholder.jpg';
}

$alternativeLang = $currentLang === 'ar' ? 'en' : 'ar';
$direction = isRTL() ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $direction; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo __('home'); ?> - Fenyal</title>
    
    <!-- PWA Icons -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fenyal">
    <link rel="apple-touch-icon" href="assets/icons/apple-icon-180x180.png">

    <!-- Web App Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/app.css" as="style">
    <link rel="preload" href="assets/css/language.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style">
    <?php if ($currentLang === 'ar'): ?>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" as="style">
    <?php endif; ?>
    
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
        /* Critical CSS for faster loading */
        [lang="ar"] {
            font-family: 'Cairo', 'Poppins', sans-serif;
        }
        
        .category-item, .menu-item {
            transition: transform 0.2s ease;
        }
        
        .category-item:hover, .menu-item:hover {
            transform: translateY(-2px);
        }
        
        .category-item:active, .menu-item:active {
            transform: scale(0.98);
        }
        
        /* Category image styling */
        .category-image {
            transition: transform 0.2s ease;
        }
        
        .category-item:hover .category-image {
            transform: scale(1.05);
        }
        
        /* Optimized loading animation */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #f8f8f8 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        /* Fast fade-in */
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Image loading placeholder */
        .image-placeholder {
            background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.7rem;
        }
    </style>
</head>

<body class="bg-background font-sans text-dark">
    <!-- Main App Container -->
    <div class="app-container pt-4 pb-30 scroll-touch fade-in">
        <!-- Header -->
        <header class="sticky top-0 z-30 bg-background/95 backdrop-blur-sm pt-4 pb-4 px-4">
            <div class="flex items-center justify-center relative">
                <!-- Centered Logo -->
                <div class="flex items-center justify-center flex-1">
                    <img src="fenyal-logo-1.png" width="100" height="48" alt="Fenyal Logo" class="h-12 object-contain">
                </div>
                
                <!-- Language Toggle -->
                <div class="absolute <?php echo isRTL() ? 'left-0' : 'right-0'; ?>">
                    <a href="<?php echo buildUrl('index.php', ['lang' => $alternativeLang]); ?>" 
                       class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-button"
                       aria-label="Switch to <?php echo $alternativeLang === 'ar' ? 'Arabic' : 'English'; ?>">
                        <span class="text-sm font-medium text-gray-700">
                            <?php echo strtoupper($alternativeLang); ?>
                        </span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="px-4">
            <!-- Categories -->
            <section class="mb-2" aria-label="<?php echo __('categories'); ?>">
                <?php if (!empty($categories)): ?>
                <div class="flex space-x-4 overflow-x-auto py-1 special-scroll <?php echo isRTL() ? 'space-x-reverse' : ''; ?>" 
                     style="direction: <?php echo $direction; ?>">
                    <?php foreach ($categories as $category): 
                        $categoryName = getCategoryName($category);
                        $categoryImage = getCategoryImage($category);
                    ?>
                    <a href="<?php echo buildUrl('menu.php', ['category' => $category['name']]); ?>" 
                       class="category-item flex flex-col items-center flex-shrink-0"
                       aria-label="<?php echo $categoryName; ?> category">
                        <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center mb-1 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($categoryImage); ?>" 
                                 alt="<?php echo htmlspecialchars($categoryName); ?>" 
                                 class="category-image h-14 w-14 object-cover rounded-full"
                                 loading="lazy" 
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'image-placeholder h-14 w-14 rounded-full\'><?php echo substr($categoryName, 0, 2); ?></div>';" />
                        </div>
                        <span class="text-xs font-medium category-label text-center">
                            <?php echo htmlspecialchars($categoryName); ?>
                        </span>
                        <?php if ($category['item_count'] > 0): ?>
                        <span class="text-xs text-gray-400 mt-0.5">
                            <?php echo $category['item_count']; ?> <?php echo $currentLang === 'ar' ? 'صنف' : 'items'; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <!-- No categories fallback -->
                <div class="text-center py-4">
                    <p class="text-gray-500 text-sm"><?php echo $currentLang === 'ar' ? 'لا توجد فئات متاحة' : 'No categories available'; ?></p>
                </div>
                <?php endif; ?>
            </section>

            <!-- Popular Items -->
            <section class="mb-5 pt-4" aria-label="<?php echo __('popular_items'); ?>">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-base font-semibold"><?php echo __('popular_items'); ?></h2>
                    <a href="<?php echo buildUrl('menu.php'); ?>" class="text-primary text-xs">
                        <?php echo __('view_all'); ?>
                    </a>
                </div>

                <div class="special-items-container">
                    <?php if (empty($popularItems)): ?>
                    <div class="p-4 text-center text-gray-500">
                        <p><?php echo __('no_items_found'); ?></p>
                        <a href="<?php echo buildUrl('menu.php'); ?>" class="text-primary text-sm mt-2 inline-block">
                            <?php echo $currentLang === 'ar' ? 'استكشف القائمة' : 'Browse Menu'; ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="flex space-x-3 overflow-x-auto py-1 special-scroll special-items-wrapper <?php echo isRTL() ? 'flex-row-reverse space-x-reverse' : ''; ?>">
                        <?php foreach ($popularItems as $item): 
                            $itemName = getItemName($item);
                            $itemCategory = getItemCategory($item);
                            $displayPrice = $item['is_half_full'] && $item['half_price'] ? $item['half_price'] : $item['price'];
                        ?>
                        <article class="flex-shrink-0 w-36 rounded-lg overflow-hidden special-item shadow-sm bg-white menu-item cursor-pointer"
                                 onclick="window.location.href='<?php echo buildUrl('menu-item-details.php', ['id' => $item['id']]); ?>'"
                                 role="button"
                                 tabindex="0"
                                 aria-label="<?php echo $itemName; ?> - <?php echo formatPrice($displayPrice); ?>">
                            <div class="h-24 overflow-hidden bg-gray-100">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($itemName); ?>" 
                                     class="w-full h-full object-cover" 
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='uploads/menu/placeholder.jpg';">
                            </div>
                            <div class="p-2.5">
                                <h3 class="font-medium text-sm leading-tight line-clamp-1">
                                    <?php echo htmlspecialchars($itemName); ?>
                                </h3>
                                <p class="text-gray-500 text-xs mt-0.5 line-clamp-1">
                                    <?php echo htmlspecialchars($itemCategory); ?>
                                </p>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-primary font-semibold text-sm">
                                        <?php echo formatPrice($displayPrice); ?>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <?php if ($item['is_popular']): ?>
                                        <span class="text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-full">
                                            <?php echo __('popular'); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($item['is_special']): ?>
                                        <span class="text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded-full">
                                            <?php echo __('special'); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <!-- Navigation bar -->
        <nav class="bottom-nav fixed bottom-0 left-0 right-0 shadow-lg bottom-safe-area z-90" 
             role="navigation" aria-label="Main navigation">
            <div class="px-4 py-0">
                <div class="flex justify-around items-center relative">
                    <!-- Home -->
                    <a href="<?php echo buildUrl('index.php'); ?>" 
                       class="nav-item ripple flex flex-col items-center justify-center active" 
                       data-page="home"
                       aria-label="<?php echo __('home'); ?>"
                       aria-current="page">
                        <i data-feather="home" class="nav-icon h-5 w-5 mb-1"></i>
                        <span class="nav-text"><?php echo __('home'); ?></span>
                    </a>
                    
                    <!-- Center Logo -->
                    <button class="center-logo w-16 h-16 rounded-full flex items-center justify-center ripple" 
                            onclick="centerLogoAction()"
                            aria-label="Fenyal logo">
                        <img src="fenyal-logo-1.png" alt="Fenyal Logo" class="w-10 h-10 rounded-full">
                    </button>
                    
                    <!-- Menu -->
                    <a href="<?php echo buildUrl('menu.php'); ?>" 
                       class="nav-item ripple flex flex-col items-center justify-center" 
                       data-page="menu-full"
                       aria-label="<?php echo __('menu'); ?>">
                        <i data-feather="menu" class="nav-icon h-5 w-5 mb-1"></i>
                        <span class="nav-text"><?php echo __('menu'); ?></span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- JavaScript -->
    <script>
        // Configuration
        const APP_CONFIG = {
            language: '<?php echo $currentLang; ?>',
            isRTL: <?php echo isRTL() ? 'true' : 'false'; ?>,
            apiBaseUrl: '/api/',
            categories: <?php echo json_encode($categories); ?>
        };

        // Initialize icons
        feather.replace();

        // Set mobile viewport height fix
        function setViewportHeight() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }

        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // Center logo action
        function centerLogoAction() {
            console.log('Center logo clicked');
            // You can add custom actions here
        }

        // Performance optimized touch feedback
        function addTouchFeedback() {
            const elements = document.querySelectorAll('button, a, .menu-item, .category-item');
            
            elements.forEach(element => {
                let touchTimeout;
                
                element.addEventListener('touchstart', function(e) {
                    touchTimeout = setTimeout(() => {
                        this.style.transform = 'scale(0.97)';
                    }, 50);
                }, { passive: true });

                element.addEventListener('touchend', function() {
                    clearTimeout(touchTimeout);
                    this.style.transform = 'scale(1)';
                }, { passive: true });
                
                element.addEventListener('touchcancel', function() {
                    clearTimeout(touchTimeout);
                    this.style.transform = 'scale(1)';
                }, { passive: true });
            });
        }

        // Lazy loading for images
        function initLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('loading-skeleton');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }

        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            addTouchFeedback();
            initLazyLoading();
            
            // Debug: Log categories loaded
            console.log('Categories loaded:', APP_CONFIG.categories);
            
            // Check if user is new and should see welcome page
            if (!localStorage.getItem('hasVisited')) {
                // Uncomment this line if you have a welcome page
                // window.location.href = 'welcome.html';
                // For now, just mark as visited
                localStorage.setItem('hasVisited', 'true');
            }
        });

        // Register service worker for PWA support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed:', err);
                    });
            });
        }

        // Preload critical pages
        function preloadCriticalPages() {
            const criticalPages = [
                '<?php echo buildUrl('menu.php'); ?>'
            ];
            
            criticalPages.forEach(url => {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = url;
                document.head.appendChild(link);
            });
        }

        // Preload after initial load
        setTimeout(preloadCriticalPages, 1000);

        // Handle image loading errors gracefully
        function handleImageError(img) {
            const placeholder = document.createElement('div');
            placeholder.className = 'image-placeholder h-14 w-14 rounded-full';
            placeholder.textContent = '📷';
            img.parentElement.replaceChild(placeholder, img);
        }

        // Add global error handling for images
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG') {
                console.log('Image failed to load:', e.target.src);
                // The onerror handlers in HTML will handle the fallback
            }
        }, true);
    </script>

    <!-- PWA Installer -->
    <script src="assets/js/pwa-installer.js"></script>
</body>
</html>