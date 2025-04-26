<?php
session_start();
// print_r($_SESSION);
// exit;
// Start session if not already started
// if (session_status() === PHP_SESSION_NONE) {
//     session_name(SESSION_NAME);
//     session_start();
//     session_regenerate_id();
// }

function isLoggedIn() {
    return (isset($_SESSION['admin']) || isset($_SESSION['staff']));
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) { 
        redirect('login.php');
    }
}

// Flash message helper
function flash($name = '', $message = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            if (!empty($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            
            if (!empty($_SESSION[$name . '_class'])) {
                unset($_SESSION[$name . '_class']);
            }
            
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

function getLoggedInUserId() {
    if (isset($_SESSION['admin'])) {
        return $_SESSION['admin'];
    } elseif (isset($_SESSION['staff'])) {
        return $_SESSION['staff'];
    } else {
        return null;
    }
}

$userId = getLoggedInUserId();

// Check user role
// function hasRole($role) {
//     if (isLoggedIn() && $_SESSION['user_role'] === $role) {
//         return true;
//     } else {
//         return false;
//     }
// }

function hasDepartment($dept) {
    global $userDepartment;
    if ($userDepartment === $dept) {
        return true;
    } else {
        return false;
    }
}

// Redirect if not authorized for the role
function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['user_role'] !== $role) {
        redirect('unauthorized.php');
    }
}

// Redirect if not authorized for any of the roles
function requireAnyRole($roles = []) {
    if (!isLoggedIn() || !in_array($_SESSION['user_role'], $roles)) {
        redirect('unauthorized.php');
    }
}

function requireAnyDepartment($departments = []) {
    global $userId;
    $user = new User();
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $userId = getLoggedInUserId();
    $department = $user->getDepartmentByUserIdstring($userId); // make sure this function exists

    if (!in_array($department, $departments)) {
        redirect('unauthorized.php');
    }
}


// General redirect function
function redirect($location) {
    header('location: ' . APP_URL . '/' . $location);
    exit;
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token']) {
        return true;
    }
    
    return false;
}

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format currency
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = 'm/d/Y') {
    return date($format, strtotime($date));
}

// Get current page name without extension
function getCurrentPage() {
    return pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
}
?>
