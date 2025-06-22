
<?php
session_start();

function isLoggedIn() {
    return (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']));
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
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    } else {
        return null;
    }
}

function hasDepartment($dept) {
    if (isset($_SESSION['department']) && $_SESSION['department'] === $dept) {
        return true;
    } else {
        return false;
    }
}

// Check user role
function hasRole($role) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role) {
        return true;
    } else {
        return false;
    }
}

// Redirect if not authorized for the role
function requireRole($role) {
    if (!isLoggedIn() || !hasRole($role)) {
        redirect('unauthorized.php');
    }
}

// Redirect if not authorized for any of the roles
function requireAnyRole($roles = []) {
    if (!isLoggedIn() || !in_array($_SESSION['user_role'], $roles)) {
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
