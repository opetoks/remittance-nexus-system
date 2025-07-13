<?php
header('Content-Type: application/json');
require_once 'config/config.php';
require_once 'config/Database.php';

$department = $_GET['department'] ?? '';

$db = new Database();
$conn = $db->getConnection();

$remitters = [];

try {
    if ($department == 'Wealth Creation') {
        // Get staff from wealth creation department
        $query = "SELECT user_id as id, full_name FROM staffs WHERE department = 'Wealth Creation' ORDER BY full_name ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($result as $row) {
            $remitters[] = [
                'value' => $row['id'] . '-wc',
                'full_name' => $row['full_name']
            ];
        }
        
        // Get other staff
        $query2 = "SELECT id, full_name, department FROM staffs_others ORDER BY full_name ASC";
        $stmt2 = $conn->prepare($query2);
        $stmt2->execute();
        $result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($result2 as $row) {
            $remitters[] = [
                'value' => $row['id'] . '-so',
                'full_name' => $row['full_name'] . ' - ' . $row['department']
            ];
        }
    } else {
        // For other departments, get all staff
        $query = "SELECT user_id as id, full_name FROM staffs ORDER BY full_name ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($result as $row) {
            $remitters[] = [
                'value' => $row['id'],
                'full_name' => $row['full_name']
            ];
        }
    }
    
    echo json_encode($remitters);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load remitters: ' . $e->getMessage()]);
}
?>