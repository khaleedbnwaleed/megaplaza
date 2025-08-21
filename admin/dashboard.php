<?php
/**
 * Admin Dashboard
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check authentication and admin role
if (!is_logged_in()) {
    redirect('auth/login.php');
}

check_role('admin');

$current_user = get_current_user();
$page_title = 'Admin Dashboard';

$database = new Database();
$db = $database->getConnection();

// Get comprehensive statistics
$stats = [];

// Basic counts
$stats['total_users'] = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$stats['total_tenants'] = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'tenant'")->fetch()['count'];
$stats['total_managers'] = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'manager'")->fetch()['count'];
$stats['total_shops'] = $db->query("SELECT COUNT(*) as count FROM shops")->fetch()['count'];
$stats['available_shops'] = $db->query("SELECT COUNT(*) as count FROM shops WHERE status = 'available'")->fetch()['count'];
$stats['occupied_shops'] = $db->query("SELECT COUNT(*) as count FROM shops WHERE status = 'occupied'")->fetch()['count'];
$stats['maintenance_shops'] = $db->query("SELECT COUNT(*) as count FROM shops WHERE status = 'maintenance'")->fetch()['count'];

// Application statistics
$stats['total_applications'] = $db->query("SELECT COUNT(*) as count FROM applications")->fetch()['count'];
$stats['pending_applications'] = $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'")->fetch()['count'];
$stats['approved_applications'] = $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'approved'")->fetch()['count'];
$stats['rejected_applications'] = $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'rejected'")->fetch()['count'];

// Lease statistics
$stats['active_leases'] = $db->query("SELECT COUNT(*) as count FROM leases WHERE status = 'active'")->fetch()['count'];
$stats['expired_leases'] = $db->query("SELECT COUNT(*) as count FROM leases WHERE status = 'expired'")->fetch()['count'];

// Payment statistics
$stats['total_payments'] = $db->query("SELECT COUNT(*) as count FROM payments")->fetch()['count'];
$stats['pending_payments'] = $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch()['count'];
$stats['overdue_payments'] = $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'overdue'")->fetch()['count'];
$stats['paid_payments'] = $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'paid'")->fetch()['count'];

// Revenue statistics
$revenue_stats = $db->query("SELECT 
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_revenue,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_revenue,
    SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue_revenue
    FROM payments")->fetch();

$stats['total_revenue'] = $revenue_stats['total_revenue'] ?? 0;
$stats['pending_revenue'] = $revenue_stats['pending_revenue'] ?? 0;
$stats['overdue_revenue'] = $revenue_stats['overdue_revenue'] ?? 0;

// Maintenance requests
$stats['maintenance_requests'] = $db->query("SELECT COUNT(*) as count FROM maintenance_requests")->fetch()['count'];
$stats['open_maintenance'] = $db->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'open'")->fetch()['count'];

// Recent activities
$recent_applications = $db->query("SELECT a.*, u.first_name, u.last_name, s.shop_number, s.title as shop_title 
                                  FROM applications a 
                                  JOIN users u ON a.user_id = u.id 
                                  JOIN shops s ON a.shop_id = s.id 
                                  ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

$recent_payments = $db->query("SELECT p.*, u.first_name, u.last_name, s.shop_number 
                              FROM payments p 
                              JOIN users u ON p.user_id = u.id 
                              JOIN leases l ON p.lease_id = l.id 
                              JOIN shops s ON l.shop_id = s.id 
                              ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// Monthly revenue chart data (last 12 months)
$monthly_revenue = $db->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as revenue
    FROM payments 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC")->fetchAll();

// Shop occupancy by category
$category_occupancy = $db->query("SELECT 
    c.name as category,
    COUNT(s.id) as total_shops,
    SUM(CASE WHEN s.status = 'occupied' THEN 1 ELSE 0 END) as occupied_shops
    FROM categories c
    LEFT JOIN shops s ON c.id = s.category_id
    WHERE c.status = 'active'
    GROUP BY c.id, c.name
    ORDER BY c.sort_order")->fetchAll();

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-2xl" style="color: var(--primary);"></i>
                    <div>
                        <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                        <p class="text-xs text-gray-500">Admin Dashboard</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users mr-1"></i>Users
                        </a>
                        <a href="../shops/index.php" class="nav-link">
                            <i class="fas fa-building mr-1"></i>Shops
                        </a>
                        <a href="applications.php" class="nav-link">
                            <i class="fas fa-file-alt mr-1"></i>Applications
                        </a>
                        <a href="verify-payments.php" class="nav-link">
                            <i class="fas fa-receipt mr-1"></i>Verify Payments
                        </a>
                        <a href="reports.php" class="nav-link">
                            <i class="fas fa-chart-bar mr-1"></i>Reports
                        </a>
                    </div>
                    
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-900" onclick="toggleDropdown('user-menu')">
                            <span><?php echo htmlspecialchars($current_user['first_name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="../dashboard/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i>Main Dashboard
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                            <div class="border-t border-gray-100"></div>
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
            <h1 class="text-3xl font-heading text-gray-900 mb-2">Admin Dashboard</h1>
            <p class="text-gray-600">Complete overview of your plaza management system</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Users -->
            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $stats['total_tenants']; ?> tenants, <?php echo $stats['total_managers']; ?> managers</p>
                    </div>
                </div>
            </div>

            <!-- Shops -->
            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Shop Occupancy</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['occupied_shops']; ?>/<?php echo $stats['total_shops']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo round(($stats['occupied_shops'] / max($stats['total_shops'], 1)) * 100, 1); ?>% occupied</p>
                    </div>
                </div>
            </div>

            <!-- Applications -->
            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending Applications</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_applications']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $stats['total_applications']; ?> total applications</p>
                    </div>
                </div>
            </div>

            <!-- Revenue -->
            <div class="card">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo format_currency($stats['total_revenue']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo format_currency($stats['pending_revenue']); ?> pending</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Stats Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Shop Status Breakdown -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Shop Status</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Available</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['available_shops']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Occupied</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['occupied_shops']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Maintenance</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['maintenance_shops']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Application Status -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Applications</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-orange-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Pending</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['pending_applications']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Approved</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['approved_applications']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Rejected</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['rejected_applications']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Status -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Payments</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Paid</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['paid_payments']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Pending</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['pending_payments']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Overdue</span>
                        </div>
                        <span class="font-medium"><?php echo $stats['overdue_payments']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Monthly Revenue Chart -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Monthly Revenue (Last 12 Months)</h3>
                <div class="h-64 flex items-end justify-between space-x-2">
                    <?php 
                    $max_revenue = 0;
                    foreach ($monthly_revenue as $month_data) {
                        $max_revenue = max($max_revenue, $month_data['revenue']);
                    }
                    $max_revenue = max($max_revenue, 1); // Avoid division by zero
                    
                    foreach ($monthly_revenue as $month_data): 
                        $height = ($month_data['revenue'] / $max_revenue) * 100;
                    ?>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-blue-500 rounded-t" style="height: <?php echo $height; ?>%; min-height: 4px;" title="<?php echo format_currency($month_data['revenue']); ?>"></div>
                            <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-left"><?php echo date('M Y', strtotime($month_data['month'] . '-01')); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Occupancy -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Occupancy by Category</h3>
                <div class="space-y-4">
                    <?php foreach ($category_occupancy as $category): 
                        $occupancy_rate = $category['total_shops'] > 0 ? ($category['occupied_shops'] / $category['total_shops']) * 100 : 0;
                    ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600"><?php echo htmlspecialchars($category['category']); ?></span>
                                <span class="font-medium"><?php echo $category['occupied_shops']; ?>/<?php echo $category['total_shops']; ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $occupancy_rate; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Applications -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-heading text-gray-900">Recent Applications</h3>
                    <a href="applications.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recent_applications as $application): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-sm text-gray-900">
                                    <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    Shop <?php echo htmlspecialchars($application['shop_number']); ?> • <?php echo format_date($application['created_at']); ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php 
                                switch($application['status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'approved': echo 'bg-green-100 text-green-800'; break;
                                    case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-heading text-gray-900">Recent Payments</h3>
                    <a href="../payments/index.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-sm text-gray-900">
                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    Shop <?php echo htmlspecialchars($payment['shop_number']); ?> • <?php echo format_currency($payment['amount']); ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php 
                                switch($payment['status']) {
                                    case 'paid': echo 'bg-green-100 text-green-800'; break;
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="users.php?action=add" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-user-plus text-2xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Add User</span>
                    </a>
                    <a href="../shops/add-shop.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-building text-2xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Add Shop</span>
                    </a>
                    <a href="applications.php?status=pending" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-file-alt text-2xl text-orange-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Review Applications</span>
                    </a>
                    <a href="reports.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-chart-bar text-2xl text-purple-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Generate Reports</span>
                    </a>
                </div>
            </div>
        </div>
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
