
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
requireAnyRole(['admin', 'manager', 'accounts', 'financial_controller']);

// Use session data directly
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];
$userRole = $_SESSION['user_role'];

// Initialize database
$database = new Database();

// Get filter parameters
$selected_month = isset($_GET['month']) ? sanitize($_GET['month']) : date('F');
$selected_year = isset($_GET['year']) ? sanitize($_GET['year']) : date('Y');

// Convert month name to number for queries
$month_num = date('m', strtotime($selected_month . ' 1'));
$start_date = $selected_year . '-' . $month_num . '-01';
$end_date = $selected_year . '-' . $month_num . '-' . date('t', strtotime($start_date));
$days_in_month = (int)date('t', strtotime($start_date));

// Get all unique income lines for the selected period
$query = "SELECT DISTINCT income_line 
          FROM account_general_transaction_new 
          WHERE income_line IS NOT NULL 
          AND income_line != '' 
          AND DATE(date_of_payment) BETWEEN :start_date AND :end_date
          ORDER BY income_line ASC";

$database->query($query);
$database->bind(':start_date', $start_date);
$database->bind(':end_date', $end_date);
$income_lines = $database->resultSet();

// Initialize data structures
$daily_analysis = [];
$daily_totals = [];
$grand_total = 0;

// Initialize daily totals
for ($day = 1; $day <= $days_in_month; $day++) {
    $daily_totals[$day] = 0;
}

// Calculate Sundays for styling
$sundays = [];
for ($week = 1; $week <= 5; $week++) {
    $sunday_date = date("Y-m-d", strtotime("{$week} Sunday of {$selected_month} {$selected_year}"));
    if (date('F', strtotime($sunday_date)) == $selected_month) {
        $sundays[] = (int)date('d', strtotime($sunday_date));
    }
}

// Process each income line
foreach ($income_lines as $income_line_row) {
    $income_line = $income_line_row['income_line'];
    
    $line_data = [
        'income_line' => $income_line,
        'days' => [],
        'total' => 0
    ];
    
    // Get daily data for this income line
    for ($day = 1; $day <= $days_in_month; $day++) {
        $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
        $date = $selected_year . '-' . $month_num . '-' . $day_formatted;
        
        $query = "SELECT SUM(amount_paid) as total 
                  FROM account_general_transaction_new 
                  WHERE income_line = :income_line 
                  AND DATE(date_of_payment) = :date";
                  
        $database->query($query);
        $database->bind(':income_line', $income_line);
        $database->bind(':date', $date);
        $result = $database->single();
        
        $amount = $result['total'] ? (float)$result['total'] : 0;
        
        $line_data['days'][$day] = $amount;
        $line_data['total'] += $amount;
        $daily_totals[$day] += $amount;
    }
    
    $grand_total += $line_data['total'];
    $daily_analysis[] = $line_data;
}

// Sort by total revenue descending
usort($daily_analysis, function($a, $b) {
    return $b['total'] <=> $a['total'];
});

// Helper function to check if day is Sunday
function isSunday($day, $sundays) {
    return in_array($day, $sundays);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Income Analysis - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <style>
        .sunday-header {
            background-color: #ef4444 !important;
            color: white !important;
        }
        .sunday-cell {
            background-color: #fef2f2 !important;
        }
        .day-header {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            min-width: 40px;
            font-size: 11px;
        }
        .income-line-cell {
            min-width: 150px;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .amount-cell {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .table-container {
            max-height: 70vh;
            overflow: auto;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
        }
        .sticky-column {
            position: sticky;
            left: 0;
            background: white;
            z-index: 5;
            border-right: 2px solid #e5e7eb;
        }
        .total-row {
            background-color: #f3f4f6 !important;
            font-weight: bold;
        }
        .total-column {
            background-color: #f9fafb !important;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-bar text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">Income ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">Daily Income Line Analysis</h1>
                        <p class="text-sm text-gray-500">Comprehensive daily performance by income line</p>
                    </div>
                </div>
                
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
                            <a href="income_performance_analysis.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-chart-line mr-2"></i> Performance Analysis
                            </a>
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
    <main class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Period Selection -->
        <div class="mb-6 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Period Selection</h2>
                <div class="flex gap-4">
                    <button id="copyBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i> Copy
                    </button>
                    <button id="excelBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i> Excel
                    </button>
                </div>
            </div>
            
            <form method="GET" class="flex items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                    <select name="month" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                  'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach ($months as $month) {
                            $selected = ($selected_month == $month) ? 'selected' : '';
                            echo "<option value=\"{$month}\" {$selected}>{$month}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                    <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php
                        $current_year = date('Y');
                        for ($year = $current_year - 2; $year <= $current_year + 1; $year++) {
                            $selected = ($selected_year == $year) ? 'selected' : '';
                            echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-search mr-2"></i> Load
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="mb-6 bg-white rounded-lg shadow p-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <h3 class="text-lg font-semibold text-red-800">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    This Month: <strong><?= $selected_month . ' ' . $selected_year ?></strong> Collection Summary as at <?= date('Y-m-d') ?>
                </h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 font-medium">Total Income Lines</div>
                    <div class="text-2xl font-bold text-blue-900"><?= count($daily_analysis) ?></div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600 font-medium">Grand Total</div>
                    <div class="text-2xl font-bold text-green-900"><?= formatCurrency($grand_total) ?></div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 font-medium">Average Daily</div>
                    <div class="text-2xl font-bold text-purple-900"><?= formatCurrency($grand_total / $days_in_month) ?></div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-sm text-orange-600 font-medium">Days in Month</div>
                    <div class="text-2xl font-bold text-orange-900"><?= $days_in_month ?></div>
                </div>
            </div>
        </div>

        <!-- Daily Analysis Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Daily Income Line Analysis</h3>
            </div>
            
            <div class="table-container">
                <table id="dailyAnalysisTable" class="min-w-full">
                    <thead class="sticky-header bg-gray-50">
                        <tr>
                            <th class="sticky-column px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Income Line
                            </th>
                            <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                                <?php 
                                $is_sunday = isSunday($day, $sundays);
                                $header_class = $is_sunday ? 'sunday-header' : 'bg-gray-50';
                                ?>
                                <th class="day-header px-2 py-3 text-center text-xs font-medium uppercase tracking-wider <?= $header_class ?>">
                                    <?= $is_sunday ? 'Sun' : 'Day' ?><br><?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>
                                </th>
                            <?php endfor; ?>
                            <th class="total-column px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                                Monthly Total
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($daily_analysis as $line): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="sticky-column income-line-cell px-6 py-4 text-sm font-medium text-gray-900" title="<?= htmlspecialchars($line['income_line']) ?>">
                                    <?= htmlspecialchars($line['income_line']) ?>
                                </td>
                                
                                <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                                    <?php 
                                    $is_sunday = isSunday($day, $sundays);
                                    $cell_class = $is_sunday ? 'sunday-cell' : '';
                                    $amount = isset($line['days'][$day]) ? $line['days'][$day] : 0;
                                    ?>
                                    <td class="amount-cell px-2 py-4 text-xs text-gray-700 <?= $cell_class ?>">
                                        <?= $amount > 0 ? number_format($amount, 0) : '' ?>
                                    </td>
                                <?php endfor; ?>
                                
                                <td class="total-column amount-cell px-4 py-4 text-sm font-bold text-green-600 bg-gray-50">
                                    <?= number_format($line['total'], 0) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    
                    <tfoot class="total-row bg-gray-100 sticky bottom-0">
                        <tr>
                            <th class="sticky-column px-6 py-4 text-left text-sm font-bold text-gray-900 bg-gray-100">
                                DAILY TOTAL
                            </th>
                            <?php for($day = 1; $day <= $days_in_month; $day++): ?>
                                <?php 
                                $is_sunday = isSunday($day, $sundays);
                                $cell_class = $is_sunday ? 'bg-red-100 text-red-800' : 'bg-gray-100';
                                ?>
                                <th class="amount-cell px-2 py-4 text-xs font-bold <?= $cell_class ?>">
                                    <?= number_format($daily_totals[$day], 0) ?>
                                </th>
                            <?php endfor; ?>
                            <th class="amount-cell px-4 py-4 text-sm font-bold text-green-800 bg-green-100">
                                <?= number_format($grand_total, 0) ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    
    <script>
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

        $(document).ready(function() {
            // Initialize DataTable with export functionality
            var table = $('#dailyAnalysisTable').DataTable({
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
                        title: 'Daily Income Analysis - <?= $selected_month . " " . $selected_year ?>',
                        className: 'hidden-button'
                    }
                ],
                paging: false,
                searching: true,
                ordering: true,
                info: false,
                scrollX: true,
                scrollY: '60vh',
                scrollCollapse: true,
                fixedColumns: {
                    leftColumns: 1
                }
            });
            
            // Connect custom buttons to DataTables buttons
            $('#copyBtn').on('click', function() {
                $('.buttons-copy').click();
            });
            
            $('#excelBtn').on('click', function() {
                $('.buttons-excel').click();
            });
            
            // Hide DataTables buttons
            $('.hidden-button').hide();
        });
    </script>
</body>
</html>
