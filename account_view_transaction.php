
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Initialize objects
$db = new Database();
$user = new User();
$transactionModel = new Transaction();
$accountModel = new Account();

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_department = $_SESSION['user_department'];

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Get transactions based on department and status
$transactions = [];
$stats = [];

if ($user_department === 'Accounts') {
    switch ($status_filter) {
        case 'pending':
            $transactions = $transactionModel->getPendingTransactionsForAccountApproval(500);
            break;
        case 'approved':
            $transactions = $transactionModel->getTransactionsByStatus('approved', 500);
            break;
        case 'declined':
            $transactions = $transactionModel->getTransactionsByStatus('rejected', 500);
            break;
        default:
            $transactions = $transactionModel->getPendingTransactionsForAccountApproval(500);
    }
    $stats = $transactionModel->getApprovalStats('accounts');
} elseif ($user_department === 'Audit/Inspections') {
    switch ($status_filter) {
        case 'pending':
            $transactions = $transactionModel->getPendingTransactionsForAuditVerification(500);
            break;
        case 'verified':
            $transactions = $transactionModel->getTransactionsByVerificationStatus('verified', 500);
            break;
        case 'rejected':
            $transactions = $transactionModel->getTransactionsByVerificationStatus('rejected', 500);
            break;
        default:
            $transactions = $transactionModel->getPendingTransactionsForAuditVerification(500);
    }
    $stats = $transactionModel->getApprovalStats('audit');
} elseif ($user_department === 'Financial Controller') {
    switch ($status_filter) {
        case 'pending':
            $transactions = $transactionModel->getPendingTransactionsForFCApproval(500);
            break;
        case 'approved':
            $transactions = $transactionModel->getTransactionsByFCStatus('approved', 500);
            break;
        case 'declined':
            $transactions = $transactionModel->getTransactionsByFCStatus('rejected', 500);
            break;
        default:
            $transactions = $transactionModel->getPendingTransactionsForFCApproval(500);
    }
    $stats = $transactionModel->getApprovalStats('fc');
}

// Filter transactions by date and search if provided
if (!empty($date_from) || !empty($date_to) || !empty($search)) {
    $transactions = array_filter($transactions, function($transaction) use ($date_from, $date_to, $search) {
        $date_match = true;
        if (!empty($date_from) && $transaction['date_of_payment'] < $date_from) {
            $date_match = false;
        }
        if (!empty($date_to) && $transaction['date_of_payment'] > $date_to) {
            $date_match = false;
        }
        
        $search_match = true;
        if (!empty($search)) {
            $search_match = (
                stripos($transaction['receipt_no'], $search) !== false ||
                stripos($transaction['customer_name'], $search) !== false ||
                stripos($transaction['transaction_desc'], $search) !== false
            );
        }
        
        return $date_match && $search_match;
    });
}

// Handle AJAX requests for transaction details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'transaction_details' && isset($_GET['id'])) {
    $transaction_id = intval($_GET['id']);
    $transaction = $transactionModel->getTransactionById($transaction_id);
    
    if ($transaction) {
        $debitAccount = $accountModel->getAccountByCode($transaction['debit_account']);
        $creditAccount = $accountModel->getAccountByCode($transaction['credit_account']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'transaction' => $transaction,
            'debit_account' => $debitAccount,
            'credit_account' => $creditAccount
        ]);
        exit;
    }
}

// Handle approval/rejection actions
if (isset($_POST['action']) && isset($_POST['transaction_id'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $action = $_POST['action'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    
    $result = false;
    
    if ($user_department === 'Accounts') {
        if ($action === 'approve') {
            $result = $transactionModel->approveTransaction($transaction_id, $user_id, $user_name);
        } elseif ($action === 'decline') {
            $result = $transactionModel->declineTransaction($transaction_id, 'accounts', $user_id, $user_name, $remarks);
        }
    } elseif ($user_department === 'Audit/Inspections') {
        if ($action === 'approve') {
            $result = $transactionModel->verifyTransaction($transaction_id, $user_id, $user_name);
        } elseif ($action === 'decline') {
            $result = $transactionModel->declineTransaction($transaction_id, 'audit', $user_id, $user_name, $remarks);
        }
    } elseif ($user_department === 'Financial Controller') {
        if ($action === 'approve') {
            $result = $transactionModel->fcApproveTransaction($transaction_id, $user_id, $user_name);
        } elseif ($action === 'decline') {
            $result = $transactionModel->declineTransaction($transaction_id, 'fc', $user_id, $user_name, $remarks);
        }
    }
    
    if ($result) {
        $success_msg = "Transaction " . ($action === 'approve' ? 'approved' : 'declined') . " successfully!";
    } else {
        $error_msg = "Error processing transaction. Please try again.";
    }
    
    // Refresh the page to show updated data
    header("Location: account_view_transaction.php?status=$status_filter");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account View Transactions - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .header-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .status-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .status-tab {
            padding: 10px 20px;
            background: #e9ecef;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
        }
        
        .status-tab.active {
            background: #007bff;
            color: white;
        }
        
        .transactions-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transaction-table th,
        .transaction-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .transaction-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .transaction-table tr:hover {
            background: #f8f9fa;
        }
        
        .account-leg {
            font-size: 0.9rem;
            margin: 2px 0;
        }
        
        .debit-leg {
            color: #dc3545;
        }
        
        .credit-leg {
            color: #28a745;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-declined {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-clipboard-check"></i> Account View Transactions</h2>
                    <p>Department: <?php echo $user_department; ?> | Officer: <?php echo $user_name; ?></p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Processed Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Approved Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo $stats['declined'] ?? 0; ?></div>
                <div class="stat-label">Declined Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo count($transactions); ?></div>
                <div class="stat-label">Current View</div>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="status-tabs">
            <?php if ($user_department === 'Accounts'): ?>
                <a href="?status=pending" class="status-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending Posts
                </a>
                <a href="?status=approved" class="status-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    Approved
                </a>
                <a href="?status=declined" class="status-tab <?php echo $status_filter === 'declined' ? 'active' : ''; ?>">
                    Declined
                </a>
            <?php elseif ($user_department === 'Audit/Inspections'): ?>
                <a href="?status=pending" class="status-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending Verification
                </a>
                <a href="?status=verified" class="status-tab <?php echo $status_filter === 'verified' ? 'active' : ''; ?>">
                    Verified
                </a>
                <a href="?status=rejected" class="status-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    Rejected
                </a>
            <?php elseif ($user_department === 'Financial Controller'): ?>
                <a href="?status=pending" class="status-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending FC Approval
                </a>
                <a href="?status=approved" class="status-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    FC Approved
                </a>
                <a href="?status=declined" class="status-tab <?php echo $status_filter === 'declined' ? 'active' : ''; ?>">
                    FC Declined
                </a>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filter-row">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                
                <div>
                    <label>From Date:</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control">
                </div>
                
                <div>
                    <label>To Date:</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control">
                </div>
                
                <div>
                    <label>Search:</label>
                    <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Receipt No, Customer, Description..." class="form-control">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="account_view_transaction.php?status=<?php echo $status_filter; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="transactions-table">
            <div class="table-responsive">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Date</th>
                            <th>Receipt No</th>
                            <th>Transaction Description</th>
                            <th>Amount</th>
                            <th>Account Legs</th>
                            <th>Status</th>
                            <th>Posted By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 10px;"></i>
                                    <p>No transactions found for the selected criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $index => $transaction): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($transaction['date_of_payment'])); ?></td>
                                    <td><?php echo $transaction['receipt_no']; ?></td>
                                    <td><?php echo $transaction['transaction_desc'] ?: $transaction['income_line']; ?></td>
                                    <td><?php echo number_format($transaction['amount_paid'], 2); ?></td>
                                    <td>
                                        <div class="account-leg debit-leg">
                                            <strong>Debit:</strong> <?php echo $transaction['debit_account']; ?>
                                        </div>
                                        <div class="account-leg credit-leg">
                                            <strong>Credit:</strong> <?php echo $transaction['credit_account']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status = 'pending';
                                        $status_class = 'status-pending';
                                        
                                        if ($user_department === 'Accounts') {
                                            if ($transaction['approval_status'] === 'approved') {
                                                $status = 'approved';
                                                $status_class = 'status-approved';
                                            } elseif ($transaction['approval_status'] === 'rejected') {
                                                $status = 'declined';
                                                $status_class = 'status-declined';
                                            }
                                        } elseif ($user_department === 'Audit/Inspections') {
                                            if ($transaction['verification_status'] === 'verified') {
                                                $status = 'verified';
                                                $status_class = 'status-approved';
                                            } elseif ($transaction['verification_status'] === 'rejected') {
                                                $status = 'rejected';
                                                $status_class = 'status-declined';
                                            }
                                        } elseif ($user_department === 'Financial Controller') {
                                            if ($transaction['fc_approval_status'] === 'approved') {
                                                $status = 'approved';
                                                $status_class = 'status-approved';
                                            } elseif ($transaction['fc_approval_status'] === 'rejected') {
                                                $status = 'declined';
                                                $status_class = 'status-declined';
                                            }
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $transaction['posting_officer_name']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-sm btn-primary" onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                            
                                            <?php if ($status === 'pending'): ?>
                                                <button class="btn-sm btn-success" onclick="approveTransaction(<?php echo $transaction['id']; ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="btn-sm btn-danger" onclick="declineTransaction(<?php echo $transaction['id']; ?>)">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Transaction Details</h3>
            <div id="transactionDetails">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <!-- Approval/Decline Form -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeActionModal()">&times;</span>
            <h3 id="actionTitle">Approve Transaction</h3>
            <form id="actionForm" method="POST">
                <input type="hidden" name="transaction_id" id="actionTransactionId">
                <input type="hidden" name="action" id="actionType">
                
                <div id="remarksSection" style="display: none;">
                    <label for="remarks">Remarks:</label>
                    <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Enter reason for declining..."></textarea>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" onclick="closeActionModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" id="actionSubmitBtn" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewTransactionDetails(transactionId) {
            fetch(`account_view_transaction.php?ajax=transaction_details&id=${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    const transaction = data.transaction;
                    const debitAccount = data.debit_account;
                    const creditAccount = data.credit_account;
                    
                    const detailsHtml = `
                        <div class="detail-grid">
                            <div class="detail-section">
                                <h4>Basic Information</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Receipt No:</span>
                                    <span class="detail-value">${transaction.receipt_no}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Date:</span>
                                    <span class="detail-value">${new Date(transaction.date_of_payment).toLocaleDateString()}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Amount:</span>
                                    <span class="detail-value">₦${parseFloat(transaction.amount_paid).toLocaleString()}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Customer:</span>
                                    <span class="detail-value">${transaction.customer_name || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Description:</span>
                                    <span class="detail-value">${transaction.transaction_desc || transaction.income_line}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h4>Account Information</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Debit Account:</span>
                                    <span class="detail-value">${transaction.debit_account} - ${debitAccount?.acct_desc || ''}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Credit Account:</span>
                                    <span class="detail-value">${transaction.credit_account} - ${creditAccount?.acct_desc || ''}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Income Line:</span>
                                    <span class="detail-value">${transaction.income_line}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Posted By:</span>
                                    <span class="detail-value">${transaction.posting_officer_name}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Posted On:</span>
                                    <span class="detail-value">${new Date(transaction.posting_time).toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('transactionDetails').innerHTML = detailsHtml;
                    document.getElementById('transactionModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading transaction details');
                });
        }

        function approveTransaction(transactionId) {
            document.getElementById('actionTransactionId').value = transactionId;
            document.getElementById('actionType').value = 'approve';
            document.getElementById('actionTitle').textContent = 'Approve Transaction';
            document.getElementById('remarksSection').style.display = 'none';
            document.getElementById('actionSubmitBtn').textContent = 'Approve';
            document.getElementById('actionSubmitBtn').className = 'btn btn-success';
            document.getElementById('actionModal').style.display = 'block';
        }

        function declineTransaction(transactionId) {
            document.getElementById('actionTransactionId').value = transactionId;
            document.getElementById('actionType').value = 'decline';
            document.getElementById('actionTitle').textContent = 'Decline Transaction';
            document.getElementById('remarksSection').style.display = 'block';
            document.getElementById('actionSubmitBtn').textContent = 'Decline';
            document.getElementById('actionSubmitBtn').className = 'btn btn-danger';
            document.getElementById('actionModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }

        function closeActionModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const transactionModal = document.getElementById('transactionModal');
            const actionModal = document.getElementById('actionModal');
            
            if (event.target === transactionModal) {
                transactionModal.style.display = 'none';
            }
            if (event.target === actionModal) {
                actionModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
