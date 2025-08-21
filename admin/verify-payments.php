<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin or manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['super_admin', 'manager'])) {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance()->getConnection();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payment_id = intval($_POST['payment_id']);
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if (in_array($action, ['verified', 'rejected'])) {
        $stmt = $db->prepare("
            UPDATE payments 
            SET verification_status = ?, verified_by = ?, verified_at = NOW(), verification_notes = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$action, $_SESSION['user_id'], $notes, $payment_id])) {
            // If verified, update invoice status if fully paid
            if ($action === 'verified') {
                $stmt = $db->prepare("
                    SELECT i.id, i.amount, COALESCE(SUM(p.amount), 0) as total_paid
                    FROM payments p1
                    JOIN invoices i ON p1.invoice_id = i.id
                    LEFT JOIN payments p ON i.id = p.invoice_id AND p.verification_status = 'verified'
                    WHERE p1.id = ?
                    GROUP BY i.id
                ");
                $stmt->execute([$payment_id]);
                $invoice_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($invoice_data && $invoice_data['total_paid'] >= $invoice_data['amount']) {
                    $stmt = $db->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
                    $stmt->execute([$invoice_data['id']]);
                }
            }
            
            $_SESSION['success'] = 'Payment ' . $action . ' successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update payment status.';
        }
    }
}

// Get pending payments
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

$where_conditions = ["p.verification_status = ?"];
$params = [$status_filter];

if ($search) {
    $where_conditions[] = "(i.invoice_number LIKE ? OR a.business_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$stmt = $db->prepare("
    SELECT p.*, i.invoice_number, i.amount as invoice_amount,
           s.name as shop_name, u.first_name, u.last_name, a.business_name,
           v.first_name as verified_by_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN leases l ON i.lease_id = l.id
    JOIN shops s ON l.shop_id = s.id
    JOIN users u ON p.created_by = u.id
    JOIN applications a ON l.application_id = a.id
    LEFT JOIN users v ON p.verified_by = v.id
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");

$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
$stmt = $db->prepare("
    SELECT verification_status, COUNT(*) as count 
    FROM payments 
    WHERE verification_status IN ('pending', 'verified', 'rejected')
    GROUP BY verification_status
");
$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$title = 'Verify Payments';
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Verification</h1>
        <p class="text-gray-600">Review and verify uploaded payment receipts</p>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Pending Verification</p>
                    <p class="text-2xl font-bold text-yellow-900"><?= $status_counts['pending'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Verified</p>
                    <p class="text-2xl font-bold text-green-900"><?= $status_counts['verified'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Rejected</p>
                    <p class="text-2xl font-bold text-red-900"><?= $status_counts['rejected'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="verified" <?= $status_filter === 'verified' ? 'selected' : '' ?>>Verified</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Invoice, business name, tenant..."
                       class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Filter
            </button>
        </form>
    </div>

    <!-- Payments List -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    Invoice: <?= htmlspecialchars($payment['invoice_number']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($payment['shop_name']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($payment['payment_date'])) ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= htmlspecialchars($payment['business_name']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                ₦<?= number_format($payment['amount'], 2) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= ucfirst(str_replace('_', ' ', $payment['method'])) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($payment['attachment']): ?>
                                <a href="<?= htmlspecialchars($payment['attachment']) ?>" target="_blank"
                                   class="text-blue-600 hover:text-blue-900 text-sm">
                                    View Receipt
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">No receipt</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'verified' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800'
                            ];
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_colors[$payment['verification_status']] ?>">
                                <?= ucfirst($payment['verification_status']) ?>
                            </span>
                            <?php if ($payment['verified_by_name']): ?>
                                <div class="text-xs text-gray-500 mt-1">
                                    by <?= htmlspecialchars($payment['verified_by_name']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($payment['verification_status'] === 'pending'): ?>
                                <button onclick="openVerificationModal(<?= $payment['id'] ?>, 'verified')" 
                                        class="text-green-600 hover:text-green-900 mr-3">
                                    Verify
                                </button>
                                <button onclick="openVerificationModal(<?= $payment['id'] ?>, 'rejected')" 
                                        class="text-red-600 hover:text-red-900">
                                    Reject
                                </button>
                            <?php else: ?>
                                <span class="text-gray-400">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div id="verificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Verify Payment</h3>
            <form method="POST">
                <input type="hidden" name="payment_id" id="modalPaymentId">
                <input type="hidden" name="action" id="modalAction">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" 
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Add verification notes (optional)"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeVerificationModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmitBtn"
                            class="px-4 py-2 rounded-md text-white">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVerificationModal(paymentId, action) {
    document.getElementById('modalPaymentId').value = paymentId;
    document.getElementById('modalAction').value = action;
    
    const modal = document.getElementById('verificationModal');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('modalSubmitBtn');
    
    if (action === 'verified') {
        title.textContent = 'Verify Payment';
        submitBtn.textContent = 'Verify';
        submitBtn.className = 'px-4 py-2 rounded-md text-white bg-green-600 hover:bg-green-700';
    } else {
        title.textContent = 'Reject Payment';
        submitBtn.textContent = 'Reject';
        submitBtn.className = 'px-4 py-2 rounded-md text-white bg-red-600 hover:bg-red-700';
    }
    
    modal.classList.remove('hidden');
}

function closeVerificationModal() {
    document.getElementById('verificationModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
