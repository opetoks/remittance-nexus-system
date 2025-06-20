
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

try {
    $db = new Database();
    
    // Check user credentials
    $db->query('SELECT * FROM users WHERE email = :email AND status = "active"');
    $db->bind(':email', $email);
    $user = $db->single();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    // Verify password (assuming you're using password_hash/password_verify)
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    // Get staff information and department
    $db->query('SELECT * FROM staffs WHERE user_id = :user_id');
    $db->bind(':user_id', $user['id']);
    $staff = $db->single();
    
    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Staff record not found']);
        exit;
    }
    
    // Determine role based on department
    $department = strtolower($staff['department']);
    $role = '';
    
    switch ($department) {
        case 'it/e-business':
            $role = 'admin';
            break;
        case 'accounts':
            $role = 'accounting_officer';
            break;
        case 'wealth creation':
            $role = 'wealth_creation';
            break;
        case 'audit/inspections':
            $role = 'auditor';
            break;
        case 'leasing':
            $role = 'leasing_officer';
            break;
        default:
            $role = strtolower($department) . '_officer';
            break;
    }
    
    // Remove sensitive data
    unset($user['password']);
    
    // Start session
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $role;
    $_SESSION['department'] = $staff['department'];
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'staff' => $staff,
        'role' => $role,
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
