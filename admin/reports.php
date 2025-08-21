<?php
/**
 * Reports and Analytics
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check authentication and role
if (!is_logged_in()) {
    redirect('auth/login.php');
}

check_role('manager'); // Requires manager or admin role

$current_user = get_current_user();
$page_title = 'Reports & Analytics';

$database = new Database();
$db = $database->getConnection();

// Date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Revenue Analytics
$revenue_query = "SELECT 
    DATE(p.payment_date) as date,
    SUM(p.amount) as daily_revenue,
    COUNT(p.id) as payment_count
    FROM payments p 
    WHERE p.payment_date BETWEEN :start_date AND :end_date 
    AND p.status = 'completed'
    GROUP BY DATE(p.payment_date)
    ORDER BY date";

$revenue_stmt = $db->prepare($revenue_query);
$revenue_stmt->bindParam(':start_date', $start_date);
$revenue_stmt->bindParam(':end_date', $end_date);
$revenue_stmt->execute();
$revenue_data = $revenue_stmt->fetchAll();

// Occupancy Analytics
$occupancy_query = "SELECT 
    s.status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM shops), 2) as percentage
    FROM shops s
    GROUP BY s.status";

$occupancy_data = $db->query($occupancy_query)->fetchAll();

// Application Analytics
$application_stats = $db->query("SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM applications), 2) as percentage
    FROM applications 
    GROUP BY status")->fetchAll();

// Top Categories
$category_stats = $db->query("SELECT 
    c.name,
    COUNT(s.id) as shop_count,
    AVG(s.monthly_rent) as avg_rent
    FROM categories c
    LEFT JOIN shops s ON c.id = s.category_id
    GROUP BY c.id, c.name
    ORDER BY shop_count DESC")->fetchAll();

// Monthly Revenue Trend (Last 12 months)
$monthly_revenue = $db->query("SELECT 
    DATE_FORMAT(payment_date, '%Y-%m') as month,
    SUM(amount) as revenue,
    COUNT(*) as payment_count
    FROM payments 
    WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    AND status = 'completed'
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month")->fetchAll();

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="dashboard.php" class="flex items-center space-x-3">
                        <i class="fas fa-chart-bar text-2xl" style="color: var(--primary);"></i>
                        <div>
                            <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                            <p class="text-xs text-gray-500">Reports & Analytics</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <a href="applications.php" class="nav-link">
                        <i class="fas fa-file-alt mr-1"></i>Applications
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading text-gray-900 mb-2">Reports & Analytics</h1>
            <p class="text-gray-600">Comprehensive insights into your business performance</p>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-8">
            <form method="GET" class="flex items-end space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" class="form-input" value="<?php echo $start_date; ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" class="form-input" value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter mr-2"></i>Update Report
                </button>
            </form>
        </div>

        <!-- Revenue Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Daily Revenue Chart -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Daily Revenue Trend</h3>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>

            <!-- Monthly Revenue Chart -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Monthly Revenue (Last 12 Months)</h3>
                <canvas id="monthlyChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Occupancy Analytics -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Shop Occupancy</h3>
                <div class="space-y-4">
                    <?php foreach ($occupancy_data as $occupancy): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3 
                                    <?php 
                                    switch($occupancy['status']) {
                                        case 'available': echo 'bg-green-500'; break;
                                        case 'occupied': echo 'bg-blue-500'; break;
                                        case 'maintenance': echo 'bg-yellow-500'; break;
                                        case 'reserved': echo 'bg-purple-500'; break;
                                        default: echo 'bg-gray-500';
                                    }
                                    ?>">
                                </div>
                                <span class="text-sm font-medium text-gray-900 capitalize">
                                    <?php echo str_replace('_', ' ', $occupancy['status']); ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-gray-900"><?php echo $occupancy['count']; ?></span>
                                <span class="text-xs text-gray-500 ml-1">(<?php echo $occupancy['percentage']; ?>%)</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full 
                                <?php 
                                switch($occupancy['status']) {
                                    case 'available': echo 'bg-green-500'; break;
                                    case 'occupied': echo 'bg-blue-500'; break;
                                    case 'maintenance': echo 'bg-yellow-500'; break;
                                    case 'reserved': echo 'bg-purple-500'; break;
                                    default: echo 'bg-gray-500';
                                }
                                ?>" 
                                style="width: <?php echo $occupancy['percentage']; ?>%">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Application Status -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-4">Application Status</h3>
                <div class="space-y-4">
                    <?php foreach ($application_stats as $stat): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3 
                                    <?php 
                                    switch($stat['status']) {
                                        case 'pending': echo 'bg-yellow-500'; break;
                                        case 'under_review': echo 'bg-blue-500'; break;
                                        case 'approved': echo 'bg-green-500'; break;
                                        case 'rejected': echo 'bg-red-500'; break;
                                        default: echo 'bg-gray-500';
                                    }
                                    ?>">
                                </div>
                                <span class="text-sm font-medium text-gray-900 capitalize">
                                    <?php echo str_replace('_', ' ', $stat['status']); ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-gray-900"><?php echo $stat['count']; ?></span>
                                <span class="text-xs text-gray-500 ml-1">(<?php echo $stat['percentage']; ?>%)</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full 
                                <?php 
                                switch($stat['status']) {
                                    case 'pending': echo 'bg-yellow-500'; break;
                                    case 'under_review': echo 'bg-blue-500'; break;
                                    case 'approved': echo 'bg-green-500'; break;
                                    case 'rejected': echo 'bg-red-500'; break;
                                    default: echo 'bg-gray-500';
                                }
                                ?>" 
                                style="width: <?php echo $stat['percentage']; ?>%">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="card mb-8">
            <h3 class="text-lg font-heading text-gray-900 mb-4">Category Performance</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop Count</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Rent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($category_stats as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $category['shop_count']; ?> shops
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $category['avg_rent'] ? format_currency($category['avg_rent']) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min(100, ($category['shop_count'] / 10) * 100); ?>%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo min(100, round(($category['shop_count'] / 10) * 100)); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Options -->
        <div class="card">
            <h3 class="text-lg font-heading text-gray-900 mb-4">Export Reports</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="exportReport('revenue')" class="btn-outline">
                    <i class="fas fa-download mr-2"></i>Export Revenue Report
                </button>
                <button onclick="exportReport('occupancy')" class="btn-outline">
                    <i class="fas fa-download mr-2"></i>Export Occupancy Report
                </button>
                <button onclick="exportReport('applications')" class="btn-outline">
                    <i class="fas fa-download mr-2"></i>Export Application Report
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($revenue_data, 'date')); ?>,
        datasets: [{
            label: 'Daily Revenue',
            data: <?php echo json_encode(array_column($revenue_data, 'daily_revenue')); ?>,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Monthly Revenue Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>,
        datasets: [{
            label: 'Monthly Revenue',
            data: <?php echo json_encode(array_column($monthly_revenue, 'revenue')); ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Export functionality
function exportReport(type) {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    
    // Create a simple CSV export
    let csvContent = '';
    let filename = '';
    
    switch(type) {
        case 'revenue':
            csvContent = 'Date,Revenue,Payment Count\n';
            <?php foreach ($revenue_data as $row): ?>
                csvContent += '<?php echo $row["date"]; ?>,<?php echo $row["daily_revenue"]; ?>,<?php echo $row["payment_count"]; ?>\n';
            <?php endforeach; ?>
            filename = 'revenue_report_' + startDate + '_to_' + endDate + '.csv';
            break;
            
        case 'occupancy':
            csvContent = 'Status,Count,Percentage\n';
            <?php foreach ($occupancy_data as $row): ?>
                csvContent += '<?php echo $row["status"]; ?>,<?php echo $row["count"]; ?>,<?php echo $row["percentage"]; ?>\n';
            <?php endforeach; ?>
            filename = 'occupancy_report_' + new Date().toISOString().split('T')[0] + '.csv';
            break;
            
        case 'applications':
            csvContent = 'Status,Count,Percentage\n';
            <?php foreach ($application_stats as $row): ?>
                csvContent += '<?php echo $row["status"]; ?>,<?php echo $row["count"]; ?>,<?php echo $row["percentage"]; ?>\n';
            <?php endforeach; ?>
            filename = 'application_report_' + new Date().toISOString().split('T')[0] + '.csv';
            break;
    }
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>

<?php include '../includes/footer.php'; ?>
