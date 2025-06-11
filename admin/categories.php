<?php
// admin/categories.php - Modern Mobile-First Categories Management
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$message = '';
$messageType = 'info';

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/categories/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle file upload
function handleImageUpload($file) {
    global $uploadsDir;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }
    
    // Check if file is an image
    $allowedTypes = ['image/jpeg','image/jpg', 'image/png', 'image/gif', 'image/webp'];
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
    $filename = 'category_' . uniqid() . '.' . $extension;
    $targetPath = $uploadsDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    return 'uploads/categories/' . $filename;
}

// Handle actions
if ($_POST) {

    
    switch ($_POST['action']) {
        case 'add':
            $name = trim($_POST['name']);
            $nameAr = trim($_POST['name_ar']) ?: null;
            $displayOrder = (int)$_POST['display_order'];
            $image = null;
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $image = handleImageUpload($_FILES['image']);
                } catch (Exception $e) {
                    $message = 'Image upload error: ' . $e->getMessage();
                    $messageType = 'error';
                    break;
                }
            }
            
            if ($name) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, name_ar, display_order, image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $nameAr, $displayOrder, $image]);
                    $message = 'Category added successfully! 🎉';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'Category already exists!';
                        $messageType = 'error';
                    } else {
                        $message = 'Error adding category: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
            }
            break;
            
        case 'edit':
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $nameAr = trim($_POST['name_ar']) ?: null;
            $displayOrder = (int)$_POST['display_order'];
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            // Get current category data
            $currentStmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $currentStmt->execute([$id]);
            $currentCategory = $currentStmt->fetch();
            
            $image = $currentCategory['image']; // Keep existing image by default
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $newImage = handleImageUpload($_FILES['image']);
                    
                    // Delete old image if it exists
                    if ($currentCategory['image'] && file_exists('../' . $currentCategory['image'])) {
                        unlink('../' . $currentCategory['image']);
                    }
                    
                    $image = $newImage;
                } catch (Exception $e) {
                    $message = 'Image upload error: ' . $e->getMessage();
                    $messageType = 'error';
                    break;
                }
            }
            
            if ($name) {
                try {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, name_ar = ?, display_order = ?, is_active = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $nameAr, $displayOrder, $isActive, $image, $id]);
                    $message = 'Category updated successfully! ✨';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'Category name already exists!';
                        $messageType = 'error';
                    } else {
                        $message = 'Error updating category: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            
            // Check if category is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category = (SELECT name FROM categories WHERE id = ?)");
            $stmt->execute([$id]);
            $itemCount = $stmt->fetchColumn();
            
            if ($itemCount > 0) {
                $message = "Cannot delete category: $itemCount items are using this category.";
                $messageType = 'warning';
            } else {
                // Get category data to delete image
                $categoryStmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
                $categoryStmt->execute([$id]);
                $category = $categoryStmt->fetch();
                
                // Delete the category
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                if ($stmt->execute([$id])) {
                    // Delete associated image file
                    if ($category['image'] && file_exists('../' . $category['image'])) {
                        unlink('../' . $category['image']);
                    }
                    $message = 'Category deleted successfully! 🗑️';
                    $messageType = 'success';
                }
            }
            break;
            
        case 'toggle_active':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Category status updated! 🔄';
                $messageType = 'success';
            }
            break;
    }
}

// Get categories with item counts
$categories = $pdo->query("
    SELECT c.*, 
           COUNT(m.id) as item_count
    FROM categories c
    LEFT JOIN menu_items m ON c.name = m.category
    GROUP BY c.id
    ORDER BY c.display_order, c.name
")->fetchAll();

// Get editing category if specified
$editingCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editingCategory = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title>Categories - Fenyal Admin</title>
    
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
                        surface: '#ffffff',
                        'surface-variant': '#f1f3f4',
                        'on-surface': '#1f2937',
                        'on-surface-variant': '#6b7280'
                    },
                    borderRadius: {
                        'xl': '1rem',
                        '2xl': '1.5rem',
                        '3xl': '2rem'
                    },
                    fontFamily: {
                        sans: ['Inter', 'Poppins', 'system-ui', 'sans-serif']
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-in': 'bounceIn 0.5s ease-out',
                        'scale-in': 'scaleIn 0.2s ease-out'
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    
    <style>
        /* Custom styles for mobile app experience */
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
            overscroll-behavior: none;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        @keyframes bounceIn {
            0% { 
                opacity: 0; 
                transform: scale(0.3); 
            }
            50% { 
                opacity: 1; 
                transform: scale(1.05); 
            }
            70% { 
                transform: scale(0.9); 
            }
            100% { 
                opacity: 1; 
                transform: scale(1); 
            }
        }
        
        @keyframes scaleIn {
            from { 
                opacity: 0; 
                transform: scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: scale(1); 
            }
        }
        
        /* Touch feedback */
        .touch-feedback:active {
            transform: scale(0.97);
            transition: transform 0.1s ease;
        }
        
        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(196, 82, 48, 0.3);
            border-radius: 2px;
        }
        
        /* Image preview styles */
        .image-preview {
            max-width: 80px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* File input custom styling */
        .file-input {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-input input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Card hover effects */
        .category-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        /* Form styling */
        .form-input {
            transition: all 0.2s ease;
            border: 2px solid #e5e7eb;
        }
        
        .form-input:focus {
            border-color: #c45230;
            box-shadow: 0 0 0 3px rgba(196, 82, 48, 0.1);
        }
        
        /* Floating action button */
        .fab {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c45230 0%, #f96d43 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(196, 82, 48, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(196, 82, 48, 0.4);
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .container-mobile {
                padding: 0 16px;
            }
            
            .card-mobile {
                margin: 0 -4px;
                border-radius: 16px;
            }
        }
        
        /* Empty state illustration */
        .empty-state {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
    </style>
</head>

<body class="bg-background min-h-screen">
    <!-- Mobile App Header -->
    <header class="glass sticky top-0 z-50 border-b border-gray-200/50">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center shadow-lg">
                    <i data-feather="grid" class="h-5 w-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-on-surface">Categories</h1>
                    <p class="text-xs text-on-surface-variant">Manage your menu categories</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <a href="index.php" class="p-2 rounded-lg bg-surface-variant/50 text-on-surface-variant touch-feedback">
                    <i data-feather="home" class="h-5 w-5"></i>
                </a>
                <a href="menu_items.php" class="p-2 rounded-lg bg-surface-variant/50 text-on-surface-variant touch-feedback">
                    <i data-feather="menu" class="h-5 w-5"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Toast Notification -->
    <?php if ($message): ?>
    <div id="toast" class="fixed top-20 left-4 right-4 z-50 animate-slide-up">
        <div class="glass rounded-xl p-4 border-l-4 <?php echo $messageType === 'success' ? 'border-green-500 bg-green-50/90' : ($messageType === 'error' ? 'border-red-500 bg-red-50/90' : 'border-yellow-500 bg-yellow-50/90'); ?>">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <?php if ($messageType === 'success'): ?>
                    <i data-feather="check-circle" class="h-5 w-5 text-green-600"></i>
                    <?php elseif ($messageType === 'error'): ?>
                    <i data-feather="x-circle" class="h-5 w-5 text-red-600"></i>
                    <?php else: ?>
                    <i data-feather="alert-circle" class="h-5 w-5 text-yellow-600"></i>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : ($messageType === 'error' ? 'text-red-800' : 'text-yellow-800'); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
                <button onclick="hideToast()" class="ml-auto pl-3">
                    <i data-feather="x" class="h-4 w-4 <?php echo $messageType === 'success' ? 'text-green-600' : ($messageType === 'error' ? 'text-red-600' : 'text-yellow-600'); ?>"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container-mobile pb-24">
        <!-- Categories Grid -->
        <div class="py-6">
            <?php if (empty($categories)): ?>
            <!-- Empty State -->
            <div class="text-center py-16 px-4">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full empty-state flex items-center justify-center">
                    <i data-feather="folder-plus" class="h-12 w-12 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-on-surface mb-2">No Categories Yet</h3>
                <p class="text-on-surface-variant mb-6 max-w-sm mx-auto">Create your first menu category to get started organizing your items</p>
                <button onclick="showAddForm()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-accent text-white rounded-xl font-medium touch-feedback">
                    <i data-feather="plus" class="h-5 w-5 mr-2"></i>
                    Add First Category
                </button>
            </div>
            <?php else: ?>
            <!-- Categories List -->
            <div class="space-y-4">
                <?php foreach ($categories as $category): ?>
                <div class="category-card bg-surface rounded-2xl p-4 shadow-sm border border-gray-100 animate-fade-in">
                    <div class="flex items-center space-x-4">
                        <!-- Category Image -->
                        <div class="flex-shrink-0">
                            <?php if ($category['image']): ?>
                            <img src="../<?php echo htmlspecialchars($category['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                 class="w-16 h-16 object-cover rounded-xl shadow-md">
                            <?php else: ?>
                            <div class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center">
                                <i data-feather="image" class="h-6 w-6 text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Category Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-on-surface truncate">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h3>
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xs font-bold">
                                        <?php echo $category['display_order']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($category['name_ar']): ?>
                            <p class="text-sm text-on-surface-variant mb-2" dir="rtl">
                                <?php echo htmlspecialchars($category['name_ar']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="status-badge <?php echo $category['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <span class="text-xs text-on-surface-variant">
                                        <?php echo $category['item_count']; ?> items
                                    </span>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-1">
                                    <button onclick="editCategory(<?php echo $category['id']; ?>)" 
                                            class="p-2 rounded-lg bg-blue-50 text-blue-600 touch-feedback">
                                        <i data-feather="edit-2" class="h-4 w-4"></i>
                                    </button>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Toggle status?')">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="p-2 rounded-lg bg-yellow-50 text-yellow-600 touch-feedback">
                                            <i data-feather="<?php echo $category['is_active'] ? 'eye-off' : 'eye'; ?>" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    
                                    <?php if ($category['item_count'] == 0): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="p-2 rounded-lg bg-red-50 text-red-600 touch-feedback">
                                            <i data-feather="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Action Button -->
    <button onclick="showAddForm()" class="fab">
        <i data-feather="plus" class="h-6 w-6"></i>
    </button>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="hideModal()"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-surface rounded-t-3xl p-6 animate-slide-up max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 id="modalTitle" class="text-xl font-bold text-on-surface">Add Category</h2>
                <button onclick="hideModal()" class="p-2 rounded-lg bg-surface-variant/50 text-on-surface-variant">
                    <i data-feather="x" class="h-5 w-5"></i>
                </button>
            </div>
            
            <form id="categoryForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" id="formAction" name="action" value="add">
                <input type="hidden" id="categoryId" name="id" value="">
                
                <!-- Category Name (English) -->
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-2">
                        Category Name (English) *
                    </label>
                    <input type="text" id="categoryName" name="name" required
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 form-input text-on-surface placeholder-on-surface-variant"
                           placeholder="e.g., Main Dishes">
                </div>
                
                <!-- Category Name (Arabic) -->
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-2">
                        Category Name (Arabic)
                    </label>
                    <input type="text" id="categoryNameAr" name="name_ar" dir="rtl"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 form-input text-on-surface placeholder-on-surface-variant"
                           placeholder="أطباق رئيسية">
                </div>
                
                <!-- Display Order -->
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-2">
                        Display Order
                    </label>
                    <input type="number" id="displayOrder" name="display_order" min="0" value="0"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 form-input text-on-surface">
                    <p class="text-xs text-on-surface-variant mt-1">Lower numbers appear first</p>
                </div>
                
                <!-- Category Image -->
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-2">
                        Category Image
                    </label>
                    <div class="file-input w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i data-feather="upload-cloud" class="h-8 w-8 text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500 font-medium">Click to upload image</p>
                                <p class="text-xs text-gray-400">PNG, JPG, GIF, WebP (max 5MB)</p>
                            </div>
                            <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                        </label>
                    </div>
                    <div id="imagePreview" class="mt-3 hidden">
                        <img id="previewImg" class="image-preview">
                        <p class="text-xs text-on-surface-variant mt-1">Image preview</p>
                    </div>
                </div>
                
                <!-- Active Status (only for edit) -->
                <div id="activeStatusContainer" class="hidden">
                    <label class="flex items-center">
                        <input type="checkbox" id="isActive" name="is_active" value="1" 
                               class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary focus:ring-2">
                        <span class="ml-3 text-sm font-medium text-on-surface">Active Category</span>
                    </label>
                    <p class="text-xs text-on-surface-variant mt-1">Inactive categories won't appear in the frontend</p>
                </div>
                
                <!-- Form Buttons -->
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="hideModal()" 
                            class="flex-1 px-6 py-3 bg-surface-variant text-on-surface-variant rounded-xl font-medium touch-feedback">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-accent text-white rounded-xl font-medium touch-feedback">
                        <span id="submitText">Add Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Initialize feather icons
        feather.replace();
        
        // Auto-hide toast after 5 seconds
        setTimeout(() => {
            hideToast();
        }, 5000);
        
        function hideToast() {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }
        
        function showAddForm() {
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('formAction').value = 'add';
            document.getElementById('categoryId').value = '';
            document.getElementById('submitText').textContent = 'Add Category';
            document.getElementById('activeStatusContainer').classList.add('hidden');
            
            // Reset form
            document.getElementById('categoryForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');
            
            showModal();
        }
        
        function editCategory(id) {
            // Find category data
            const categories = <?php echo json_encode($categories); ?>;
            const category = categories.find(c => c.id == id);
            
            if (category) {
                document.getElementById('modalTitle').textContent = 'Edit Category';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('categoryId').value = category.id;
                document.getElementById('submitText').textContent = 'Update Category';
                document.getElementById('activeStatusContainer').classList.remove('hidden');
                
                // Fill form data
                document.getElementById('categoryName').value = category.name;
                document.getElementById('categoryNameAr').value = category.name_ar || '';
                document.getElementById('displayOrder').value = category.display_order;
                document.getElementById('isActive').checked = category.is_active == 1;
                
                // Show existing image if available
                if (category.image) {
                    document.getElementById('previewImg').src = '../' + category.image;
                    document.getElementById('imagePreview').classList.remove('hidden');
                } else {
                    document.getElementById('imagePreview').classList.add('hidden');
                }
                
                showModal();
            }
        }
        
        function showModal() {
            document.getElementById('categoryModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function hideModal() {
            document.getElementById('categoryModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Touch feedback for all interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const interactiveElements = document.querySelectorAll('.touch-feedback');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.97)';
                    this.style.transition = 'transform 0.1s ease';
                }, { passive: true });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                }, { passive: true });
                
                element.addEventListener('touchcancel', function() {
                    this.style.transform = 'scale(1)';
                }, { passive: true });
            });
        });
        
        // Handle form submission with loading state
        document.getElementById('categoryForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            const submitText = document.getElementById('submitText');
            const originalText = submitText.textContent;
            
            submitBtn.disabled = true;
            submitText.textContent = 'Processing...';
            
            // Re-enable after 3 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitText.textContent = originalText;
            }, 3000);
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideModal();
            }
        });
        
        // Prevent zoom on input focus (iOS)
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // Set viewport height for mobile browsers
        function setViewportHeight() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Add loading skeleton for image uploads
        function showImageLoading() {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = `
                <div class="w-20 h-20 bg-gray-200 rounded-xl animate-pulse"></div>
                <p class="text-xs text-gray-400 mt-1">Uploading...</p>
            `;
            preview.classList.remove('hidden');
        }
        
        // Enhanced file input handling
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show loading state
                showImageLoading();
                
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    document.getElementById('imagePreview').classList.add('hidden');
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only image files (JPEG, PNG, GIF, WebP) are allowed');
                    this.value = '';
                    document.getElementById('imagePreview').classList.add('hidden');
                    return;
                }
                
                // Preview image
                previewImage(this);
            }
        });
        
        // Add haptic feedback simulation
        function vibrate(duration = 10) {
            if (navigator.vibrate) {
                navigator.vibrate(duration);
            }
        }
        
        // Add vibration to touch feedback
        document.querySelectorAll('.touch-feedback').forEach(element => {
            element.addEventListener('touchstart', () => vibrate(5), { passive: true });
        });
        
        // Auto-refresh categories every 30 seconds (optional)
        setInterval(() => {
            // You can add auto-refresh logic here if needed
            console.log('Auto-refresh check...');
        }, 30000);
        
        // Progressive Web App install prompt
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });
        
        // Analytics tracking (placeholder)
        function trackEvent(eventName, properties = {}) {
            console.log('Event tracked:', eventName, properties);
            // Implement your analytics tracking here
        }
        
        // Track user interactions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.category-card')) {
                trackEvent('category_card_clicked');
            }
            if (e.target.closest('.fab')) {
                trackEvent('add_category_fab_clicked');
            }
        });
    </script>
</body>
</html>