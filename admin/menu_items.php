<?php
// menu_items.php - Menu items management
require_once 'config.php';
checkAuth();

$pdo = getConnection();
$message = '';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Handle actions
if ($_POST) {
    switch ($_POST['action']) {
        case 'delete':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            if ($stmt->execute([$id])) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Fenyal Admin</title>
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
                            <h1 class="text-xl font-semibold text-gray-900">Menu Items</h1>
                        </div>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="logout.php" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md text-sm font-medium text-gray-700">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Menu Items</h2>
                <p class="text-gray-600">Manage your restaurant menu items</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="edit_item.php" class="bg-primary text-white px-4 py-2 rounded-lg font-medium hover:bg-primary/90 transition-colors">
                    Add New Item
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search items..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <select name="category" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Filter
                    </button>
                    <a href="menu_items.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Items Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No items found. <a href="edit_item.php" class="text-primary hover:underline">Add your first item</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-16 w-16 flex-shrink-0">
                                    <img class="h-16 w-16 rounded-lg object-cover" 
                                         src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         onerror="this.src='https://via.placeholder.com/64x64/e5e7eb/9ca3af?text=No+Image'">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                    <?php if ($item['name_ar']): ?>
                                    <div class="text-sm text-gray-500" dir="rtl">
                                        <?php echo htmlspecialchars($item['name_ar']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-400 mt-1">
                                        ID: <?php echo $item['id']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['category']); ?></div>
                            <?php if ($item['category_ar']): ?>
                            <div class="text-sm text-gray-500" dir="rtl"><?php echo htmlspecialchars($item['category_ar']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">QAR <?php echo number_format($item['price'], 2); ?></div>
                            <?php if ($item['is_half_full']): ?>
                            <div class="text-xs text-gray-500">
                                Half/Full: <?php echo $item['half_price'] ? 'QAR ' . number_format($item['half_price'], 2) : 'N/A'; ?> / 
                                <?php echo $item['full_price'] ? 'QAR ' . number_format($item['full_price'], 2) : 'N/A'; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col space-y-1">
                                <?php if ($item['is_popular']): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Popular
                                </span>
                                <?php endif; ?>
                                <?php if ($item['is_special']): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Special
                                </span>
                                <?php endif; ?>
                                <?php if ($item['is_half_full']): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Half/Full
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex flex-col space-y-2">
                                <a href="edit_item.php?id=<?php echo $item['id']; ?>" 
                                   class="text-primary hover:text-primary/80">Edit</a>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Toggle popular status?')">
                                    <input type="hidden" name="action" value="toggle_popular">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-left">
                                        <?php echo $item['is_popular'] ? 'Remove Popular' : 'Make Popular'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Toggle special status?')">
                                    <input type="hidden" name="action" value="toggle_special">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-left">
                                        <?php echo $item['is_special'] ? 'Remove Special' : 'Make Special'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-left">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalItems); ?> of <?php echo $totalItems; ?> results
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 px-3 py-2 rounded-md text-sm font-medium">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="<?php echo $i === $page ? 'bg-primary text-white' : 'bg-white border border-gray-300 text-gray-500 hover:bg-gray-50'; ?> px-3 py-2 rounded-md text-sm font-medium">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                   class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 px-3 py-2 rounded-md text-sm font-medium">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>