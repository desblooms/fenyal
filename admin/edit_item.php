<?php
// admin/edit_item.php - Complete Fixed Mobile-First Add/Edit menu item with proper image handling
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$id = $_GET['id'] ?? null;
$isEdit = $id !== null;
$message = '';


// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/menu/';
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        die('Failed to create upload directory. Please check permissions.');
    }
}

// Ensure directory is writable
if (!is_writable($uploadsDir)) {
    die('Upload directory is not writable. Please check permissions.');
}

// Enhanced image upload handler
function handleImageUpload($file) {


    // echo "dd";
    // exit();


    global $uploadsDir;
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds php.ini limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form limit)', 
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        $errorMsg = $errors[$file['error']] ?? 'Unknown upload error';
        throw new Exception($errorMsg . ' (Error code: ' . $file['error'] . ')');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed. Detected: ' . $mimeType);
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        $fileSizeMB = round($file['size'] / 1024 / 1024, 2);
        throw new Exception("File size too large. Maximum 5MB allowed. Your file: {$fileSizeMB}MB");
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'item_' . uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadsDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file to: ' . $targetPath);
    }
    
    // Verify file exists
    if (!file_exists($targetPath)) {
        throw new Exception('File upload verification failed');
    }
    
    // Return relative path for database storage
    return 'uploads/menu/' . $filename;
}

// Get categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name")->fetchAll();

// Initialize item data with proper defaults
$item = [
    'id' => '',
    'name' => '',
    'name_ar' => '',
    'description' => '',
    'description_ar' => '',
    'price' => '',
    'category' => '',
    'category_ar' => '',
    'image' => '',
    'is_popular' => false,
    'is_special' => false,
    'is_half_full' => false,
    'half_price' => '',
    'full_price' => ''
];

$addons = [];
$spiceLevels = [];

// Load existing item data if editing
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $existingItem = $stmt->fetch();
    
    if (!$existingItem) {
        header('Location: menu_items.php?error=' . urlencode('Item not found'));
        exit;
    }
    
    // Merge existing data with defaults
    $item = array_merge($item, $existingItem);
    
    // Get addons
    $stmt = $pdo->prepare("SELECT * FROM menu_addons WHERE menu_item_id = ? ORDER BY name");
    $stmt->execute([$id]);
    $addons = $stmt->fetchAll();
    
    // Get spice levels
    $stmt = $pdo->prepare("SELECT * FROM menu_spice_levels WHERE menu_item_id = ? ORDER BY name");
    $stmt->execute([$id]);
    $spiceLevels = $stmt->fetchAll();
}

// Handle form submission
if ($_POST) {




    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        $required = ['name', 'description', 'price', 'category'];
        $errors = [];
        
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
        
        // Start with existing image path
        $imagePath = $item['image'];
// print_r($imagePath);
// echo $isEdit;

// print_r($_FILES['image']);
// exit();
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $newImagePath = handleImageUpload($_FILES['image']);
                
                // Delete old image if updating and new image uploaded successfully
                if ($isEdit && !empty($item['image'])) {
                    $oldImagePath = '../' . $item['image'];
                    if (file_exists($oldImagePath)) {
                        if (!unlink($oldImagePath)) {
                            error_log("Warning: Could not delete old image: " . $oldImagePath);
                        }
                    }
                }
                
                $imagePath = $newImagePath;



                
            } catch (Exception $e) {
                throw new Exception('Image upload failed: ' . $e->getMessage());
            }
        }
        

// echo $imagePath;
// exit();

        // Prepare item data
        $itemData = [
            'name' => trim($_POST['name']),
            'name_ar' => !empty($_POST['name_ar']) ? trim($_POST['name_ar']) : null,
            'description' => trim($_POST['description']),
            'description_ar' => !empty($_POST['description_ar']) ? trim($_POST['description_ar']) : null,
            'price' => (float)$_POST['price'],
            'category' => trim($_POST['category']),
            'category_ar' => !empty($_POST['category_ar']) ? trim($_POST['category_ar']) : null,
            'image' => $imagePath,
            'is_popular' => isset($_POST['is_popular']) && $_POST['is_popular'] == '1' ? 1 : 0,
            'is_special' => isset($_POST['is_special']) && $_POST['is_special'] == '1' ? 1 : 0,
            'is_half_full' => isset($_POST['is_half_full']) && $_POST['is_half_full'] == '1' ? 1 : 0,
            'half_price' => !empty($_POST['half_price']) ? (float)$_POST['half_price'] : null,
            'full_price' => !empty($_POST['full_price']) ? (float)$_POST['full_price'] : null
        ];
        
        if ($isEdit) {
            // Update existing item
            $sql = "UPDATE menu_items SET 
                    name = ?, name_ar = ?, description = ?, description_ar = ?, 
                    price = ?, category = ?, category_ar = ?, image = ?, 
                    is_popular = ?, is_special = ?, is_half_full = ?, half_price = ?, full_price = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $itemData['name'], 
                $itemData['name_ar'], 
                $itemData['description'], 
                $itemData['description_ar'],
                $itemData['price'], 
                $itemData['category'], 
                $itemData['category_ar'], 
                $itemData['image'],
                $itemData['is_popular'], 
                $itemData['is_special'], 
                $itemData['is_half_full'], 
                $itemData['half_price'], 
                $itemData['full_price'], 
                $id
            ]);
            
            if (!$success) {
                throw new Exception('Failed to update menu item in database');
            }
            
            $itemId = $id;
            
            // Debug logging
            error_log("Updated item ID {$itemId} with image: " . $itemData['image']);
            
        } else {
            // Insert new item
            $sql = "INSERT INTO menu_items 
                    (name, name_ar, description, description_ar, price, category, category_ar, image, 
                     is_popular, is_special, is_half_full, half_price, full_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $itemData['name'], 
                $itemData['name_ar'], 
                $itemData['description'], 
                $itemData['description_ar'],
                $itemData['price'], 
                $itemData['category'], 
                $itemData['category_ar'], 
                $itemData['image'],
                $itemData['is_popular'], 
                $itemData['is_special'], 
                $itemData['is_half_full'], 
                $itemData['half_price'], 
                $itemData['full_price']
            ]);
            
            if (!$success) {
                throw new Exception('Failed to create menu item in database');
            }
            
            $itemId = $pdo->lastInsertId();
        }
        
        // Delete existing addons and spice levels
        $pdo->prepare("DELETE FROM menu_addons WHERE menu_item_id = ?")->execute([$itemId]);
        $pdo->prepare("DELETE FROM menu_spice_levels WHERE menu_item_id = ?")->execute([$itemId]);
        
        // Insert addons
        if (!empty($_POST['addon_names']) && is_array($_POST['addon_names'])) {
            foreach ($_POST['addon_names'] as $index => $addonName) {
                $addonName = trim($addonName);
                if ($addonName && isset($_POST['addon_prices'][$index]) && $_POST['addon_prices'][$index] !== '') {
                    $stmt = $pdo->prepare("INSERT INTO menu_addons (menu_item_id, name, name_ar, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $itemId,
                        $addonName,
                        !empty($_POST['addon_names_ar'][$index]) ? trim($_POST['addon_names_ar'][$index]) : null,
                        (float)$_POST['addon_prices'][$index]
                    ]);
                }
            }
        }
        
        // Insert spice levels
        if (!empty($_POST['spice_names']) && is_array($_POST['spice_names'])) {
            foreach ($_POST['spice_names'] as $index => $spiceName) {
                $spiceName = trim($spiceName);
                if ($spiceName) {
                    $stmt = $pdo->prepare("INSERT INTO menu_spice_levels (menu_item_id, name, name_ar) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $itemId,
                        $spiceName,
                        !empty($_POST['spice_names_ar'][$index]) ? trim($_POST['spice_names_ar'][$index]) : null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        // Redirect with success message
        $message = $isEdit ? 'Item updated successfully!' : 'Item created successfully!';
        header('Location: menu_items.php?success=' . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = 'Error: ' . $e->getMessage();
        error_log("Menu item save error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Item - Fenyal Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/themecolor.js"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif;
            -webkit-tap-highlight-color: transparent;
            overscroll-behavior: none;
            background-color: #f8f9fa;
        }
        
        .app-container {
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-group {
            transition: all 0.2s ease;
        }
        
        .input-group:focus-within {
            transform: translateY(-1px);
        }
        
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            background: #e5e7eb;
            border-radius: 12px;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .toggle-switch.active {
            background: #c45230;
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .toggle-switch.active::after {
            transform: translateX(20px);
        }
        
        .section-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .floating-action {
            position: fixed;
            bottom: 24px;
            right: 20px;
            left: 20px;
            z-index: 50;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #c45230 0%, #f96d43 100%);
            box-shadow: 0 4px 12px rgba(196, 82, 48, 0.3);
            transition: all 0.2s ease;
        }
        
        .btn-primary:active {
            transform: scale(0.98);
        }
        
        .addon-item, .spice-item {
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        
        .addon-item:hover, .spice-item:hover {
            border-color: #c45230;
            box-shadow: 0 2px 8px rgba(196, 82, 48, 0.1);
        }
        
        /* Enhanced image upload styles */
        .image-upload-container {
            position: relative;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-upload-container:hover {
            border-color: #c45230;
            background-color: #fef7f0;
        }
        
        .image-upload-container.has-image {
            border: 2px solid #e5e7eb;
            padding: 0;
            min-height: 200px;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .image-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .image-upload-container:hover .image-upload-overlay {
            opacity: 1;
        }
        
        .drag-over {
            border-color: #c45230 !important;
            background-color: #fef7f0 !important;
        }
        
        /* Input validation styles */
        .input-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }
        
        .input-success {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
        }
        
        /* Loading state */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff40;
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Enhanced upload prompt */
        .upload-prompt {
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            z-index: 100;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            transform: translateY(-100px);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateY(0);
        }
        
        .toast.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .toast.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="app-container">
        <!-- Mobile Header -->
        <header class="sticky top-0 z-40 bg-white border-b border-gray-100 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="menu_items.php" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                        <i data-feather="arrow-left" class="h-5 w-5 text-gray-600"></i>
                    </a>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">
                            <?php echo $isEdit ? 'Edit Item' : 'Add New Item'; ?>
                        </h1>
                        <?php if ($isEdit): ?>
                        <p class="text-xs text-gray-500">ID: <?php echo $item['id']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Save button in header for mobile -->
                <button type="button" onclick="saveItem()" 
                        class="bg-primary text-white px-4 py-2 rounded-lg font-medium text-sm">
                    Save
                </button>
            </div>
        </header>

        <!-- Success/Error Toast -->
        <?php if ($message): ?>
        <div id="toast" class="toast <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?> show fade-in">
            <div class="flex items-center">
                <i data-feather="<?php echo strpos($message, 'Error') !== false ? 'alert-circle' : 'check-circle'; ?>" class="h-5 w-5 mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Content -->
        <div class="px-4 pb-32">
            <form id="item-form" method="POST" enctype="multipart/form-data" class="space-y-4">
                
                <!-- Basic Info Section -->
                <div class="section-card">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <i data-feather="edit-3" class="h-4 w-4 mr-2 text-primary"></i>
                            Basic Information
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Item Name English -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Item Name (English) *</label>
                            <input type="text" name="name" required
                                   value="<?php echo htmlspecialchars($item['name']); ?>"
                                   placeholder="e.g., Butter Chicken"
                                   class="w-full px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                        </div>
                        
                        <!-- Item Name Arabic -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Item Name (Arabic)</label>
                            <input type="text" name="name_ar" dir="rtl"
                                   value="<?php echo htmlspecialchars($item['name_ar'] ?? ''); ?>"
                                   placeholder="اسم الصنف بالعربية"
                                   class="w-full px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                        </div>
                        
                        <!-- Description English -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description (English) *</label>
                            <textarea name="description" rows="3" required
                                      placeholder="Describe your dish in English..."
                                      class="w-full px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm resize-none"><?php echo htmlspecialchars($item['description']); ?></textarea>
                        </div>
                        
                        <!-- Description Arabic -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description (Arabic)</label>
                            <textarea name="description_ar" rows="3" dir="rtl"
                                      placeholder="وصف الطبق بالعربية..."
                                      class="w-full px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm resize-none"><?php echo htmlspecialchars($item['description_ar'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Image Upload Section -->
                <div class="section-card">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <i data-feather="image" class="h-4 w-4 mr-2 text-primary"></i>
                            Item Image
                        </h3>
                    </div>
                    <div class="p-4">

                        <div class="file-input w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i data-feather="upload-cloud" class="h-8 w-8 text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500 font-medium">Click to upload image</p>
                                <p class="text-xs text-gray-400">PNG, JPG, GIF, WebP (max 5MB)</p>
                            </div>
                            <!-- <input type="file" name="image" accept="image/*" onchange="previewImage(this)"> --><input type="file" 
                                   id="image-input" 
                                   name="image" 
                                   accept="image/*" 
                                   style="display: none;">
                        </label>
                    </div>
                        <!-- <div class="image-upload-container <?php echo !empty($item['image']) ? 'has-image' : ''; ?>" 
                             onclick="document.getElementById('image-input').click()"
                             ondrop="dropHandler(event)" 
                             ondragover="dragOverHandler(event)"
                             ondragleave="dragLeaveHandler(event)">
                            
                            <?php if (!empty($item['image'])): ?>
                            <div class="relative w-full">
                                <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="Current image" 
                                     class="image-preview"
                                     id="image-preview"
                                     onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-48 bg-gray-100 text-gray-500\'><i data-feather=\'image\' class=\'h-8 w-8\'></i><span class=\'ml-2\'>Image not found</span></div>'; feather.replace();">
                                <div class="image-upload-overlay">
                                    <div class="text-white text-center">
                                        <i data-feather="upload" class="h-6 w-6 mx-auto mb-2"></i>
                                        <p class="text-sm">Click or drag to change image</p>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="upload-prompt">
                                <i data-feather="upload-cloud" class="h-12 w-12 text-gray-400 mb-4"></i>
                                <h4 class="text-lg font-medium text-gray-700 mb-2">Upload Item Image</h4>
                                <p class="text-sm text-gray-500 mb-4">Drag and drop or click to select</p>
                                <p class="text-xs text-gray-400">Supports JPEG, PNG, GIF, WebP (max 5MB)</p>
                            </div>
                            <?php endif; ?>
                            
                            <input type="file" 
                                   id="image-input" 
                                   name="image" 
                                   accept="image/*" 
                                   style="display: none;"
                                   onchange="previewImage(this)">
                        </div> -->
                        
                        <!-- Image Upload Info -->
                        <div class="mt-3 text-xs text-gray-500">
                            <p>• Recommended size: 800x600 pixels</p>
                            <p>• Maximum file size: 5MB</p>
                            <p>• Supported formats: JPEG, PNG, GIF, WebP</p>
                            <?php if (!empty($item['image'])): ?>
                            <p>• Current image: <?php echo basename($item['image']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Category & Price Section -->
                <div class="section-card">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <i data-feather="tag" class="h-4 w-4 mr-2 text-primary"></i>
                            Category & Pricing
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Category -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select name="category" required onchange="updateCategoryAr()"
                                    class="w-full px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                        data-ar="<?php echo htmlspecialchars($cat['name_ar'] ?? ''); ?>"
                                        <?php echo $item['category'] === $cat['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                    <?php if (!empty($cat['name_ar'])): ?>
                                        (<?php echo htmlspecialchars($cat['name_ar']); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Category Arabic (Hidden) -->
                        <input type="hidden" name="category_ar" id="category_ar" value="<?php echo htmlspecialchars($item['category_ar'] ?? ''); ?>">
                        
                        <!-- Base Price -->
                        <div class="input-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Base Price (QAR) *</label>
                            <div class="relative">
                                <input type="number" name="price" step="0.01" min="0" required
                                       value="<?php echo $item['price']; ?>"
                                       placeholder="0.00"
                                       class="w-full px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm pl-12">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">QAR</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Options Section -->
                <div class="section-card">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <i data-feather="settings" class="h-4 w-4 mr-2 text-primary"></i>
                            Item Options
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Popular Item Toggle -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <i data-feather="star" class="h-4 w-4 mr-2 text-yellow-500"></i>
                                <span class="text-sm font-medium text-gray-700">Popular Item</span>
                            </div>
                            <div class="toggle-switch <?php echo $item['is_popular'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'is_popular')">
                                <input type="hidden" name="is_popular" value="<?php echo $item['is_popular'] ? '1' : '0'; ?>">
                            </div>
                        </div>
                        
                        <!-- Special Item Toggle -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <i data-feather="award" class="h-4 w-4 mr-2 text-green-500"></i>
                                <span class="text-sm font-medium text-gray-700">Special Item</span>
                            </div>
                            <div class="toggle-switch <?php echo $item['is_special'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'is_special')">
                                <input type="hidden" name="is_special" value="<?php echo $item['is_special'] ? '1' : '0'; ?>">
                            </div>
                        </div>
                        
                        <!-- Half/Full Portions Toggle -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <i data-feather="divide" class="h-4 w-4 mr-2 text-blue-500"></i>
                                <span class="text-sm font-medium text-gray-700">Half/Full Portions</span>
                            </div>
                            <div class="toggle-switch <?php echo $item['is_half_full'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'is_half_full'); toggleHalfFullPrices()">
                                <input type="hidden" name="is_half_full" value="<?php echo $item['is_half_full'] ? '1' : '0'; ?>">
                            </div>
                        </div>
                        
                        <!-- Half/Full Prices -->
                        <div id="half-full-prices" class="space-y-3 mt-4 <?php echo !$item['is_half_full'] ? 'hidden' : ''; ?>">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="input-group">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Half Price (QAR)</label>
                                    <input type="number" name="half_price" step="0.01" min="0"
                                           value="<?php echo $item['half_price'] ?? ''; ?>"
                                           placeholder="0.00"
                                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 focus:border-primary text-sm">
                                </div>
                                <div class="input-group">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Full Price (QAR)</label>
                                    <input type="number" name="full_price" step="0.01" min="0"
                                           value="<?php echo $item['full_price'] ?? ''; ?>"
                                           placeholder="0.00"
                                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 focus:border-primary text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add-ons Section -->
                <div class="section-card">
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-gray-900 flex items-center">
                                <i data-feather="plus-circle" class="h-4 w-4 mr-2 text-primary"></i>
                                Add-ons (English & Arabic)
                            </h3>
                            <button type="button" onclick="addAddon()" 
                                    class="bg-primary/10 text-primary px-3 py-1 rounded-lg text-xs font-medium">
                                + Add
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div id="addons-container" class="space-y-3">
                            <?php foreach ($addons as $index => $addon): ?>
                            <div class="addon-item p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-600">Add-on #<?php echo $index + 1; ?></span>
                                    <button type="button" onclick="removeAddon(this)" 
                                            class="text-red-500 text-xs hover:text-red-700">Remove</button>
                                </div>
                                <div class="space-y-2">
                                    <input type="text" name="addon_names[]" 
                                           value="<?php echo htmlspecialchars($addon['name']); ?>"
                                           placeholder="Add-on name (English)"
                                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                                    <input type="text" name="addon_names_ar[]" 
                                           value="<?php echo htmlspecialchars($addon['name_ar'] ?? ''); ?>" 
                                           dir="rtl" placeholder="اسم الإضافة (العربية)"
                                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                                    <input type="number" name="addon_prices[]" 
                                           value="<?php echo $addon['price']; ?>" 
                                           step="0.01" min="0" placeholder="Price (QAR)"
                                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (empty($addons)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i data-feather="plus-circle" class="h-8 w-8 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm">No add-ons yet. Click "Add" to create your first add-on.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Spice Levels Section -->
                <div class="section-card">
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-gray-900 flex items-center">
                                <i data-feather="thermometer" class="h-4 w-4 mr-2 text-primary"></i>
                                Spice Levels (English & Arabic)
                            </h3>
                            <button type="button" onclick="addSpiceLevel()" 
                                    class="bg-primary/10 text-primary px-3 py-1 rounded-lg text-xs font-medium">
                                + Add
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div id="spice-levels-container" class="space-y-3">
                            <?php foreach ($spiceLevels as $index => $spiceLevel): ?>
                            <div class="spice-item p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-600">Level #<?php echo $index + 1; ?></span>
                                    <button type="button" onclick="removeSpiceLevel(this)" 
                                            class="text-red-500 text-xs hover:text-red-700">Remove</button>
                                </div>
                                <div class="space-y-2">
                                    <input type="text" name="spice_names[]" 
                                           value="<?php echo htmlspecialchars($spiceLevel['name']); ?>"
                                           placeholder="e.g., Mild, Medium, Spicy"
                                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                                    <input type="text" name="spice_names_ar[]" 
                                           value="<?php echo htmlspecialchars($spiceLevel['name_ar'] ?? ''); ?>" 
                                           dir="rtl" placeholder="مثال: خفيف، متوسط، حار"
                                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (empty($spiceLevels)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i data-feather="thermometer" class="h-8 w-8 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm">No spice levels yet. Click "Add" to create your first spice level.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </form>
        </div>

        <!-- Floating Save Button (Desktop) -->
        <div class="floating-action hidden md:block">
            <button type="button" id="save-btn" onclick="saveItem()" 
                    class="w-full btn-primary text-white text-center py-4 rounded-xl font-medium text-lg">
                <?php echo $isEdit ? 'Update Item' : 'Create Item'; ?>
            </button>
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
        
        // Enhanced image preview functionality
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type.toLowerCase())) {
                    showToast('Please select a valid image file (JPEG, PNG, GIF, WebP)', 'error');
                    input.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    showToast(`File size too large (${sizeMB}MB). Maximum 5MB allowed.`, 'error');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.querySelector('.image-upload-container');
                    container.classList.add('has-image');
                    
                    container.innerHTML = `
                        <div class="relative w-full">
                            <img src="${e.target.result}" 
                                 alt="Preview" 
                                 class="image-preview"
                                 id="image-preview">
                            <div class="image-upload-overlay">
                                <div class="text-white text-center">
                                    <i data-feather="upload" class="h-6 w-6 mx-auto mb-2"></i>
                                    <p class="text-sm">Click or drag to change image</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Re-initialize feather icons
                    feather.replace();
                    
                    // Re-attach click handler
                    container.onclick = function() {
                        document.getElementById('image-input').click();
                    };
                    
                    showToast('Image uploaded successfully!', 'success');
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Enhanced drag and drop functionality
        function dragOverHandler(e) {
            e.preventDefault();
            e.currentTarget.classList.add('drag-over');
        }
        
        function dragLeaveHandler(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('drag-over');
        }
        
        function dropHandler(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('image-input');
                fileInput.files = files;
                previewImage(fileInput);
            }
        }
        
        // Toggle switch functionality
        function toggleSwitch(element, fieldName) {
            const isActive = element.classList.contains('active');
            const hiddenInput = element.querySelector('input[type="hidden"]');
            
            if (isActive) {
                element.classList.remove('active');
                hiddenInput.value = '0';
            } else {
                element.classList.add('active');
                hiddenInput.value = '1';
            }
            
            // Haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
        
        // Update category Arabic when English category changes
        function updateCategoryAr() {
            const categorySelect = document.querySelector('select[name="category"]');
            const categoryArInput = document.getElementById('category_ar');
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            
            if (selectedOption.dataset.ar) {
                categoryArInput.value = selectedOption.dataset.ar;
            } else {
                categoryArInput.value = '';
            }
        }
        
        // Toggle half/full price inputs
        function toggleHalfFullPrices() {
            const pricesDiv = document.getElementById('half-full-prices');
            const isHalfFullActive = document.querySelector('input[name="is_half_full"]').value === '1';
            
            if (isHalfFullActive) {
                pricesDiv.classList.remove('hidden');
                // Focus on first price input
                setTimeout(() => {
                    pricesDiv.querySelector('input').focus();
                }, 100);
            } else {
                pricesDiv.classList.add('hidden');
                // Clear half/full price values when disabled
                document.querySelector('input[name="half_price"]').value = '';
                document.querySelector('input[name="full_price"]').value = '';
            }
        }
        
        // Add new addon
        function addAddon() {
            const container = document.getElementById('addons-container');
            const addonCount = container.children.length + 1;
            
            // Hide empty state if exists
            const emptyState = container.querySelector('.text-center');
            if (emptyState) {
                emptyState.remove();
            }
            
            const div = document.createElement('div');
            div.className = 'addon-item p-3';
            div.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600">Add-on #${addonCount}</span>
                    <button type="button" onclick="removeAddon(this)" 
                            class="text-red-500 text-xs hover:text-red-700">Remove</button>
                </div>
                <div class="space-y-2">
                    <input type="text" name="addon_names[]" 
                           placeholder="Add-on name (English)"
                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                    <input type="text" name="addon_names_ar[]" 
                           dir="rtl" placeholder="اسم الإضافة (العربية)"
                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                    <input type="number" name="addon_prices[]" 
                           step="0.01" min="0" placeholder="Price (QAR)"
                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                </div>
            `;
            container.appendChild(div);
            
            // Smooth scroll to new addon and focus first input
            setTimeout(() => {
                div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                div.querySelector('input').focus();
            }, 100);
        }
        
        // Remove addon
        function removeAddon(button) {
            const addonItem = button.closest('.addon-item');
            addonItem.style.transform = 'translateX(-100%)';
            addonItem.style.opacity = '0';
            setTimeout(() => {
                addonItem.remove();
                updateAddonNumbers();
                
                // Show empty state if no addons left
                const container = document.getElementById('addons-container');
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i data-feather="plus-circle" class="h-8 w-8 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm">No add-ons yet. Click "Add" to create your first add-on.</p>
                        </div>
                    `;
                    feather.replace();
                }
            }, 200);
        }
        
        // Update addon numbers after removal
        function updateAddonNumbers() {
            const addonItems = document.querySelectorAll('.addon-item');
            addonItems.forEach((item, index) => {
                const numberSpan = item.querySelector('.text-xs.font-medium.text-gray-600');
                numberSpan.textContent = `Add-on #${index + 1}`;
            });
        }
        
        // Add new spice level
        function addSpiceLevel() {
            const container = document.getElementById('spice-levels-container');
            const spiceCount = container.children.length + 1;
            
            // Hide empty state if exists
            const emptyState = container.querySelector('.text-center');
            if (emptyState) {
                emptyState.remove();
            }
            
            const div = document.createElement('div');
            div.className = 'spice-item p-3';
            div.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600">Level #${spiceCount}</span>
                    <button type="button" onclick="removeSpiceLevel(this)" 
                            class="text-red-500 text-xs hover:text-red-700">Remove</button>
                </div>
                <div class="space-y-2">
                    <input type="text" name="spice_names[]" 
                           placeholder="e.g., Mild, Medium, Spicy"
                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                    <input type="text" name="spice_names_ar[]" 
                           dir="rtl" placeholder="مثال: خفيف، متوسط، حار"
                           class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary/20 text-sm">
                </div>
            `;
            container.appendChild(div);
            
            // Smooth scroll to new spice level and focus first input
            setTimeout(() => {
                div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                div.querySelector('input').focus();
            }, 100);
        }
        
        // Remove spice level
        function removeSpiceLevel(button) {
            const spiceItem = button.closest('.spice-item');
            spiceItem.style.transform = 'translateX(-100%)';
            spiceItem.style.opacity = '0';
            setTimeout(() => {
                spiceItem.remove();
                updateSpiceLevelNumbers();
                
                // Show empty state if no spice levels left
                const container = document.getElementById('spice-levels-container');
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i data-feather="thermometer" class="h-8 w-8 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm">No spice levels yet. Click "Add" to create your first spice level.</p>
                        </div>
                    `;
                    feather.replace();
                }
            }, 200);
        }
        
        // Update spice level numbers after removal
        function updateSpiceLevelNumbers() {
            const spiceItems = document.querySelectorAll('.spice-item');
            spiceItems.forEach((item, index) => {
                const numberSpan = item.querySelector('.text-xs.font-medium.text-gray-600');
                numberSpan.textContent = `Level #${index + 1}`;
            });
        }
        
        // Enhanced save function with better validation and UX
        function saveItem() {
            const form = document.getElementById('item-form');
            const saveBtn = document.getElementById('save-btn');
            const headerSaveBtn = document.querySelector('header button');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstError = null;
            
            // Clear previous errors
            document.querySelectorAll('.input-error').forEach(el => {
                el.classList.remove('input-error');
            });
            
            // Check required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('input-error');
                    isValid = false;
                    if (!firstError) {
                        firstError = field;
                    }
                } else {
                    field.classList.remove('input-error');
                    field.classList.add('input-success');
                    setTimeout(() => {
                        field.classList.remove('input-success');
                    }, 1000);
                }
            });
            
            if (!isValid) {
                showToast('Please fill in all required fields', 'error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => firstError.focus(), 500);
                }
                return;
            }
            
            // Show loading state
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.classList.add('btn-loading');
            }
            if (headerSaveBtn) {
                headerSaveBtn.disabled = true;
                headerSaveBtn.textContent = 'Saving...';
            }
            
            // Show progress message
            showToast('Saving item...', 'info');
            
            // Submit form after short delay to show loading state
            setTimeout(() => {
                form.submit();
            }, 300);
        }
        
        // Enhanced toast notification
        function showToast(message, type = 'info') {
            // Remove existing toasts
            document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `toast-notification fixed top-20 left-4 right-4 p-4 rounded-xl text-sm font-medium z-50 transform -translate-y-20 opacity-0 transition-all duration-300 ${
                type === 'error' ? 'bg-red-100 text-red-700 border border-red-200' : 
                type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 
                'bg-blue-100 text-blue-700 border border-blue-200'
            }`;
            
            toast.innerHTML = `
                <div class="flex items-center">
                    <i data-feather="${type === 'error' ? 'alert-circle' : type === 'success' ? 'check-circle' : 'info'}" class="h-5 w-5 mr-2"></i>
                    ${message}
                </div>
            `;
            
            document.body.appendChild(toast);
            feather.replace();
            
            // Animate in
            requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });
            
            // Auto-remove
            setTimeout(() => {
                toast.style.transform = 'translateY(-20px)';
                toast.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, type === 'error' ? 5000 : 3000);
        }
        
        // Auto-hide existing toast
        const existingToast = document.getElementById('toast');
        if (existingToast) {
            setTimeout(() => {
                existingToast.style.transform = 'translateY(-20px)';
                existingToast.style.opacity = '0';
                setTimeout(() => {
                    if (existingToast.parentNode) {
                        existingToast.parentNode.removeChild(existingToast);
                    }
                }, 300);
            }, 4000);
        }
        
        // Enhanced touch feedback
        function addTouchFeedback() {
            const interactiveElements = document.querySelectorAll('button, .toggle-switch, a, .image-upload-container');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    if (!this.disabled) {
                        this.style.transform = 'scale(0.97)';
                    }
                }, { passive: true });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                }, { passive: true });
                
                element.addEventListener('touchcancel', function() {
                    this.style.transform = 'scale(1)';
                }, { passive: true });
            });
        }
        
        // Form validation helpers
        function validateForm() {
            const form = document.getElementById('item-form');
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('input-error');
                    isValid = false;
                } else {
                    input.classList.remove('input-error');
                }
            });
            
            return isValid;
        }
        
        // Real-time validation
        function setupRealTimeValidation() {
            const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        this.classList.remove('input-error');
                        this.classList.add('input-success');
                        setTimeout(() => {
                            this.classList.remove('input-success');
                        }, 1000);
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('input-error') && this.value.trim()) {
                        this.classList.remove('input-error');
                    }
                });
            });
        }
        
        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            addTouchFeedback();
            setupRealTimeValidation();
            
            // Auto-focus first empty input
            const firstInput = document.querySelector('input[name="name"]');
            if (firstInput && !firstInput.value) {
                setTimeout(() => {
                    firstInput.focus();
                }, 500);
            }
            
            // Prevent zoom on input focus (iOS)
            if (window.innerWidth < 768) {
                document.querySelectorAll('input, textarea, select').forEach(element => {
                    element.addEventListener('focus', function() {
                        this.style.fontSize = '16px';
                    });
                    
                    element.addEventListener('blur', function() {
                        this.style.fontSize = '';
                    });
                });
            }
        });
        
        // Enhanced keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveItem();
            }
            
            // Escape to go back (only if form is mostly empty)
            if (e.key === 'Escape') {
                const form = document.getElementById('item-form');
                const inputs = form.querySelectorAll('input, textarea, select');
                const hasContent = Array.from(inputs).some(input => 
                    input.value.trim() !== '' && input.name !== 'is_popular' && input.name !== 'is_special' && input.name !== 'is_half_full'
                );
                
                if (!hasContent) {
                    if (confirm('Are you sure you want to go back? Any unsaved changes will be lost.')) {
                        window.location.href = 'menu_items.php';
                    }
                }
            }
            
            // Tab navigation enhancements
            if (e.key === 'Tab') {
                // Allow normal tab behavior but ensure proper focus order
                const focusableElements = document.querySelectorAll(
                    'input:not([disabled]), textarea:not([disabled]), select:not([disabled]), button:not([disabled])'
                );
                
                const currentIndex = Array.from(focusableElements).indexOf(document.activeElement);
                
                if (e.shiftKey) {
                    // Shift+Tab (backward)
                    if (currentIndex === 0) {
                        e.preventDefault();
                        focusableElements[focusableElements.length - 1].focus();
                    }
                } else {
                    // Tab (forward)
                    if (currentIndex === focusableElements.length - 1) {
                        e.preventDefault();
                        focusableElements[0].focus();
                    }
                }
            }
        });
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // Re-initialize when page becomes visible
                feather.replace();
            }
        });
        
        // Auto-save draft functionality (localStorage)
        function saveDraft() {
            const form = document.getElementById('item-form');
            const formData = new FormData(form);
            const draft = {};
            
            for (let [key, value] of formData.entries()) {
                if (draft[key]) {
                    if (Array.isArray(draft[key])) {
                        draft[key].push(value);
                    } else {
                        draft[key] = [draft[key], value];
                    }
                } else {
                    draft[key] = value;
                }
            }
            
            localStorage.setItem('menu_item_draft_<?php echo $isEdit ? $id : "new"; ?>', JSON.stringify(draft));
        }
        
        function loadDraft() {
            const draft = localStorage.getItem('menu_item_draft_<?php echo $isEdit ? $id : "new"; ?>');
            if (draft && !<?php echo $isEdit ? 'true' : 'false'; ?>) {
                try {
                    const draftData = JSON.parse(draft);
                    
                    if (confirm('A draft was found. Would you like to restore it?')) {
                        Object.keys(draftData).forEach(key => {
                            const element = document.querySelector(`[name="${key}"]`);
                            if (element) {
                                element.value = draftData[key];
                            }
                        });
                        
                        showToast('Draft restored successfully!', 'success');
                    }
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        }
        
        function clearDraft() {
            localStorage.removeItem('menu_item_draft_<?php echo $isEdit ? $id : "new"; ?>');
        }
        
        // Auto-save draft every 30 seconds
        setInterval(saveDraft, 30000);
        
        // Load draft on page load
        setTimeout(loadDraft, 1000);
        
        // Clear draft on successful save
        window.addEventListener('beforeunload', function() {
            // Only save draft if form has content
            const form = document.getElementById('item-form');
            const inputs = form.querySelectorAll('input, textarea, select');
            const hasContent = Array.from(inputs).some(input => input.value.trim() !== '');
            
            if (hasContent) {
                saveDraft();
            }
        });
        
        // Enhanced error handling
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
            showToast('An unexpected error occurred. Please try again.', 'error');
        });
        
        // Network status monitoring
        window.addEventListener('online', function() {
            showToast('Connection restored', 'success');
        });
        
        window.addEventListener('offline', function() {
            showToast('Connection lost. Changes will be saved locally.', 'warning');
        });
        
        // Performance monitoring
        window.addEventListener('load', function() {
            // Log performance metrics
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
        });
        
        // Accessibility enhancements
        function enhanceAccessibility() {
            // Add ARIA labels to interactive elements
            document.querySelectorAll('.toggle-switch').forEach((toggle, index) => {
                toggle.setAttribute('role', 'switch');
                toggle.setAttribute('tabindex', '0');
                
                const label = toggle.previousElementSibling?.textContent || `Toggle ${index + 1}`;
                toggle.setAttribute('aria-label', label);
                
                // Keyboard support for toggle switches
                toggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // Add proper form labels and descriptions
            document.querySelectorAll('input, textarea, select').forEach(input => {
                const label = input.closest('.input-group')?.querySelector('label');
                if (label && !input.id) {
                    const id = 'input_' + Math.random().toString(36).substr(2, 9);
                    input.id = id;
                    label.setAttribute('for', id);
                }
            });
        }
        
        // Initialize accessibility enhancements
        setTimeout(enhanceAccessibility, 100);
        
        console.log('Fenyal Edit Item - Enhanced with Full Image Upload Support ✨');
    </script>
</body>
</html>