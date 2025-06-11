<?php
// login.php - Modern Mobile-First Admin Login
require_once 'config.php';

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (adminLogin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title>Fenyal Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#c45230',
                        accent: '#f96d43',
                        background: '#f8f8f8',
                        dark: '#1a1a1a'
                    },
                    fontFamily: {
                        sans: ['"Poppins"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        .app-container {
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100);
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(196, 82, 48, 0.15);
        }
        
        .login-button {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #c45230 0%, #f96d43 100%);
        }
        
        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(196, 82, 48, 0.3);
        }
        
        .login-button:active {
            transform: scale(0.98);
        }
        
        .logo-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .form-container {
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Loading state */
        .loading {
            position: relative;
            color: transparent;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Mobile viewport fix */
        .mobile-height {
            min-height: 100vh;
            min-height: calc(var(--vh, 1vh) * 100);
        }
    </style>
</head>
<body class="bg-background">
    <div class="app-container mobile-height flex flex-col">
        <!-- Main Content -->
        <div class="flex-1 flex flex-col justify-center px-6 py-8">
            <!-- Logo Section -->
            <div class="logo-container text-center mb-12">
                <div class="w-20 h-20 rounded-full bg-white shadow-lg flex items-center justify-center mx-auto mb-4">
                    <img src="../fenyal-logo-1.png" width="50" height="50" alt="Fenyal Logo" class="rounded-full">
                </div>
                <h1 class="text-2xl font-bold text-dark mb-1">Welcome Back</h1>
                <p class="text-gray-500 text-sm">Sign in to your admin panel</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="error-shake bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-center text-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="form-container">
                <form method="POST" class="space-y-6" id="loginForm">
                    <!-- Username Field -->
                    <div>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required
                               placeholder="Username"
                               class="input-field w-full px-4 py-4 bg-white border border-gray-200 rounded-xl text-dark placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Password Field -->
                    <div>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               placeholder="Password"
                               class="input-field w-full px-4 py-4 bg-white border border-gray-200 rounded-xl text-dark placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Login Button -->
                    <button type="submit" 
                            id="loginButton"
                            class="login-button w-full py-4 px-6 text-white font-medium rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 pb-8">
            <div class="text-center">
                <div class="bg-white/50 backdrop-blur-sm rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-2">Demo Credentials</p>
                    <div class="flex justify-center space-x-4 text-xs">
                        <span class="text-gray-600">admin</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-600">fenyal2024</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile viewport height fix
        function setViewportHeight() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        window.addEventListener('orientationchange', () => {
            setTimeout(setViewportHeight, 100);
        });

        // Auto-focus username field with delay for better UX
        setTimeout(() => {
            document.getElementById('username').focus();
        }, 500);

        // Enhanced form handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Basic validation
            if (!username || !password) {
                e.preventDefault();
                showError('Please fill in all fields');
                return;
            }
            
            // Loading state
            button.classList.add('loading');
            button.disabled = true;
            
            // Remove error styling if exists
            removeErrorStyling();
        });

        // Enhanced touch feedback
        document.querySelectorAll('input, button').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            }, { passive: true });

            element.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });
        });

        // Error handling functions
        function showError(message) {
            // Remove existing error
            const existingError = document.querySelector('.dynamic-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Create new error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'dynamic-error error-shake bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-center text-sm';
            errorDiv.textContent = message;
            
            // Insert before form
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(errorDiv, form);
            
            // Add error styling to inputs
            document.querySelectorAll('input').forEach(input => {
                input.classList.add('border-red-300', 'bg-red-50');
            });
        }

        function removeErrorStyling() {
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('border-red-300', 'bg-red-50');
            });
        }

        // Input animations
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
            
            // Clear error styling on input
            input.addEventListener('input', function() {
                removeErrorStyling();
                const dynamicError = document.querySelector('.dynamic-error');
                if (dynamicError) {
                    dynamicError.remove();
                }
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const focused = document.activeElement;
                if (focused.id === 'username') {
                    document.getElementById('password').focus();
                    e.preventDefault();
                }
            }
        });

        // Demo credentials helper
        let tapCount = 0;
        document.querySelector('.bg-white\\/50').addEventListener('click', function() {
            tapCount++;
            if (tapCount === 3) {
                document.getElementById('username').value = 'admin';
                document.getElementById('password').value = 'fenyal2024';
                tapCount = 0;
                
                // Visual feedback
                this.style.background = 'rgba(196, 82, 48, 0.1)';
                setTimeout(() => {
                    this.style.background = '';
                }, 200);
            }
            
            // Reset tap count after 2 seconds
            setTimeout(() => {
                tapCount = 0;
            }, 2000);
        });
    </script>
</body>
</html>