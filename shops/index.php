<?php
/**
 * Shop Management (Admin/Manager)
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check authentication and role
if (!is_logged_in()) {
    redirect('auth/login.php');
}

check_role('manager'); // Requires manager or admin role

$current_user = get_current_user();
$page_title = 'Shop Management';

// Handle shop status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($_POST['action'] === 'update_status') {
            $shop_id = (int)$_POST['shop_id'];
            $status = sanitize_input($_POST['status']);
            
            $query = "UPDATE shops SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $shop_id);
            
            if ($stmt->execute()) {
                $success_message = 'Shop status updated successfully.';
            } else {
                $error_message = 'Failed to update shop status.';
            }
        }
    }
}

// Get shops with pagination
$database = new Database();
$db = $database->getConnection();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($_GET['status'])) {
    $where_conditions[] = "s.status = :status";
    $params[':status'] = $_GET['status'];
}

if (!empty($_GET['category'])) {
    $where_conditions[] = "s.category_id = :category_id";
    $params[':category_id'] = $_GET['category'];
}

if (!empty($_GET['search'])) {
    $where_conditions[] = "(s.title LIKE :search OR s.shop_number LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM shops s 
                LEFT JOIN categories c ON s.category_id = c.id 
                $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_shops = $count_stmt->fetch()['total'];
$total_pages = ceil($total_shops / ITEMS_PER_PAGE);

// Get shops
$query = "SELECT s.*, c.name as category_name,
          (SELECT COUNT(*) FROM applications a WHERE a.shop_id = s.id AND a.status = 'pending') as pending_applications,
          (SELECT COUNT(*) FROM leases l WHERE l.shop_id = s.id AND l.status = 'active') as active_leases
          FROM shops s 
          LEFT JOIN categories c ON s.category_id = c.id 
          $where_clause
          ORDER BY s.shop_number ASC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$shops = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name")->fetchAll();

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="../dashboard/index.php" class="flex items-center space-x-3">
                        <i class="fas fa-building text-2xl" style="color: var(--primary);"></i>
                        <div>
                            <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                            <p class="text-xs text-gray-500">Shop Management</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="add-shop.php" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>Add Shop
                    </a>
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-900" onclick="toggleDropdown('user-menu')">
                            <span><?php echo htmlspecialchars($current_user['first_name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="../dashboard/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                            </a>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading text-gray-900 mb-2">Shop Management</h1>
            <p class="text-gray-600">Manage all shops in the plaza</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        class="form-input" 
                        placeholder="Shop number or title"
                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="form-input">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo ($_GET['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo ($_GET['status'] ?? '') === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo ($_GET['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="reserved" <?php echo ($_GET['status'] ?? '') === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" class="form-input">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($_GET['category'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                    <a href="index.php" class="btn-outline">Clear</a>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Shops</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_shops; ?></p>
                    </div>
                </div>
            </div>

            <?php
            $status_counts = $db->query("SELECT status, COUNT(*) as count FROM shops GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
            ?>

            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Available</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['available'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Occupied</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['occupied'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tools text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Maintenance</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['maintenance'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shops Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($shops as $shop): ?>
                <div class="card hover:shadow-lg transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-heading text-gray-900 mb-1">
                                <?php echo htmlspecialchars($shop['shop_number']); ?>
                            </h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($shop['title']); ?></p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php 
                            switch($shop['status']) {
                                case 'available': echo 'bg-green-100 text-green-800'; break;
                                case 'occupied': echo 'bg-red-100 text-red-800'; break;
                                case 'maintenance': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'reserved': echo 'bg-blue-100 text-blue-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($shop['status']); ?>
                        </span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Category:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($shop['category_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Area:</span>
                            <span class="font-medium"><?php echo number_format($shop['area_sqft']); ?> sq ft</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Rent:</span>
                            <span class="font-medium"><?php echo format_currency($shop['monthly_rent']); ?>/month</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Floor:</span>
                            <span class="font-medium"><?php echo $shop['floor_number']; ?></span>
                        </div>
                    </div>

                    <?php if ($shop['pending_applications'] > 0 || $shop['active_leases'] > 0): ?>
                        <div class="border-t border-gray-200 pt-3 mb-4">
                            <?php if ($shop['pending_applications'] > 0): ?>
                                <div class="flex items-center text-sm text-orange-600 mb-1">
                                    <i class="fas fa-clock mr-2"></i>
                                    <?php echo $shop['pending_applications']; ?> pending application(s)
                                </div>
                            <?php endif; ?>
                            <?php if ($shop['active_leases'] > 0): ?>
                                <div class="flex items-center text-sm text-green-600">
                                    <i class="fas fa-handshake mr-2"></i>
                                    <?php echo $shop['active_leases']; ?> active lease(s)
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex space-x-2">
                        <a href="view-shop.php?id=<?php echo $shop['id']; ?>" class="flex-1 btn-outline text-center text-sm py-2">
                            <i class="fas fa-eye mr-1"></i>View
                        </a>
                        <a href="edit-shop.php?id=<?php echo $shop['id']; ?>" class="flex-1 btn-primary text-center text-sm py-2">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                        
                        <!-- Status Update Form -->
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to change the status?')">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                            <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                <option value="available" <?php echo $shop['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="occupied" <?php echo $shop['status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                <option value="maintenance" <?php echo $shop['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="reserved" <?php echo $shop['status'] === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                            </select>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                           class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-white bg-blue-600' : 'text-gray-500 bg-white hover:bg-gray-50'; ?> border border-gray-300 rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest(`#${id}`) && !event.target.closest('button')) {
            dropdown.classList.add('hidden');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
