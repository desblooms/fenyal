<?php
// welcome.php - Bilingual Welcome Page with Language Switcher
session_start();

// Default to English, but check if language is already set
$currentLang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';

// Validate and set language
if (!in_array($currentLang, ['en', 'ar'])) {
    $currentLang = 'en';
}

// Store in session
$_SESSION['language'] = $currentLang;

$isRTL = $currentLang === 'ar';
$direction = $isRTL ? 'rtl' : 'ltr';
$alternativeLang = $currentLang === 'ar' ? 'en' : 'ar';

// Translations for welcome page
$translations = [
    'en' => [
        'welcome_title' => 'Welcome to Fenyal',
        'welcome_subtitle' => 'Authentic Middle Eastern Cuisine',
        'welcome_description' => 'Discover the finest traditional dishes from Qatar and the Middle East, prepared with authentic flavors and fresh ingredients.',
        'get_started' => 'Get Started',
        'choose_language' => 'Choose your language',
        'english' => 'English',
        'arabic' => 'العربية',
        'explore_menu' => 'Explore Our Menu',
        'popular_dishes' => 'Popular Dishes',
        'special_offers' => 'Special Offers',
        'about_us' => 'About Fenyal',
        'about_description' => 'Fenyal brings you the authentic taste of Qatar with traditional recipes passed down through generations.',
        'continue' => 'Continue'
    ],
    'ar' => [
        'welcome_title' => 'مرحباً بكم في فنيال',
        'welcome_subtitle' => 'المأكولات الشرق أوسطية الأصيلة',
        'welcome_description' => 'اكتشف أجود الأطباق التقليدية من قطر والشرق الأوسط، المحضرة بنكهات أصيلة ومكونات طازجة.',
        'get_started' => 'ابدأ الآن',
        'choose_language' => 'اختر لغتك',
        'english' => 'English',
        'arabic' => 'العربية',
        'explore_menu' => 'استكشف قائمتنا',
        'popular_dishes' => 'الأطباق الشائعة',
        'special_offers' => 'العروض الخاصة',
        'about_us' => 'حول فنيال',
        'about_description' => 'فنيال يقدم لك الطعم الأصيل لقطر بوصفات تقليدية متوارثة عبر الأجيال.',
        'continue' => 'متابعة'
    ]
];

function __($key) {
    global $translations, $currentLang;
    return $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $key;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo __('welcome_title'); ?> - Fenyal</title>
    
    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fenyal">
    <link rel="apple-touch-icon" href="assets/icons/apple-icon-180x180.png">
    <link rel="manifest" href="manifest.json">
    
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
        
        /* Welcome page specific styles */
        .welcome-bg {
            background: linear-gradient(135deg, #c45230 0%, #f96d43 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('uploads/menu/pattern.png') repeat;
            opacity: 0.1;
            z-index: 1;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        .logo-animation {
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .slide-up {
            animation: slideUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        
        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-up-delay-1 { animation-delay: 0.2s; }
        .slide-up-delay-2 { animation-delay: 0.4s; }
        .slide-up-delay-3 { animation-delay: 0.6s; }
        
        .language-button {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .language-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .language-button.active {
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .get-started-btn {
            background: linear-gradient(135deg, #ffffff 0%, #f8f8f8 100%);
            color: #c45230;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .get-started-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .get-started-btn:active {
            transform: translateY(0);
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: #c45230;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Features grid */
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="welcome-bg">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-white text-sm"><?php echo __('continue'); ?>...</p>
        </div>
    </div>

    <!-- Main Welcome Container -->
    <div class="welcome-content min-h-screen flex flex-col">
        <!-- Header with Language Switcher -->
        <header class="p-4 flex justify-between items-start">
            <!-- Language Toggle -->
            <div class="flex gap-2">
                <a href="welcome.php?lang=en" 
                   class="language-button <?php echo $currentLang === 'en' ? 'active' : ''; ?> px-4 py-2 rounded-full bg-white/20 text-white text-sm font-medium border border-white/30">
                    English
                </a>
                <a href="welcome.php?lang=ar" 
                   class="language-button <?php echo $currentLang === 'ar' ? 'active' : ''; ?> px-4 py-2 rounded-full bg-white/20 text-white text-sm font-medium border border-white/30">
                    العربية
                </a>
            </div>
            
            <!-- Skip Button -->
            <button onclick="continueToApp()" 
                    class="text-white/80 text-sm hover:text-white transition-colors">
                <?php echo __('continue'); ?> →
            </button>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col justify-center px-6 pb-8">
            <!-- Logo Section -->
            <div class="text-center mb-8 slide-up">
                <div class="logo-animation mb-6">
                    <img src="fenyal-logo-1.png" alt="Fenyal Logo" class="w-24 h-24 mx-auto rounded-full shadow-2xl">
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">
                    <?php echo __('welcome_title'); ?>
                </h1>
                <p class="text-white/90 text-lg font-medium">
                    <?php echo __('welcome_subtitle'); ?>
                </p>
            </div>

            <!-- Description -->
            <div class="text-center mb-8 slide-up slide-up-delay-1">
                <p class="text-white/80 text-base leading-relaxed max-w-sm mx-auto">
                    <?php echo __('welcome_description'); ?>
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-3 gap-3 mb-8 slide-up slide-up-delay-2">
                <div class="feature-card p-3 rounded-xl text-center">
                    <i data-feather="utensils" class="h-6 w-6 text-white mx-auto mb-2"></i>
                    <p class="text-white text-xs font-medium"><?php echo __('explore_menu'); ?></p>
                </div>
                <div class="feature-card p-3 rounded-xl text-center">
                    <i data-feather="star" class="h-6 w-6 text-white mx-auto mb-2"></i>
                    <p class="text-white text-xs font-medium"><?php echo __('popular_dishes'); ?></p>
                </div>
                <div class="feature-card p-3 rounded-xl text-center">
                    <i data-feather="gift" class="h-6 w-6 text-white mx-auto mb-2"></i>
                    <p class="text-white text-xs font-medium"><?php echo __('special_offers'); ?></p>
                </div>
            </div>

            <!-- About Section -->
            <div class="text-center mb-8 slide-up slide-up-delay-3">
                <h3 class="text-white font-semibold text-lg mb-2"><?php echo __('about_us'); ?></h3>
                <p class="text-white/80 text-sm leading-relaxed max-w-xs mx-auto">
                    <?php echo __('about_description'); ?>
                </p>
            </div>

            <!-- Get Started Button -->
            <div class="text-center slide-up slide-up-delay-3">
                <button onclick="continueToApp()" 
                        class="get-started-btn w-full max-w-xs mx-auto py-4 px-8 rounded-2xl font-bold text-lg">
                    <?php echo __('get_started'); ?>
                </button>
            </div>
        </main>

        <!-- Footer -->
        <footer class="p-4 text-center">
            <p class="text-white/60 text-xs">
                © 2025 Fenyal. All rights reserved.
            </p>
        </footer>
    </div>

    <!-- JavaScript -->
    <script>
        // Initialize icons
        feather.replace();

        // Set viewport height for mobile
        function setViewportHeight() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);

        // Continue to main app
        function continueToApp() {
            const currentLang = '<?php echo $currentLang; ?>';
            
            // Show loading overlay
            const loadingOverlay = document.getElementById('loading-overlay');
            loadingOverlay.classList.remove('hidden');
            
            // Mark as visited
            localStorage.setItem('hasVisited', 'true');
            localStorage.setItem('selectedLanguage', currentLang);
            
            // Navigate to main app after short delay
            setTimeout(() => {
                window.location.href = `index.php?lang=${currentLang}`;
            }, 1000);
        }

        // Auto-continue if user has already visited
        document.addEventListener('DOMContentLoaded', function() {
            // Remove loading overlay after page loads
            setTimeout(() => {
                const loadingOverlay = document.getElementById('loading-overlay');
                loadingOverlay.classList.add('hidden');
            }, 500);

            // Check if user has visited before
            const hasVisited = localStorage.getItem('hasVisited');
            const selectedLanguage = localStorage.getItem('selectedLanguage');
            
            if (hasVisited && selectedLanguage) {
                // Auto-redirect after 3 seconds if they've been here before
                setTimeout(() => {
                    if (confirm('<?php echo $currentLang === "ar" ? "الانتقال إلى التطبيق؟" : "Continue to app?"; ?>')) {
                        continueToApp();
                    }
                }, 3000);
            }
        });

        // Touch feedback for buttons
        document.querySelectorAll('button, a, .feature-card').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.97)';
            }, { passive: true });

            element.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });

            element.addEventListener('touchcancel', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });
        });

        // Language switcher analytics
        document.querySelectorAll('.language-button').forEach(button => {
            button.addEventListener('click', function(e) {
                const lang = this.href.includes('lang=ar') ? 'ar' : 'en';
                console.log('Language switched to:', lang);
                
                // Store preference
                localStorage.setItem('selectedLanguage', lang);
            });
        });

        // Prevent back button on this page
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };

        // Register service worker
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

        // PWA install prompt handling
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show a custom install button after 5 seconds
            setTimeout(() => {
                if (deferredPrompt && !localStorage.getItem('pwa-dismissed')) {
                    showInstallPrompt();
                }
            }, 5000);
        });

        function showInstallPrompt() {
            const installPrompt = document.createElement('div');
            installPrompt.className = 'fixed bottom-4 left-4 right-4 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl p-4 text-center';
            installPrompt.innerHTML = `
                <p class="text-white text-sm mb-2"><?php echo $currentLang === 'ar' ? 'تثبيت التطبيق على الشاشة الرئيسية؟' : 'Install app on your home screen?'; ?></p>
                <div class="flex gap-2 justify-center">
                    <button onclick="installPWA()" class="bg-white text-primary px-4 py-2 rounded-lg text-sm font-medium">
                        <?php echo $currentLang === 'ar' ? 'تثبيت' : 'Install'; ?>
                    </button>
                    <button onclick="dismissInstall()" class="bg-white/20 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <?php echo $currentLang === 'ar' ? 'لاحقاً' : 'Later'; ?>
                    </button>
                </div>
            `;
            document.body.appendChild(installPrompt);
        }

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
            dismissInstall();
        }

        function dismissInstall() {
            localStorage.setItem('pwa-dismissed', 'true');
            const installPrompt = document.querySelector('.fixed.bottom-4');
            if (installPrompt) {
                installPrompt.remove();
            }
        }
    </script>
</body>
</html>