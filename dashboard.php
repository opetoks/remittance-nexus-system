<?php
session_start();
require_once 'helpers/auth_helper.php';
require_once 'config/Database.php';

// Require login
requireLogin();

$db = new Database();

// Get user information
$user_id = getUserId();
$user_name = getUserName();
$user_role = getUserRole();
$department = getDepartment();

// Get dashboard statistics based on role
$stats = array();

try {
    // Total collections today
    $db->query('SELECT
                IFNULL(COUNT(id), 0) as total_count,
                IFNULL(SUM(amount), 0) as total_amount
                FROM collection_transactions
                WHERE DATE(created_at) = CURDATE()
                AND status IN ("collected", "posted")');
    $today_collections = $db->single();
    $stats['today_collections'] = $today_collections;

    // Total customers
    $db->query('SELECT COUNT(id) as total FROM customers WHERE is_active = 1');
    $customers = $db->single();
    $stats['total_customers'] = $customers['total'];

    // Total shops
    $db->query('SELECT
                COUNT(id) as total,
                SUM(CASE WHEN status = "occupied" THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = "vacant" THEN 1 ELSE 0 END) as vacant
                FROM shops WHERE is_active = 1');
    $shops = $db->single();
    $stats['shops'] = $shops;

    // Expiring leases (next 90 days)
    $db->query('SELECT COUNT(id) as total
                FROM lease_agreements
                WHERE status = "active"
                AND lease_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)');
    $expiring = $db->single();
    $stats['expiring_leases'] = $expiring['total'];

    // Recent collections
    $db->query('SELECT ct.*, il.income_name, u.full_name as collected_by_name
                FROM collection_transactions ct
                JOIN income_lines il ON ct.income_line_id = il.id
                JOIN users u ON ct.collected_by = u.id
                ORDER BY ct.created_at DESC
                LIMIT 10');
    $recent_collections = $db->resultSet();

} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="gradient-header shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-chart-line text-white text-2xl mr-3"></i>
                    <span class="text-white text-xl font-bold">Income ERP System</span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-white text-sm">
                        <i class="fas fa-user-circle mr-1"></i>
                        <span class="font-medium"><?= htmlspecialchars($user_name) ?></span>
                        <span class="text-blue-200 ml-2">(<?= htmlspecialchars(ucwords(str_replace('_', ' ', $user_role))) ?>)</span>
                    </div>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Welcome back, <?= htmlspecialchars($user_name) ?>!
            </h1>
            <p class="text-gray-600">
                <?= htmlspecialchars($department ? $department : 'General') ?> Department - <?= date('l, F j, Y') ?>
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Collections -->
            <div class="bg-white rounded-xl shadow-lg p-6 stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Today's Collections</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= formatCurrency($stats['today_collections']['total_amount']) ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            <?= number_format($stats['today_collections']['total_count']) ?> transactions
                        </p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Customers -->
            <div class="bg-white rounded-xl shadow-lg p-6 stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= number_format($stats['total_customers']) ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Active tenants</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Shop Occupancy -->
            <div class="bg-white rounded-xl shadow-lg p-6 stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Shop Occupancy</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= $stats['shops']['occupied'] ?>/<?= $stats['shops']['total'] ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            <?= $stats['shops']['vacant'] ?> vacant
                        </p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-store text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Expiring Leases -->
            <div class="bg-white rounded-xl shadow-lg p-6 stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Expiring Leases</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= number_format($stats['expiring_leases']) ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Next 90 days</p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-calendar-times text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                Quick Actions
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php if (isLeasingOfficer() || isAdmin()): ?>
                    <a href="collection_posting.php" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-center transition-colors">
                        <i class="fas fa-receipt text-blue-600 text-2xl mb-2"></i>
                        <p class="text-sm font-medium text-gray-900">Post Collection</p>
                    </a>
                <?php endif; ?>

                <a href="view_customers.php" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-users text-green-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-900">View Customers</p>
                </a>

                <a href="view_shops.php" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-store text-purple-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-900">View Shops</p>
                </a>

                <a href="reports.php" class="bg-orange-50 hover:bg-orange-100 p-4 rounded-lg text-center transition-colors">
                    <i class="fas fa-chart-bar text-orange-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-900">Reports</p>
                </a>
            </div>
        </div>

        <!-- Recent Collections -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-clock text-blue-500 mr-2"></i>
                Recent Collections
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Income Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($recent_collections)): ?>
                            <?php foreach ($recent_collections as $collection): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($collection['receipt_number']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?= formatDate($collection['transaction_date']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?= htmlspecialchars($collection['income_name']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <?= formatCurrency($collection['amount']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?= htmlspecialchars($collection['collected_by_name']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($collection['status'] == 'posted'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Posted
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Collected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No collections recorded yet</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> Income ERP System - International Standard Accounting</p>
        </div>
    </div>
</body>
</html>
