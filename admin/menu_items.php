<?php
// admin/menu_items.php - Enhanced Mobile Menu Items Management with Image Support
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$message = '';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Handle actions
if ($_POST) {
    switch ($_POST['action']) {
        case 'delete':
            $id = (int)$_POST['id'];
            
            // Get item data to delete image file
            $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            // Delete the item
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            if ($stmt->execute([$id])) {
                // Delete associated image file
                if ($item['image'] && file_exists('../' . $item['image'])) {
                    unlink('../' . $item['image']);
                }
                $message = 'Item deleted successfully!';
            }
            break;
            
        case 'toggle_popular':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE menu_items SET is_popular = NOT is_popular WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Popular status updated!';
            }
            break;
            
        case 'toggle_special':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE menu_items SET is_special = NOT is_special WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Special status updated!';
            }
            break;
    }
}

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR name_ar LIKE ? OR description LIKE ? OR category LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) FROM menu_items $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

// Get menu items
$sql = "SELECT * FROM menu_items $whereClause ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM menu_items ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title>Menu Items - Fenyal Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#c45230',
                        accent: '#f96d43',
                        background: '#f8f9fa',
                        surface: '#ffffff'
                    },
                    fontFamily: {
                        sans: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-tap-highlight-color: transparent;
            -webkit-font-smoothing: antialiased;
            background-color: #f8f9fa;
        }
        
        .smooth-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .scale-tap:active {
            transform: scale(0.96);
        }
        
        .slide-up {
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .item-card {
            transition: all 0.2s ease;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #c45230;
            color: white;
            border: none;
            box-shadow: 0 8px 32px rgba(196, 82, 48, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            transition: all 0.3s ease;
        }
        
        .fab:hover {
            transform: scale(1.1);
        }
        
        .fab:active {
            transform: scale(0.95);
        }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
        }
        
        .filter-chip {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            background: white;
            color: #6b7280;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .filter-chip.active {
            background: #c45230;
            color: white;
            border-color: #c45230;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .image-placeholder {
            background: linear-gradient(45deg, #f3f4f6 25%, transparent 25%), 
                        linear-gradient(-45deg, #f3f4f6 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #f3f4f6 75%), 
                        linear-gradient(-45deg, transparent 75%, #f3f4f6 75%);
            background-size: 8px 8px;
            background-position: 0 0, 0 4px, 4px -4px, -4px 0px;
        }
    </style>
</head>

<body class="bg-background text-gray-900">
    <!-- Header -->
    <header class="bg-surface shadow-sm sticky top-0 z-40 slide-up">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Back Button & Title -->
                <div class="flex items-center space-x-3">
                    <button onclick="history.back()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-tap smooth-transition">
                        <i data-feather="arrow-left" class="h-5 w-5 text-gray-600"></i>
                    </button>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">Menu Items</h1>
                        <p class="text-sm text-gray-500"><?php echo $totalItems; ?> items</p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <button onclick="toggleSearch()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-tap smooth-transition">
                        <i data-feather="search" class="h-5 w-5 text-gray-600"></i>
                    </button>
                    <button onclick="openSettings()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-tap smooth-transition">
                        <i data-feather="more-vertical" class="h-5 w-5 text-gray-600"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Search Bar (Initially Hidden) -->
    <div id="searchBar" class="bg-surface px-4 py-3 border-b border-gray-100 transform -translate-y-full transition-transform duration-300 ease-in-out">
        <form method="GET" class="relative">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search menu items..." 
                   class="w-full bg-gray-50 rounded-xl py-3 pl-12 pr-4 text-sm border-none focus:outline-none focus:ring-2 focus:ring-primary/20 smooth-transition">
            <i data-feather="search" class="absolute left-4 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400"></i>
            <?php if ($search): ?>
            <button type="button" onclick="clearSearch()" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center">
                <i data-feather="x" class="h-3 w-3 text-gray-600"></i>
            </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Filter -->
    <div class="bg-surface px-4 py-3 border-b border-gray-100">
        <div class="flex space-x-2 overflow-x-auto pb-1" style="scrollbar-width: none; -ms-overflow-style: none;">
            <a href="?search=<?php echo urlencode($search); ?>" 
               class="filter-chip scale-tap <?php echo empty($category) ? 'active' : ''; ?>">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="?category=<?php echo urlencode($cat); ?>&search=<?php echo urlencode($search); ?>" 
               class="filter-chip scale-tap <?php echo $category === $cat ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($message): ?>
    <div class="mx-4 mt-4 bg-green-50 border border-green-200 rounded-xl p-4 slide-up">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i data-feather="check-circle" class="h-5 w-5 text-green-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="px-4 py-4 pb-24">
        <?php if (empty($items)): ?>
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-16 slide-up">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i data-feather="coffee" class="h-10 w-10 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No menu items found</h3>
            <p class="text-gray-500 text-center mb-6 max-w-sm">
                <?php echo $search || $category ? 'Try adjusting your search or filter' : 'Start building your menu by adding your first item'; ?>
            </p>
            <?php if (!($search || $category)): ?>
            <a href="edit_item.php" class="bg-primary text-white px-6 py-3 rounded-xl font-medium scale-tap smooth-transition">
                Add First Item
            </a>
            <?php else: ?>
            <a href="menu_items.php" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-medium scale-tap smooth-transition">
                Clear Filters
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        
        <!-- Items Grid -->
        <div class="space-y-3">
            <?php foreach ($items as $index => $item): ?>
            <div class="item-card shadow-sm slide-up" style="animation-delay: <?php echo $index * 0.05; ?>s">
                <div class="p-4">
                    <div class="flex items-start space-x-4">
                        <!-- Item Image -->
                        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 image-placeholder">
                            <?php if ($item['image']): ?>
                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-gray-400\'><i data-feather=\'image\' class=\'h-6 w-6\'></i></div>'; feather.replace();">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <i data-feather="image" class="h-6 w-6"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Item Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <?php if ($item['name_ar']): ?>
                                    <p class="text-sm text-gray-500 truncate" dir="rtl"><?php echo htmlspecialchars($item['name_ar']); ?></p>
                                    <?php endif; ?>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category']); ?></span>
                                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                        <span class="text-sm font-semibold text-primary">QAR <?php echo number_format($item['price'], 0); ?></span>
                                        <?php if ($item['is_half_full']): ?>
                                        <span class="text-xs text-gray-400">(Half/Full)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Actions Menu -->
                                <button onclick="openActionMenu(<?php echo $item['id']; ?>)" class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center scale-tap smooth-transition">
                                    <i data-feather="more-horizontal" class="h-4 w-4 text-gray-600"></i>
                                </button>
                            </div>
                            
                            <!-- Description -->
                            <?php if ($item['description']): ?>
                            <p class="text-xs text-gray-500 mt-2 line-clamp-2"><?php echo htmlspecialchars(substr($item['description'], 0, 80)); ?><?php echo strlen($item['description']) > 80 ? '...' : ''; ?></p>
                            <?php endif; ?>
                            
                            <!-- Status Badges -->
                            <div class="flex items-center space-x-2 mt-3">
                                <?php if ($item['is_popular']): ?>
                                <span class="status-badge bg-yellow-100 text-yellow-800">
                                    <i data-feather="star" class="h-3 w-3 mr-1"></i>
                                    Popular
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($item['is_special']): ?>
                                <span class="status-badge bg-green-100 text-green-800">
                                    <i data-feather="zap" class="h-3 w-3 mr-1"></i>
                                    Special
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($item['is_half_full']): ?>
                                <span class="status-badge bg-blue-100 text-blue-800">
                                    <i data-feather="layers" class="h-3 w-3 mr-1"></i>
                                    Half/Full
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More Button -->
        <?php if ($totalPages > $page): ?>
        <div class="mt-6 text-center">
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
               class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium scale-tap smooth-transition">
                <i data-feather="arrow-down" class="h-4 w-4 mr-2"></i>
                Load More (<?php echo $totalPages - $page; ?> pages left)
            </a>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </main>

    <!-- Floating Action Button -->
    <button onclick="window.location.href='edit_item.php'" class="fab">
        <i data-feather="plus" class="h-6 w-6"></i>
    </button>

    <!-- Action Menu Modal -->
    <div id="actionModal" class="fixed inset-0 z-50 modal-overlay hidden">
        <div class="flex items-end justify-center min-h-screen p-4">
            <div class="bg-surface rounded-t-3xl w-full max-w-md transform translate-y-full transition-transform duration-300 ease-out" id="actionSheet">
                <div class="p-6">
                    <!-- Handle -->
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-6"></div>
                    
                    <!-- Menu Item Info -->
                    <div id="modalItemInfo" class="mb-6">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    
                    <!-- Actions -->
                    <div class="space-y-3">
                        <button onclick="editItem()" class="w-full flex items-center px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <i data-feather="edit-3" class="h-5 w-5 text-gray-600 mr-3"></i>
                            Edit Item
                        </button>
                        
                        <button onclick="togglePopular()" class="w-full flex items-center px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <i data-feather="star" class="h-5 w-5 text-gray-600 mr-3"></i>
                            <span id="popularText">Toggle Popular</span>
                        </button>
                        
                        <button onclick="toggleSpecial()" class="w-full flex items-center px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <i data-feather="zap" class="h-5 w-5 text-gray-600 mr-3"></i>
                            <span id="specialText">Toggle Special</span>
                        </button>
                        
                        <button onclick="deleteItem()" class="w-full flex items-center px-4 py-3 text-left text-red-600 hover:bg-red-50 rounded-xl smooth-transition">
                            <i data-feather="trash-2" class="h-5 w-5 text-red-600 mr-3"></i>
                            Delete Item
                        </button>
                    </div>
                    
                    <!-- Cancel Button -->
                    <button onclick="closeActionMenu()" class="w-full mt-6 bg-gray-100 text-gray-700 py-3 rounded-xl font-medium">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="fixed inset-0 z-50 modal-overlay hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-surface rounded-3xl w-full max-w-md transform translate-y-full transition-transform duration-300 ease-out" id="settingsSheet">
                <div class="p-6">
                    <!-- Handle -->
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-6"></div>
                    
                    <!-- Title -->
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Settings</h2>
                    
                    <!-- Settings Options -->
                    <div class="space-y-3">
                        <button onclick="goToCategories()" class="w-full flex items-center justify-between px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <div class="flex items-center">
                                <i data-feather="grid" class="h-5 w-5 text-gray-600 mr-3"></i>
                                Manage Categories
                            </div>
                            <i data-feather="chevron-right" class="h-4 w-4 text-gray-400"></i>
                        </button>
                        
                        <button onclick="exportData()" class="w-full flex items-center justify-between px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <div class="flex items-center">
                                <i data-feather="download" class="h-5 w-5 text-gray-600 mr-3"></i>
                                Export Menu Data
                            </div>
                            <i data-feather="chevron-right" class="h-4 w-4 text-gray-400"></i>
                        </button>
                        
                        <button onclick="importData()" class="w-full flex items-center justify-between px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <div class="flex items-center">
                                <i data-feather="upload" class="h-5 w-5 text-gray-600 mr-3"></i>
                                Import Menu Data
                            </div>
                            <i data-feather="chevron-right" class="h-4 w-4 text-gray-400"></i>
                        </button>
                        
                        <div class="border-t border-gray-100 my-4"></div>
                        
                        <button onclick="viewStatistics()" class="w-full flex items-center justify-between px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <div class="flex items-center">
                                <i data-feather="bar-chart-3" class="h-5 w-5 text-gray-600 mr-3"></i>
                                View Statistics
                            </div>
                            <i data-feather="chevron-right" class="h-4 w-4 text-gray-400"></i>
                        </button>
                        
                        <button onclick="goToDashboard()" class="w-full flex items-center justify-between px-4 py-3 text-left text-gray-900 hover:bg-gray-50 rounded-xl smooth-transition">
                            <div class="flex items-center">
                                <i data-feather="home" class="h-5 w-5 text-gray-600 mr-3"></i>
                                Dashboard
                            </div>
                            <i data-feather="chevron-right" class="h-4 w-4 text-gray-400"></i>
                        </button>
                        
                        <div class="border-t border-gray-100 my-4"></div>
                        
                        <button onclick="logout()" class="w-full flex items-center px-4 py-3 text-left text-red-600 hover:bg-red-50 rounded-xl smooth-transition">
                            <i data-feather="log-out" class="h-5 w-5 text-red-600 mr-3"></i>
                            Logout
                        </button>
                    </div>
                    
                    <!-- Cancel Button -->
                    <button onclick="closeSettings()" class="w-full mt-6 bg-gray-100 text-gray-700 py-3 rounded-xl font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Feather Icons
        feather.replace();

        // Global variables
        let currentItemId = null;
        let searchVisible = false;

        // Menu items data (for modal)
        const menuItems = <?php echo json_encode($items); ?>;

        // Toggle search bar
        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            searchVisible = !searchVisible;
            
            if (searchVisible) {
                searchBar.style.transform = 'translateY(0)';
                searchBar.querySelector('input').focus();
            } else {
                searchBar.style.transform = 'translateY(-100%)';
            }
        }

        // Clear search
        function clearSearch() {
            window.location.href = 'menu_items.php' + (new URLSearchParams(window.location.search).get('category') ? '?category=' + new URLSearchParams(window.location.search).get('category') : '');
        }

        // Open settings menu
        function openSettings() {
            const modal = document.getElementById('settingsModal');
            const sheet = document.getElementById('settingsSheet');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                sheet.style.transform = 'translateY(0)';
            }, 10);
            
            // Add haptic feedback
            hapticFeedback('medium');
        }

        // Close settings menu
        function closeSettings() {
            const modal = document.getElementById('settingsModal');
            const sheet = document.getElementById('settingsSheet');
            
            sheet.style.transform = 'translateY(100%)';
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Settings menu actions
        function goToCategories() {
            window.location.href = 'categories.php';
        }

        function exportData() {
            closeSettings();
            
            // Show loading toast
            showToast('Preparing export...', 'info');
            
            // Create form for export
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php';
            form.innerHTML = '<input type="hidden" name="action" value="export_json">';
            document.body.appendChild(form);
            form.submit();
            
            setTimeout(() => {
                showToast('Export completed!', 'success');
            }, 1000);
        }

        function importData() {
            closeSettings();
            
            // Create file input
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = '.json';
            fileInput.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (confirm('This will replace all existing menu data. Are you sure?')) {
                        const formData = new FormData();
                        formData.append('json_file', file);
                        formData.append('action', 'import_json');
                        
                        showToast('Importing data...', 'info');
                        
                        fetch('index.php', {
                            method: 'POST',
                            body: formData
                        }).then(() => {
                            showToast('Import completed!', 'success');
                            setTimeout(() => location.reload(), 1500);
                        }).catch(() => {
                            showToast('Import failed!', 'error');
                        });
                    }
                }
            };
            fileInput.click();
        }

        function viewStatistics() {
            closeSettings();
            showStatisticsModal();
        }

        function goToDashboard() {
            window.location.href = 'index.php';
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Haptic feedback for supported devices
        function hapticFeedback(type = 'light') {
            if ('vibrate' in navigator) {
                switch(type) {
                    case 'light':
                        navigator.vibrate(10);
                        break;
                    case 'medium':
                        navigator.vibrate(20);
                        break;
                    case 'heavy':
                        navigator.vibrate(50);
                        break;
                }
            }
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-20 left-4 right-4 px-4 py-3 rounded-xl text-white text-sm font-medium z-50 transform -translate-y-full transition-transform duration-300`;
            
            switch(type) {
                case 'success':
                    toast.classList.add('bg-green-500');
                    break;
                case 'error':
                    toast.classList.add('bg-red-500');
                    break;
                case 'warning':
                    toast.classList.add('bg-yellow-500');
                    break;
                default:
                    toast.classList.add('bg-blue-500');
            }
            
            toast.innerHTML = `
                <div class="flex items-center">
                    <i data-feather="${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info'}" class="h-4 w-4 mr-2"></i>
                    ${message}
                </div>
            `;
            
            document.body.appendChild(toast);
            feather.replace();
            
            setTimeout(() => {
                toast.style.transform = 'translateY(0)';
            }, 100);
            
            setTimeout(() => {
                toast.style.transform = 'translateY(-100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Statistics modal function
        function showStatisticsModal() {
            const statsModal = document.createElement('div');
            statsModal.className = 'fixed inset-0 z-50 modal-overlay';
            statsModal.innerHTML = `
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-surface rounded-3xl w-full max-w-md transform scale-95 transition-transform duration-300 ease-out">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-semibold text-gray-900">Statistics</h2>
                                <button onclick="this.closest('.modal-overlay').remove()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                    <i data-feather="x" class="h-4 w-4 text-gray-600"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mr-3">
                                            <i data-feather="package" class="h-5 w-5 text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">Total Items</p>
                                            <p class="text-sm text-gray-500">All menu items</p>
                                        </div>
                                    </div>
                                    <span class="text-xl font-bold text-gray-900"><?php echo $totalItems; ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center mr-3">
                                            <i data-feather="star" class="h-5 w-5 text-yellow-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">Popular Items</p>
                                            <p class="text-sm text-gray-500">Marked as popular</p>
                                        </div>
                                    </div>
                                    <span class="text-xl font-bold text-gray-900">${menuItems.filter(item => item.is_popular).length}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3">
                                            <i data-feather="zap" class="h-5 w-5 text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">Special Items</p>
                                            <p class="text-sm text-gray-500">Marked as special</p>
                                        </div>
                                    </div>
                                    <span class="text-xl font-bold text-gray-900">${menuItems.filter(item => item.is_special).length}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-3">
                                            <i data-feather="grid" class="h-5 w-5 text-purple-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">Categories</p>
                                            <p class="text-sm text-gray-500">Menu categories</p>
                                        </div>
                                    </div>
                                    <span class="text-xl font-bold text-gray-900"><?php echo count($categories); ?></span>
                                </div>
                            </div>
                            
                            <button onclick="this.closest('.modal-overlay').remove()" class="w-full mt-6 bg-primary text-white py-3 rounded-xl font-medium">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(statsModal);
            feather.replace();
            
            setTimeout(() => {
                statsModal.querySelector('.bg-surface').style.transform = 'scale(1)';
            }, 100);
        }

        // Open action menu
        function openActionMenu(itemId) {
            currentItemId = itemId;
            const item = menuItems.find(i => i.id == itemId);
            
            if (item) {
                // Populate modal info
                const imageSrc = item.image ? `../${item.image}` : 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" fill="%23f3f4f6"/><text x="24" y="28" text-anchor="middle" fill="%239ca3af" font-size="9">No Image</text></svg>';
                
                document.getElementById('modalItemInfo').innerHTML = `
                    <div class="flex items-center space-x-3">
                        <img src="${imageSrc}" alt="${item.name}" class="w-12 h-12 rounded-lg object-cover"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2248%22 height=%2248%22 viewBox=%220 0 48 48%22><rect width=%2248%22 height=%2248%22 fill=%22%23f3f4f6%22/><text x=%2224%22 y=%2228%22 text-anchor=%22middle%22 fill=%22%239ca3af%22 font-size=%229%22>No Image</text></svg>'">
                        <div>
                            <h3 class="font-semibold text-gray-900">${item.name}</h3>
                            <p class="text-sm text-gray-500">${item.category} • QAR ${item.price}</p>
                        </div>
                    </div>
                `;
                
                // Update button texts
                document.getElementById('popularText').textContent = item.is_popular ? 'Remove from Popular' : 'Mark as Popular';
                document.getElementById('specialText').textContent = item.is_special ? 'Remove from Special' : 'Mark as Special';
            }
            
            // Show modal
            const modal = document.getElementById('actionModal');
            const sheet = document.getElementById('actionSheet');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                sheet.style.transform = 'translateY(0)';
            }, 10);
        }

        // Close action menu
        function closeActionMenu() {
            const modal = document.getElementById('actionModal');
            const sheet = document.getElementById('actionSheet');
            
            sheet.style.transform = 'translateY(100%)';
            setTimeout(() => {
                modal.classList.add('hidden');
                currentItemId = null;
            }, 300);
        }

        // Edit item
        function editItem() {
            if (currentItemId) {
                window.location.href = `edit_item.php?id=${currentItemId}`;
            }
        }

        // Toggle popular status
        function togglePopular() {
            if (currentItemId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_popular">
                    <input type="hidden" name="id" value="${currentItemId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle special status
        function toggleSpecial() {
            if (currentItemId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_special">
                    <input type="hidden" name="id" value="${currentItemId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete item
        function deleteItem() {
            if (currentItemId && confirm('Are you sure you want to delete this item? This action cannot be undone and will also delete the associated image file.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${currentItemId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('actionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeActionMenu();
            }
        });

        // Close settings modal when clicking outside
        document.getElementById('settingsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSettings();
            }
        });

        // Handle search input with debounce
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

        // Auto-hide search when scrolling down
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100 && searchVisible) {
                toggleSearch(); // Hide search when scrolling down
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        }, { passive: true });

        // Add touch feedback to buttons
        document.querySelectorAll('.scale-tap').forEach(element => {
            element.addEventListener('touchstart', () => {
                element.style.transform = 'scale(0.96)';
                hapticFeedback('light');
            }, { passive: true });
            
            element.addEventListener('touchend', () => {
                element.style.transform = 'scale(1)';
            }, { passive: true });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (!searchVisible) toggleSearch();
                else document.querySelector('input[name="search"]').focus();
            }
            
            // Escape to close modals/search
            if (e.key === 'Escape') {
                if (searchVisible) toggleSearch();
                if (!document.getElementById('actionModal').classList.contains('hidden')) {
                    closeActionMenu();
                }
                if (!document.getElementById('settingsModal').classList.contains('hidden')) {
                    closeSettings();
                }
            }
            
            // N for new item
            if (e.key === 'n' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT') {
                window.location.href = 'edit_item.php';
            }
        });

        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success message
            const successMessage = document.querySelector('.bg-green-50');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-20px)';
                    setTimeout(() => successMessage.remove(), 300);
                }, 3000);
            }
            
            // Add haptic feedback to action buttons
            document.querySelectorAll('button').forEach(button => {
                if (button.onclick && button.onclick.toString().includes('toggle')) {
                    button.addEventListener('click', () => hapticFeedback('medium'));
                }
            });
        });

        console.log('Fenyal Admin Menu Items - Enhanced with Full Settings Support ✨');
    </script>
</body>
</html>