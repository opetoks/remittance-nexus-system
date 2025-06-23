<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Use session data directly instead of database queries
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];
$userRole = $_SESSION['user_role'];

// Initialize objects
$transactionModel = new Transaction();
$db = new Database();

// Process approve/reject/flag actions
$success_msg = $error_msg = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $id = intval($_GET['id']);
    
    if ($id > 0) {
        $transaction = $transactionModel->getTransactionById($id);
        
        if ($transaction) {
            switch ($action) {
                case 'approve_post':
                    if (hasDepartment('Accounts') && $transaction['leasing_post_status'] === 'pending') {
                        $result = $transactionModel->approveLeasingPost($id, $userId, $userName);
                        if ($result) {
                            $success_msg = "Transaction post approved successfully!";
                        } else {
                            $error_msg = "Error approving transaction post. Please try again.";
                        }
                    }
                    break;
                    
                case 'approve_account':
                    if (hasDepartment('Accounts') && $transaction['approval_status'] === 'pending') {
                        $result = $transactionModel->approveTransaction($id, $userId, $userName);
                        if ($result) {
                            $success_msg = "Transaction approved successfully!";
                        } else {
                            $error_msg = "Error approving transaction. Please try again.";
                        }
                    }
                    break;
                    
                case 'verify':
                    if (hasDepartment('Audit/Inspections') && $transaction['verification_status'] === 'pending') {
                        $result = $transactionModel->verifyTransaction($id, $userId, $userName);
                        if ($result) {
                            $success_msg = "Transaction verified successfully!";
                        } else {
                            $error_msg = "Error verifying transaction. Please try again.";
                        }
                    }
                    break;
                    
                case 'reject_post':
                case 'reject_account':
                case 'reject_audit':
                    $stage = str_replace('reject_', '', $action);
                    if ($stage === 'post') $stage = 'leasing';
                    if ($stage === 'account') $stage = 'account';
                    if ($stage === 'audit') $stage = 'audit';
                    
                    $result = $transactionModel->rejectTransaction($id, $stage, $userId, $userName);
                    if ($result) {
                        $success_msg = "Transaction rejected successfully.";
                    } else {
                        $error_msg = "Error rejecting transaction. Please try again.";
                    }
                    break;
                    
                default:
                    $error_msg = "Invalid action.";
                    break;
            }
        } else {
            $error_msg = "Transaction not found.";
        }
    }
}

// Get categorized transactions for Wealth Creation officers
if (hasDepartment('Wealth Creation')) {
    // Get unposted transactions (newly posted, not yet approved by accounts)
    $db->query("SELECT * FROM account_general_transaction_new 
               WHERE posting_officer_id = :officer_id 
               AND leasing_post_status = 'pending' 
               ORDER BY posting_time DESC");
    $db->bind(':officer_id', $userId);
    $unposted_transactions = $db->resultSet();
    
    // Get declined transactions (rejected at any stage)
    $db->query("SELECT * FROM account_general_transaction_new 
               WHERE posting_officer_id = :officer_id 
               AND (leasing_post_status = 'rejected' OR approval_status = 'rejected' OR verification_status = 'rejected')
               ORDER BY posting_time DESC");
    $db->bind(':officer_id', $userId);
    $declined_transactions = $db->resultSet();
    
    // Get pending transactions (approved by accounts but not yet verified)
    $db->query("SELECT * FROM account_general_transaction_new 
               WHERE posting_officer_id = :officer_id 
               AND leasing_post_status = 'approved' 
               AND approval_status = 'approved' 
               AND verification_status = 'pending'
               ORDER BY posting_time DESC");
    $db->bind(':officer_id', $userId);
    $pending_transactions = $db->resultSet();
    
    // Get wrong entries (flagged or need correction)
    $db->query("SELECT * FROM account_general_transaction_new 
               WHERE posting_officer_id = :officer_id 
               AND entry_status = 'flagged'
               ORDER BY posting_time DESC");
    $db->bind(':officer_id', $userId);
    $wrong_entries = $db->resultSet();
    
    // Calculate collection till (total amount collected by this officer)
    $db->query("SELECT SUM(amount_paid) as total FROM account_general_transaction_new 
               WHERE posting_officer_id = :officer_id 
               AND DATE(posting_time) = CURDATE()");
    $db->bind(':officer_id', $userId);
    $collection_till = $db->single();
    $collection_amount = $collection_till['total'] ?? 0;
    
} else {
    // Other departments see all transactions
    $transactions = $transactionModel->getTransactions();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Income ERP System</title>
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
        .section-header {
            background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%);
            border-left: 4px solid #3b82f6;
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
                        <h1 class="text-lg font-semibold text-gray-900">Transaction Management</h1>
                        <p class="text-sm text-gray-500">View & Manage Your Transactions</p>
                    </div>
                </div>
                
                <?php if (hasDepartment('Wealth Creation')): ?>
                <div class="flex items-center gap-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-2 flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-600">Collection Till</span>
                        <span class="text-lg font-bold text-red-600">₦ <?= number_format($collection_amount, 2) ?></span>
                    </div>
                    <a href="officers.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        View Transactions Analysis
                    </a>
                </div>
                <?php endif; ?>
                
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
                            <?php if(hasDepartment('Wealth Creation')): ?>
                            <a href="post_collection.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-receipt mr-2"></i> Post Collections
                            </a>
                            <a href="officers.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-users mr-2"></i> Officers & Shops
                            </a>
                            <?php endif; ?>
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

        <?php if (hasDepartment('Wealth Creation')): ?>
            <!-- Unposted Transactions -->
            <div class="mb-8">
                <div class="section-header px-4 py-3 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Unposted Transactions</h2>
                </div>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (!empty($unposted_transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-blue-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">S/N</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($unposted_transactions as $index => $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="view_transaction.php?id=<?= $transaction['id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $index + 1 ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatDate($transaction['date_of_payment']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['shop_no'] ?? '-') ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($transaction['transaction_desc']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600"><?= formatCurrency($transaction['amount_paid']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['receipt_no']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    Pending Approval
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">Awaiting accounts approval</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No unposted transactions found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Declined Transactions -->
            <div class="mb-8">
                <div class="section-header px-4 py-3 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Declined Transactions</h2>
                </div>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (!empty($declined_transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-red-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">S/N</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Space No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plate No/No of Tickets</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approval Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($declined_transactions as $index => $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="view_transaction.php?id=<?= $transaction['id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $index + 1 ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatDate($transaction['date_of_payment']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['shop_no'] ?? '-') ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($transaction['plate_no'] ?? $transaction['no_of_tickets'] ?? '-') ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($transaction['transaction_desc']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600"><?= formatCurrency($transaction['amount_paid']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['receipt_no']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Rejected
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">Transaction was rejected</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No declined transactions found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Transactions -->
            <div class="mb-8">
                <div class="section-header px-4 py-3 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Pending Transactions</h2>
                </div>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (!empty($pending_transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-yellow-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">S/N</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($pending_transactions as $index => $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="view_transaction.php?id=<?= $transaction['id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $index + 1 ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatDate($transaction['date_of_payment']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['customer_name']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($transaction['transaction_desc']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600"><?= formatCurrency($transaction['amount_paid']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['receipt_no']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Pending Verification
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No pending transactions found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- For other departments, show the regular transaction management interface -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">All Transactions</h2>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?= count($transactions) ?> Total
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <?php if (!empty($transactions)): ?>
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($transactions as $transaction): ?>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if($transaction['verification_status'] == 'verified'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i> Verified
                                                    </span>
                                                <?php elseif($transaction['verification_status'] == 'rejected'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fas fa-times-circle mr-1"></i> Rejected
                                                    </span>
                                                <?php elseif($transaction['approval_status'] == 'approved'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i> Pending Verification
                                                    </span>
                                                <?php elseif($transaction['approval_status'] == 'rejected'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fas fa-times-circle mr-1"></i> Rejected
                                                    </span>
                                                <?php elseif($transaction['leasing_post_status'] == 'approved'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-clock mr-1"></i> Pending Approval
                                                    </span>
                                                <?php elseif($transaction['leasing_post_status'] == 'pending'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        <i class="fas fa-clock mr-1"></i> Pending Post Approval
                                                    </span>
                                                <?php elseif($transaction['leasing_post_status'] == 'rejected'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fas fa-times-circle mr-1"></i> Rejected
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-2">
                                                    <a href="view_transaction.php?id=<?= $transaction['id'] ?>" 
                                                       class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition-colors">
                                                        <i class="fas fa-eye mr-1"></i> View
                                                    </a>
                                                    
                                                    <?php if (hasDepartment('Accounts')): ?>
                                                        <?php if ($transaction['leasing_post_status'] === 'pending'): ?>
                                                            <a href="transactions.php?action=approve_post&id=<?= $transaction['id'] ?>" 
                                                               onclick="return confirm('Are you sure you want to approve this post?')"
                                                               class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors">
                                                                <i class="fas fa-check mr-1"></i> Approve Post
                                                            </a>
                                                            <a href="transactions.php?action=reject_post&id=<?= $transaction['id'] ?>" 
                                                               onclick="return confirm('Are you sure you want to reject this post?')"
                                                               class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors">
                                                                <i class="fas fa-times mr-1"></i> Reject
                                                            </a>
                                                        <?php elseif ($transaction['approval_status'] === 'pending'): ?>
                                                            <a href="transactions.php?action=approve_account&id=<?= $transaction['id'] ?>" 
                                                               onclick="return confirm('Are you sure you want to approve this transaction?')"
                                                               class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors">
                                                                <i class="fas fa-check mr-1"></i> Approve
                                                            </a>
                                                            <a href="transactions.php?action=reject_account&id=<?= $transaction['id'] ?>" 
                                                               onclick="return confirm('Are you sure you want to reject this transaction?')"
                                                               class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors">
                                                                <i class="fas fa-times mr-1"></i> Reject
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (hasDepartment('Audit/Inspections')): ?>
                                                        <?php if ($transaction['verification_status'] === 'pending'): ?>
                                                            <a href="transactions.php?action=verify&id=<?= $transaction['id'] ?>" 
                                                               onclick="return confirm('Are you sure you want to verify this transaction?')"
                                                               class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition-colors">
                                                                <i class="fas fa-check mr-1"></i> Verify
                                                            </a>
                                                            <a href="transactions.php?action=reject_audit&id=<?= $transaction['id'] ?>" 
                                                               onclick="return confirm('Are you sure you want to reject this transaction?')"
                                                               class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition-colors">
                                                                <i class="fas fa-times mr-1"></i> Reject
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-exchange-alt text-gray-400 text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Transactions Found</h3>
                            <p class="text-gray-500">There are no transactions to display at the moment.</p>
                            <?php if(hasDepartment('Wealth Creation')): ?>
                            <a href="post_collection.php" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i> Add New Transaction
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('table').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[2, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [0] }
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
