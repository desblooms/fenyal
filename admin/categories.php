<?php
// admin/categories.php - Enhanced Categories management with image upload
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$message = '';

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
                    break;
                }
            }
            
            if ($name) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, name_ar, display_order, image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $nameAr, $displayOrder, $image]);
                    $message = 'Category added successfully!';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'Category already exists!';
                    } else {
                        $message = 'Error adding category: ' . $e->getMessage();
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
                    break;
                }
            }
            
            if ($name) {
                try {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, name_ar = ?, display_order = ?, is_active = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $nameAr, $displayOrder, $isActive, $image, $id]);
                    $message = 'Category updated successfully!';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'Category name already exists!';
                    } else {
                        $message = 'Error updating category: ' . $e->getMessage();
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
                    $message = 'Category deleted successfully!';
                }
            }
            break;
            
        case 'toggle_active':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Category status updated!';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Fenyal Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#c45230',
                        accent: '#f96d43',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                            <span class="text-white font-bold text-sm">F</span>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-xl font-semibold text-gray-900">Categories</h1>
                        </div>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="menu_items.php" class="text-gray-600 hover:text-gray-900">Menu Items</a>
                    <a href="logout.php" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium text-gray-700">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Category Management</h2>
            <p class="text-gray-600">Manage menu categories, their display order, and images</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add/Edit Category Form -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <?php echo $editingCategory ? 'Edit Category' : 'Add New Category'; ?>
                </h3>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="<?php echo $editingCategory ? 'edit' : 'add'; ?>">
                    <?php if ($editingCategory): ?>
                    <input type="hidden" name="id" value="<?php echo $editingCategory['id']; ?>">
                    <?php endif; ?>
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name (English) *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo $editingCategory ? htmlspecialchars($editingCategory['name']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="name_ar" class="block text-sm font-medium text-gray-700 mb-2">Name (Arabic)</label>
                        <input type="text" id="name_ar" name="name_ar" dir="rtl"
                               value="<?php echo $editingCategory ? htmlspecialchars($editingCategory['name_ar']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                        <input type="number" id="display_order" name="display_order" min="0"
                               value="<?php echo $editingCategory ? $editingCategory['display_order'] : '0'; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                    </div>
                    
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Category Image</label>
                        <?php if ($editingCategory && $editingCategory['image']): ?>
                        <div class="mb-2">
                            <img src="../<?php echo htmlspecialchars($editingCategory['image']); ?>" 
                                 alt="Current image" class="image-preview">
                            <p class="text-xs text-gray-500 mt-1">Current image</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Upload image (JPEG, PNG, GIF, WebP, max 5MB)</p>
                    </div>
                    
                    <?php if ($editingCategory): ?>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo $editingCategory['is_active'] ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-primary focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 rounded-md font-medium hover:bg-primary/90 transition-colors">
                            <?php echo $editingCategory ? 'Update' : 'Add'; ?> Category
                        </button>
                        
                        <?php if ($editingCategory): ?>
                        <a href="categories.php" class="flex-1 text-center bg-gray-300 text-gray-700 px-4 py-2 rounded-md font-medium hover:bg-gray-400 transition-colors">
                            Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Categories List -->
            <div class="lg:col-span-2 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Categories List</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No categories found. Add your first category using the form on the left.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50 <?php echo $editingCategory && $editingCategory['id'] == $category['id'] ? 'bg-blue-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($category['image']): ?>
                                    <img src="../<?php echo htmlspecialchars($category['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                         class="w-12 h-12 object-cover rounded-lg">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">No image</span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-600 font-medium">
                                        <?php echo $category['display_order']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </div>
                                    <?php if ($category['name_ar']): ?>
                                    <div class="text-sm text-gray-500" dir="rtl">
                                        <?php echo htmlspecialchars($category['name_ar']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $category['item_count']; ?> items
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-col space-y-2">
                                        <a href="?edit=<?php echo $category['id']; ?>" 
                                           class="text-primary hover:text-primary/80">Edit</a>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Toggle active status?')">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 text-left">
                                                <?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        
                                        <?php if ($category['item_count'] == 0): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category? This will also delete the associated image.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-left">Delete</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">Cannot delete (has items)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tips -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-blue-900 mb-2">Tips for Category Management</h4>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>• Categories are displayed in the order specified by the "Display Order" field</li>
                <li>• Arabic names are optional but recommended for bilingual support</li>
                <li>• You can upload images for each category (JPEG, PNG, GIF, WebP up to 5MB)</li>
                <li>• Images will be automatically resized and optimized for display</li>
                <li>• You cannot delete categories that have menu items assigned to them</li>
                <li>• Inactive categories won't appear in the frontend but existing items remain accessible</li>
                <li>• Consider organizing categories logically: Breakfast → Main Dishes → Desserts → Drinks</li>
            </ul>
        </div>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Remove existing preview if any
                    const existingPreview = document.getElementById('image-preview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Create new preview
                    const preview = document.createElement('img');
                    preview.id = 'image-preview';
                    preview.src = e.target.result;
                    preview.className = 'image-preview mt-2';
                    
                    // Insert after the file input
                    e.target.parentNode.insertBefore(preview, e.target.nextSibling);
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>