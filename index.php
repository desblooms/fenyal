<?php
// index-optimized.php - Optimized Bilingual Home Page with Fast Data Retrieval
require_once 'config/app.php';

// Get menu data with caching
$categories = $db->getCategories();
$popularItems = $db->getMenuItems(['popular' => true, 'limit' => 6]);

// Current language and direction
$currentLang = getCurrentLanguage();
$direction = isRTL() ? 'rtl' : 'ltr';
$alternativeLang = getAlternativeLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $direction; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo __('home'); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- PWA Icons -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo APP_NAME; ?>">
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
                    <img src="fenyal-logo-1.png" width="100" height="48" alt="<?php echo APP_NAME; ?> Logo" class="h-12 object-contain">
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
                <div class="flex space-x-4 overflow-x-auto py-1 special-scroll <?php echo isRTL() ? 'space-x-reverse' : ''; ?>" 
                     style="direction: <?php echo $direction; ?>">
                    <?php 
                    $categoryImages = [
                        'Breakfast' => 'uploads/menu/1.png',
                        'Dishes' => 'uploads/menu/2.png',
                        'Bread' => 'uploads/menu/3.png',
                        'Desserts' => 'uploads/menu/4.png',
                        'Cold Drinks' => 'uploads/menu/5.png',
                        'Hot Drinks' => 'uploads/menu/6.png'
                    ];
                    
                    foreach ($categories as $category): 
                        $categoryName = getLocalizedText($category, 'category', $currentLang);
                        $categoryImage = $categoryImages[$category['category']] ?? 'uploads/menu/1.png';
                    ?>
                    <a href="<?php echo buildUrl('menu.php', ['category' => $category['category']]); ?>" 
                       class="category-item flex flex-col items-center flex-shrink-0"
                       aria-label="<?php echo $categoryName; ?> category">
                        <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center mb-1 overflow-hidden">
                            <img src="<?php echo $categoryImage; ?>" 
                                 alt="<?php echo $categoryName; ?>" 
                                 class="h-14 w-14 object-cover rounded-full"
                                 loading="lazy" />
                        </div>
                        <span class="text-xs font-medium category-label">
                            <?php echo htmlspecialchars($categoryName); ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
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
                    </div>
                    <?php else: ?>
                    <div class="flex space-x-3 overflow-x-auto py-1 special-scroll special-items-wrapper <?php echo isRTL() ? 'flex-row-reverse space-x-reverse' : ''; ?>">
                        <?php foreach ($popularItems as $item): 
                            $itemName = getLocalizedText($item, 'name', $currentLang);
                            $itemCategory = getLocalizedText($item, 'category', $currentLang);
                            $displayPrice = $item['is_half_full'] && $item['half_price'] ? $item['half_price'] : $item['price'];
                        ?>
                        <article class="flex-shrink-0 w-36 rounded-lg overflow-hidden special-item shadow-sm bg-white menu-item cursor-pointer"
                                 onclick="window.location.href='<?php echo buildUrl('menu-item-details.php', ['id' => $item['id']]); ?>'"
                                 role="button"
                                 tabindex="0"
                                 aria-label="<?php echo $itemName; ?> - <?php echo formatPrice($displayPrice, $currentLang); ?>">
                            <div class="h-24 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($itemName); ?>" 
                                     class="w-full h-full object-cover" 
                                     loading="lazy"
                                     onerror="this.src='uploads/menu/placeholder.jpg'">
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
                                        <?php echo formatPrice($displayPrice, $currentLang); ?>
                                    </span>
                                    <?php if ($item['is_popular']): ?>
                                    <span class="text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-full">
                                        <?php echo __('popular'); ?>
                                    </span>
                                    <?php endif; ?>
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
                            aria-label="<?php echo APP_NAME; ?> logo">
                        <img src="fenyal-logo-1.png" alt="<?php echo APP_NAME; ?> Logo" class="w-10 h-10 rounded-full">
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
            enableCache: <?php echo ENABLE_CACHE ? 'true' : 'false'; ?>
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
            // Custom action here
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
                            img.src = img.dataset.src || img.src;
                            img.classList.remove('loading-skeleton');
                            observer.unobserve(img);
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
            
            // Check if user is new and should see welcome page
            if (!localStorage.getItem('hasVisited')) {
                window.location.href = 'welcome.html';
                return;
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
                '<?php echo buildUrl('menu.php'); ?>',
                '<?php echo buildUrl('api/menu.php', ['action' => 'categories']); ?>'
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
    </script>

    <!-- PWA Installer -->
    <script src="assets/js/pwa-installer.js"></script>
</body>
</html>