
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Use session data directly
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$department = $_SESSION['department'];
$userRole = $_SESSION['user_role'];

// Initialize database
$db = new Database();

// Get all officers from Wealth Creation department
$db->query("SELECT DISTINCT s.user_id, s.user_name, s.email, s.department, s.user_role,
           COUNT(t.id) as total_transactions,
           SUM(t.amount_paid) as total_amount,
           MAX(t.posting_time) as last_transaction
           FROM staffs s
           LEFT JOIN account_general_transaction_new t ON s.user_id = t.posting_officer_id
           WHERE s.department = 'Wealth Creation'
           GROUP BY s.user_id, s.user_name, s.email, s.department, s.user_role
           ORDER BY s.user_name");
$officers = $db->resultSet();

// Get current officer's assigned shops if they exist
$assigned_shops = [];
if (hasDepartment('Wealth Creation')) {
    // This would typically come from a shop_assignments table
    // For now, we'll simulate it based on transaction history
    $db->query("SELECT DISTINCT shop_no, shop_id, customer_name, 
               COUNT(*) as transaction_count,
               SUM(amount_paid) as total_collected,
               MAX(date_of_payment) as last_payment
               FROM account_general_transaction_new 
               WHERE posting_officer_id = :officer_id 
               AND shop_no IS NOT NULL 
               GROUP BY shop_no, shop_id, customer_name
               ORDER BY last_payment DESC");
    $db->bind(':officer_id', $userId);
    $assigned_shops = $db->resultSet();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officers Management - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .section-header {
            background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%);
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!--Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        <span class="text-xl font-bold text-gray-900">Income ERP</span>
                    </div>
                    <div class="ml-8">
                        <h1 class="text-lg font-semibold text-gray-900">Officers & Shops Management</h1>
                        <p class="text-sm text-gray-500">Manage Officers and Assigned Shops</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="transactions.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Transactions
                    </a>
                    
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($userName) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($department) ?></div>
                    </div>
                    <div class="relative">
                        <button class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors" onclick="toggleDropdown()">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                <?= strtoupper($userName[0]) ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 hidden z-50">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <a href="transactions.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-exchange-alt mr-2"></i> Transactions
                            </a>
                            <div class="border-t my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Officers List -->
        <div class="mb-8">
            <div class="section-header px-4 py-3 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">All Officers</h2>
            </div>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="officersTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Officer Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Transactions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Transaction</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($officers as $officer): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                                                <?= strtoupper($officer['user_name'][0]) ?>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($officer['user_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($officer['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($officer['user_role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $officer['total_transactions'] ?? 0 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        <?= formatCurrency($officer['total_amount'] ?? 0) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $officer['last_transaction'] ? formatDate($officer['last_transaction']) : 'Never' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewOfficerDetails(<?= $officer['user_id'] ?>)" 
                                               class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye mr-1"></i>View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- My Assigned Shops (only for current officer) -->
        <?php if (hasDepartment('Wealth Creation') && !empty($assigned_shops)): ?>
        <div class="mb-8">
            <div class="section-header px-4 py-3 mb-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">My Assigned Shops</h2>
                <button onclick="showMyShops()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-store mr-2"></i>Show All My Shops
                </button>
            </div>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="shopsTable">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Transactions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Collected</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($assigned_shops as $shop): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($shop['shop_no']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($shop['shop_id'] ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($shop['customer_name'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $shop['transaction_count'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        <?= formatCurrency($shop['total_collected']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= formatDate($shop['last_payment']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewShopHistory('<?= $shop['shop_no'] ?>')" 
                                               class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-history mr-1"></i>View History
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Officers</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($officers) ?></p>
                    </div>
                </div>
            </div>

            <?php if (hasDepartment('Wealth Creation')): ?>
            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-store text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">My Shops</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($assigned_shops) ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-card rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Collected</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $total = 0;
                            foreach($assigned_shops as $shop) {
                                $total += $shop['total_collected'];
                            }
                            echo formatCurrency($total);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#officersTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });

            $('#shopsTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[5, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });
        });

        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick) {
                dropdown.classList.add('hidden');
            }
        });

        // View officer details
        function viewOfficerDetails(officerId) {
            // This could open a modal or redirect to a detailed view
            alert('Officer details for ID: ' + officerId);
        }

        // Show all shops for current officer
        function showMyShops() {
            // Highlight the shops table
            document.getElementById('shopsTable').parentElement.parentElement.scrollIntoView({
                behavior: 'smooth'
            });
        }

        // View shop transaction history
        function viewShopHistory(shopNo) {
            // This could open a modal or redirect to transaction history
            window.location.href = 'transactions.php?shop_no=' + encodeURIComponent(shopNo);
        }
    </script>
</body>
</html>
