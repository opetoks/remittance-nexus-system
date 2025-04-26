
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Remittance.php'; 
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

$userId = getLoggedInUserId();
// Initialize objects
$db = new Database();
$user = new User();
$transaction = new Transaction();
$remittance = new Remittance();
$account = new Account();

// Get transaction statistics
$stats = $transaction->getTransactionStats();

// Get current user information
$currentUser = $user->getUserById($userId);

// Get today's remittances
$todayRemittances = $remittance->getRemittancesByDate(date('Y-m-d'));

// Get income line accounts
$incomeLines = $account->getIncomeLineAccounts();

// Get Current User department
$userDepartment = $user->getDepartmentByUserIdstring($userId);

// Get pending transactions based on user role
$pendingTransactions = [];

if (hasDepartment('Wealth Creation')) {
    // Get remittances for this officer
    $myRemittances = $remittance->getRemittancesByOfficer($userId);
} elseif (hasDepartment('Accounts')) {
    // Get pending transactions for account approval
    $pendingTransactions = $transaction->getPendingTransactionsForAccountApproval();
} elseif (hasDepartment('Audit/Inspections')) {
    // Get pending transactions for audit verification
    $pendingTransactions = $transaction->getPendingTransactionsForAuditVerification();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include('include/sidebar.php'); ?>

        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include('include/header.php'); ?>
            
            <!-- Content Body -->
            <div class="content-body">
            <php 
                <?php if(hasDepartment('Accounts') || hasDepartment('Audit/Inspections')): 
                    include('include/dashboard-overview.php');
                    endif;
                 ?>
                
                <!-- Charts Section -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Revenue Trend</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenue-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Income Sources</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="income-sources-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity Section -->
                <?php if(hasDepartment('admin') || hasDepartment('Accounts')): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Today's Remittances</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Remit ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>No. of Receipts</th>
                                        <th>Category</th>
                                        <th>Remitting Officer</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($todayRemittances)): ?>
                                        <?php foreach($todayRemittances as $remit): ?>
                                            <tr>
                                                <td><?php echo $remit['remit_id']; ?></td>
                                                <td><?php echo formatDate($remit['date']); ?></td>
                                                <td><?php echo formatCurrency($remit['amount_paid']); ?></td>
                                                <td><?php echo $remit['no_of_receipts']; ?></td>
                                                <td><?php echo $remit['category']; ?></td>
                                                <td><?php echo $remit['remitting_officer_name']; ?></td>
                                                <td>
                                                    <a href="view_remittance.php?id=<?php echo $remit['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No remittances recorded today</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Pending Approvals Section -->
                <?php if(hasDepartment('Accounts') || hasDepartment('Audit/Inspections')): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Pending Approvals</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Income Line</th>
                                        <th>Posted By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($pendingTransactions)): ?>
                                        <?php foreach($pendingTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo $transaction['receipt_no']; ?></td>
                                                <td><?php echo formatDate($transaction['date_of_payment']); ?></td>
                                                <td><?php echo $transaction['customer_name']; ?></td>
                                                <td><?php echo formatCurrency($transaction['amount_paid']); ?></td>
                                                <td><?php echo $transaction['income_line']; ?></td>
                                                <td><?php echo $transaction['posting_officer_name']; ?></td>
                                                <td>
                                                    <?php if(hasDepartment('Accounts')): ?>
                                                        <span class="badge badge-warning">Awaiting Approval</span>
                                                    <?php elseif(hasDepartment('Audit/Inspections')): ?>
                                                        <span class="badge badge-info">Awaiting Verification</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No pending transactions found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Leasing Officer Remittances -->
                <?php if(hasDepartment('Wealth Creation')): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">My Remittances</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Remit ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>No. of Receipts</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($myRemittances)): ?>
                                        <?php foreach($myRemittances as $remit): ?>
                                            <?php 
                                                $isPosted = $remittance->isRemittanceFullyPosted($remit['remit_id']);
                                            ?>
                                            <tr>
                                                <td><?php echo $remit['remit_id']; ?></td>
                                                <td><?php echo formatDate($remit['date']); ?></td>
                                                <td><?php echo formatCurrency($remit['amount_paid']); ?></td>
                                                <td><?php echo $remit['no_of_receipts']; ?></td>
                                                <td>
                                                    <?php if($isPosted): ?>
                                                        <span class="badge badge-success">Fully Posted</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending Posts</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="post_collection.php?remit_id=<?php echo $remit['remit_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-receipt"></i> Post
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No remittances found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
