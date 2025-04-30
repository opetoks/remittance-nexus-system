
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
$page_title = 'Income ERP ' . $selected_period_vchar . ' Collection Summary as at ' . $today;

// Calculate Sundays for the selected month/year
$first_sunday = date("Y-m-d", strtotime("first Sunday of " . $selected_period_month_str . " " . $selected_period_year_int));
$second_sunday = date("Y-m-d", strtotime("second Sunday of " . $selected_period_month_str . " " . $selected_period_year_int));
$third_sunday = date("Y-m-d", strtotime("third Sunday of " . $selected_period_month_str . " " . $selected_period_year_int));
$fourth_sunday = date("Y-m-d", strtotime("fourth Sunday of " . $selected_period_month_str . " " . $selected_period_year_int));
$fifth_sunday = date("Y-m-d", strtotime("fifth Sunday of " . $selected_period_month_str . " " . $selected_period_year_int));

list($fsday_y, $fsday_m, $fsday_d) = explode("-", $first_sunday);
list($ssday_y, $ssday_m, $ssday_d) = explode("-", $second_sunday);
list($tsday_y, $tsday_m, $tsday_d) = explode("-", $third_sunday);
list($fosday_y, $fosday_m, $fosday_d) = explode("-", $fourth_sunday);
list($ffsday_y, $ffsday_m, $ffsday_d) = explode("-", $fifth_sunday);

// Initialize totals arrays
$daily_totals = [];
for ($i = 1; $i <= 31; $i++) {
    $daily_totals[$i] = 0;
}
$grand_total = 0;

// Fetch income line accounts
$query = "SELECT acct_id, acct_desc, acct_table_name, active, income_line 
          FROM accounts 
          WHERE income_line = 'Yes' 
          AND active = 'Yes' 
          ORDER BY acct_desc ASC";

$db->query($query);
$income_lines = $db->resultSet();

// Prepare data structure for MPR
$mpr_data = [];

// Process each income line to gather collection data
foreach ($income_lines as $income_line) {
    $acct_id = $income_line["acct_id"];
    $acct_desc = $income_line["acct_desc"];
    $ledger = $income_line["acct_table_name"];
    
    // Initialize data structure for this income line
    $line_data = [
        'income_line' => $acct_desc,
        'days' => [],
        'total' => 0
    ];
    
    // Get collection data for each day of the month
    for ($day = 1; $day <= 31; $day++) {
        // Format day with leading zero if needed
        $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
        
        // Create date string for query
        $date = $selected_period_year_int . '-' . date('m', strtotime($selected_period_month_str . ' 1')) . '-' . $day_formatted;
        
        // Skip invalid dates (e.g., Feb 30)
        if (!checkdate(date('m', strtotime($selected_period_month_str . ' 1')), $day, $selected_period_year_int)) {
            continue;
        }
        
        // Query for daily collection amount
        $query = "SELECT SUM(t.amount_paid) as total 
                  FROM account_general_transaction_new t 
                  WHERE t.credit_account = :acct_id 
                  AND DATE(t.date_of_payment) = :date";
        
        $db->query($query);
        $db->bind(':acct_id', $acct_id);
        $db->bind(':date', $date);
        $result = $db->single();
        
        $amount = $result['total'] ? $result['total'] : 0;
        
        // Store amount for this day
        $line_data['days'][$day] = $amount;
        $line_data['total'] += $amount;
        
        // Add to daily totals
        $daily_totals[$day] += $amount;
        $grand_total += $amount;
    }
    
    // Add this income line to MPR data
    $mpr_data[] = $line_data;
}

// Function to format number with commas
function formatNumber($number) {
    return number_format($number, 0);
}

// Function to check if a day is a Sunday
function isSunday($day, $fsday_d, $ssday_d, $tsday_d, $fosday_d, $ffsday_d) {
    return ($day == $fsday_d || $day == $ssday_d || $day == $tsday_d || $day == $fosday_d || $day == $ffsday_d);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="Income ERP | Monthly Performance Report">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .sunday-header {
            background-color: #ec7063 !important;
            color: white !important;
        }
        .sunday-cell {
            background-color: #fdf2f0 !important;
        }
        .day-label {
            color: #ec7063;
            font-size: 10px;
            display: block;
        }
    </style>
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
                    <h4 class="page-title">MPR | General Summary</h4>
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
                        <form name="periodForm" method="get" action="mpr.php">
                            <div class="row align-items-end">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="smonth" class="form-label">Month</label>
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
                                        <label for="syear" class="form-label">Year</label>
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
                                    <a href="mpr_officers.php" class="btn btn-danger">
                                        View Officer by Officer Summary
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- MPR Summary Card -->
                <div class="card mb-4">
                    <div class="card-header bg-red-100 text-red-800">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            <?php 
                            if (isset($_GET["smonth"]) && isset($_GET["syear"])) {
                                echo 'Selected Month: <strong>' . $selected_period_vchar . '</strong> Collection Summary as at ' . $today;
                            } else {
                                echo 'This Month: <strong>' . $selected_period_vchar . '</strong> Collection Summary as at ' . $today;
                            }
                            ?>
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
                        <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Filter income lines...">
                    </div>
                </div>
                
                <!-- MPR Table -->
                <div class="table-responsive">
                    <table id="mprTable" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Income Line</th>
                                <?php for($day = 1; $day <= 31; $day++): ?>
                                    <?php 
                                    $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
                                    $is_sunday = isSunday($day, $fsday_d, $ssday_d, $tsday_d, $fosday_d, $ffsday_d);
                                    $class = $is_sunday ? 'sunday-header' : '';
                                    ?>
                                    <th class="text-right <?php echo $class; ?>">
                                        <span class="day-label"><?php echo $is_sunday ? 'Sun' : 'Day'; ?></span>
                                        <?php echo $day_formatted; ?>
                                    </th>
                                <?php endfor; ?>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php foreach($mpr_data as $line): ?>
                                <tr>
                                    <td><?php echo $line['income_line']; ?></td>
                                    
                                    <?php for($day = 1; $day <= 31; $day++): ?>
                                        <?php 
                                        $is_sunday = isSunday($day, $fsday_d, $ssday_d, $tsday_d, $fosday_d, $ffsday_d);
                                        $class = $is_sunday ? 'sunday-cell' : '';
                                        $amount = isset($line['days'][$day]) ? $line['days'][$day] : 0;
                                        ?>
                                        <td class="text-right <?php echo $class; ?>">
                                            <?php echo formatNumber($amount); ?>
                                        </td>
                                    <?php endfor; ?>
                                    
                                    <td class="text-right font-weight-bold">
                                        <?php echo formatNumber($line['total']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        
                        <tfoot>
                            <tr class="bg-light">
                                <th></th>
                                <?php for($day = 1; $day <= 31; $day++): ?>
                                    <?php 
                                    $is_sunday = isSunday($day, $fsday_d, $ssday_d, $tsday_d, $fosday_d, $ffsday_d);
                                    $class = $is_sunday ? 'bg-danger text-white' : '';
                                    ?>
                                    <th class="text-right <?php echo $class; ?>">
                                        <?php echo formatNumber($daily_totals[$day]); ?>
                                    </th>
                                <?php endfor; ?>
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
        var table = $('#mprTable').DataTable({
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
            info: true,
            pageLength: 500000
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
