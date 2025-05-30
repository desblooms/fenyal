<?php
// admin/migrate_categories.php - Migration script for adding image support to categories
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$message = '';
$migrationStatus = [];

// Check current database structure
function checkDatabaseStructure($pdo) {
    $status = [];
    
    // Check if image column exists in categories table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'");
        $status['image_column_exists'] = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        $status['image_column_exists'] = false;
        $status['error'] = 'Categories table may not exist';
    }
    
    // Check if updated_at column exists
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
        $status['updated_at_column_exists'] = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        $status['updated_at_column_exists'] = false;
    }
    
    // Check if uploads directory exists
    $status['uploads_dir_exists'] = is_dir('../uploads/categories/');
    $status['uploads_dir_writable'] = is_writable('../uploads/categories/') || is_writable('../uploads/');
    
    return $status;
}

// Perform migration
if ($_POST && $_POST['action'] === 'migrate') {
    try {
        $pdo->beginTransaction();
        
        // Create uploads directory if it doesn't exist
        if (!is_dir('../uploads/categories/')) {
            if (!mkdir('../uploads/categories/', 0755, true)) {
                throw new Exception('Failed to create uploads directory');
            }
            $migrationStatus[] = 'Created uploads/categories/ directory';
        }
        
        // Add image column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(500) NULL AFTER display_order");
            $migrationStatus[] = 'Added image column to categories table';
        }
        
        // Add updated_at column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            $migrationStatus[] = 'Added updated_at column to categories table';
        }
        
        // Add indexes if they don't exist
        try {
            $pdo->exec("CREATE INDEX idx_display_order ON categories (display_order)");
            $migrationStatus[] = 'Added display_order index';
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_active ON categories (is_active)");
            $migrationStatus[] = 'Added is_active index';
        } catch (Exception $e) {
            // Index might already exist
        }
        
        // Update existing categories with default images
        $defaultImages = [
            'Breakfast' => 'uploads/categories/breakfast.png',
            'Dishes' => 'uploads/categories/dishes.png',
            'Bread' => 'uploads/categories/bread.png',
            'Desserts' => 'uploads/categories/desserts.png',
            'Cold Drinks' => 'uploads/categories/cold-drinks.png',
            'Hot Drinks' => 'uploads/categories/hot-drinks.png'
        ];
        
        foreach ($defaultImages as $category => $imagePath) {
            $stmt = $pdo->prepare("UPDATE categories SET image = ? WHERE name = ? AND (image IS NULL OR image = '')");
            $stmt->execute([$imagePath, $category]);
            if ($stmt->rowCount() > 0) {
                $migrationStatus[] = "Updated default image for $category category";
            }
        }
        
        $pdo->commit();
        $message = 'Migration completed successfully!';
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = 'Migration failed: ' . $e->getMessage();
    }
}

$currentStatus = checkDatabaseStructure($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Fenyal Admin</title>
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
                            <h1 class="text-xl font-semibold text-gray-900">Database Migration</h1>
                        </div>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="categories.php" class="text-gray-600 hover:text-gray-900">Categories</a>
                    <a href="logout.php" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium text-gray-700">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Categories Image Support Migration</h2>
            <p class="text-gray-600">This script will update your database to support category images</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
            
            <?php if (!empty($migrationStatus)): ?>
            <div class="mt-3">
                <h4 class="font-semibold">Migration Steps Completed:</h4>
                <ul class="mt-2 list-disc list-inside text-sm">
                    <?php foreach ($migrationStatus as $status): ?>
                    <li><?php echo htmlspecialchars($status); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Database Status</h3>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Image column in categories table</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['image_column_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $currentStatus['image_column_exists'] ? 'EXISTS' : 'MISSING'; ?>
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Updated_at column in categories table</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['updated_at_column_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $currentStatus['updated_at_column_exists'] ? 'EXISTS' : 'MISSING'; ?>
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Uploads directory (uploads/categories/)</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['uploads_dir_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $currentStatus['uploads_dir_exists'] ? 'EXISTS' : 'MISSING'; ?>
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Uploads directory writable</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $currentStatus['uploads_dir_writable'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $currentStatus['uploads_dir_writable'] ? 'WRITABLE' : 'NOT WRITABLE'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Migration Actions -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Migration Actions</h3>
            
            <?php 
            $needsMigration = !$currentStatus['image_column_exists'] || 
                             !$currentStatus['updated_at_column_exists'] || 
                             !$currentStatus['uploads_dir_exists'];
            ?>
            
            <?php if ($needsMigration): ?>
            <div class="mb-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Migration Required</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Your database needs to be updated to support category images. This migration will:</p>
                                <ul class="mt-2 list-disc list-inside">
                                    <?php if (!$currentStatus['image_column_exists']): ?>
                                    <li>Add 'image' column to categories table</li>
                                    <?php endif; ?>
                                    <?php if (!$currentStatus['updated_at_column_exists']): ?>
                                    <li>Add 'updated_at' column to categories table</li>
                                    <?php endif; ?>
                                    <?php if (!$currentStatus['uploads_dir_exists']): ?>
                                    <li>Create uploads/categories/ directory</li>
                                    <?php endif; ?>
                                    <li>Add database indexes for better performance</li>
                                    <li>Set default images for existing categories</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to run the migration? This will modify your database structure.')">
                <input type="hidden" name="action" value="migrate">
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-medium hover:bg-primary/90 transition-colors">
                    Run Migration
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
                        <h3 class="text-sm font-medium text-green-800">Migration Complete</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Your database is already up to date and supports category images. You can now:</p>
                            <ul class="mt-2 list-disc list-inside">
                                <li>Upload images for your categories</li>
                                <li>Manage categories with the enhanced admin interface</li>
                                <li>View dynamic categories on your website</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="categories.php" class="bg-primary text-white px-6 py-2 rounded-lg font-medium hover:bg-primary/90 transition-colors inline-block">
                    Go to Categories Management
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Post-Migration Instructions -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-blue-900 mb-2">Next Steps</h4>
            <div class="text-sm text-blue-800 space-y-2">
                <p><strong>After running the migration:</strong></p>
                <ol class="list-decimal list-inside space-y-1 ml-4">
                    <li>Go to the <a href="categories.php" class="underline">Categories Management</a> page</li>
                    <li>Upload images for each category (recommended size: 64x64px to 128x128px)</li>
                    <li>Arrange categories in your preferred display order</li>
                    <li>Test the frontend to see your dynamic categories with images</li>
                    <li>Consider creating default placeholder images for better consistency</li>
                </ol>
                
                <p class="mt-4"><strong>Image Guidelines:</strong></p>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li>Supported formats: JPEG, PNG, GIF, WebP</li>
                    <li>Maximum file size: 5MB</li>
                    <li>Recommended dimensions: 64x64px to 128x128px</li>
                    <li>Images will be displayed in circular format</li>
                    <li>Use clear, simple icons that represent each category</li>
                </ul>
            </div>
        </div>

        <!-- Troubleshooting -->
        <?php if (isset($currentStatus['error'])): ?>
        <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-red-900 mb-2">Troubleshooting</h4>
            <div class="text-sm text-red-800">
                <p><strong>Error detected:</strong> <?php echo htmlspecialchars($currentStatus['error']); ?></p>
                <p class="mt-2">Please ensure that:</p>
                <ul class="list-disc list-inside space-y-1 ml-4 mt-2">
                    <li>Your database connection is working properly</li>
                    <li>The categories table exists in your database</li>
                    <li>Your database user has ALTER privileges</li>
                    <li>You have run the initial database setup from config.php</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$currentStatus['uploads_dir_writable']): ?>
        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-yellow-900 mb-2">Permissions Warning</h4>
            <div class="text-sm text-yellow-800">
                <p>The uploads directory is not writable. Please set the correct permissions:</p>
                <div class="mt-2 bg-gray-100 p-3 rounded font-mono text-xs">
                    chmod 755 uploads/<br>
                    chmod 755 uploads/categories/
                </div>
                <p class="mt-2">Or ensure your web server has write permissions to the uploads directory.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>