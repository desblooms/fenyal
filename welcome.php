<?php
// welcome.php - Bilingual Welcome/Onboarding Page
require_once 'config/language.php';

$currentLang = getCurrentLanguage();
$direction = getDirection();
$alternativeLang = getAlternativeLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo __('home'); ?> - Fenyal</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    
    <style>
        [lang="ar"] {
            font-family: 'Cairo', 'Poppins', sans-serif;
        }
        
        .welcome-container {
            background: linear-gradient(135deg, #c45230 0%, #f96d43 100%);
            min-height: 100vh;
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="welcome-container text-white">
    <div class="min-h-screen flex flex-col justify-center items-center px-6 text-center">
        <!-- Language Toggle -->
        <div class="absolute top-6 <?php echo isRTL() ? 'left-6' : 'right-6'; ?>">
            <a href="welcome.php?lang=<?php echo $alternativeLang; ?>" 
               class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center">
                <span class="text-sm font-medium text-white">
                    <?php echo strtoupper($alternativeLang); ?>
                </span>
            </a>
        </div>
        
        <!-- Logo -->
        <div class="mb-8 fade-in">
            <img src="fenyal-logo-1.png" alt="Fenyal Logo" class="w-32 h-32 mx-auto mb-4 rounded-full bg-white/10 p-4">
        </div>
        
        <!-- Welcome Content -->
        <div class="slide-up" style="animation-delay: 0.2s;">
            <h1 class="text-3xl font-bold mb-4">
                <?php echo $currentLang === 'ar' ? 'مرحباً بك في فنيال' : 'Welcome to Fenyal'; ?>
            </h1>
            <p class="text-lg text-white/90 mb-8 leading-relaxed">
                <?php echo $currentLang === 'ar' 
                    ? 'اكتشف أشهى الأطباق الشرق أوسطية والخليجية من راحة منزلك' 
                    : 'Discover the finest Middle Eastern and Gulf cuisine from the comfort of your home'; ?>
            </p>
        </div>
        
        <!-- Features -->
        <div class="mb-8 space-y-4 slide-up" style="animation-delay: 0.4s;">
            <div class="flex items-center <?php echo isRTL() ? 'flex-row-reverse' : ''; ?> text-left">
                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center <?php echo isRTL() ? 'ml-3' : 'mr-3'; ?>">
                    <span class="text-xs">✓</span>
                </div>
                <span class="text-white/90">
                    <?php echo $currentLang === 'ar' ? 'أطباق طازجة ومُحضرة يومياً' : 'Fresh, daily prepared dishes'; ?>
                </span>
            </div>
            <div class="flex items-center <?php echo isRTL() ? 'flex-row-reverse' : ''; ?> text-left">
                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center <?php echo isRTL() ? 'ml-3' : 'mr-3'; ?>">
                    <span class="text-xs">✓</span>
                </div>
                <span class="text-white/90">
                    <?php echo $currentLang === 'ar' ? 'توصيل سريع وموثوق' : 'Fast and reliable delivery'; ?>
                </span>
            </div>
            <div class="flex items-center <?php echo isRTL() ? 'flex-row-reverse' : ''; ?> text-left">
                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center <?php echo isRTL() ? 'ml-3' : 'mr-3'; ?>">
                    <span class="text-xs">✓</span>
                </div>
                <span class="text-white/90">
                    <?php echo $currentLang === 'ar' ? 'أسعار مناسبة وجودة عالية' : 'Affordable prices, premium quality'; ?>
                </span>
            </div>
        </div>
        
        <!-- CTA Button -->
        <div class="slide-up" style="animation-delay: 0.6s;">
            <button onclick="enterApp()" 
                    class="bg-white text-primary font-semibold py-4 px-8 rounded-xl text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                <?php echo $currentLang === 'ar' ? 'ابدأ الطلب الآن' : 'Start Ordering Now'; ?>
            </button>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-white/70 text-sm slide-up" style="animation-delay: 0.8s;">
            <?php echo $currentLang === 'ar' ? 'أفضل المأكولات الشرق أوسطية في قطر' : 'The finest Middle Eastern cuisine in Qatar'; ?>
        </div>
    </div>
    
    <script>
        function enterApp() {
            // Mark user as visited
            localStorage.setItem('hasVisited', 'true');
            
            // Fade out and redirect
            document.body.style.transition = 'opacity 0.5s ease-out';
            document.body.style.opacity = '0';
            
            setTimeout(() => {
                window.location.href = 'index.php?lang=<?php echo $currentLang; ?>';
            }, 500);
        }
        
        // Auto-advance after 5 seconds if user doesn't interact
        setTimeout(() => {
            if (!localStorage.getItem('hasVisited')) {
                enterApp();
            }
        }, 5000);
    </script>
</body>
</html>