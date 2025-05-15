<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<footer class="fixed bottom-0 left-0 w-full bg-[#3a001e] text-[#fff6f0] shadow-lg z-50">
    <!-- Mobile Navigation Bar -->
    <div class="container mx-auto">
        <div class="flex justify-between items-center h-16">
            <!-- Home -->
            <a href="index.php" class="flex flex-col items-center justify-center w-1/5 py-2 text-center <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-[#c35331] font-bold' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs mt-1">Home</span>
            </a>

            <!-- Menu -->
            <a href="pages/menu.php" class="flex flex-col items-center justify-center w-1/5 py-2 text-center <?php echo (basename($_SERVER['PHP_SELF']) == 'menu.php') ? 'text-[#c35331] font-bold' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="text-xs mt-1">Menu</span>
            </a>

            <!-- Cart -->
            <a href="pages/cart.php" class="flex flex-col items-center justify-center w-1/5 py-2 text-center <?php echo (basename($_SERVER['PHP_SELF']) == 'cart.php') ? 'text-[#c35331] font-bold' : ''; ?>">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-[#c35331] text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?php echo count($_SESSION['cart']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <span class="text-xs mt-1">Cart</span>
            </a>

            <!-- My Orders -->
            <a href="pages/my-orders.php" class="flex flex-col items-center justify-center w-1/5 py-2 text-center <?php echo (basename($_SERVER['PHP_SELF']) == 'my-orders.php') ? 'text-[#c35331] font-bold' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="text-xs mt-1">Orders</span>
            </a>

            <!-- Account -->
            <a href="<?php echo $isLoggedIn ? 'pages/my-account.php' : 'pages/login.php'; ?>" class="flex flex-col items-center justify-center w-1/5 py-2 text-center <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php' || basename($_SERVER['PHP_SELF']) == 'my-account.php') ? 'text-[#c35331] font-bold' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-xs mt-1"><?php echo $isLoggedIn ? 'Account' : 'Login'; ?></span>
            </a>
        </div>
    </div>
    
    <!-- Add space at the bottom to account for the fixed navbar -->
    <div class="h-16 md:hidden"></div>
    
    <!-- JavaScript Files -->
    <script src="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>assets/js/main.js"></script>
    <script src="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>assets/js/cart.js"></script>
    
    <?php if (isset($_SESSION['theme'])): ?>
    <script>
        // Apply selected theme
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = "<?php echo $_SESSION['theme']; ?>";
            if (currentTheme) {
                applyTheme(currentTheme);
            }
        });
    </script>
    <?php endif; ?>
</footer>

<script>
    // Function to handle relative paths for different directory levels
    function fixRelativePaths() {
        const currentPath = window.location.pathname;
        const isInPagesDir = currentPath.includes('/pages/');
        
        // Fix navigation links if we're in the pages directory
        if (isInPagesDir) {
            document.querySelectorAll('footer a').forEach(link => {
                if (link.getAttribute('href').startsWith('pages/')) {
                    link.setAttribute('href', link.getAttribute('href').replace('pages/', '../pages/'));
                } else if (link.getAttribute('href') === 'index.php') {
                    link.setAttribute('href', '../index.php');
                }
            });
        }
    }
    
    // Apply theme based on session
    function applyTheme(themeName) {
        const themeLink = document.getElementById('theme-css');
        if (!themeLink) {
            const head = document.getElementsByTagName('head')[0];
            const link = document.createElement('link');
            link.id = 'theme-css';
            link.rel = 'stylesheet';
            link.type = 'text/css';
            link.href = '<?php echo isset($baseUrl) ? $baseUrl : ''; ?>assets/css/themes/' + themeName + '.css';
            head.appendChild(link);
        } else {
            themeLink.href = '<?php echo isset($baseUrl) ? $baseUrl : ''; ?>assets/css/themes/' + themeName + '.css';
        }
    }
    
    // Execute when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', fixRelativePaths);
</script>

</body>
</html>