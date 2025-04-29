
<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../helpers/session_helper.php';

// Allow for development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Check if user is logged in or has API key
if (!isset($_SESSION['user_id']) && !isset($_GET['api_key'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get query parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('F');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Validate parameters
if (empty($month) || empty($year)) {
    http_response_code(400);
    echo json_encode(['error' => 'Month and year are required parameters']);
    exit;
}

// Initialize database connection
$db = new Database();

try {
    // Convert month name to number for queries
    $month_num = date('m', strtotime($month . ' 1'));
    $start_date = $year . '-' . $month_num . '-01';
    $end_date = $year . '-' . $month_num . '-' . date('t', strtotime($start_date));
    
    // Get income line accounts
    $query = "SELECT acct_id, acct_desc, acct_table_name 
              FROM accounts 
              WHERE income_line = 'Yes' 
              AND active = 'Yes' 
              ORDER BY acct_desc ASC";
              
    $db->query($query);
    $income_lines = $db->resultSet();
    
    // Prepare data structure
    $mpr_data = [];
    $daily_totals = [];
    $grand_total = 0;
    
    for ($day = 1; $day <= 31; $day++) {
        if (!checkdate((int)$month_num, $day, (int)$year)) {
            continue;
        }
        $daily_totals[$day] = 0;
    }
    
    // Process each income line
    foreach ($income_lines as $income_line) {
        $acct_id = $income_line["acct_id"];
        $acct_desc = $income_line["acct_desc"];
        
        // Initialize days data and total
        $days_data = [];
        $line_total = 0;
        
        // Get daily collection data
        for ($day = 1; $day <= 31; $day++) {
            // Skip invalid dates
            if (!checkdate((int)$month_num, $day, (int)$year)) {
                continue;
            }
            
            $day_formatted = str_pad($day, 2, '0', STR_PAD_LEFT);
            $date = $year . '-' . $month_num . '-' . $day_formatted;
            
            // Query for daily amount
            $query = "SELECT SUM(amount_paid) as total 
                      FROM account_general_transaction_new 
                      WHERE credit_account = :acct_id 
                      AND DATE(date_of_payment) = :date";
                      
            $db->query($query);
            $db->bind(':acct_id', $acct_id);
            $db->bind(':date', $date);
            $result = $db->single();
            
            $amount = $result['total'] ? $result['total'] : 0;
            
            // Store in days data
            $days_data[$day] = $amount;
            $line_total += $amount;
            
            // Add to daily totals
            $daily_totals[$day] += $amount;
        }
        
        // Add to grand total
        $grand_total += $line_total;
        
        // Add this income line to MPR data
        $mpr_data[] = [
            'incomeLine' => $acct_desc,
            'days' => $days_data,
            'total' => $line_total
        ];
    }
    
    // Calculate Sundays for styling/formatting on the frontend
    $first_sunday = date("Y-m-d", strtotime("first Sunday of " . $month . " " . $year));
    $second_sunday = date("Y-m-d", strtotime("second Sunday of " . $month . " " . $year));
    $third_sunday = date("Y-m-d", strtotime("third Sunday of " . $month . " " . $year));
    $fourth_sunday = date("Y-m-d", strtotime("fourth Sunday of " . $month . " " . $year));
    $fifth_sunday = date("Y-m-d", strtotime("fifth Sunday of " . $month . " " . $year));
    
    $sundays = [
        (int)date('d', strtotime($first_sunday)),
        (int)date('d', strtotime($second_sunday)),
        (int)date('d', strtotime($third_sunday)),
        (int)date('d', strtotime($fourth_sunday))
    ];
    
    if (date('F', strtotime($fifth_sunday)) == $month) {
        $sundays[] = (int)date('d', strtotime($fifth_sunday));
    }
    
    // Prepare response
    $response = [
        'data' => $mpr_data,
        'period' => [
            'month' => $month,
            'year' => $year
        ],
        'totals' => $daily_totals + ['grandTotal' => $grand_total],
        'sundays' => $sundays
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
