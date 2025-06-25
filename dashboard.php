
<?php
require_once 'helpers/session_helper.php';
require_once 'config/config.php';

// Check if user is logged in
requireLogin();

// Get user information from session
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;
$department = $_SESSION['department'] ?? null;
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Define department-specific features
$departmentFeatures = [
    'IT/E-Business' => [
        'admin' => true,
        'features' => [
            'User Management',
            'System Settings',
            'All Reports',
            'Chart of Accounts',
            'Full Transaction Access',
            'Approve All Posts',
            'System Backup',
            // Accounts features
            'Financial Reports',
            'Transaction Verification',
            'Income Summary',
            'MPR Reports',
            // Wealth Creation features
            'Post Collections',
            'Customer Management',
            'Collection Reports',
            'Income Tracking',
            'Cash Remittance',
            // Audit features
            'Verify Transactions',
            'Audit Reports',
            'Transaction History',
            'Compliance Monitoring',
            // Leasing features
            'Shop Rent Management',
            'Scroll Board Operations',
            'Lease Reports',
            'Property Management'
        ]
    ],
    'Accounts' => [
        'admin' => false,
        'features' => [
            'Approve Posts',
            'Financial Reports',
            'Transaction Verification',
            'Income Summary',
            'MPR Reports'
        ]
    ],
    'Wealth Creation' => [
        'admin' => false,
        'features' => [
            'Post Collections',
            'Customer Management',
            'Collection Reports',
            'Income Tracking'
        ]
    ],
    'Audit/Inspections' => [
        'admin' => false,
        'features' => [
            'Verify Transactions',
            'Audit Reports',
            'Transaction History',
            'Compliance Monitoring'
        ]
    ],
    'Leasing' => [
        'admin' => false,
        'features' => [
            'Shop Rent Management',
            'Scroll Board Operations',
            'Lease Reports',
            'Property Management'
        ]
    ]
];

$currentDeptFeatures = $departmentFeatures[$department] ?? ['admin' => false, 'features' => []];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income ERP Dashboard - <?= htmlspecialchars($department) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dept-badge {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-white text-xl font-bold flex items-center">
                        <i class="fas fa-chart-line mr-2"></i>
                        Income ERP System
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="dept-badge text-white px-3 py-1 rounded-full text-sm font-medium">
                        <?= htmlspecialchars($department) ?>
                    </div>
                    <div class="text-white text-sm">
                        Welcome, <?= htmlspecialchars($userName) ?>
                    </div>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                <?= htmlspecialchars($department) ?> Dashboard
            </h1>
            <p class="text-gray-600 text-lg">
                Role: <?= htmlspecialchars($userRole) ?> | 
                Email: <?= htmlspecialchars($userEmail) ?>
            </p>
        </div>

        <!-- Department Features Overview -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Department Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach($currentDeptFeatures['features'] as $feature): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900"><?= htmlspecialchars($feature) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Department-Specific Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <?php if($department === 'IT/E-Business'): ?>
            <!-- IT/E-Business Admin Panel -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-cogs text-purple-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">System Administration</h2>
                </div>
                <div class="space-y-3">
                    <a href="users.php" class="card-hover block bg-gradient-to-r from-purple-50 to-violet-50 p-4 rounded-lg border border-purple-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-users text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">User Management</span>
                        </div>
                    </a>
                    <a href="settings.php" class="card-hover block bg-gradient-to-r from-indigo-50 to-blue-50 p-4 rounded-lg border border-indigo-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-cog text-indigo-600 mr-3"></i>
                            <span class="font-medium text-gray-900">System Settings</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Accounts Functions for IT -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-calculator text-green-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Financial Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="approve_posts.php" class="card-hover block bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Approve Posts</span>
                        </div>
                    </a>
                    <a href="income_summary.html" class="card-hover block bg-gradient-to-r from-blue-50 to-cyan-50 p-4 rounded-lg border border-blue-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chart-pie text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Income Summary</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Wealth Creation Functions for IT -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-coins text-yellow-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Collection Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="post_collection.php" class="card-hover block bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-receipt text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Post Collections</span>
                        </div>
                    </a>
                    <a href="remittance.php" class="card-hover block bg-gradient-to-r from-orange-50 to-red-50 p-4 rounded-lg border border-orange-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-money-bill-wave text-orange-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Cash Remittance</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Audit Functions for IT -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Audit & Verification</h2>
                </div>
                <div class="space-y-3">
                    <a href="verify_transactions.php" class="card-hover block bg-gradient-to-r from-red-50 to-pink-50 p-4 rounded-lg border border-red-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-clipboard-check text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Verify Transactions</span>
                        </div>
                    </a>
                    <a href="transactions.php" class="card-hover block bg-gradient-to-r from-pink-50 to-purple-50 p-4 rounded-lg border border-pink-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-list text-pink-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Transaction History</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Leasing Functions for IT -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-teal-100 p-3 rounded-full mr-4">
                        <i class="fas fa-building text-teal-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Property & Leasing</h2>
                </div>
                <div class="space-y-3">
                    <a href="post_remittance.php?type=shop" class="card-hover block bg-gradient-to-r from-teal-50 to-cyan-50 p-4 rounded-lg border border-teal-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-store text-teal-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Shop Rent Management</span>
                        </div>
                    </a>
                    <a href="scroll_board_dashboard.php" class="card-hover block bg-gradient-to-r from-cyan-50 to-blue-50 p-4 rounded-lg border border-cyan-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chalkboard text-cyan-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Scroll Board Operations</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($department === 'Accounts'): ?>
            <!-- Accounts Department -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-calculator text-green-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Financial Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="approve_posts.php" class="card-hover block bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Approve Posts</span>
                        </div>
                    </a>
                    <a href="income_summary.html" class="card-hover block bg-gradient-to-r from-blue-50 to-cyan-50 p-4 rounded-lg border border-blue-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chart-pie text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Income Summary</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($department === 'Wealth Creation'): ?>
            <!-- Wealth Creation Department -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-coins text-yellow-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Collection Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="post_collection.php" class="card-hover block bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-receipt text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Post Collections</span>
                        </div>
                    </a>
                    <a href="remittance.php" class="card-hover block bg-gradient-to-r from-orange-50 to-red-50 p-4 rounded-lg border border-orange-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-money-bill-wave text-orange-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Cash Remittance</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Officer Management for Wealth Creation -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Officer Management</h2>
                </div>
                <div class="space-y-3">
                    <a href="officer_management.php" class="card-hover block bg-gradient-to-r from-purple-50 to-indigo-50 p-4 rounded-lg border border-purple-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-user-tie text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">View Officers & Assignments</span>
                        </div>
                    </a>
                    <a href="officers.php" class="card-hover block bg-gradient-to-r from-indigo-50 to-blue-50 p-4 rounded-lg border border-indigo-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-store text-indigo-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Individual Officer Shops</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($department === 'Audit/Inspections'): ?>
            <!-- Audit Department -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Audit & Verification</h2>
                </div>
                <div class="space-y-3">
                    <a href="verify_transactions.php" class="card-hover block bg-gradient-to-r from-red-50 to-pink-50 p-4 rounded-lg border border-red-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-clipboard-check text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Verify Transactions</span>
                        </div>
                    </a>
                    <a href="transactions.php" class="card-hover block bg-gradient-to-r from-pink-50 to-purple-50 p-4 rounded-lg border border-pink-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-list text-pink-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Transaction History</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if($department === 'Leasing'): ?>
            <!-- Leasing Department -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-teal-100 p-3 rounded-full mr-4">
                        <i class="fas fa-building text-teal-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Property & Leasing</h2>
                </div>
                <div class="space-y-3">
                    <a href="post_remittance.php?type=shop" class="card-hover block bg-gradient-to-r from-teal-50 to-cyan-50 p-4 rounded-lg border border-teal-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-store text-teal-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Shop Rent Management</span>
                        </div>
                    </a>
                    <a href="scroll_board_dashboard.php" class="card-hover block bg-gradient-to-r from-cyan-50 to-blue-50 p-4 rounded-lg border border-cyan-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chalkboard text-cyan-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Scroll Board Operations</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Common Features for All Departments -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Reports & Analytics</h2>
                </div>
                <div class="space-y-3">
                    <a href="mpr.php" class="card-hover block bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-file-alt text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Monthly Performance Report</span>
                        </div>
                    </a>
                    <a href="power_consumption.html" class="card-hover block bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-lg border border-indigo-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-bolt text-indigo-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Power Consumption</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Recent Activity</h2>
            <div class="text-gray-600">
                <p>Welcome back, <?= htmlspecialchars($userName) ?>!</p>
                <p class="mt-2">Your last login: <?= date('F j, Y, g:i a') ?></p>
                <p class="mt-1">Department: <?= htmlspecialchars($department) ?></p>
                <p class="mt-1">Access Level: <?= $currentDeptFeatures['admin'] ? 'Administrator' : 'Standard User' ?></p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center py-6 border-t border-gray-200">
            <p class="text-gray-600">&copy; 2024 Income ERP System - <?= htmlspecialchars($department) ?> Department. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Add hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
