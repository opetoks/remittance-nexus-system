
<?php
require_once '../../config/Database.php';
require_once '../../helpers/session_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in and has proper access
session_start();
if (!isLoggedIn() || !in_array($_SESSION['department'], ['Wealth Creation', 'IT/E-Business'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $database = new Database();
    
    // Get all leasing officers from Wealth Creation department
    $database->query("SELECT user_id, first_name, last_name, email, phone FROM staffs 
                     WHERE department = 'Wealth Creation' 
                     AND level = 'leasing officer' 
                     ORDER BY first_name ASC");
    
    $officers = $database->resultSet();
    
    $officersWithShops = [];
    
    foreach ($officers as $officer) {
        // Get active shops assigned to this officer
        $database->query("SELECT shop_no, customer_name, space_size, space_location, 
                         current_tenancy, start_date, expiry_date, expected_rent, service_charge 
                         FROM customers 
                         WHERE shop_no != '' 
                         AND staff_id = :staff_id 
                         AND facility_status = 'active'
                         ORDER BY shop_no ASC");
        
        $database->bind(':staff_id', $officer['user_id']);
        $shops = $database->resultSet();
        
        $officersWithShops[] = [
            'officer_id' => $officer['user_id'],
            'officer_name' => $officer['first_name'] . ' ' . $officer['last_name'],
            'email' => $officer['email'],
            'phone' => $officer['phone'] ?? '',
            'shops_count' => count($shops),
            'shops' => $shops
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $officersWithShops,
        'total_officers' => count($officersWithShops),
        'total_shops' => array_sum(array_column($officersWithShops, 'shops_count'))
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_officers_with_shops.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
