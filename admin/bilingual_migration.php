<?php
// admin/bilingual_migration.php - Ensure bilingual support is properly set up
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$message = '';
$migrationSteps = [];

if ($_POST && $_POST['action'] === 'migrate') {
    try {
        $pdo->beginTransaction();
        
        // Step 1: Check and add Arabic columns to menu_items if they don't exist
        $columns = $pdo->query("SHOW COLUMNS FROM menu_items")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('name_ar', $columns)) {
            $pdo->exec("ALTER TABLE menu_items ADD COLUMN name_ar VARCHAR(255) AFTER name");
            $migrationSteps[] = 'Added name_ar column to menu_items';
        }
        
        if (!in_array('description_ar', $columns)) {
            $pdo->exec("ALTER TABLE menu_items ADD COLUMN description_ar TEXT AFTER description");
            $migrationSteps[] = 'Added description_ar column to menu_items';
        }
        
        if (!in_array('category_ar', $columns)) {
            $pdo->exec("ALTER TABLE menu_items ADD COLUMN category_ar VARCHAR(100) AFTER category");
            $migrationSteps[] = 'Added category_ar column to menu_items';
        }
        
        // Step 2: Check and add Arabic columns to menu_addons
        $addonColumns = $pdo->query("SHOW COLUMNS FROM menu_addons")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('name_ar', $addonColumns)) {
            $pdo->exec("ALTER TABLE menu_addons ADD COLUMN name_ar VARCHAR(255) AFTER name");
            $migrationSteps[] = 'Added name_ar column to menu_addons';
        }
        
        // Step 3: Check and add Arabic columns to menu_spice_levels
        $spiceColumns = $pdo->query("SHOW COLUMNS FROM menu_spice_levels")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('name_ar', $spiceColumns)) {
            $pdo->exec("ALTER TABLE menu_spice_levels ADD COLUMN name_ar VARCHAR(100) AFTER name");
            $migrationSteps[] = 'Added name_ar column to menu_spice_levels';
        }
        
        // Step 4: Check and add Arabic columns to categories
        $categoryColumns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('name_ar', $categoryColumns)) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN name_ar VARCHAR(100) AFTER name");
            $migrationSteps[] = 'Added name_ar column to categories';
        }
        
        // Step 5: Update existing categories with Arabic translations
        $defaultCategoryTranslations = [
            'Breakfast' => 'فطور',
            'Dishes' => 'أطباق',
            'Bread' => 'خبز',
            'Desserts' => 'حلويات',
            'Cold Drinks' => 'مشروبات باردة',
            'Hot Drinks' => 'مشروبات ساخنة'
        ];
        
        foreach ($defaultCategoryTranslations as $english => $arabic) {
            $stmt = $pdo->prepare("UPDATE categories SET name_ar = ? WHERE name = ? AND (name_ar IS NULL OR name_ar = '')");
            $affected = $stmt->execute([$arabic, $english]);
            if ($stmt->rowCount() > 0) {
                $migrationSteps[] = "Updated Arabic translation for category: $english → $arabic";
            }
        }
        
        // Step 6: Update menu items with category Arabic translations
        foreach ($defaultCategoryTranslations as $english => $arabic) {
            $stmt = $pdo->prepare("UPDATE menu_items SET category_ar = ? WHERE category = ? AND (category_ar IS NULL OR category_ar = '')");
            $stmt->execute([$arabic, $english]);
            if ($stmt->rowCount() > 0) {
                $migrationSteps[] = "Updated menu items with Arabic category: $english → $arabic";
            }
        }
        
        // Step 7: Add some sample Arabic translations for existing items (if they don't have any)
        $sampleTranslations = [
            // Breakfast items
            'Fried Eggs & Crunchy Mozzarella' => [
                'name_ar' => 'بيض مقلي وموتزاريلا مقرمشة',
                'description_ar' => 'بيض مقلي يُقدم مع جبنة الموتزاريلا المقرمشة المقلية.'
            ],
            'Zatar Omelette' => [
                'name_ar' => 'عجة الزعتر',
                'description_ar' => 'عجة هشة متبلة بخليط الزعتر العطري.'
            ],
            'Pastrami Scrambled Eggs' => [
                'name_ar' => 'بيض مخفوق بالباسترامي',
                'description_ar' => 'بيض مخفوق ناعم ممزوج بشرائح الباسترامي اللذيذة.'
            ],
            'Balaleet' => [
                'name_ar' => 'بلاليط',
                'description_ar' => 'طبق شعيرية حلو ومالح، يُقدم غالباً مع العجة.'
            ],
            // Common add-ons
            'Extra Mozzarella' => ['name_ar' => 'موتزاريلا إضافية'],
            'Avocado Slice' => ['name_ar' => 'شريحة أفوكادو'],
            'Extra Olive Oil' => ['name_ar' => 'زيت زيتون إضافي'],
            'Extra Cardamom' => ['name_ar' => 'هيل إضافي'],
            'Saffron' => ['name_ar' => 'زعفران'],
            // Common spice levels
            'Mild' => ['name_ar' => 'خفيف'],
            'Medium' => ['name_ar' => 'متوسط'],
            'Spicy' => ['name_ar' => 'حار'],
            'Hot' => ['name_ar' => 'حار جداً']
        ];
        
        // Update menu items with sample translations
        foreach ($sampleTranslations as $englishName => $translations) {
            if (isset($translations['name_ar']) && isset($translations['description_ar'])) {
                $stmt = $pdo->prepare("UPDATE menu_items SET name_ar = ?, description_ar = ? WHERE name = ? AND (name_ar IS NULL OR name_ar = '')");
                $stmt->execute([$translations['name_ar'], $translations['description_ar'], $englishName]);
                if ($stmt->rowCount() > 0) {
                    $migrationSteps[] = "Added Arabic translation for menu item: $englishName";
                }
            }
        }
        
        // Update add-ons with sample translations
        foreach ($sampleTranslations as $englishName => $translations) {
            if (isset($translations['name_ar']) && !isset($translations['description_ar'])) {
                $stmt = $pdo->prepare("UPDATE menu_addons SET name_ar = ? WHERE name = ? AND (name_ar IS NULL OR name_ar = '')");
                $stmt->execute([$translations['name_ar'], $englishName]);
                if ($stmt->rowCount() > 0) {
                    $migrationSteps[] = "Added Arabic translation for add-on: $englishName";
                }
            }
        }
        
        // Update spice levels with sample translations
        foreach ($sampleTranslations as $englishName => $translations) {
            if (isset($translations['name_ar']) && !isset($translations['description_ar'])) {
                $stmt = $pdo->prepare("UPDATE menu_spice_levels SET name_ar = ? WHERE name = ? AND (name_ar IS NULL OR name_ar = '')");
                $stmt->execute([$translations['name_ar'], $englishName]);
                if ($stmt->rowCount() > 0) {
                    $migrationSteps[] = "Added Arabic translation for spice level: $englishName";
                }
            }
        }
        
        // Step 8: Add indexes for better performance
        try {
            $pdo->exec("CREATE INDEX idx_menu_items_name_ar ON menu_items (name_ar)");
            $migrationSteps[] = 'Added index on menu_items.name_ar';
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_menu_items_category_ar ON menu_items (category_ar)");
            $migrationSteps[] = 'Added index on menu_items.category_ar';
        } catch (Exception $e) {
            // Index might already exist
        }
        
        $pdo->commit();
        $message = 'Bilingual migration completed successfully!';
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = 'Migration failed: ' . $e->getMessage();
    }
}

// Check current status
function checkBilingualStatus($pdo) {
    $status = [];
    
    // Check menu_items table
    $columns = $pdo->query("SHOW COLUMNS FROM menu_items")->fetchAll(PDO::FETCH_COLUMN);
    $status['menu_items'] = [
        'name_ar' => in_array('name_ar', $columns),
        'description_ar' => in_array('description_ar', $columns),
        'category_ar' => in_array('category_ar', $columns)
    ];
    
    // Check menu_addons table
    $addonColumns = $pdo->query("SHOW COLUMNS FROM menu_addons")->fetchAll(PDO::FETCH_COLUMN);
    $status['menu_addons'] = [
        'name_ar' => in_array('name_ar', $addonColumns)
    ];
    
    // Check menu_spice_levels table
    $spiceColumns = $pdo->query("SHOW COLUMNS FROM menu_spice_levels")->fetchAll(PDO::FETCH_COLUMN);
    $status['menu_spice_levels'] = [
        'name_ar' => in_array('name_ar', $spiceColumns)
    ];
    
    // Check categories table
    $categoryColumns = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
    $status['categories'] = [
        'name_ar' => in_array('name_ar', $categoryColumns)
    ];
    
    // Check if any items have Arabic content
    $arabicContentCount = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE name_ar IS NOT NULL AND name_ar != ''")->fetchColumn();
    $status['has_arabic_content'] = $arabicContentCount > 0;
    $status['arabic_items_count'] = $arabicContentCount;
    
    return $status;
}

$currentStatus = checkBilingualStatus($pdo);
$needsMigration = !$currentStatus['menu_items']['name_ar'] || 
                  !$currentStatus['menu_items']['description_ar'] || 
                  !$currentStatus['menu_items']['category_ar'] ||
                  !$currentStatus['menu_addons']['name_ar'] ||
                  !$currentStatus['menu_spice_levels']['name_ar'] ||
                  !$currentStatus['categories']['name_ar'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilingual Migration - Fenyal Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/themecolor.js"></script>
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
                            <h1 class="text-xl font-semibold text-gray-900">Bilingual Migration</h1>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Bilingual Support Migration</h2>
            <p class="text-gray-600">This tool will set up Arabic language support for your menu system</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
            
            <?php if (!empty($migrationSteps)): ?>
            <div class="mt-3">
                <h4 class="font-semibold">Migration Steps Completed:</h4>
                <ul class="mt-2 list-disc list-inside text-sm">
                    <?php foreach ($migrationSteps as $step): ?>
                    <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Bilingual Status</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Database Structure -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Database Structure</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Menu Items Arabic Fields</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($currentStatus['menu_items']['name_ar'] && $currentStatus['menu_items']['description_ar'] && $currentStatus['menu_items']['category_ar']) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ($currentStatus['menu_items']['name_ar'] && $currentStatus['menu_items']['description_ar'] && $currentStatus['menu_items']['category_ar']) ? 'READY' : 'MISSING'; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Add-ons Arabic Fields</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['menu_addons']['name_ar'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $currentStatus['menu_addons']['name_ar'] ? 'READY' : 'MISSING'; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Spice Levels Arabic Fields</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['menu_spice_levels']['name_ar'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $currentStatus['menu_spice_levels']['name_ar'] ? 'READY' : 'MISSING'; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Categories Arabic Fields</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['categories']['name_ar'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $currentStatus['categories']['name_ar'] ? 'READY' : 'MISSING'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Content Status -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Arabic Content</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Items with Arabic Names</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['has_arabic_content'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo $currentStatus['arabic_items_count']; ?> items
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <?php if ($currentStatus['has_arabic_content']): ?>
                            <p>✅ Your menu has Arabic translations</p>
                            <?php else: ?>
                            <p>⚠️ No Arabic content found. The migration will add sample translations.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Migration Actions -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Migration Actions</h3>
            
            <?php if ($needsMigration): ?>
            <div class="mb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Migration Required</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Your database needs to be updated for bilingual support. This migration will:</p>
                                <ul class="mt-2 list-disc list-inside">
                                    <li>Add Arabic columns to all menu tables</li>
                                    <li>Update categories with Arabic translations</li>
                                    <li>Add sample Arabic translations for existing items</li>
                                    <li>Add database indexes for better performance</li>
                                    <li>Set up proper bilingual data structure</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to run the bilingual migration? This will modify your database structure.')">
                <input type="hidden" name="action" value="migrate">
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-medium hover:bg-primary/90 transition-colors">
                    Run Bilingual Migration
                </button>
            </form>
            
            <?php else: ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Bilingual Support Ready</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Your database is properly configured for bilingual support! You can now:</p>
                            <ul class="mt-2 list-disc list-inside">
                                <li>Add Arabic translations to menu items</li>
                                <li>Use the language toggle on your website</li>
                                <li>Manage categories in both languages</li>
                                <li>Set up add-ons and spice levels bilingually</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <a href="menu_items.php" class="bg-primary text-white px-6 py-2 rounded-lg font-medium hover:bg-primary/90 transition-colors">
                    Manage Menu Items
                </a>
                <a href="categories.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    Manage Categories
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Instructions -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-blue-900 mb-2">How to Use Bilingual Features</h4>
            <div class="text-sm text-blue-800 space-y-3">
                <div>
                    <h5 class="font-semibold">1. Admin Panel</h5>
                    <p>When editing menu items, you'll see separate fields for English and Arabic content. Fill in both languages for complete bilingual support.</p>
                </div>
                
                <div>
                    <h5 class="font-semibold">2. Frontend Display</h5>
                    <p>Users can toggle between English and Arabic using the language switcher. The content will automatically display in the selected language.</p>
                </div>
                
                <div>
                    <h5 class="font-semibold">3. Categories</h5>
                    <p>Categories support both languages. Make sure to fill in Arabic names for all categories for the best user experience.</p>
                </div>
                
                <div>
                    <h5 class="font-semibold">4. Add-ons & Spice Levels</h5>
                    <p>These also support bilingual content. Add Arabic translations to provide a complete localized experience.</p>
                </div>
                
                <div>
                    <h5 class="font-semibold">5. URL Structure</h5>
                    <p>The system supports language-specific URLs like <code>?lang=ar</code> for Arabic and <code>?lang=en</code> for English.</p>
                </div>
            </div>
        </div>

        <!-- Testing -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-2">Testing Your Bilingual Setup</h4>
            <div class="text-sm text-gray-700 space-y-2">
                <p><strong>After migration:</strong></p>
                <ol class="list-decimal list-inside space-y-1 ml-4">
                    <li>Visit your homepage and look for the language toggle (EN/AR)</li>
                    <li>Switch between languages to see the content change</li>
                    <li>Check that categories display in the correct language</li>
                    <li>Test menu item details pages in both languages</li>
                    <li>Verify that add-ons and spice levels show Arabic names when available</li>
                </ol>
                
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <p class="text-yellow-800"><strong>Note:</strong> Items without Arabic translations will fall back to English content automatically.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>