
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Initialize database connection
$db = new Database();
$user = new User();

// Get current date for display
$today = date('Y-m-d');

// Set default month and year
$selected_period_month_str = date('F'); // Current month name (e.g., April)
$selected_period_year_int = date('Y'); // Current year (e.g., 2025)
$selected_period_vchar = $selected_period_month_str . ' ' . $selected_period_year_int;

// Process selected month and year if provided
if (isset($_GET["smonth"]) && isset($_GET["syear"])) {
    $selected_period_month_str = $_GET["smonth"];
    $selected_period_year_int = $_GET["syear"];
    $selected_period_vchar = $selected_period_month_str . ' ' . $selected_period_year_int;
}

// Set page title
$page_title = 'Income ERP ' . $selected_period_vchar . ' Officer Performance Summary as at ' . $today;

// Construct month dates for SQL query
$month_num = date('m', strtotime($selected_period_month_str . ' 1'));
$start_date = $selected_period_year_int . '-' . $month_num . '-01';
$end_date = $selected_period_year_int . '-' . $month_num . '-' . date('t', strtotime($start_date));

// Get all officers with leasing_officer role
$leasing_officers = $user->getUsersByRole('leasing_officer');

// Fetch income line accounts
$query = "SELECT acct_id, acct_desc, acct_table_name 
          FROM accounts 
          WHERE income_line = 'Yes' 
          AND active = 'Yes' 
          ORDER BY acct_desc ASC";

$db->query($query);
$income_lines = $db->resultSet();

// Initialize officer data arrays
$officer_data = [];
$income_line_totals = [];
$grand_total = 0;

// Initialize income line totals
foreach ($income_lines as $line) {
    $income_line_totals[$line['acct_desc']] = 0;
}

// Prepare data for each officer
foreach ($leasing_officers as $officer) {
    $officer_id = $officer['id'];
    $officer_name = $officer['full_name'];
    
    // Get transactions posted by this officer in the selected period
    $query = "SELECT t.credit_account, a.acct_desc, SUM(t.amount_paid) as total_amount 
              FROM account_general_transaction_new t
              JOIN accounts a ON t.credit_account = a.acct_id
              WHERE t.posting_officer_id = :officer_id
              AND t.date_of_payment BETWEEN :start_date AND :end_date
              AND a.income_line = 'Yes'
              GROUP BY t.credit_account, a.acct_desc";
    
    $db->query($query);
    $db->bind(':officer_id', $officer_id);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    
    $collections = $db->resultSet();
    
    // Calculate total collected by this officer
    $total_collected = 0;
    $officer_collections = [];
    
    // Process each income line collection
    foreach ($collections as $collection) {
        $income_line = $collection['acct_desc'];
        $amount = $collection['total_amount'];
        
        $officer_collections[] = [
            'incomeLine' => $income_line,
            'amount' => $amount
        ];
        
        $total_collected += $amount;
        
        // Add to income line totals
        if (isset($income_line_totals[$income_line])) {
            $income_line_totals[$income_line] += $amount;
        }
    }
    
    // Add to grand total
    $grand_total += $total_collected;
    
    // Add officer data to array
    $officer_data[] = [
        'officerId' => $officer_id,
        'officerName' => $officer_name,
        'collections' => $officer_collections,
        'totalCollected' => $total_collected
    ];
}

// Function to format number with commas
function formatNumber($number) {
    return number_format($number, 0);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="Income ERP | Officer Performance Report">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
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
                
                <a href="index.php" class="sidebar-menu-item">
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
                
                <a href="mpr.php" class="sidebar-menu-item active">
                    <i class="fas fa-file-alt"></i> MPR
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
                    <h4 class="page-title">MPR | Officer by Officer Summary</h4>
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
                <!-- Period Selection Form -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form name="periodForm" method="get" action="mpr_officers.php">
                            <div class="row align-items-end">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="smonth">Month</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            <select name="smonth" class="form-control" id="smonth" required>
                                                <option value="">Select month...</option>
                                                <?php
                                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                                          'July', 'August', 'September', 'October', 'November', 'December'];
                                                foreach ($months as $month) {
                                                    $selected = ($selected_period_month_str == $month) ? 'selected' : '';
                                                    echo "<option value=\"{$month}\" {$selected}>{$month}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="syear">Year</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            <select name="syear" class="form-control" id="syear" required>
                                                <option value="">Select year...</option>
                                                <?php
                                                $current_year = date('Y');
                                                for ($year = $current_year - 2; $year <= $current_year + 2; $year++) {
                                                    $selected = ($selected_period_year_int == $year) ? 'selected' : '';
                                                    echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-sync-alt"></i> Load
                                    </button>
                                    <a href="mpr.php" class="btn btn-primary">
                                        <i class="fas fa-table"></i> View General Summary
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- MPR Summary Card -->
                <div class="card mb-4">
                    <div class="card-header bg-blue-100 text-blue-800">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            <?php echo $selected_period_vchar; ?> Officer Performance Summary
                        </h5>
                    </div>
                </div>
                
                <!-- Search & Export Controls -->
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <button id="copyButton" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button id="excelButton" class="btn btn-outline-success">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <label for="tableSearch" class="me-2">Search:</label>
                        <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Filter officers...">
                    </div>
                </div>
                
                <!-- Officer Performance Table -->
                <div class="table-responsive">
                    <table id="officerTable" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Officer Name</th>
                                <?php foreach ($income_lines as $line): ?>
                                    <th class="text-right"><?php echo $line['acct_desc']; ?></th>
                                <?php endforeach; ?>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php foreach($officer_data as $officer): ?>
                                <tr>
                                    <td><?php echo $officer['officerName']; ?></td>
                                    
                                    <?php foreach($income_lines as $line): ?>
                                        <?php
                                        $amount = 0;
                                        foreach($officer['collections'] as $collection) {
                                            if ($collection['incomeLine'] == $line['acct_desc']) {
                                                $amount = $collection['amount'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <td class="text-right">
                                            <?php echo formatNumber($amount); ?>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <td class="text-right font-weight-bold">
                                        <?php echo formatNumber($officer['totalCollected']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        
                        <tfoot>
                            <tr class="bg-light">
                                <th>Total</th>
                                <?php foreach($income_lines as $line): ?>
                                    <th class="text-right">
                                        <?php 
                                        $total = isset($income_line_totals[$line['acct_desc']]) ? 
                                            $income_line_totals[$line['acct_desc']] : 0;
                                        echo formatNumber($total);
                                        ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-right"><?php echo formatNumber($grand_total); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable with hidden buttons (we'll use our custom buttons)
        var table = $('#officerTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copyHtml5',
                    footer: true,
                    text: 'Copy Data',
                    className: 'hidden-button'
                },
                {
                    extend: 'excelHtml5',
                    footer: true,
                    text: 'Export to Excel',
                    title: '<?php echo $page_title; ?>',
                    className: 'hidden-button'
                }
            ],
            paging: false,
            searching: true,
            ordering: true,
            info: true
        });
        
        // Connect our custom buttons to DataTables buttons
        $('#copyButton').on('click', function() {
            $('.buttons-copy').click();
        });
        
        $('#excelButton').on('click', function() {
            $('.buttons-excel').click();
        });
        
        // Use custom search box
        $('#tableSearch').keyup(function() {
            table.search($(this).val()).draw();
        });
        
        // Hide DataTables buttons
        $('.hidden-button').hide();
    });
    </script>
</body>
</html>
