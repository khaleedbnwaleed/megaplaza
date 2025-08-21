<?php
/**
 * User Management
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check authentication and admin role
if (!is_logged_in()) {
    redirect('auth/login.php');
}

check_role('admin');

$current_user = get_current_user();
$page_title = 'User Management';

$database = new Database();
$db = $database->getConnection();

$error_message = '';
$success_message = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $user_id = (int)$_POST['user_id'];
            $status = sanitize_input($_POST['status']);
            
            if ($user_id !== $current_user['id']) { // Prevent self-modification
                $query = "UPDATE users SET status = :status WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = 'User status updated successfully.';
                } else {
                    $error_message = 'Failed to update user status.';
                }
            } else {
                $error_message = 'You cannot modify your own status.';
            }
        }
        
        if ($action === 'update_role') {
            $user_id = (int)$_POST['user_id'];
            $role = sanitize_input($_POST['role']);
            
            if ($user_id !== $current_user['id']) { // Prevent self-modification
                $query = "UPDATE users SET role = :role WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = 'User role updated successfully.';
                } else {
                    $error_message = 'Failed to update user role.';
                }
            } else {
                $error_message = 'You cannot modify your own role.';
            }
        }
    }
}

// Get users with pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

$where_conditions = [];
$params = [];

if (!empty($_GET['role'])) {
    $where_conditions[] = "role = :role";
    $params[':role'] = $_GET['role'];
}

if (!empty($_GET['status'])) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $where_conditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_users = $count_stmt->fetch()['total'];
$total_pages = ceil($total_users / ITEMS_PER_PAGE);

// Get users
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM applications a WHERE a.user_id = u.id) as application_count,
          (SELECT COUNT(*) FROM leases l WHERE l.user_id = u.id AND l.status = 'active') as active_lease_count
          FROM users u 
          $where_clause
          ORDER BY u.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="dashboard.php" class="flex items-center space-x-3">
                        <i class="fas fa-shield-alt text-2xl" style="color: var(--primary);"></i>
                        <div>
                            <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                            <p class="text-xs text-gray-500">User Management</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <a href="add-user.php" class="btn-primary">
                        <i class="fas fa-user-plus mr-2"></i>Add User
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading text-gray-900 mb-2">User Management</h1>
            <p class="text-gray-600">Manage all system users and their permissions</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
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
                        placeholder="Name or email"
                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" class="form-input">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo ($_GET['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo ($_GET['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="tenant" <?php echo ($_GET['role'] ?? '') === 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="form-input">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo ($_GET['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                    <a href="users.php" class="btn-outline">Clear</a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($user['role']) {
                                            case 'admin': echo 'bg-red-100 text-red-800'; break;
                                            case 'manager': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'tenant': echo 'bg-green-100 text-green-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($user['status']) {
                                            case 'active': echo 'bg-green-100 text-green-800'; break;
                                            case 'inactive': echo 'bg-gray-100 text-gray-800'; break;
                                            case 'suspended': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900">
                                        <?php if ($user['role'] === 'tenant'): ?>
                                            <?php echo $user['application_count']; ?> applications<br>
                                            <?php echo $user['active_lease_count']; ?> active leases
                                        <?php else: ?>
                                            <span class="text-gray-500">Staff member</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900"><?php echo format_date($user['created_at']); ?></div>
                                    <?php if ($user['last_login']): ?>
                                        <div class="text-xs text-gray-500">Last: <?php echo format_date($user['last_login']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-2">
                                        <a href="view-user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($user['id'] !== $current_user['id']): ?>
                                            <!-- Status Update -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-1 py-1">
                                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </form>
                                            
                                            <!-- Role Update -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-1 py-1">
                                                    <option value="tenant" <?php echo $user['role'] === 'tenant' ? 'selected' : ''; ?>>Tenant</option>
                                                    <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-500">Current User</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-6">
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

<?php include '../includes/footer.php'; ?>
