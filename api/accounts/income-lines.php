
<?php
header('Content-Type: application/json');
require_once '../config/Database.php';
require_once '../models/Account.php';

// Allow all origins for development (you may want to restrict this in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Initialize account model
    $account = new Account();
    
    // Get all income line accounts
    $accounts = $account->getIncomeLineAccounts();
    
    // Return the accounts as JSON
    echo json_encode($accounts);
} else {
    // Return error for non-GET requests
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}
?>
