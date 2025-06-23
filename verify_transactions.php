
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
requireAnyRole(['admin', 'staff']);

// Check if user is from Audit/Inspections department
if (!hasDepartment('Audit/Inspections')) {
    redirect('unauthorized.php');
}

// Use session data directly instead of database queries
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];

// Initialize objects
$transactionModel = new Transaction();

// Process verify/reject actions
$success_msg = $error_msg = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $id = intval($_GET['id']);
    
    if ($id > 0) {
        $transaction = $transactionModel->getTransactionById($id);
        
        if ($action === 'verify' && $transaction && $transaction['approval_status'] === 'approved' && $transaction['verification_status'] === 'pending') {
            $result = $transactionModel->verifyTransaction($id, $userId, $userName);
            if ($result) {
                $success_msg = "Transaction verified successfully!";
            } else {
                $error_msg = "Error verifying transaction. Please try again.";
            }
        } elseif ($action === 'reject' && $transaction && $transaction['verification_status'] === 'pending') {
            $result = $transactionModel->rejectTransaction($id, 'audit', $userId, $userName);
            if ($result) {
                $success_msg = "Transaction rejected successfully.";
            } else {
                $error_msg = "Error rejecting transaction. Please try again.";
            }
        } else {
            $error_msg = "Invalid transaction or transaction is not in a state that can be verified/rejected.";
        }
    }
}

// Get pending transactions for audit verification
$pendingTransactions = $transactionModel->getPendingTransactionsForAuditVerification();
$transactionStats = $transactionModel->getTransactionStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Dashboard - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">Income ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">Audit Dashboard</h1>
                        <p class="text-sm text-gray-500">Transaction Verification & Audit Management</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($userName) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($department) ?></div>
                    </div>
                    <div class="relative">
                        <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                <?= strtoupper($userName[0]) ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <a href="transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-exchange-alt mr-2"></i> All Transactions
                            </a>
                            <div class="border-t my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?= htmlspecialchars($success_msg) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending Verification</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($pendingTransactions) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Today's Transactions</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $transactionStats['today']['count'] ?? 0 ?></p>
                        <p class="text-sm text-gray-500"><?= formatCurrency($transactionStats['today']['total'] ?? 0) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-week text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">This Week</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $transactionStats['week']['count'] ?? 0 ?></p>
                        <p class="text-sm text-gray-500"><?= formatCurrency($transactionStats['week']['total'] ?? 0) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">This Month</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $transactionStats['month']['count'] ?? 0 ?></p>
                        <p class="text-sm text-gray-500"><?= formatCurrency($transactionStats['month']['total'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="transactions.php" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-exchange-alt text-white"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">View All Transactions</p>
                            <p class="text-sm text-gray-500">Browse complete transaction history</p>
                        </div>
                    </a>

                    <a href="dashboard.php" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-tachometer-alt text-white"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Main Dashboard</p>
                            <p class="text-sm text-gray-500">Return to main dashboard</p>
                        </div>
                    </a>

                    <button onclick="refreshPage()" class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                        <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-sync-alt text-white"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Refresh Data</p>
                            <p class="text-sm text-gray-500">Update pending transactions</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pending Verifications Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Pending Transaction Verifications</h2>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            <?= count($pendingTransactions) ?> Pending
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <?php if (!empty($pendingTransactions)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="transactionsTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($pendingTransactions as $transaction): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($transaction['receipt_no']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= formatDate($transaction['date_of_payment']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($transaction['customer_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                            <?= formatCurrency($transaction['amount_paid']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($transaction['income_line']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($transaction['posting_officer_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($transaction['approving_acct_officer_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-2">
                                                <a href="view_transaction.php?id=<?= $transaction['id'] ?>" 
                                                   class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors">
                                                    <i class="fas fa-eye mr-1"></i> View
                                                </a>
                                                <a href="verify_transactions.php?action=verify&id=<?= $transaction['id'] ?>" 
                                                   onclick="return confirm('Are you sure you want to verify this transaction?')"
                                                   class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors">
                                                    <i class="fas fa-check mr-1"></i> Verify
                                                </a>
                                                <a href="verify_transactions.php?action=reject&id=<?= $transaction['id'] ?>" 
                                                   onclick="return confirm('Are you sure you want to reject this transaction?')"
                                                   class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors">
                                                    <i class="fas fa-times mr-1"></i> Reject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">All Caught Up!</h3>
                        <p class="text-gray-500">No pending transactions for verification at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[1, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7] }
                ]
            });
        });

        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick) {
                dropdown.classList.add('hidden');
            }
        });

        // Refresh page
        function refreshPage() {
            window.location.reload();
        }

        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(alert => {
                if (alert.textContent.includes('successfully') || alert.textContent.includes('Error')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>
