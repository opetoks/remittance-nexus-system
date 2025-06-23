
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Get user information from session (no redundant queries)
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userDepartment = $_SESSION['user_department'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

// Initialize database and transaction model
$db = new Database();
$transactionModel = new Transaction();

// Determine approval level based on user department/role
$approvalLevel = '';
$canApprove = false;

if (hasDepartment('Accounts')) {
    $approvalLevel = 'accounts';
    $canApprove = true;
} elseif (hasDepartment('Audit/Inspections')) {
    $approvalLevel = 'audit';
    $canApprove = true;
} elseif ($userRole === 'FC' || $userRole === 'Financial Controller') {
    $approvalLevel = 'fc';
    $canApprove = true;
}

// Process approval/decline actions
$success_msg = $error_msg = '';

if (isset($_POST['action']) && isset($_POST['transaction_id']) && $canApprove) {
    $action = sanitize($_POST['action']);
    $transaction_id = intval($_POST['transaction_id']);
    $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : '';
    
    if ($transaction_id > 0) {
        $transaction = $transactionModel->getTransactionById($transaction_id);
        
        if ($transaction) {
            $result = false;
            
            if ($action === 'approve') {
                switch ($approvalLevel) {
                    case 'accounts':
                        if ($transaction['leasing_post_status'] === 'approved' && 
                            $transaction['approval_status'] === 'pending') {
                            $result = $transactionModel->approveTransaction($transaction_id, $userId, $userName);
                        }
                        break;
                        
                    case 'audit':
                        if ($transaction['approval_status'] === 'approved' && 
                            $transaction['verification_status'] === 'pending') {
                            $result = $transactionModel->verifyTransaction($transaction_id, $userId, $userName);
                        }
                        break;
                        
                    case 'fc':
                        if ($transaction['verification_status'] === 'verified' && 
                            $transaction['fc_approval_status'] === 'pending') {
                            $result = $transactionModel->fcApproveTransaction($transaction_id, $userId, $userName);
                        }
                        break;
                }
                
                if ($result) {
                    $success_msg = "Transaction approved successfully!";
                } else {
                    $error_msg = "Error approving transaction or invalid transaction state.";
                }
                
            } elseif ($action === 'decline') {
                $result = $transactionModel->declineTransaction($transaction_id, $approvalLevel, $userId, $userName, $remarks);
                
                if ($result) {
                    $success_msg = "Transaction declined successfully.";
                } else {
                    $error_msg = "Error declining transaction.";
                }
            }
        } else {
            $error_msg = "Transaction not found.";
        }
    }
}

// Get pending transactions based on user's approval level
$pendingTransactions = [];
$stats = [];

if ($canApprove) {
    switch ($approvalLevel) {
        case 'accounts':
            $pendingTransactions = $transactionModel->getPendingTransactionsForAccountApproval();
            break;
        case 'audit':
            $pendingTransactions = $transactionModel->getPendingTransactionsForAuditVerification();
            break;
        case 'fc':
            $pendingTransactions = $transactionModel->getPendingTransactionsForFCApproval();
            break;
    }
    
    // Get approval statistics
    $stats = $transactionModel->getApprovalStats($approvalLevel);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($approvalLevel); ?> Approval Dashboard - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #718096;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(102, 126, 234, 0.1);
            padding: 12px 20px;
            border-radius: 10px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.pending { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.approved { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.declined { background: linear-gradient(135deg, #fa709a, #fee140); }
        .stat-icon.total { background: linear-gradient(135deg, #a8edea, #fed6e3); }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            font-weight: 500;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .content-header {
            padding: 25px;
            border-bottom: 1px solid #e2e8f0;
            background: rgba(102, 126, 234, 0.05);
        }

        .content-header h2 {
            color: #2d3748;
            font-size: 24px;
            font-weight: 600;
        }

        .alert {
            margin: 20px 25px;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .table-container {
            padding: 25px;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .no-access {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 1);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>

    <div class="dashboard-container">
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <h1><?php echo ucfirst($approvalLevel); ?> Approval Dashboard</h1>
                    <p>Multi-level transaction approval system</p>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($userName, 0, 2)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #2d3748;"><?php echo $userName; ?></div>
                        <div style="font-size: 14px; color: #718096;"><?php echo $userDepartment; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canApprove): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo count($pendingTransactions); ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon declined">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['declined'] ?? 0; ?></div>
                    <div class="stat-label">Declined Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Processed</div>
                </div>
            </div>

            <div class="main-content">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-tasks"></i>
                        Pending Transactions - <?php echo ucfirst($approvalLevel); ?> Level
                    </h2>
                </div>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table">
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
                            <?php if (!empty($pendingTransactions)): ?>
                                <?php foreach ($pendingTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $transaction['receipt_no']; ?></td>
                                        <td><?php echo formatDate($transaction['date_of_payment']); ?></td>
                                        <td><?php echo $transaction['customer_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo formatCurrency($transaction['amount_paid']); ?></td>
                                        <td><?php echo $transaction['income_line']; ?></td>
                                        <td><?php echo $transaction['posting_officer_name']; ?></td>
                                        <td>
                                            <?php 
                                            $status = '';
                                            switch ($approvalLevel) {
                                                case 'accounts':
                                                    $status = 'Pending Account Approval';
                                                    break;
                                                case 'audit':
                                                    $status = 'Pending Audit Verification';
                                                    break;
                                                case 'fc':
                                                    $status = 'Pending FC Approval';
                                                    break;
                                            }
                                            echo $status;
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button class="btn btn-success" onclick="approveTransaction(<?php echo $transaction['id']; ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="btn btn-danger" onclick="declineTransaction(<?php echo $transaction['id']; ?>)">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 40px; color: #718096;">
                                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                        No pending transactions found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="main-content">
                <div class="no-access">
                    <i class="fas fa-lock" style="font-size: 64px; margin-bottom: 20px; color: #e2e8f0;"></i>
                    <h2>Access Restricted</h2>
                    <p>You don't have permission to approve transactions.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px; color: #2d3748;">
                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                Approve Transaction
            </h3>
            <p style="margin-bottom: 20px; color: #718096;">Are you sure you want to approve this transaction?</p>
            <form id="approvalForm" method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="transaction_id" id="approveTransactionId">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()" style="background: #e2e8f0; color: #4a5568;">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px; color: #2d3748;">
                <i class="fas fa-times-circle" style="color: #f56565;"></i>
                Decline Transaction
            </h3>
            <form id="declineForm" method="POST">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="transaction_id" id="declineTransactionId">
                <div class="form-group">
                    <label for="remarks">Reason for declining:</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3" required placeholder="Please provide a reason for declining this transaction..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()" style="background: #e2e8f0; color: #4a5568;">Cancel</button>
                    <button type="submit" class="btn btn-danger">Decline</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewTransaction(id) {
            window.open('view_transaction.php?id=' + id, '_blank');
        }

        function approveTransaction(id) {
            document.getElementById('approveTransactionId').value = id;
            document.getElementById('approvalModal').style.display = 'block';
        }

        function declineTransaction(id) {
            document.getElementById('declineTransactionId').value = id;
            document.getElementById('declineModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('declineModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const approvalModal = document.getElementById('approvalModal');
            const declineModal = document.getElementById('declineModal');
            if (event.target === approvalModal) {
                approvalModal.style.display = 'none';
            }
            if (event.target === declineModal) {
                declineModal.style.display = 'none';
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
