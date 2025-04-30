
<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/User.php';
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

try {
    // Initialize database and user model
    $db = new Database();
    $user = new User();
    
    // Convert month name to number for queries
    $month_num = date('m', strtotime($month . ' 1'));
    $start_date = $year . '-' . $month_num . '-01';
    $end_date = $year . '-' . $month_num . '-' . date('t', strtotime($start_date));
    
    // Get all officers with leasing_officer role
    $leasing_officers = $user->getUsersByRole('leasing_officer');
    
    // Fetch income line accounts
    $query = "SELECT acct_id, acct_desc 
              FROM accounts 
              WHERE income_line = 'Yes' 
              AND active = 'Yes' 
              ORDER BY acct_desc ASC";
              
    $db->query($query);
    $income_lines = $db->resultSet();
    
    // Initialize data arrays
    $officer_data = [];
    $income_line_totals = [];
    $grand_total = 0;
    
    // Initialize income line totals
    foreach ($income_lines as $line) {
        $income_line_totals[$line['acct_desc']] = 0;
    }
    
    // Process each officer
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
        
        // Process collections
        $total_collected = 0;
        $officer_collections = [];
        
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
        
        // Add officer data
        $officer_data[] = [
            'officerId' => $officer_id,
            'officerName' => $officer_name,
            'collections' => $officer_collections,
            'totalCollected' => $total_collected
        ];
    }
    
    // Prepare response
    $response = [
        'period' => [
            'month' => $month,
            'year' => $year
        ],
        'officers' => $officer_data,
        'incomeLines' => array_column($income_lines, 'acct_desc'),
        'incomeTotals' => $income_line_totals,
        'grandTotal' => $grand_total
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
