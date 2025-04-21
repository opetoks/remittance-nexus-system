
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    session_regenerate_id();
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

// Check if user is logged in
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    } else {
        return false;
    }
}

// Check user role
function hasRole($role) {
    if (isLoggedIn() && $_SESSION['user_role'] === $role) {
        return true;
    } else {
        return false;
    }
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
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
function formatDate($date, $format = 'd-M-Y') {
    return date($format, strtotime($date));
}

// Get current page name without extension
function getCurrentPage() {
    return pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
}
?>
