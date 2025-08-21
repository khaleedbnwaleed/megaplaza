<?php
/**
 * Application Management
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check authentication and role
if (!is_logged_in()) {
    redirect('auth/login.php');
}

check_role('manager'); // Requires manager or admin role

$current_user = get_current_user();
$page_title = 'Application Management';

$database = new Database();
$db = $database->getConnection();

$error_message = '';
$success_message = '';

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $application_id = (int)$_POST['application_id'];
            $status = sanitize_input($_POST['status']);
            $admin_notes = sanitize_input($_POST['admin_notes'] ?? '');
            
            $query = "UPDATE applications SET status = :status, admin_notes = :admin_notes, reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':admin_notes', $admin_notes);
            $stmt->bindParam(':reviewed_by', $current_user['id']);
            $stmt->bindParam(':id', $application_id);
            
            if ($stmt->execute()) {
                // Create notification for applicant
                $app_query = "SELECT user_id, shop_id FROM applications WHERE id = :id";
                $app_stmt = $db->prepare($app_query);
                $app_stmt->bindParam(':id', $application_id);
                $app_stmt->execute();
                $app_data = $app_stmt->fetch();
                
                if ($app_data) {
                    $notification_message = "Your application status has been updated to: " . ucfirst($status);
                    $notification_type = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'error' : 'info');
                    
                    $notification_query = "INSERT INTO notifications (user_id, title, message, type, category, created_at) 
                                          VALUES (:user_id, 'Application Update', :message, :type, 'application', NOW())";
                    $notification_stmt = $db->prepare($notification_query);
                    $notification_stmt->bindParam(':user_id', $app_data['user_id']);
                    $notification_stmt->bindParam(':message', $notification_message);
                    $notification_stmt->bindParam(':type', $notification_type);
                    $notification_stmt->execute();
                }
                
                $success_message = 'Application status updated successfully.';
            } else {
                $error_message = 'Failed to update application status.';
            }
        }
    }
}

// Get applications with pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

$where_conditions = [];
$params = [];

if (!empty($_GET['status'])) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $_GET['status'];
}

if (!empty($_GET['shop_id'])) {
    $where_conditions[] = "a.shop_id = :shop_id";
    $params[':shop_id'] = $_GET['shop_id'];
}

if (!empty($_GET['search'])) {
    $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR a.business_name LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM applications a 
                JOIN users u ON a.user_id = u.id 
                JOIN shops s ON a.shop_id = s.id 
                $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_applications = $count_stmt->fetch()['total'];
$total_pages = ceil($total_applications / ITEMS_PER_PAGE);

// Get applications
$query = "SELECT a.*, u.first_name, u.last_name, u.email, u.phone,
          s.shop_number, s.title as shop_title, s.monthly_rent,
          c.name as category_name,
          reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name
          FROM applications a 
          JOIN users u ON a.user_id = u.id 
          JOIN shops s ON a.shop_id = s.id 
          LEFT JOIN categories c ON s.category_id = c.id
          LEFT JOIN users reviewer ON a.reviewed_by = reviewer.id
          $where_clause
          ORDER BY a.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll();

// Get shops for filter
$shops = $db->query("SELECT id, shop_number, title FROM shops ORDER BY shop_number")->fetchAll();

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
                            <p class="text-xs text-gray-500">Application Management</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users mr-1"></i>Users
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading text-gray-900 mb-2">Application Management</h1>
            <p class="text-gray-600">Review and manage shop rental applications</p>
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

        <!-- Stats -->
        <?php
        $status_counts = $db->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
        ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['pending'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-eye text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Under Review</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['under_review'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Approved</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['approved'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Rejected</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $status_counts['rejected'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        class="form-input" 
                        placeholder="Applicant or business name"
                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="form-input">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_review" <?php echo ($_GET['status'] ?? '') === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Shop</label>
                    <select name="shop_id" class="form-input">
                        <option value="">All Shops</option>
                        <?php foreach ($shops as $shop): ?>
                            <option value="<?php echo $shop['id']; ?>" <?php echo ($_GET['shop_id'] ?? '') == $shop['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($shop['shop_number'] . ' - ' . $shop['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                    <a href="applications.php" class="btn-outline">Clear</a>
                </div>
            </form>
        </div>

        <!-- Applications List -->
        <div class="space-y-6">
            <?php foreach ($applications as $application): ?>
                <div class="card">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-heading text-gray-900 mb-1">
                                <?php echo htmlspecialchars($application['business_name']); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                by <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                • <?php echo format_date($application['created_at']); ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php 
                            switch($application['status']) {
                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'under_review': echo 'bg-blue-100 text-blue-800'; break;
                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Application Details -->
                        <div class="lg:col-span-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Shop</p>
                                    <p class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($application['shop_number']); ?> - <?php echo htmlspecialchars($application['shop_title']); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Monthly Rent</p>
                                    <p class="text-sm text-gray-900"><?php echo format_currency($application['monthly_rent']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Business Type</p>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['business_type']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Move-in Date</p>
                                    <p class="text-sm text-gray-900"><?php echo format_date($application['move_in_date']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Lease Duration</p>
                                    <p class="text-sm text-gray-900"><?php echo $application['lease_duration']; ?> months</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Years in Business</p>
                                    <p class="text-sm text-gray-900"><?php echo $application['years_in_business']; ?> years</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-500 mb-2">Business Description</p>
                                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($application['business_description']); ?></p>
                            </div>

                            <?php if ($application['special_requests']): ?>
                                <div class="mb-4">
                                    <p class="text-sm font-medium text-gray-500 mb-2">Special Requests</p>
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($application['special_requests']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($application['admin_notes']): ?>
                                <div class="border-t border-gray-200 pt-4">
                                    <p class="text-sm font-medium text-gray-500 mb-2">Admin Notes</p>
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($application['admin_notes']); ?></p>
                                    <?php if ($application['reviewer_first_name']): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Reviewed by <?php echo htmlspecialchars($application['reviewer_first_name'] . ' ' . $application['reviewer_last_name']); ?>
                                            on <?php echo format_date($application['reviewed_at']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">Review Application</h4>
                                
                                <form method="POST" data-ajax>
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                            <select name="status" class="form-input text-sm" required>
                                                <option value="pending" <?php echo $application['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="under_review" <?php echo $application['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                                <option value="approved" <?php echo $application['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                                            <textarea 
                                                name="admin_notes" 
                                                rows="3" 
                                                class="form-input text-sm"
                                                placeholder="Add notes about this application..."
                                            ><?php echo htmlspecialchars($application['admin_notes']); ?></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn-primary w-full text-sm">
                                            <i class="fas fa-save mr-2"></i>Update Application
                                        </button>
                                    </div>
                                </form>

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="space-y-2">
                                        <a href="view-application.php?id=<?php echo $application['id']; ?>" class="btn-outline w-full text-center text-sm">
                                            <i class="fas fa-eye mr-2"></i>View Details
                                        </a>
                                        <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>" class="btn-outline w-full text-center text-sm">
                                            <i class="fas fa-envelope mr-2"></i>Contact Applicant
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-8">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>

        <?php if (empty($applications)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No applications found</h3>
                <p class="text-gray-500">No applications match your current filters.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// AJAX form submission for status updates
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-ajax]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const button = form.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reload the page to show updated status
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.innerHTML = originalText;
                alert('An error occurred while updating the application.');
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
