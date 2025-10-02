<?php
// Authentication Helper Functions

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function checkRole($required_roles) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    if (!is_array($required_roles)) {
        $required_roles = array($required_roles);
    }

    if (!in_array($_SESSION['user_role'], $required_roles)) {
        die('Access Denied: You do not have permission to access this page.');
    }
}

function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getUserName() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
}

function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function getDepartment() {
    return isset($_SESSION['department']) ? $_SESSION['department'] : null;
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isAccountOfficer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'account_officer';
}

function isLeasingOfficer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'leasing_officer';
}

function isAuditor() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'auditor';
}

function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

function formatDate($date) {
    if (empty($date)) return '-';
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('M d, Y h:i A', strtotime($datetime));
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setMessage($key, $message, $type = 'info') {
    $_SESSION['messages'][$key] = array(
        'text' => $message,
        'type' => $type
    );
}

function getMessage($key) {
    if (isset($_SESSION['messages'][$key])) {
        $message = $_SESSION['messages'][$key];
        unset($_SESSION['messages'][$key]);
        return $message;
    }
    return null;
}

function showMessage($key) {
    $message = getMessage($key);
    if ($message) {
        $type_class = '';
        $icon = '';

        switch($message['type']) {
            case 'success':
                $type_class = 'bg-green-50 border-green-200 text-green-700';
                $icon = 'fa-check-circle';
                break;
            case 'error':
                $type_class = 'bg-red-50 border-red-200 text-red-700';
                $icon = 'fa-exclamation-triangle';
                break;
            case 'warning':
                $type_class = 'bg-yellow-50 border-yellow-200 text-yellow-700';
                $icon = 'fa-exclamation-circle';
                break;
            default:
                $type_class = 'bg-blue-50 border-blue-200 text-blue-700';
                $icon = 'fa-info-circle';
        }

        echo "<div class='$type_class border px-4 py-3 rounded-lg mb-6'>
                <div class='flex items-center'>
                    <i class='fas $icon mr-2'></i>
                    <span>" . htmlspecialchars($message['text']) . "</span>
                </div>
              </div>";
    }
}
?>
