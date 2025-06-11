<?php
// admin/index.php - Mobile-First Admin Dashboard with File Upload Support
require_once 'config.php';
checkAuth();

$pdo = getConnection();

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/menu/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Get statistics
$totalItems = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$popularItems = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_popular = 1")->fetchColumn();
$specialItems = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_special = 1")->fetchColumn();
$categories = $pdo->query("SELECT COUNT(DISTINCT category) FROM menu_items")->fetchColumn();

// Get recent items with better image handling
$recentItems = $pdo->query("
    SELECT name, name_ar, category, price, created_at, image, is_popular, is_special
    FROM menu_items 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// Handle file upload for images
function handleImageUpload($file) {
    global $uploadsDir;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }
    
    // Check if file is an image
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Only image files (JPEG, PNG, GIF, WebP) are allowed');
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size must be less than 5MB');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'item_' . uniqid() . '.' . $extension;
    $targetPath = $uploadsDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    return 'uploads/menu/' . $filename;
}

// Handle quick actions
$message = '';
$messageType = 'success';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'import_json':
                try {
                    if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
                        $tempFile = $_FILES['json_file']['tmp_name'];
                        importJSONData($tempFile);
                        $message = 'JSON data imported successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Please select a valid JSON file.';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Import failed: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'export_json':
                try {
                    $jsonData = exportToJSON();
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="menu_export_' . date('Y-m-d_H-i-s') . '.json"');
                    echo $jsonData;
                    exit;
                } catch (Exception $e) {
                    $message = 'Export failed: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'quick_add_item':
                try {
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $price = floatval($_POST['price']);
                    $category = trim($_POST['category']);
                    
                    if (empty($name) || empty($description) || $price <= 0 || empty($category)) {
                        throw new Exception('Please fill in all required fields');
                    }
                    
                    $image = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $image = handleImageUpload($_FILES['image']);
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO menu_items (name, description, price, category, image) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $price, $category, $image]);
                    
                    $message = 'Item added successfully!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Failed to add item: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Helper function to get proper image URL
function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return '../uploads/menu/placeholder.jpg';
    }
    
    // If it's already a full path starting with uploads/, use as is
    if (strpos($imagePath, 'uploads/') === 0) {
        return '../' . $imagePath;
    }
    
    // If it's an old external URL, use placeholder
    if (strpos($imagePath, 'http') === 0) {
        return '../uploads/menu/placeholder.jpg';
    }
    
    // Otherwise, assume it's a filename in uploads/menu/
    return '../uploads/menu/' . basename($imagePath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title>Fenyal Admin Dashboard</title>
    
    <!-- PWA Icons -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fenyal Admin">
    
    <!-- Tailwind CSS -->
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
                    },
                    borderRadius: {
                        xl: '1.2rem'
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

    <style>
        body {
            -webkit-tap-highlight-color: transparent;
            line-height: 1.3;
            font-family: 'Poppins', sans-serif;
            touch-action: manipulation;
            overscroll-behavior: none;
        }

        .app-container {
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100);
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
            padding-bottom: 100px;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .scale-button {
            transition: transform 0.2s ease;
        }

        .scale-button:active {
            transform: scale(0.97);
        }

        .card-hover {
            transition: all 0.2s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.95) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .action-button {
            background: linear-gradient(135deg, var(--tw-gradient-stops));
            transition: all 0.3s ease;
        }

        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .mobile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .mobile-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .nav-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .recent-items {
            max-height: 400px;
            overflow-y: auto;
        }

        .item-row {
            transition: background-color 0.2s ease;
        }

        .item-row:hover {
            background-color: rgba(196, 82, 48, 0.05);
        }

        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .floating-button {
            position: fixed;
            bottom: 90px;
            right: 20px;
            z-index: 50;
            background: linear-gradient(135deg, #c45230 0%, #f96d43 100%);
            box-shadow: 0 4px 15px rgba(196, 82, 48, 0.3);
        }

        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }

        .file-upload-area.dragover {
            border-color: #c45230;
            background-color: rgba(196, 82, 48, 0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body class="bg-background font-sans text-dark">
    <!-- Main App Container -->
    <div class="app-container fade-in">
        <!-- Header -->
        <header class="nav-header sticky top-0 z-30 px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-sm">F</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">Admin Panel</h1>
                        <p class="text-xs text-gray-500">Welcome, <?php echo $_SESSION['admin_username']; ?></p>
                    </div>
                </div>
                
                <!-- Logout Button -->
                <a href="logout.php" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center scale-button">
                    <i data-feather="log-out" class="h-5 w-5 text-gray-600"></i>
                </a>
            </div>
        </header>

        <!-- Success/Error Message -->
        <?php if ($message): ?>
        <div class="mx-4 mb-4 p-4 <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?> border rounded-xl">
            <div class="flex items-center space-x-2">
                <i data-feather="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" 
                   class="h-5 w-5 <?php echo $messageType === 'success' ? 'text-green-600' : 'text-red-600'; ?>"></i>
                <span class="text-sm <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="px-4 pt-6 pb-8">
            <!-- Quick Actions Grid -->
            <section class="mb-8">
                <h2 class="text-base font-semibold mb-4 text-gray-900">Quick Actions</h2>
                <div class="mobile-grid">
                    <a href="menu_items.php" 
                       class="action-button from-primary to-accent text-white p-4 rounded-xl scale-button flex flex-col items-center space-y-2">
                        <i data-feather="menu" class="h-6 w-6"></i>
                        <span class="text-sm font-medium">Menu Items</span>
                    </a>
                    
                    <a href="categories.php" 
                       class="action-button from-blue-500 to-blue-600 text-white p-4 rounded-xl scale-button flex flex-col items-center space-y-2">
                        <i data-feather="grid" class="h-6 w-6"></i>
                        <span class="text-sm font-medium">Categories</span>
                    </a>
                    
                    <button onclick="showQuickAddModal()" 
                            class="action-button from-green-500 to-green-600 text-white p-4 rounded-xl scale-button flex flex-col items-center space-y-2">
                        <i data-feather="plus-circle" class="h-6 w-6"></i>
                        <span class="text-sm font-medium">Quick Add</span>
                    </button>
                    
                    <button onclick="showImportModal()" 
                            class="action-button from-purple-500 to-purple-600 text-white p-4 rounded-xl scale-button flex flex-col items-center space-y-2">
                        <i data-feather="upload" class="h-6 w-6"></i>
                        <span class="text-sm font-medium">Import JSON</span>
                    </button>
                </div>
                
                <!-- Export Button (Full Width) -->
                <form method="POST" class="mt-4">
                    <input type="hidden" name="action" value="export_json">
                    <button type="submit" 
                            class="action-button from-orange-500 to-orange-600 text-white p-4 rounded-xl scale-button flex items-center justify-center space-x-2 w-full">
                        <i data-feather="download" class="h-5 w-5"></i>
                        <span class="text-sm font-medium">Export Menu Data (JSON)</span>
                    </button>
                </form>
            </section>

            <!-- Statistics Cards -->
            <section class="mb-8">
                <h2 class="text-base font-semibold mb-4 text-gray-900">Statistics</h2>
                <div class="mobile-grid">
                    <div class="stat-card p-4 rounded-xl card-hover">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                <i data-feather="file-text" class="h-5 w-5 text-primary"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $totalItems; ?></p>
                                <p class="text-xs text-gray-500">Total Items</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card p-4 rounded-xl card-hover">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i data-feather="star" class="h-5 w-5 text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $popularItems; ?></p>
                                <p class="text-xs text-gray-500">Popular</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card p-4 rounded-xl card-hover">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i data-feather="zap" class="h-5 w-5 text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $specialItems; ?></p>
                                <p class="text-xs text-gray-500">Special</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card p-4 rounded-xl card-hover">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i data-feather="folder" class="h-5 w-5 text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $categories; ?></p>
                                <p class="text-xs text-gray-500">Categories</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Items -->
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Recent Items</h2>
                    <a href="menu_items.php" class="text-primary text-sm font-medium">
                        View All →
                    </a>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <?php if (empty($recentItems)): ?>
                    <div class="p-8 text-center">
                        <i data-feather="inbox" class="h-12 w-12 text-gray-300 mx-auto mb-3"></i>
                        <p class="text-gray-500 text-sm mb-2">No items yet</p>
                        <button onclick="showQuickAddModal()" class="text-primary text-sm font-medium">Add your first item</button>
                    </div>
                    <?php else: ?>
                    <div class="recent-items">
                        <?php foreach ($recentItems as $item): ?>
                        <div class="item-row p-4 border-b border-gray-50 flex items-center space-x-3 last:border-b-0">
                            <div class="w-12 h-12 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                <img src="<?php echo htmlspecialchars(getImageUrl($item['image'])); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="w-full h-full object-cover"
                                     onerror="this.src='../uploads/menu/placeholder.jpg'">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-medium text-sm text-gray-900 truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <?php if (!empty($item['name_ar'])): ?>
                                <p class="text-xs text-gray-400 truncate" dir="rtl"><?php echo htmlspecialchars($item['name_ar']); ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['category']); ?> • QAR <?php echo number_format($item['price'], 0); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                            </div>
                            <div class="flex flex-col items-end space-y-1">
                                <?php if ($item['is_popular']): ?>
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Popular</span>
                                <?php endif; ?>
                                <?php if ($item['is_special']): ?>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Special</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <!-- Floating Action Button -->
        <a href="edit_item.php" class="floating-button w-14 h-14 rounded-full flex items-center justify-center scale-button">
            <i data-feather="plus" class="h-6 w-6 text-white"></i>
        </a>
    </div>

    <!-- Quick Add Modal -->
    <div id="quickAddModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay fixed inset-0"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-full overflow-y-auto">
                <form method="POST" enctype="multipart/form-data">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Add Item</h3>
                            <button type="button" onclick="hideQuickAddModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                <i data-feather="x" class="h-4 w-4 text-gray-600"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                                <input type="text" name="name" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                       placeholder="Enter item name">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                                <textarea name="description" required rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                          placeholder="Enter item description"></textarea>
                            </div>
                            
                            <div class="form-grid">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Price (QAR) *</label>
                                    <input type="number" name="price" step="0.01" min="0" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                           placeholder="0.00">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                    <select name="category" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="">Select category</option>
                                        <option value="Breakfast">Breakfast</option>
                                        <option value="Dishes">Dishes</option>
                                        <option value="Bread">Bread</option>
                                        <option value="Desserts">Desserts</option>
                                        <option value="Cold Drinks">Cold Drinks</option>
                                        <option value="Hot Drinks">Hot Drinks</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Item Image</label>
                                <div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                    <i data-feather="image" class="h-8 w-8 text-gray-400 mx-auto mb-2"></i>
                                    <p class="text-sm text-gray-600 mb-2">Click to upload image</p>
                                    <input type="file" name="image" accept="image/*"
                                           class="hidden" id="quickAddFileInput" onchange="handleQuickAddFileSelect(this)">
                                    <button type="button" onclick="document.getElementById('quickAddFileInput').click()"
                                            class="text-primary text-sm font-medium">Browse Files</button>
                                    <p class="text-xs text-gray-500 mt-1">JPEG, PNG, GIF, WebP (max 5MB)</p>
                                </div>
                                
                                <div id="quickAddSelectedFile" class="hidden mt-3">
                                    <img id="quickAddPreview" class="image-preview mx-auto" alt="Preview">
                                    <p id="quickAddFileName" class="text-sm text-gray-600 text-center mt-2"></p>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="action" value="quick_add_item">
                    </div>
                    
                    <div class="px-6 pb-6 flex space-x-3">
                        <button type="button" onclick="hideQuickAddModal()" 
                                class="flex-1 py-3 px-4 bg-gray-100 text-gray-700 rounded-xl font-medium scale-button">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 py-3 px-4 bg-primary text-white rounded-xl font-medium scale-button">
                            Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay fixed inset-0"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
                <form method="POST" enctype="multipart/form-data">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Import Menu Data</h3>
                            <button type="button" onclick="hideImportModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                <i data-feather="x" class="h-4 w-4 text-gray-600"></i>
                            </button>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-4">
                            Select a JSON file to import menu items. This will replace all existing menu data.
                        </p>
                        
                        <div class="file-upload-area border-2 border-dashed border-gray-300 rounded-xl p-6 text-center">
                            <i data-feather="upload-cloud" class="h-8 w-8 text-gray-400 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-600 mb-2">Click to select file</p>
                            <input type="file" name="json_file" accept=".json" required
                                   class="hidden" id="fileInput" onchange="handleFileSelect(this)">
                            <button type="button" onclick="document.getElementById('fileInput').click()"
                                    class="text-primary text-sm font-medium">Browse Files</button>
                        </div>
                        
                        <div id="selectedFile" class="hidden mt-3 p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <i data-feather="file" class="h-4 w-4 text-green-600"></i>
                                <span id="fileName" class="text-sm text-green-800"></span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="action" value="import_json">
                    </div>
                    
                    <div class="px-6 pb-6 flex space-x-3">
                        <button type="button" onclick="hideImportModal()" 
                                class="flex-1 py-3 px-4 bg-gray-100 text-gray-700 rounded-xl font-medium scale-button">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 py-3 px-4 bg-primary text-white rounded-xl font-medium scale-button">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

        // Quick Add Modal functions
        function showQuickAddModal() {
            document.getElementById('quickAddModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideQuickAddModal() {
            document.getElementById('quickAddModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Reset form
            document.querySelector('#quickAddModal form').reset();
            document.getElementById('quickAddSelectedFile').classList.add('hidden');
        }

        // Import Modal functions
        function showImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideImportModal() {
            document.getElementById('importModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Reset form
            document.querySelector('#importModal form').reset();
            document.getElementById('selectedFile').classList.add('hidden');
        }

        // Quick Add file handling
        function handleQuickAddFileSelect(input) {
            const file = input.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, WebP)');
                    input.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('quickAddPreview').src = e.target.result;
                    document.getElementById('quickAddFileName').textContent = file.name;
                    document.getElementById('quickAddSelectedFile').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
                
                // Update upload area appearance
                const uploadArea = input.closest('.file-upload-area');
                uploadArea.classList.add('border-primary', 'bg-primary/5');
                uploadArea.classList.remove('border-gray-300');
            }
        }

        // Import file handling
        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('selectedFile').classList.remove('hidden');
                feather.replace();
            }
        }

        // Drag and drop for file uploads
        function setupDragAndDrop() {
            const uploadAreas = document.querySelectorAll('.file-upload-area');
            
            uploadAreas.forEach(area => {
                area.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });
                
                area.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                });
                
                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const fileInput = this.querySelector('input[type="file"]');
                        fileInput.files = files;
                        
                        // Trigger change event
                        const event = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(event);
                    }
                });
            });
        }

        // Touch feedback for all interactive elements
        function setupTouchFeedback() {
            const interactiveElements = document.querySelectorAll('.scale-button');
            
            interactiveElements.forEach(element => {
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
        }

        // Form validation
        function setupFormValidation() {
            const quickAddForm = document.querySelector('#quickAddModal form');
            
            quickAddForm.addEventListener('submit', function(e) {
                const name = this.querySelector('input[name="name"]').value.trim();
                const description = this.querySelector('textarea[name="description"]').value.trim();
                const price = parseFloat(this.querySelector('input[name="price"]').value);
                const category = this.querySelector('select[name="category"]').value;
                
                if (!name || !description || !price || price <= 0 || !category) {
                    e.preventDefault();
                    alert('Please fill in all required fields with valid values');
                    return;
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = 'Adding Item...';
                submitBtn.disabled = true;
            });
        }

        // Close modals on backdrop click
        function setupModalBackdropClose() {
            document.getElementById('quickAddModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideQuickAddModal();
                }
            });
            
            document.getElementById('importModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideImportModal();
                }
            });
        }

        // Escape key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideQuickAddModal();
                hideImportModal();
            }
        });

        // Initialize all functionality
        document.addEventListener('DOMContentLoaded', function() {
            setupTouchFeedback();
            setupDragAndDrop();
            setupFormValidation();
            setupModalBackdropClose();
            
            // Auto-refresh stats every 30 seconds
            setInterval(function() {
                // Reload stats without refreshing page
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Update statistics
                        const newStats = doc.querySelectorAll('.stat-card p:first-child');
                        const currentStats = document.querySelectorAll('.stat-card p:first-child');
                        
                        newStats.forEach((stat, index) => {
                            if (currentStats[index] && stat.textContent !== currentStats[index].textContent) {
                                currentStats[index].textContent = stat.textContent;
                                currentStats[index].closest('.stat-card').style.animation = 'pulse 0.5s ease';
                            }
                        });
                    })
                    .catch(error => console.log('Stats refresh failed:', error));
            }, 30000);
        });

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const message = document.querySelector('.mx-4.mb-4.p-4');
            if (message) {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-10px)';
                message.style.transition = 'all 0.3s ease';
                setTimeout(() => message.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>