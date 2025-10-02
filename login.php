<?php
session_start();
require_once 'config/Database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db = new Database();

            // Query user by username or email
            $db->query('SELECT * FROM users WHERE (username = :username OR email = :username) AND is_active = 1');
            $db->bind(':username', $username);
            $user = $db->single();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Update last login time
                $db->query('UPDATE users SET last_login = NOW() WHERE id = :user_id');
                $db->bind(':user_id', $user['id']);
                $db->execute();

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;

            } else {
                $error = 'Invalid username or password. Please try again.';
            }

        } catch (Exception $e) {
            $error = 'Login error. Please contact system administrator.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #06b6d4 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }
        .input-icon {
            pointer-events: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="login-card w-full max-w-md rounded-2xl shadow-2xl p-8 animate-fade-in">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center items-center gap-3 mb-4">
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-chart-line text-4xl text-blue-600"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Income ERP System</h1>
            <p class="text-gray-600">Sign in to your account</p>
        </div>

        <!-- Success Alert -->
        <?php if (!empty($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-1"></i> Username or Email
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                        placeholder="Enter your username or email"
                        required
                        autofocus
                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                    <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-1"></i> Password
                </label>
                <div class="relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        class="w-full px-4 py-3 pl-11 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                    <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    <button
                        type="button"
                        onclick="togglePassword()"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                        tabindex="-1"
                    >
                        <i id="toggleIcon" class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center shadow-lg hover:shadow-xl"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                Sign In
            </button>
        </form>

        <!-- Demo Credentials Info -->
        <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p class="text-sm text-blue-900 text-center font-medium mb-2">
                <i class="fas fa-info-circle mr-1"></i> Demo Credentials
            </p>
            <div class="grid grid-cols-1 gap-2 text-sm text-blue-800">
                <div class="flex justify-between items-center">
                    <span class="font-medium">Leasing Officer:</span>
                    <span class="font-mono">leasing1 / password123</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium">Account Officer:</span>
                    <span class="font-mono">account1 / password123</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium">Auditor:</span>
                    <span class="font-mono">auditor1 / password123</span>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="mt-8 grid grid-cols-3 gap-4 text-center">
            <div class="p-3">
                <i class="fas fa-shield-alt text-2xl text-blue-600 mb-2"></i>
                <p class="text-xs text-gray-600">Secure</p>
            </div>
            <div class="p-3">
                <i class="fas fa-chart-bar text-2xl text-blue-600 mb-2"></i>
                <p class="text-xs text-gray-600">Reports</p>
            </div>
            <div class="p-3">
                <i class="fas fa-clock text-2xl text-blue-600 mb-2"></i>
                <p class="text-xs text-gray-600">Real-time</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                International Standard Accounting System
            </p>
            <p class="text-xs text-gray-400 mt-2">
                &copy; <?= date('Y') ?> Income ERP System. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Add Enter key support
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.bg-green-50, .bg-red-50');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s, transform 0.5s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Prevent back button after logout
        if (window.location.search.includes('logout=success')) {
            if (window.history && window.history.pushState) {
                window.history.pushState(null, null, window.location.pathname);
            }
        }
    </script>
</body>
</html>
