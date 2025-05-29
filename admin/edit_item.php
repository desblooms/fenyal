<?php
// edit_item.php - Add/Edit menu item
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$id = $_GET['id'] ?? null;
$isEdit = $id !== null;
$message = '';

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order, name")->fetchAll();

// Initialize item data
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
        header('Location: menu_items.php');
        exit;
    }
    
    $item = $existingItem;
    
    // Get addons
    $stmt = $pdo->prepare("SELECT * FROM menu_addons WHERE menu_item_id = ?");
    $stmt->execute([$id]);
    $addons = $stmt->fetchAll();
    
    // Get spice levels
    $stmt = $pdo->prepare("SELECT * FROM menu_spice_levels WHERE menu_item_id = ?");
    $stmt->execute([$id]);
    $spiceLevels = $stmt->fetchAll();
}

// Handle form submission
if ($_POST) {
    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        $required = ['name', 'description', 'price', 'category'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Prepare item data
        $itemData = [
            'name' => $_POST['name'],
            'name_ar' => $_POST['name_ar'] ?: null,
            'description' => $_POST['description'],
            'description_ar' => $_POST['description_ar'] ?: null,
            'price' => (float)$_POST['price'],
            'category' => $_POST['category'],
            'category_ar' => $_POST['category_ar'] ?: null,
            'image' => $_POST['image'] ?: null,
            'is_popular' => isset($_POST['is_popular']) ? 1 : 0,
            'is_special' => isset($_POST['is_special']) ? 1 : 0,
            'is_half_full' => isset($_POST['is_half_full']) ? 1 : 0,
            'half_price' => $_POST['half_price'] ? (float)$_POST['half_price'] : null,
            'full_price' => $_POST['full_price'] ? (float)$_POST['full_price'] : null
        ];
        
        if ($isEdit) {
            // Update existing item
            $sql = "UPDATE menu_items SET 
                    name = ?, name_ar = ?, description = ?, description_ar = ?, 
                    price = ?, category = ?, category_ar = ?, image = ?, 
                    is_popular = ?, is_special = ?, is_half_full = ?, half_price = ?, full_price = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $itemData['name'], $itemData['name_ar'], $itemData['description'], $itemData['description_ar'],
                $itemData['price'], $itemData['category'], $itemData['category_ar'], $itemData['image'],
                $itemData['is_popular'], $itemData['is_special'], $itemData['is_half_full'], 
                $itemData['half_price'], $itemData['full_price'], $id
            ]);
            
            $itemId = $id;
        } else {
            // Insert new item
            $sql = "INSERT INTO menu_items 
                    (name, name_ar, description, description_ar, price, category, category_ar, image, 
                     is_popular, is_special, is_half_full, half_price, full_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $itemData['name'], $itemData['name_ar'], $itemData['description'], $itemData['description_ar'],
                $itemData['price'], $itemData['category'], $itemData['category_ar'], $itemData['image'],
                $itemData['is_popular'], $itemData['is_special'], $itemData['is_half_full'], 
                $itemData['half_price'], $itemData['full_price']
            ]);
            
            $itemId = $pdo->lastInsertId();
        }
        
        // Delete existing addons and spice levels
        $pdo->prepare("DELETE FROM menu_addons WHERE menu_item_id = ?")->execute([$itemId]);
        $pdo->prepare("DELETE FROM menu_spice_levels WHERE menu_item_id = ?")->execute([$itemId]);
        
        // Insert addons
        if (!empty($_POST['addon_names'])) {
            foreach ($_POST['addon_names'] as $index => $addonName) {
                if ($addonName && isset($_POST['addon_prices'][$index]) && $_POST['addon_prices'][$index] !== '') {
                    $stmt = $pdo->prepare("INSERT INTO menu_addons (menu_item_id, name, name_ar, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $itemId,
                        $addonName,
                        $_POST['addon_names_ar'][$index] ?: null,
                        (float)$_POST['addon_prices'][$index]
                    ]);
                }
            }
        }
        
        // Insert spice levels
        if (!empty($_POST['spice_names'])) {
            foreach ($_POST['spice_names'] as $index => $spiceName) {
                if ($spiceName) {
                    $stmt = $pdo->prepare("INSERT INTO menu_spice_levels (menu_item_id, name, name_ar) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $itemId,
                        $spiceName,
                        $_POST['spice_names_ar'][$index] ?: null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        $message = $isEdit ? 'Item updated successfully!' : 'Item created successfully!';
        
        // Redirect to items list after successful creation
        if (!$isEdit) {
            header('Location: menu_items.php');
            exit;
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Menu Item - Fenyal Admin</title>
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
                            <h1 class="text-xl font-semibold text-gray-900">
                                <?php echo $isEdit ? 'Edit' : 'Add'; ?> Menu Item
                            </h1>
                        </div>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="menu_items.php" class="text-gray-600 hover:text-gray-900">Back to Items</a>
                    <a href="logout.php" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium text-gray-700">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($message): ?>
        <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name (English) *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($item['name']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="name_ar" class="block text-sm font-medium text-gray-700 mb-2">Name (Arabic)</label>
                        <input type="text" id="name_ar" name="name_ar" dir="rtl"
                               value="<?php echo htmlspecialchars($item['name_ar']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (English) *</label>
                        <textarea id="description" name="description" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($item['description']); ?></textarea>
                    </div>
                    
                    <div>
                        <label for="description_ar" class="block text-sm font-medium text-gray-700 mb-2">Description (Arabic)</label>
                        <textarea id="description_ar" name="description_ar" rows="3" dir="rtl"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($item['description_ar']); ?></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select id="category" name="category" required onchange="updateCategoryAr()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                    data-ar="<?php echo htmlspecialchars($cat['name_ar']); ?>"
                                    <?php echo $item['category'] === $cat['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="category_ar" class="block text-sm font-medium text-gray-700 mb-2">Category (Arabic)</label>
                        <input type="text" id="category_ar" name="category_ar" dir="rtl"
                               value="<?php echo htmlspecialchars($item['category_ar']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (QAR) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                               value="<?php echo $item['price']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                    <input type="url" id="image" name="image"
                           value="<?php echo htmlspecialchars($item['image']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Enter the full URL to the item image</p>
                </div>
            </div>

            <!-- Options -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Options</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_popular" value="1" 
                                   <?php echo $item['is_popular'] ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-primary focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Popular Item</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_special" value="1" 
                                   <?php echo $item['is_special'] ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-primary focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Special Item</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_half_full" value="1" 
                                   <?php echo $item['is_half_full'] ? 'checked' : ''; ?>
                                   onchange="toggleHalfFullPrices()"
                                   class="rounded border-gray-300 text-primary focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Half/Full Portions</span>
                        </label>
                    </div>
                </div>

                <div id="half-full-prices" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4" style="display: <?php echo $item['is_half_full'] ? 'grid' : 'none'; ?>">
                    <div>
                        <label for="half_price" class="block text-sm font-medium text-gray-700 mb-2">Half Portion Price (QAR)</label>
                        <input type="number" id="half_price" name="half_price" step="0.01" min="0"
                               value="<?php echo $item['half_price']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="full_price" class="block text-sm font-medium text-gray-700 mb-2">Full Portion Price (QAR)</label>
                        <input type="number" id="full_price" name="full_price" step="0.01" min="0"
                               value="<?php echo $item['full_price']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Add-ons -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add-ons</h3>
                    <button type="button" onclick="addAddon()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                        Add Add-on
                    </button>
                </div>
                
                <div id="addons-container">
                    <?php foreach ($addons as $index => $addon): ?>
                    <div class="addon-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 border border-gray-200 rounded">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name (English)</label>
                            <input type="text" name="addon_names[]" value="<?php echo htmlspecialchars($addon['name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name (Arabic)</label>
                            <input type="text" name="addon_names_ar[]" value="<?php echo htmlspecialchars($addon['name_ar']); ?>" dir="rtl"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (QAR)</label>
                            <input type="number" name="addon_prices[]" value="<?php echo $addon['price']; ?>" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="removeAddon(this)" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">
                                Remove
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Spice Levels -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Spice Levels</h3>
                    <button type="button" onclick="addSpiceLevel()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                        Add Spice Level
                    </button>
                </div>
                
                <div id="spice-levels-container">
                    <?php foreach ($spiceLevels as $index => $spiceLevel): ?>
                    <div class="spice-row grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4 border border-gray-200 rounded">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name (English)</label>
                            <input type="text" name="spice_names[]" value="<?php echo htmlspecialchars($spiceLevel['name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name (Arabic)</label>
                            <input type="text" name="spice_names_ar[]" value="<?php echo htmlspecialchars($spiceLevel['name_ar']); ?>" dir="rtl"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="removeSpiceLevel(this)" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">
                                Remove
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <a href="menu_items.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-400 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-medium hover:bg-primary/90 transition-colors">
                    <?php echo $isEdit ? 'Update' : 'Create'; ?> Item
                </button>
            </div>
        </form>
    </div>

    <script>
        function updateCategoryAr() {
            const categorySelect = document.getElementById('category');
            const categoryArInput = document.getElementById('category_ar');
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            
            if (selectedOption.dataset.ar) {
                categoryArInput.value = selectedOption.dataset.ar;
            }
        }

        function toggleHalfFullPrices() {
            const checkbox = document.querySelector('input[name="is_half_full"]');
            const pricesDiv = document.getElementById('half-full-prices');
            
            if (checkbox.checked) {
                pricesDiv.style.display = 'grid';
            } else {
                pricesDiv.style.display = 'none';
            }
        }

        function addAddon() {
            const container = document.getElementById('addons-container');
            const div = document.createElement('div');
            div.className = 'addon-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 border border-gray-200 rounded';
            div.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name (English)</label>
                    <input type="text" name="addon_names[]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name (Arabic)</label>
                    <input type="text" name="addon_names_ar[]" dir="rtl" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (QAR)</label>
                    <input type="number" name="addon_prices[]" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="removeAddon(this)" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Remove</button>
                </div>
            `;
            container.appendChild(div);
        }

        function removeAddon(button) {
            button.closest('.addon-row').remove();
        }

        function addSpiceLevel() {
            const container = document.getElementById('spice-levels-container');
            const div = document.createElement('div');
            div.className = 'spice-row grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4 border border-gray-200 rounded';
            div.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name (English)</label>
                    <input type="text" name="spice_names[]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name (Arabic)</label>
                    <input type="text" name="spice_names_ar[]" dir="rtl" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="removeSpiceLevel(this)" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Remove</button>
                </div>
            `;
            container.appendChild(div);
        }

        function removeSpiceLevel(button) {
            button.closest('.spice-row').remove();
        }
    </script>
</body>
</html>