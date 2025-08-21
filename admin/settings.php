<?php
/**
 * System Settings
 * Mega School Plaza Management System
 */

require_once '../config/config.php';

// Check authentication and role
if (!is_logged_in()) {
    redirect('auth/login.php');
}

check_role('admin'); // Only admins can access settings

$current_user = get_current_user();
$page_title = 'System Settings';

$database = new Database();
$db = $database->getConnection();

$error_message = '';
$success_message = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_settings') {
            $settings = [
                'app_name' => sanitize_input($_POST['app_name']),
                'app_description' => sanitize_input($_POST['app_description']),
                'contact_email' => sanitize_input($_POST['contact_email']),
                'contact_phone' => sanitize_input($_POST['contact_phone']),
                'address' => sanitize_input($_POST['address']),
                'default_lease_duration' => (int)$_POST['default_lease_duration'],
                'late_fee_percentage' => (float)$_POST['late_fee_percentage'],
                'security_deposit_months' => (int)$_POST['security_deposit_months'],
                'maintenance_email' => sanitize_input($_POST['maintenance_email']),
                'auto_approve_applications' => isset($_POST['auto_approve_applications']) ? 1 : 0,
                'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0
            ];
            
            $updated = 0;
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO settings (setting_key, setting_value, updated_at) 
                         VALUES (:key, :value, NOW()) 
                         ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW()";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':key', $key);
                $stmt->bindParam(':value', $value);
                if ($stmt->execute()) {
                    $updated++;
                }
            }
            
            if ($updated > 0) {
                $success_message = 'Settings updated successfully.';
            } else {
                $error_message = 'Failed to update settings.';
            }
        }
    }
}

// Get current settings
$settings_query = "SELECT setting_key, setting_value FROM settings";
$settings_result = $db->query($settings_query)->fetchAll(PDO::FETCH_KEY_PAIR);

// Default values
$defaults = [
    'app_name' => 'Mega School Plaza',
    'app_description' => 'Professional Shop Rental Management System',
    'contact_email' => 'info@megaschoolplaza.com',
    'contact_phone' => '+1 (555) 123-4567',
    'address' => '123 Business District, City, State 12345',
    'default_lease_duration' => 12,
    'late_fee_percentage' => 5.0,
    'security_deposit_months' => 2,
    'maintenance_email' => 'maintenance@megaschoolplaza.com',
    'auto_approve_applications' => 0,
    'email_notifications' => 1,
    'sms_notifications' => 0
];

// Merge with current settings
$current_settings = array_merge($defaults, $settings_result);

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="dashboard.php" class="flex items-center space-x-3">
                        <i class="fas fa-cog text-2xl" style="color: var(--primary);"></i>
                        <div>
                            <h1 class="text-xl font-heading text-gray-900"><?php echo APP_NAME; ?></h1>
                            <p class="text-xs text-gray-500">System Settings</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar mr-1"></i>Reports
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading text-gray-900 mb-2">System Settings</h1>
            <p class="text-gray-600">Configure your shop management system</p>
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

        <form method="POST" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="update_settings">

            <!-- General Settings -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-6">General Settings</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Application Name</label>
                        <input 
                            type="text" 
                            name="app_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($current_settings['app_name']); ?>"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                        <input 
                            type="email" 
                            name="contact_email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($current_settings['contact_email']); ?>"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                        <input 
                            type="tel" 
                            name="contact_phone" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($current_settings['contact_phone']); ?>"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maintenance Email</label>
                        <input 
                            type="email" 
                            name="maintenance_email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($current_settings['maintenance_email']); ?>"
                        >
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Application Description</label>
                    <textarea 
                        name="app_description" 
                        rows="3" 
                        class="form-input"
                        placeholder="Brief description of your business"
                    ><?php echo htmlspecialchars($current_settings['app_description']); ?></textarea>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                    <textarea 
                        name="address" 
                        rows="3" 
                        class="form-input"
                        placeholder="Full business address"
                    ><?php echo htmlspecialchars($current_settings['address']); ?></textarea>
                </div>
            </div>

            <!-- Lease Settings -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-6">Lease Settings</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Lease Duration (months)</label>
                        <input 
                            type="number" 
                            name="default_lease_duration" 
                            class="form-input" 
                            value="<?php echo $current_settings['default_lease_duration']; ?>"
                            min="1" 
                            max="60"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Late Fee Percentage (%)</label>
                        <input 
                            type="number" 
                            name="late_fee_percentage" 
                            class="form-input" 
                            value="<?php echo $current_settings['late_fee_percentage']; ?>"
                            min="0" 
                            max="50" 
                            step="0.1"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Security Deposit (months)</label>
                        <input 
                            type="number" 
                            name="security_deposit_months" 
                            class="form-input" 
                            value="<?php echo $current_settings['security_deposit_months']; ?>"
                            min="0" 
                            max="6"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- Application Settings -->
            <div class="card">
                <h3 class="text-lg font-heading text-gray-900 mb-6">Application Settings</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="auto_approve_applications" 
                            id="auto_approve_applications" 
                            class="form-checkbox"
                            <?php echo $current_settings['auto_approve_applications'] ? 'checked' : ''; ?>
                        >
                        <label for="auto_approve_applications" class="ml-2 text-sm text-gray-700">
                            Auto-approve applications (not recommended)
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="email_notifications" 
                            id="email_notifications" 
                            class="form-checkbox"
                            <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?>
                        >
                        <label for="email_notifications" class="ml-2 text-sm text-gray-700">
                            Send email notifications
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="sms_notifications" 
                            id="sms_notifications" 
                            class="form-checkbox"
                            <?php echo $current_settings['sms_notifications'] ? 'checked' : ''; ?>
                        >
                        <label for="sms_notifications" class="ml-2 text-sm text-gray-700">
                            Send SMS notifications (requires SMS service)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
