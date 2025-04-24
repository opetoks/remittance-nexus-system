
<?php
require_once '../config/config.php';
require_once '../config/Database.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

// Instantiate DB & connect
$database = new Database();
$db = $database->conn;

// Get shop number from URL
$shop_no = isset($_GET['shop_no']) ? $_GET['shop_no'] : '';

if(empty($shop_no)) {
    echo json_encode([
        'success' => false,
        'message' => 'Shop number is required'
    ]);
    exit();
}

try {
    // Get customer data
    $stmt = $db->prepare("SELECT * FROM customers_power_consumption WHERE shop_no = :shop_no");
    $stmt->bindParam(':shop_no', $shop_no);
    $stmt->execute();
    
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($customer) {
        echo json_encode([
            'success' => true,
            'customer' => $customer
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No customer found with shop number: ' . $shop_no
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
