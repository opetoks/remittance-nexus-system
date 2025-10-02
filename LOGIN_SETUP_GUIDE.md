# Login System Setup Guide
## Direct PHP Authentication (No API/CORS Issues)

---

## What Was Changed

The login system has been completely rewritten to use **direct PHP authentication** instead of API calls. This eliminates all CORS (Cross-Origin Resource Sharing) errors and region issues.

### Old System (Had Issues):
```
Login Form → AJAX/Fetch → API Endpoint → CORS Error ❌
```

### New System (Works Directly):
```
Login Form → PHP POST → Database → Session → Dashboard ✅
```

---

## Installation Steps

### 1. Database Setup

```bash
# Open phpMyAdmin (http://localhost/phpmyadmin)
# Create the database
CREATE DATABASE income_erp_system;

# Import the schema
# File: database_schema_v2.sql

# Insert demo users
# File: insert_demo_users.sql
```

### 2. Configure Database Connection

Edit `config/config.php`:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'income_erp_system');
?>
```

### 3. Test the Login

1. Access: `http://localhost/your-project-folder/login.php`
2. Use any demo credential:

| Username | Password | Role |
|----------|----------|------|
| admin1 | password123 | Admin |
| leasing1 | password123 | Leasing Officer |
| leasing2 | password123 | Leasing Officer |
| account1 | password123 | Account Officer |
| auditor1 | password123 | Auditor |
| cashier1 | password123 | Cashier |

---

## How the New Login Works

### 1. Login Process (`login.php`)

```php
// Direct database query - no API call
$db = new Database();
$db->query('SELECT * FROM users WHERE (username = :username OR email = :username) AND is_active = 1');
$db->bind(':username', $username);
$user = $db->single();

// Verify password
if ($user && password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['full_name'];
    // ... etc

    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
}
```

### 2. Authentication Check (`helpers/auth_helper.php`)

```php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Use in any protected page:
session_start();
require_once 'helpers/auth_helper.php';
requireLogin();
```

### 3. Logout Process (`logout.php`)

```php
session_start();
$_SESSION = array();
session_destroy();
header('Location: login.php?logout=success');
exit;
```

---

## Key Features

### ✅ No CORS Issues
- Direct PHP execution
- No cross-origin requests
- Works on any server configuration

### ✅ Secure Password Storage
- Uses PHP `password_hash()`
- Bcrypt algorithm
- Cannot be reversed

### ✅ Session Management
- Server-side sessions
- Automatic timeout
- Secure cookie handling

### ✅ Role-Based Access
- Admin, Officer, Auditor roles
- Department-based permissions
- Helper functions for checks

---

## Helper Functions Available

```php
// Authentication
isLoggedIn()              // Check if user is logged in
requireLogin()            // Redirect if not logged in
checkRole(['admin'])      // Require specific role

// User Info
getUserId()               // Get current user ID
getUserName()             // Get current user name
getUserRole()             // Get current user role
getDepartment()           // Get user department

// Role Checks
isAdmin()                 // Check if admin
isAccountOfficer()        // Check if account officer
isLeasingOfficer()        // Check if leasing officer
isAuditor()               // Check if auditor

// Utilities
formatCurrency($amount)   // Format as ₦50,000.00
formatDate($date)         // Format as Jan 01, 2025
formatDateTime($datetime) // Full date/time
sanitize($data)           // Clean user input
```

---

## Usage Examples

### Protect a Page

```php
<?php
session_start();
require_once 'helpers/auth_helper.php';

// Require login
requireLogin();

// Or require specific role
checkRole(['admin', 'account_officer']);

// Get user info
$user_name = getUserName();
$user_role = getUserRole();
?>

<h1>Welcome, <?= htmlspecialchars($user_name) ?></h1>
<p>Your role: <?= htmlspecialchars($user_role) ?></p>
```

### Conditional Display

```php
<?php if (isAdmin()): ?>
    <a href="admin_panel.php">Admin Panel</a>
<?php endif; ?>

<?php if (isLeasingOfficer() || isAdmin()): ?>
    <a href="post_collection.php">Post Collection</a>
<?php endif; ?>
```

---

## Troubleshooting

### Issue: "Cannot modify header information"

**Cause:** Output sent before `header()` call

**Fix:** Ensure no spaces or output before `<?php` tag

```php
<?php  // No space before this
session_start();
// ... rest of code
```

### Issue: "Call to undefined function password_verify()"

**Cause:** PHP version < 5.5

**Fix:** Upgrade PHP to 5.5+ or use password_compat library

### Issue: Sessions not persisting

**Cause:** Session configuration issue

**Fix:** Check `php.ini`:
```ini
session.save_path = "/tmp"
session.cookie_httponly = 1
```

### Issue: Login works but dashboard shows blank

**Cause:** Database query error or missing tables

**Fix:**
1. Check error logs
2. Verify all tables are created
3. Enable error display temporarily:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

---

## Security Best Practices

### 1. Password Hashing
```php
// NEVER store plain passwords
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Always verify with password_verify()
if (password_verify($input_password, $hash)) {
    // Login successful
}
```

### 2. SQL Injection Prevention
```php
// Use prepared statements (already done in Database.php)
$db->query('SELECT * FROM users WHERE id = :id');
$db->bind(':id', $user_id);
$user = $db->single();
```

### 3. XSS Prevention
```php
// Always escape output
echo htmlspecialchars($user_input);
```

### 4. Session Security
```php
// Regenerate session ID on login
session_regenerate_id(true);

// Use HTTPOnly cookies
ini_set('session.cookie_httponly', 1);

// Use secure cookies (HTTPS only)
ini_set('session.cookie_secure', 1);
```

---

## File Structure

```
project/
├── login.php              # Login page (direct authentication)
├── logout.php             # Logout handler
├── dashboard.php          # Main dashboard (protected)
├── config/
│   ├── config.php         # Database configuration
│   └── Database.php       # PDO database class
├── helpers/
│   └── auth_helper.php    # Authentication functions
└── models/
    ├── TillManagement.php
    ├── GeneralLedger.php
    ├── ShopManagement.php
    └── CollectionManagement.php
```

---

## Testing the System

### 1. Test Login
```
URL: http://localhost/your-project/login.php
Username: leasing1
Password: password123
Expected: Redirect to dashboard
```

### 2. Test Protected Page
```
URL: http://localhost/your-project/dashboard.php
Without login: Redirect to login.php
With login: Show dashboard
```

### 3. Test Logout
```
Click Logout button
Expected: Redirect to login with success message
Try accessing dashboard: Redirect back to login
```

### 4. Test Role Access
```
Login as: leasing1
Try accessing: admin_panel.php (if exists)
Expected: "Access Denied" message
```

---

## Production Deployment

### Before Going Live:

1. **Change default passwords**
   ```sql
   UPDATE users SET password = '$2y$10$...' WHERE username = 'admin1';
   ```

2. **Disable error display**
   ```php
   ini_set('display_errors', 0);
   error_reporting(0);
   ```

3. **Enable HTTPS**
   ```php
   ini_set('session.cookie_secure', 1);
   ```

4. **Set strong session settings**
   ```ini
   session.cookie_httponly = 1
   session.cookie_secure = 1
   session.cookie_samesite = Strict
   session.gc_maxlifetime = 1800  # 30 minutes
   ```

5. **Regular backups**
   - Database backup daily
   - Code backup before updates

---

## Support

If you encounter issues:

1. Check PHP error logs
2. Verify database connection
3. Ensure all tables are created
4. Check session configuration
5. Verify file permissions

---

**System is now ready for XAMPP deployment with direct PHP authentication!**
