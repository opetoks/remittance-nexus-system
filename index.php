
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

// Initialize objects
$db = new Database();
$user = new User();
$transaction = new Transaction();
$remittance = new Remittance();
$account = new Account();

// Get transaction statistics
$stats = $transaction->getTransactionStats();

// Get current user information
$currentUser = $user->getUserById($_SESSION['user_id']);

// Get today's remittances
$todayRemittances = $remittance->getRemittancesByDate(date('Y-m-d'));

// Get income line accounts
$incomeLines = $account->getIncomeLineAccounts();

// Get pending transactions based on user role
$pendingTransactions = [];

if (hasRole('leasing_officer')) {
    // Get remittances for this officer
    $myRemittances = $remittance->getRemittancesByOfficer($_SESSION['user_id']);
} elseif (hasRole('account_officer')) {
    // Get pending transactions for account approval
    $pendingTransactions = $transaction->getPendingTransactionsForAccountApproval();
} elseif (hasRole('auditor')) {
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
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-chart-line"></i> Income ERP
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="sidebar-menu-title">MAIN MENU</div>
                
                <a href="index.php" class="sidebar-menu-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <?php if(hasRole('admin') || hasRole('account_officer')): ?>
                <a href="remittance.php" class="sidebar-menu-item">
                    <i class="fas fa-money-bill-wave"></i> Remittances
                </a>
                <?php endif; ?>
                
                <?php if(hasRole('leasing_officer')): ?>
                <a href="post_collection.php" class="sidebar-menu-item">
                    <i class="fas fa-receipt"></i> Post Collections
                </a>
                <?php endif; ?>
                
                <?php if(hasRole('account_officer')): ?>
                <a href="approve_posts.php" class="sidebar-menu-item">
                    <i class="fas fa-check-circle"></i> Approve Posts
                </a>
                <?php endif; ?>
                
                <?php if(hasRole('auditor')): ?>
                <a href="verify_transactions.php" class="sidebar-menu-item">
                    <i class="fas fa-clipboard-check"></i> Verify Transactions
                </a>
                <?php endif; ?>
                
                <a href="transactions.php" class="sidebar-menu-item">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
                
                <?php if(hasRole('admin')): ?>
                <div class="sidebar-menu-title">ADMINISTRATION</div>
                
                <a href="accounts.php" class="sidebar-menu-item">
                    <i class="fas fa-chart-pie"></i> Chart of Accounts
                </a>
                
                <a href="users.php" class="sidebar-menu-item">
                    <i class="fas fa-users"></i> User Management
                </a>
                
                <a href="reports.php" class="sidebar-menu-item">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
                
                <a href="settings.php" class="sidebar-menu-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <?php endif; ?>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="toggle-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="page-title">Dashboard</h4>
                </div>
                
                <div class="header-right">
                    <div class="user-dropdown">
                        <button class="user-dropdown-toggle">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="name"><?php echo $_SESSION['user_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="user-dropdown-menu">
                            <a href="profile.php" class="user-dropdown-item">
                                <i class="fas fa-user-circle"></i> Profile
                            </a>
                            <a href="change_password.php" class="user-dropdown-item">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <a href="logout.php" class="user-dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Body -->
            <div class="content-body">
                <!-- Dashboard Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-title">Today's Collection</div>
                        <div class="stat-card-value"><?php echo formatCurrency($stats['today']['total'] ?? 0); ?></div>
                        <div class="stat-card-text"><?php echo $stats['today']['count'] ?? 0; ?> transactions</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">This Week</div>
                        <div class="stat-card-value"><?php echo formatCurrency($stats['week']['total'] ?? 0); ?></div>
                        <div class="stat-card-text"><?php echo $stats['week']['count'] ?? 0; ?> transactions</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">This Month</div>
                        <div class="stat-card-value"><?php echo formatCurrency($stats['month']['total'] ?? 0); ?></div>
                        <div class="stat-card-text"><?php echo $stats['month']['count'] ?? 0; ?> transactions</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-title">Pending Approvals</div>
                        <div class="stat-card-value"><?php echo count($pendingTransactions); ?></div>
                        <div class="stat-card-text">Waiting for your action</div>
                    </div>
                </div>
                
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
                <?php if(hasRole('admin') || hasRole('account_officer')): ?>
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
                <?php if(hasRole('account_officer') || hasRole('auditor')): ?>
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
                                                    <?php if(hasRole('account_officer')): ?>
                                                        <span class="badge badge-warning">Awaiting Approval</span>
                                                    <?php elseif(hasRole('auditor')): ?>
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
                <?php if(hasRole('leasing_officer')): ?>
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
