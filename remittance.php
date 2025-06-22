
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Remittance.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
$userId = getLoggedInUserId();

function requireAnyDepartment($departments = []) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $userId = getLoggedInUserId();
    
    require_once 'config/Database.php';
    $db = new Database();

    $db->query('SELECT department FROM staffs WHERE user_id = :userId LIMIT 1');
    $db->bind(':userId', $userId);
    $result = $db->single();

    $department = $result ? $result['department'] : null;

    if (!in_array($department, $departments)) {
        redirect('unauthorized.php');
    }
}

requireAnyDepartment(['IT/E-Business', 'Accounts']);

// Initialize objects
$db = new Database();
$user = new User();
$remittanceModel = new Remittance();
$transactionModel = new Transaction();

// Get all leasing officers
$leasingOfficers = $user->getUsersByDepartment('Wealth Creation');
// Get current user information
$currentUser = $user->getUserById($userId);
$userDepartment = $currentUser['department'];

// Process form submission for new remittance
$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_remittance') {
        // Validate and sanitize input
        $remitting_officer_id = sanitize($_POST['remitting_officer_id']);
        $date = sanitize($_POST['date']);
        $amount_paid = floatval(sanitize($_POST['amount_paid']));
        $no_of_receipts = intval(sanitize($_POST['no_of_receipts']));
        $category = sanitize($_POST['category']);
        
        // Basic validation
        $errors = [];
        
        if (empty($remitting_officer_id)) {
            $errors[] = "Remitting officer is required";
        }
        
        if (empty($date)) {
            $errors[] = "Date is required";
        }
        
        if ($amount_paid <= 0) {
            $errors[] = "Amount must be greater than zero";
        }
        
        if ($no_of_receipts <= 0) {
            $errors[] = "Number of receipts must be greater than zero";
        }
        
        if (empty($category)) {
            $errors[] = "Category is required";
        }
        
        // If no errors, process the remittance
        if (empty($errors)) {
            $officer = $user->getUserById($remitting_officer_id);
            
            if ($officer) {
                $remit_id = $remittanceModel->generateRemitId();
                
                $remittanceData = [
                    'remit_id' => $remit_id,
                    'date' => $date,
                    'amount_paid' => $amount_paid,
                    'no_of_receipts' => $no_of_receipts,
                    'category' => $category,
                    'remitting_officer_id' => $remitting_officer_id,
                    'remitting_officer_name' => $officer['full_name'],
                    'posting_officer_id' => $_SESSION['user_id'],
                    'posting_officer_name' => $_SESSION['user_name']
                ];
                
                $result = $remittanceModel->addRemittance($remittanceData);
                
                if ($result) {
                    $success_msg = "Remittance added successfully with ID: " . $remit_id;
                } else {
                    $error_msg = "Error adding remittance. Please try again.";
                }
            } else {
                $error_msg = "Invalid remitting officer selected.";
            }
        } else {
            $error_msg = implode('<br>', $errors);
        }
    }
    
    // Handle transaction approval/rejection
    if ($action === 'approve_transaction' || $action === 'reject_transaction') {
        $transaction_id = intval($_POST['transaction_id']);
        
        if ($transaction_id > 0) {
            $transaction = $transactionModel->getTransactionById($transaction_id);
            
            if ($transaction) {
                if ($action === 'approve_transaction') {
                    $result = $transactionModel->approveTransaction($transaction_id, $_SESSION['user_id'], $_SESSION['user_name']);
                    $success_msg = $result ? "Transaction approved successfully!" : "Error approving transaction.";
                } else {
                    $result = $transactionModel->rejectTransaction($transaction_id, 'account', $_SESSION['user_id'], $_SESSION['user_name']);
                    $success_msg = $result ? "Transaction rejected successfully." : "Error rejecting transaction.";
                }
            }
        }
    }
    
    // Handle verification approval/rejection
    if ($action === 'verify_transaction' || $action === 'reject_verification') {
        $transaction_id = intval($_POST['transaction_id']);
        
        if ($transaction_id > 0) {
            $transaction = $transactionModel->getTransactionById($transaction_id);
            
            if ($transaction) {
                if ($action === 'verify_transaction') {
                    $result = $transactionModel->verifyTransaction($transaction_id, $_SESSION['user_id'], $_SESSION['user_name']);
                    $success_msg = $result ? "Transaction verified successfully!" : "Error verifying transaction.";
                } else {
                    $result = $transactionModel->rejectTransaction($transaction_id, 'verification', $_SESSION['user_id'], $_SESSION['user_name']);
                    $success_msg = $result ? "Transaction verification rejected." : "Error rejecting verification.";
                }
            }
        }
    }
}

// Get pending transactions for different stages
$pendingApprovals = $transactionModel->getPendingTransactionsForAccountApproval();
$pendingVerifications = $transactionModel->getPendingTransactionsForVerification();

// Get all remittances
$remittances = $remittanceModel->getRemittances();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management Dashboard - ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .quick-actions {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .pending-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <i class="fas fa-chart-line text-2xl text-blue-600 mr-3"></i>
                            <h1 class="text-xl font-bold text-gray-900">Account Management</h1>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                        </div>
                        <a href="logout.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Account Management Dashboard</h2>
                <p class="text-gray-600">
                    Manage remittances, approve posts, and verify transactions for <?= htmlspecialchars($userDepartment) ?> Department
                </p>
            </div>

            <!-- Alert Messages -->
            <?php if(!empty($success_msg)): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= $success_msg ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error_msg)): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= $error_msg ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/80 text-sm">Total Remittances</p>
                            <p class="text-2xl font-bold text-white"><?= count($remittances) ?></p>
                        </div>
                        <i class="fas fa-money-bill-wave text-3xl text-white/60"></i>
                    </div>
                </div>
                
                <div class="stat-card p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/80 text-sm">Pending Approvals</p>
                            <p class="text-2xl font-bold text-white"><?= count($pendingApprovals) ?></p>
                        </div>
                        <i class="fas fa-clock text-3xl text-white/60"></i>
                    </div>
                </div>
                
                <div class="stat-card p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/80 text-sm">Pending Verifications</p>
                            <p class="text-2xl font-bold text-white"><?= count($pendingVerifications) ?></p>
                        </div>
                        <i class="fas fa-clipboard-check text-3xl text-white/60"></i>
                    </div>
                </div>
                
                <div class="stat-card p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/80 text-sm">Active Officers</p>
                            <p class="text-2xl font-bold text-white"><?= count($leasingOfficers) ?></p>
                        </div>
                        <i class="fas fa-users text-3xl text-white/60"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions p-6 rounded-xl shadow-lg mb-8">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-bolt mr-2"></i>Quick Actions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <button onclick="toggleRemittanceForm()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i>New Remittance
                    </button>
                    <button onclick="showSection('approvals')" class="bg-white/20 hover:bg-white/30 text-white px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-check mr-2"></i>Approve Posts
                    </button>
                    <button onclick="showSection('verifications')" class="bg-white/20 hover:bg-white/30 text-white px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-shield-alt mr-2"></i>Verify Transactions
                    </button>
                    <button onclick="showSection('remittances')" class="bg-white/20 hover:bg-white/30 text-white px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-list mr-2"></i>View All Remittances
                    </button>
                </div>
            </div>

            <!-- New Remittance Form (Hidden by default) -->
            <div id="remittance-form" class="bg-white rounded-xl shadow-lg p-6 mb-8 hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-plus-circle mr-2 text-blue-600"></i>Create New Remittance
                    </h3>
                    <button onclick="toggleRemittanceForm()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                    <input type="hidden" name="action" value="add_remittance">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Remitting Officer</label>
                            <select name="remitting_officer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">-- Select Officer --</option>
                                <?php foreach($leasingOfficers as $officer): ?>
                                    <option value="<?= $officer['id'] ?>"><?= $officer['full_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                            <input type="date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount Paid</label>
                            <input type="number" name="amount_paid" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Receipts</label>
                            <input type="number" name="no_of_receipts" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">-- Select Category --</option>
                                <option value="Shop Rent">Rent Collections</option>
                                <option value="Service Charge">Service Charge</option>
                                <option value="Mixed">Other Collections</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Save Remittance
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pending Approvals Section -->
            <div id="approvals-section" class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-check-circle mr-2 text-green-600"></i>Pending Approvals
                    <span class="bg-red-100 text-red-800 text-sm px-2 py-1 rounded-full ml-2"><?= count($pendingApprovals) ?></span>
                </h3>
                
                <?php if(!empty($pendingApprovals)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($pendingApprovals as $transaction): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $transaction['receipt_no'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatDate($transaction['date_of_payment']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= formatCurrency($transaction['amount_paid']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $transaction['income_line'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $transaction['posting_officer_name'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="approve_transaction">
                                                <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Approve this transaction?')">
                                                    <i class="fas fa-check mr-1"></i>Approve
                                                </button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="reject_transaction">
                                                <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Reject this transaction?')">
                                                    <i class="fas fa-times mr-1"></i>Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No pending approvals found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Verifications Section -->
            <div id="verifications-section" class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-shield-alt mr-2 text-blue-600"></i>Pending Verifications
                    <span class="bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded-full ml-2"><?= count($pendingVerifications) ?></span>
                </h3>
                
                <?php if(!empty($pendingVerifications)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Line</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($pendingVerifications as $transaction): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $transaction['receipt_no'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatDate($transaction['date_of_payment']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= formatCurrency($transaction['amount_paid']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $transaction['income_line'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $transaction['approval_officer_name'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="verify_transaction">
                                                <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Verify this transaction?')">
                                                    <i class="fas fa-shield-alt mr-1"></i>Verify
                                                </button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="reject_verification">
                                                <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Reject this verification?')">
                                                    <i class="fas fa-times mr-1"></i>Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-shield-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No pending verifications found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Remittances List -->
            <div id="remittances-section" class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-money-bill-wave mr-2 text-purple-600"></i>All Remittances
                </h3>
                
                <div class="overflow-x-auto">
                    <table id="remittancesTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remit ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. of Receipts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remitting Officer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize DataTable
        $('#remittancesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/get_remittances.php',
                type: 'POST',
                dataSrc: function(json) {
                    console.log('Response from server:', json);
                    return json.data;
                },
                error: function(xhr, error, thrown) {
                    console.error('AJAX error:', xhr.responseText);
                }
            },
            pageLength: 10,
            columns: [
                { data: 'remit_id' },
                { data: 'date' },
                { data: 'amount_paid' },
                { data: 'no_of_receipts' },
                { data: 'category' },
                { data: 'remitting_officer_name' },
                { data: 'posting_officer_name' },
                { data: 'status' },
                { data: 'actions', orderable: false }
            ],
            order: [[1, 'desc']],
            responsive: true,
            language: {
                processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>'
            }
        });

        // Toggle functions
        function toggleRemittanceForm() {
            const form = document.getElementById('remittance-form');
            form.classList.toggle('hidden');
        }

        function showSection(section) {
            // Hide all sections first
            document.getElementById('approvals-section').style.display = 'none';
            document.getElementById('verifications-section').style.display = 'none';
            document.getElementById('remittances-section').style.display = 'none';
            
            // Show selected section
            document.getElementById(section + '-section').style.display = 'block';
            
            // Scroll to section
            document.getElementById(section + '-section').scrollIntoView({ behavior: 'smooth' });
        }

        // Show all sections by default
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('approvals-section').style.display = 'block';
            document.getElementById('verifications-section').style.display = 'block';
            document.getElementById('remittances-section').style.display = 'block';
        });
    </script>
</body>
</html>
