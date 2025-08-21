<?php
/**
 * Main Dashboard
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

// Check session timeout
check_session_timeout();

$current_user = get_current_user();
$page_title = 'Dashboard';

// Get dashboard stats based on user role
$database = new Database();
$db = $database->getConnection();

$stats = [];

if ($current_user['role'] === 'admin' || $current_user['role'] === 'manager') {
    // Admin/Manager stats
    $stats['total_shops'] = $db->query("SELECT COUNT(*) as count FROM shops")->fetch()['count'];
    $stats['available_shops'] = $db->query("SELECT COUNT(*) as count FROM shops WHERE status = 'available'")->fetch()['count'];
    $stats['total_tenants'] = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'tenant'")->fetch()['count'];
    $stats['pending_applications'] = $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'")->fetch()['count'];
    $stats['active_leases'] = $db->query("SELECT COUNT(*) as count FROM leases WHERE status = 'active'")->fetch()['count'];
    $stats['overdue_payments'] = $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'overdue'")->fetch()['count'];
} else {
    // Tenant stats
    $user_id = $current_user['id'];
    $stats['my_applications'] = $db->query("SELECT COUNT(*) as count FROM applications WHERE user_id = $user_id")->fetch()['count'];
    $stats['active_leases'] = $db->query("SELECT COUNT(*) as count FROM leases WHERE user_id = $user_id AND status = 'active'")->fetch()['count'];
    $stats['pending_payments'] = $db->query("SELECT COUNT(*) as count FROM payments WHERE user_id = $user_id AND status = 'pending'")->fetch()['count'];
    $stats['maintenance_requests'] = $db->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE user_id = $user_id")->fetch()['count'];
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-building text-2xl" style="color: var(--primary);"></i>
                    <div>
                        <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                        <p class="text-xs text-gray-500">Dashboard</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        Welcome, <?php echo htmlspecialchars($current_user['first_name']); ?>
                    </span>
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-900" onclick="toggleDropdown('user-menu')">
                            <i class="fas fa-user-circle text-xl"></i>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
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
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading text-gray-900 mb-2">
                Welcome back, <?php echo htmlspecialchars($current_user['first_name']); ?>!
            </h1>
            <p class="text-gray-600">
                <?php if ($current_user['role'] === 'admin'): ?>
                    Manage your plaza operations from this admin dashboard.
                <?php elseif ($current_user['role'] === 'manager'): ?>
                    Monitor and manage plaza activities from this manager dashboard.
                <?php else: ?>
                    Manage your shop rentals and applications from this tenant dashboard.
                <?php endif; ?>
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'manager'): ?>
                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-building text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Shops</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_shops']; ?></p>
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
                            <p class="text-sm font-medium text-gray-500">Available</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['available_shops']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Tenants</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_tenants']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-orange-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pending Apps</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_applications']; ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">My Applications</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['my_applications']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-handshake text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Active Leases</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['active_leases']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-credit-card text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pending Payments</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_payments']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tools text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Maintenance</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['maintenance_requests']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'manager'): ?>
                        <a href="../shops/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-building text-blue-600 mr-3"></i>
                            <span class="font-medium">Manage Shops</span>
                        </a>
                        <a href="../applications/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-file-alt text-green-600 mr-3"></i>
                            <span class="font-medium">Review Applications</span>
                        </a>
                        <a href="../payments/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-credit-card text-purple-600 mr-3"></i>
                            <span class="font-medium">Manage Payments</span>
                        </a>
                    <?php else: ?>
                        <a href="../shops/browse.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-search text-blue-600 mr-3"></i>
                            <span class="font-medium">Browse Available Shops</span>
                        </a>
                        <a href="../applications/my-applications.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-file-alt text-green-600 mr-3"></i>
                            <span class="font-medium">My Applications</span>
                        </a>
                        <!-- Updated payment link to correct billing page -->
                        <a href="../billing/index.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-credit-card text-purple-600 mr-3"></i>
                            <span class="font-medium">My Payments & Billing</span>
                        </a>
                        <!-- Added direct link to upload payment receipts -->
                        <a href="../billing/upload-payment.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-upload text-orange-600 mr-3"></i>
                            <span class="font-medium">Upload Payment Receipt</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Recent Activity</h3>
                <div class="space-y-3">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-sm">Welcome to the system!</p>
                            <p class="text-xs text-gray-500">Get started by exploring the features</p>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-sm">Account verified</p>
                            <p class="text-xs text-gray-500">Your account is ready to use</p>
                        </div>
                    </div>
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
