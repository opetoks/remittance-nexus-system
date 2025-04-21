
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'income_erp');

// Application configuration
define('APP_NAME', 'Income ERP');
define('APP_URL', 'http://localhost/income_erp');
define('APP_VERSION', '1.0.0');

// Session configuration
define('SESSION_NAME', 'income_erp_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Date and time configuration
date_default_timezone_set('Africa/Lagos');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Secret key for token generation
define('SECRET_KEY', 'your_secret_key_here');
?>
