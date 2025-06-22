
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Remittance.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
$userId = getLoggedInUserId();
hasDepartment('Wealth Creation');

// Initialize objects
$db = new Database();
$user = new User();
$remittanceModel = new Remittance();
$transactionModel = new Transaction();
$accountModel = new Account();

// Get current user information
$currentUser = $user->getUserById($userId);
$userDepartment = $_SESSION['department'] ?? 'Wealth Creation';
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Get all remittances for this officer
$myRemittances = $remittanceModel->getRemittancesByOfficer($_SESSION['user_id']);

// Filter remittances to only show today's unposted ones
$todayRemittances = [];
$today = date('Y-m-d');

foreach($myRemittances as $remit) {
    $remitDate = date('Y-m-d', strtotime($remit['date']));
    if ($remitDate === $today && !$remittanceModel->isRemittanceFullyPosted($remit['remit_id'])) {
        $todayRemittances[] = $remit;
    }
}

// Check if officer has no unposted remittances for today
$hasUnpostedToday = count($todayRemittances) > 0;

// Get income line accounts
$incomeLines = $accountModel->getIncomeLineAccounts();

// Initialize variables
$success_msg = $error_msg = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $remit_id = sanitize($_POST['remit_id']);
    $receipt_no = sanitize($_POST['receipt_no']);
    $customer_name = sanitize(isset($_POST['customer_name']) ? $_POST['customer_name'] : '');
    $date_of_payment = sanitize($_POST['date_of_payment']);
    $amount_paid = floatval(sanitize($_POST['amount_paid']));
    $income_line = sanitize($_POST['income_line']);
    $payment_type = sanitize($_POST['payment_type']);

    // Additional details based on income line
    $shop_id = sanitize(isset($_POST['shop_id']) ? $_POST['shop_id'] : '');
    $shop_no = sanitize(isset($_POST['shop_no']) ? $_POST['shop_no'] : '');
    $shop_size = sanitize(isset($_POST['shop_size']) ? $_POST['shop_size'] : '');
    $start_date = sanitize(isset($_POST['start_date']) ? $_POST['start_date'] : '');
    $end_date = sanitize(isset($_POST['end_date']) ? $_POST['end_date'] : '');
    $no_of_tickets = intval(sanitize(isset($_POST['no_of_tickets']) ? $_POST['no_of_tickets'] : 0));
    $plate_no = sanitize(isset($_POST['plate_no']) ? $_POST['plate_no'] : '');
    $transaction_desc = sanitize(isset($_POST['transaction_desc']) ? $_POST['transaction_desc'] : '');

    // Validation
    $errors = [];
    
    if (empty($remit_id)) {
        $errors[] = "Remittance ID is required";
    }
    
    if (empty($receipt_no)) {
        $errors[] = "Receipt number is required";
    }
    
    if (empty($date_of_payment)) {
        $errors[] = "Date of payment is required";
    }
    
    if ($amount_paid <= 0) {
        $errors[] = "Amount must be greater than zero";
    }
    
    if (empty($income_line)) {
        $errors[] = "Income line is required";
    }
    
    // Verify remittance exists and belongs to this officer
    $remittance = $remittanceModel->getRemittanceByRemitId($remit_id);
    if (!$remittance || $remittance['remitting_officer_id'] != $_SESSION['user_id']) {
        $errors[] = "Invalid remittance ID";
    }
    
    // If no errors, process the transaction
    if (empty($errors)) {
        // Get the account codes for debit and credit
        $debit_account = 'TILL-001'; // Account Till (default)
        
        // Get the account code for the selected income line
        $credit_account = '';
        foreach ($incomeLines as $line) {
            if ($line['acct_alias'] == $income_line) {
                $credit_account = $line['acct_code'];
                break;
            }
        }
        
        if (empty($credit_account)) {
            $errors[] = "Invalid income line selected";
        } else {
            // Prepare transaction data
            $transactionData = [
                'remit_id' => $remit_id,
                'receipt_no' => $receipt_no,
                'customer_name' => $customer_name,
                'date_of_payment' => $date_of_payment,
                'amount_paid' => $amount_paid,
                'income_line' => $income_line,
                'payment_type' => $payment_type,
                'debit_account' => $debit_account,
                'credit_account' => $credit_account,
                'posting_officer_id' => $_SESSION['user_id'],
                'posting_officer_name' => $_SESSION['user_name'],
                'shop_id' => $shop_id,
                'shop_no' => $shop_no,
                'shop_size' => $shop_size,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'no_of_tickets' => $no_of_tickets,
                'plate_no' => $plate_no,
                'transaction_desc' => $transaction_desc
            ];
            
            // Add the transaction
            $result = $transactionModel->addTransaction($transactionData);
            
            if ($result) {
                $success_msg = "Transaction posted successfully!";
                
                // If remittance is fully posted, show additional message
                if ($remittanceModel->isRemittanceFullyPosted($remit_id)) {
                    $success_msg .= " All receipts for this remittance have been posted.";
                }
            } else {
                $error_msg = "Error posting transaction. Please try again.";
            }
        }
    } else {
        $error_msg = implode('<br>', $errors);
    }
}

// Get current time in Lagos timezone
$current_time = new DateTime('now', new DateTimeZone('Africa/Lagos'));
$cutoff_time = new DateTime('18:30', new DateTimeZone('Africa/Lagos'));
$is_after_cutoff = $current_time > $cutoff_time;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Posting - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dept-badge {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-white text-xl font-bold flex items-center">
                        <i class="fas fa-chart-line mr-2"></i>
                        Income ERP System
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="dept-badge text-white px-3 py-1 rounded-full text-sm font-medium">
                        <?= htmlspecialchars($userDepartment) ?>
                    </div>
                    <div class="text-white text-sm">
                        Welcome, <?= htmlspecialchars($userName) ?>
                    </div>
                    <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Collection Posting Dashboard
            </h1>
            <p class="text-gray-600 text-lg">
                Post and manage collections for <?= htmlspecialchars($userDepartment) ?> Department
            </p>
        </div>

        <!-- No Unposted Remittances Notice -->
        <?php if(!$hasUnpostedToday): ?>
            <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-6 mb-6 rounded-lg shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-2xl text-orange-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-orange-800 mb-2">
                            NO UNPOSTED REMITTANCES FOR TODAY
                        </h3>
                        <p class="text-lg font-semibold">
                            You have no pending remittances to post for today (<?= date('F j, Y') ?>).
                        </p>
                        <p class="text-sm mt-2">
                            If you need to create a new remittance, please use the "New Remittance" button below.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Alert Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?= $success_msg ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error_msg)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?= $error_msg ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($is_after_cutoff): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Note: It's past 6:30 PM. New transactions must be recorded as unposted.</span>
                    </div>
                    <a href="unposted_transactions.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-receipt mr-1"></i> Record Unposted
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- New Remittance -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-all duration-200">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-plus text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">New Remittance</h3>
                </div>
                <p class="text-gray-600 mb-4">Create a new remittance for collection posting</p>
                <a href="remittance.php" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg inline-block text-center transition-colors">
                    <i class="fas fa-money-bill-wave mr-2"></i>Create Remittance
                </a>
            </div>

            <!-- Quick Post -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-all duration-200">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <i class="fas fa-receipt text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Quick Post</h3>
                </div>
                <p class="text-gray-600 mb-4">Quickly post a single transaction</p>
                <button onclick="toggleQuickPost()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-lightning-bolt mr-2"></i>Quick Post
                </button>
            </div>

            <!-- View Reports -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-all duration-200">
                <div class="flex items-center mb-4">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">View Reports</h3>
                </div>
                <p class="text-gray-600 mb-4">Access collection reports and analytics</p>
                <a href="mpr.php" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg inline-block text-center transition-colors">
                    <i class="fas fa-file-alt mr-2"></i>View Reports
                </a>
            </div>
        </div>

        <!-- Quick Post Form (Hidden by default) -->
        <div id="quickPostForm" class="bg-white rounded-xl shadow-lg p-6 mb-8 hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Quick Post Transaction</h2>
                <button onclick="toggleQuickPost()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="remit_id" class="block text-sm font-medium text-gray-700 mb-2">Remittance ID</label>
                        <select name="remit_id" id="remit_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Select Remittance --</option>
                            <?php foreach($myRemittances as $remit): ?>
                                <?php if(!$remittanceModel->isRemittanceFullyPosted($remit['remit_id'])): ?>
                                    <option value="<?= $remit['remit_id'] ?>"><?= $remit['remit_id'] ?> - <?= formatCurrency($remit['amount_paid']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="receipt_no" class="block text-sm font-medium text-gray-700 mb-2">Receipt Number</label>
                        <input type="text" name="receipt_no" id="receipt_no" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="date_of_payment" class="block text-sm font-medium text-gray-700 mb-2">Date of Payment</label>
                        <input type="date" name="date_of_payment" id="date_of_payment" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="amount_paid" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid</label>
                        <input type="number" name="amount_paid" id="amount_paid" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label for="payment_type" class="block text-sm font-medium text-gray-700 mb-2">Payment Type</label>
                        <select name="payment_type" id="payment_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="cash">Cash</option>
                            <option value="transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="pos">POS</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="income_line" class="block text-sm font-medium text-gray-700 mb-2">Income Line</label>
                        <select name="income_line" id="income_line" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Select Income Line --</option>
                            <?php foreach($incomeLines as $line): ?>
                                <option value="<?= $line['acct_alias'] ?>"><?= $line['acct_desc'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                        <input type="text" name="customer_name" id="customer_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <!-- Dynamic fields will be inserted here -->
                <div id="dynamicFields"></div>
                
                <div>
                    <label for="transaction_desc" class="block text-sm font-medium text-gray-700 mb-2">Transaction Description</label>
                    <textarea name="transaction_desc" id="transaction_desc" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="toggleQuickPost()" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-2"></i>Post Transaction
                    </button>
                </div>
            </form>
        </div>

        <!-- My Remittances Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">My Remittances</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remit ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(!empty($myRemittances)): ?>
                            <?php foreach($myRemittances as $remit): ?>
                                <?php 
                                    $isPosted = $remittanceModel->isRemittanceFullyPosted($remit['remit_id']);
                                    $transactions = $transactionModel->getTransactionsByRemitId($remit['remit_id']);
                                    $postedCount = count($transactions);
                                    $pendingCount = $remit['no_of_receipts'] - $postedCount;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $remit['remit_id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= formatDate($remit['date']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= formatCurrency($remit['amount_paid']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="text-green-600"><?= $postedCount ?></span> / 
                                        <span class="text-gray-500"><?= $remit['no_of_receipts'] ?></span>
                                        <?php if($pendingCount > 0): ?>
                                            <span class="text-orange-600">(<?= $pendingCount ?> pending)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $remit['category'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if($isPosted): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>Fully Posted
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i>Pending Posts
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if(!$isPosted && !$is_after_cutoff): ?>
                                                <button onclick="loadRemittanceForPosting('<?= $remit['remit_id'] ?>')" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-receipt mr-1"></i>Post
                                                </button>
                                            <?php endif; ?>
                                            <a href="view_remittance.php?id=<?= $remit['remit_id'] ?>" class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No remittances found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center py-6 border-t border-gray-200">
            <p class="text-gray-600">&copy; 2024 Income ERP System - <?= htmlspecialchars($userDepartment) ?> Department. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function toggleQuickPost() {
            const form = document.getElementById('quickPostForm');
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.classList.add('hidden');
            }
        }

        function loadRemittanceForPosting(remitId) {
            document.getElementById('remit_id').value = remitId;
            toggleQuickPost();
        }

        // Handle dynamic fields based on income line selection
        document.getElementById('income_line').addEventListener('change', function() {
            const dynamicFields = document.getElementById('dynamicFields');
            const selectedValue = this.value;
            
            // Clear existing dynamic fields
            dynamicFields.innerHTML = '';
            
            if (selectedValue === 'Shop Rent') {
                dynamicFields.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <h3 class="md:col-span-2 lg:col-span-3 text-lg font-medium text-gray-900">Shop Rent Details</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shop ID</label>
                            <input type="text" name="shop_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shop Number</label>
                            <input type="text" name="shop_no" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shop Size</label>
                            <input type="text" name="shop_size" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                `;
            } else if (selectedValue === 'Service Charge') {
                dynamicFields.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <h3 class="md:col-span-2 lg:col-span-3 text-lg font-medium text-gray-900">Service Charge Details</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shop ID</label>
                            <input type="text" name="shop_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shop Number</label>
                            <input type="text" name="shop_no" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Month/Year</label>
                            <input type="month" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                `;
            } else if (['Car Loading', 'Car Park', 'Hawkers', 'WheelBarrow', 'Abattoir', 'Daily Trade', 'POS Ticket'].includes(selectedValue)) {
                dynamicFields.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <h3 class="md:col-span-2 text-lg font-medium text-gray-900">Ticket Details</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Tickets</label>
                            <input type="number" name="no_of_tickets" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plate Number (if applicable)</label>
                            <input type="text" name="plate_no" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                `;
            }
        });

        // Add hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
