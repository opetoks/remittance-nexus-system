
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
hasDepartment('Wealth Creation');

// Get user information from session (no redundant queries)
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userDepartment = $_SESSION['user_department'] ?? '';

// Initialize database and transaction model
$db = new Database();
$transactionModel = new Transaction();

// Pagination settings
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 25;
$offset = ($page - 1) * $limit;

// Filter settings
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$incomeLineFilter = isset($_GET['income_line']) ? sanitize($_GET['income_line']) : '';
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// AJAX request for loading data chunks
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    try {
        $result = $transactionModel->getTransactionsPaginated($page, $limit, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'income_line' => $incomeLineFilter,
            'search' => $searchQuery,
            'posting_officer_id' => $userId
        ]);
        
        echo json_encode([
            'success' => true,
            'data' => $result['transactions'],
            'total' => $result['total'],
            'page' => $page,
            'totalPages' => ceil($result['total'] / $limit)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error loading transactions: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle form submission for new collection
$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_collection'])) {
    // Prepare transaction data
    $transactionData = [
        'shop_id' => sanitize($_POST['shop_id'] ?? ''),
        'customer_name' => sanitize($_POST['customer_name'] ?? ''),
        'shop_no' => sanitize($_POST['shop_no'] ?? ''),
        'shop_size' => sanitize($_POST['shop_size'] ?? ''),
        'date_of_payment' => sanitize($_POST['date_of_payment']),
        'date_on_receipt' => sanitize($_POST['date_on_receipt'] ?? ''),
        'start_date' => sanitize($_POST['start_date'] ?? ''),
        'end_date' => sanitize($_POST['end_date'] ?? ''),
        'payment_type' => sanitize($_POST['payment_type']),
        'transaction_desc' => sanitize($_POST['transaction_desc'] ?? ''),
        'bank_name' => sanitize($_POST['bank_name'] ?? ''),
        'cheque_no' => sanitize($_POST['cheque_no'] ?? ''),
        'teller_no' => sanitize($_POST['teller_no'] ?? ''),
        'receipt_no' => sanitize($_POST['receipt_no']),
        'amount_paid' => floatval($_POST['amount_paid']),
        'debit_account' => sanitize($_POST['debit_account']),
        'credit_account' => sanitize($_POST['credit_account']),
        'income_line' => sanitize($_POST['income_line']),
        'posting_officer_id' => $userId,
        'posting_officer_name' => $userName,
        'leasing_post_status' => 'pending',
        'approval_status' => 'pending',
        'verification_status' => 'pending'
    ];
    
    $result = $transactionModel->addTransaction($transactionData);
    
    if ($result) {
        $success_msg = "Collection posted successfully! Transaction ID: " . $result;
        // Clear form data
        $_POST = [];
    } else {
        $error_msg = "Error posting collection. Please try again.";
    }
}

// Get summary statistics for the dashboard
$stats = $transactionModel->getOfficerStats($userId);
$incomeLines = $transactionModel->getIncomeLines();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Collections - Income ERP System</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-icon.today { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.week { background: linear-gradient(135deg, #fa709a, #fee140); }
        .stat-icon.pending { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.total { background: linear-gradient(135deg, #a8edea, #fed6e3); }

        .tab-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .tab-header {
            display: flex;
            background: rgba(102, 126, 234, 0.05);
            border-bottom: 1px solid #e2e8f0;
        }

        .tab-button {
            padding: 20px 30px;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .tab-content {
            padding: 30px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .filter-bar {
            background: rgba(102, 126, 234, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin: 0;
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

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
        }

        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .loading i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            margin-bottom: 20px;
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
            z-index: 1000;
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
            
            .tab-header {
                flex-direction: column;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
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
                    <h1>Post Collections Dashboard</h1>
                    <p>Wealth Creation Department</p>
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #2d3748; margin-bottom: 5px;">
                    <?php echo $stats['today_count'] ?? 0; ?>
                </div>
                <div style="color: #718096; font-size: 14px;">Today's Collections</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon week">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #2d3748; margin-bottom: 5px;">
                    <?php echo formatCurrency($stats['today_amount'] ?? 0); ?>
                </div>
                <div style="color: #718096; font-size: 14px;">Today's Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #2d3748; margin-bottom: 5px;">
                    <?php echo $stats['pending_count'] ?? 0; ?>
                </div>
                <div style="color: #718096; font-size: 14px;">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #2d3748; margin-bottom: 5px;">
                    <?php echo $stats['total_count'] ?? 0; ?>
                </div>
                <div style="color: #718096; font-size: 14px;">Total Collections</div>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-header">
                <button class="tab-button active" onclick="switchTab('post')">
                    <i class="fas fa-plus-circle"></i> Post New Collection
                </button>
                <button class="tab-button" onclick="switchTab('view')">
                    <i class="fas fa-list"></i> View Collections
                </button>
            </div>

            <!-- Post Collection Tab -->
            <div id="post-tab" class="tab-content active">
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

                <form method="POST" id="collectionForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="receipt_no">Receipt Number *</label>
                            <input type="text" name="receipt_no" id="receipt_no" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="date_of_payment">Date of Payment *</label>
                            <input type="date" name="date_of_payment" id="date_of_payment" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="amount_paid">Amount Paid *</label>
                            <input type="number" name="amount_paid" id="amount_paid" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="income_line">Income Line *</label>
                            <select name="income_line" id="income_line" class="form-control" required>
                                <option value="">Select Income Line</option>
                                <?php foreach ($incomeLines as $line): ?>
                                    <option value="<?php echo $line['income_line']; ?>"><?php echo $line['income_line']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_type">Payment Type *</label>
                            <select name="payment_type" id="payment_type" class="form-control" required>
                                <option value="">Select Payment Type</option>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="POS">POS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="debit_account">Debit Account *</label>
                            <input type="text" name="debit_account" id="debit_account" class="form-control" required placeholder="e.g., 1001">
                        </div>
                        <div class="form-group">
                            <label for="credit_account">Credit Account *</label>
                            <input type="text" name="credit_account" id="credit_account" class="form-control" required placeholder="e.g., 4001">
                        </div>
                        <div class="form-group">
                            <label for="shop_no">Shop Number</label>
                            <input type="text" name="shop_no" id="shop_no" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="transaction_desc">Description</label>
                            <textarea name="transaction_desc" id="transaction_desc" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="submit_collection" class="btn btn-primary">
                            <i class="fas fa-save"></i> Post Collection
                        </button>
                        <button type="reset" class="btn" style="background: #e2e8f0; color: #4a5568; margin-left: 10px;">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- View Collections Tab -->
            <div id="view-tab" class="tab-content">
                <div class="filter-bar">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="filter_date_from">Date From</label>
                            <input type="date" id="filter_date_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="filter_date_to">Date To</label>
                            <input type="date" id="filter_date_to" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="filter_income_line">Income Line</label>
                            <select id="filter_income_line" class="form-control">
                                <option value="">All Income Lines</option>
                                <?php foreach ($incomeLines as $line): ?>
                                    <option value="<?php echo $line['income_line']; ?>"><?php echo $line['income_line']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_search">Search</label>
                            <input type="text" id="filter_search" class="form-control" placeholder="Receipt no, customer name...">
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" class="btn btn-primary" onclick="loadTransactions()">
                            <i class="fas fa-search"></i> Filter Results
                        </button>
                        <button type="button" class="btn" style="background: #e2e8f0; color: #4a5568; margin-left: 10px;" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    </div>
                </div>

                <div id="transactions-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <div>Loading transactions...</div>
                    </div>
                </div>

                <div id="pagination-container" style="display: none;">
                    <div class="pagination"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let isLoading = false;

        function switchTab(tab) {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
            document.getElementById(`${tab}-tab`).classList.add('active');
            
            // Load transactions when switching to view tab
            if (tab === 'view') {
                loadTransactions();
            }
        }

        function loadTransactions(page = 1) {
            if (isLoading) return;
            
            isLoading = true;
            currentPage = page;
            
            const dateFrom = document.getElementById('filter_date_from').value;
            const dateTo = document.getElementById('filter_date_to').value;
            const incomeLine = document.getElementById('filter_income_line').value;
            const search = document.getElementById('filter_search').value;
            
            const params = new URLSearchParams({
                ajax: '1',
                page: page,
                limit: 25,
                date_from: dateFrom,
                date_to: dateTo,
                income_line: incomeLine,
                search: search
            });
            
            // Show loading
            document.getElementById('transactions-container').innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <div>Loading transactions...</div>
                </div>
            `;
            
            fetch(`post_collections.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTransactions(data.data);
                        updatePagination(data.page, data.totalPages, data.total);
                    } else {
                        document.getElementById('transactions-container').innerHTML = `
                            <div class="loading">
                                <i class="fas fa-exclamation-triangle" style="color: #f56565;"></i>
                                <div>Error: ${data.error}</div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('transactions-container').innerHTML = `
                        <div class="loading">
                            <i class="fas fa-exclamation-triangle" style="color: #f56565;"></i>
                            <div>Error loading transactions: ${error.message}</div>
                        </div>
                    `;
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        function displayTransactions(transactions) {
            let html = '';
            
            if (transactions.length > 0) {
                html = `
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Income Line</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                transactions.forEach(transaction => {
                    const status = getStatusBadge(transaction);
                    html += `
                        <tr>
                            <td>${transaction.receipt_no}</td>
                            <td>${formatDate(transaction.date_of_payment)}</td>
                            <td>${transaction.customer_name || 'N/A'}</td>
                            <td>${formatCurrency(transaction.amount_paid)}</td>
                            <td>${transaction.income_line}</td>
                            <td>${status}</td>
                            <td>
                                <button class="btn btn-primary" onclick="viewTransaction(${transaction.id})" style="padding: 6px 12px; font-size: 12px;">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            } else {
                html = `
                    <div class="loading">
                        <i class="fas fa-inbox" style="color: #e2e8f0;"></i>
                        <div>No transactions found</div>
                    </div>
                `;
            }
            
            document.getElementById('transactions-container').innerHTML = html;
        }

        function updatePagination(page, totalPages, total) {
            currentPage = page;
            totalPages = totalPages;
            
            let paginationHtml = `
                <div style="text-align: center; margin-bottom: 15px; color: #718096;">
                    Showing results for page ${page} of ${totalPages} (${total} total)
                </div>
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
            `;
            
            // Previous button
            paginationHtml += `
                <button onclick="loadTransactions(${page - 1})" ${page <= 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
            `;
            
            // Page numbers
            const startPage = Math.max(1, page - 2);
            const endPage = Math.min(totalPages, page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <button onclick="loadTransactions(${i})" ${i === page ? 'class="active"' : ''}>
                        ${i}
                    </button>
                `;
            }
            
            // Next button
            paginationHtml += `
                <button onclick="loadTransactions(${page + 1})" ${page >= totalPages ? 'disabled' : ''}>
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            `;
            
            paginationHtml += '</div>';
            
            document.querySelector('#pagination-container .pagination').innerHTML = paginationHtml;
            document.getElementById('pagination-container').style.display = 'block';
        }

        function getStatusBadge(transaction) {
            let status = 'Unknown';
            let color = '#6c757d';
            
            if (transaction.leasing_post_status === 'pending') {
                status = 'Pending Leasing';
                color = '#f093fb';
            } else if (transaction.leasing_post_status === 'approved' && transaction.approval_status === 'pending') {
                status = 'Pending Account';
                color = '#4facfe';
            } else if (transaction.approval_status === 'approved' && transaction.verification_status === 'pending') {
                status = 'Pending Audit';
                color = '#fa709a';
            } else if (transaction.verification_status === 'verified') {
                status = 'Completed';
                color = '#48bb78';
            } else if (transaction.leasing_post_status === 'rejected' || transaction.approval_status === 'rejected' || transaction.verification_status === 'rejected') {
                status = 'Rejected';
                color = '#f56565';
            }
            
            return `<span style="background: ${color}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${status}</span>`;
        }

        function clearFilters() {
            document.getElementById('filter_date_from').value = '';
            document.getElementById('filter_date_to').value = '';
            document.getElementById('filter_income_line').value = '';
            document.getElementById('filter_search').value = '';
            loadTransactions();
        }

        function viewTransaction(id) {
            window.open('view_transaction.php?id=' + id, '_blank');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }

        function formatCurrency(amount) {
            return '₦' + parseFloat(amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);

        // Set default date to today
        document.getElementById('date_of_payment').value = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
