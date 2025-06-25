
<?php
require_once 'helpers/session_helper.php';
require_once 'config/config.php';

// Check if user is logged in and authorized
requireLogin();

// Check if user has access (Wealth Creation or IT/E-Business departments)
if (!in_array($_SESSION['department'] ?? '', ['Wealth Creation', 'IT/E-Business'])) {
    redirect('unauthorized.php');
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;
$department = $_SESSION['department'] ?? null;
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Management - Income ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dept-badge {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .officer-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .shop-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
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
                        <i class="fas fa-users mr-2"></i>
                        Officer Management
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="dept-badge text-white px-3 py-1 rounded-full text-sm font-medium">
                        <?= htmlspecialchars($department) ?>
                    </div>
                    <div class="text-white text-sm">
                        Welcome, <?= htmlspecialchars($userName) ?>
                    </div>
                    <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-dashboard mr-1"></i> Dashboard
                    </a>
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
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Officer Management</h1>
            <p class="text-gray-600 text-lg">Manage leasing officers and their assigned shops</p>
        </div>

        <!-- Statistics Overview -->
        <div id="statsOverview" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Stats will be loaded here -->
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="flex justify-center items-center py-12">
            <div class="loading-spinner"></div>
            <span class="ml-3 text-gray-600">Loading officers data...</span>
        </div>

        <!-- Officers Grid -->
        <div id="officersGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-8 hidden">
            <!-- Officers will be loaded here -->
        </div>

        <!-- Shop Details Modal -->
        <div id="shopModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg max-w-6xl w-full max-h-screen overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 id="modalTitle" class="text-xl font-bold text-gray-900"></h3>
                            <button onclick="closeShopModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div id="modalContent">
                            <!-- Shop details will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let officersData = [];

        // Load officers data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadOfficersData();
        });

        async function loadOfficersData() {
            try {
                const response = await fetch('api/officers/get_officers_with_shops.php');
                const result = await response.json();
                
                if (result.success) {
                    officersData = result.data;
                    displayStats(result);
                    displayOfficers(result.data);
                    document.getElementById('loadingState').classList.add('hidden');
                    document.getElementById('officersGrid').classList.remove('hidden');
                } else {
                    throw new Error(result.error || 'Failed to load data');
                }
            } catch (error) {
                console.error('Error loading officers data:', error);
                document.getElementById('loadingState').innerHTML = `
                    <div class="text-center text-red-600">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Error loading officers data. Please try again.</p>
                        <button onclick="loadOfficersData()" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Retry
                        </button>
                    </div>
                `;
            }
        }

        function displayStats(result) {
            const activeOfficers = result.data.filter(officer => officer.shops_count > 0).length;
            const inactiveOfficers = result.data.filter(officer => officer.shops_count === 0).length;
            
            document.getElementById('statsOverview').innerHTML = `
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Total Officers</p>
                            <p class="text-3xl font-bold text-gray-900">${result.total_officers}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-user-check text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Active Officers</p>
                            <p class="text-3xl font-bold text-gray-900">${activeOfficers}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-full mr-4">
                            <i class="fas fa-store text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Total Shops</p>
                            <p class="text-3xl font-bold text-gray-900">${result.total_shops}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover transition-transform duration-200">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-3 rounded-full mr-4">
                            <i class="fas fa-user-times text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Inactive Officers</p>
                            <p class="text-3xl font-bold text-gray-900">${inactiveOfficers}</p>
                        </div>
                    </div>
                </div>
            `;
        }

        function displayOfficers(officers) {
            const officersHTML = officers.map(officer => `
                <div class="officer-card rounded-xl shadow-lg p-6 card-hover transition-transform duration-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="bg-indigo-100 p-3 rounded-full mr-4">
                                <i class="fas fa-user text-indigo-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">${officer.officer_name}</h3>
                                <p class="text-gray-600">${officer.email}</p>
                                ${officer.phone ? `<p class="text-gray-500 text-sm">${officer.phone}</p>` : ''}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                ${officer.shops_count} Shops
                            </div>
                        </div>
                    </div>
                    
                    ${officer.shops_count > 0 ? `
                        <div class="space-y-2 mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2">Recent Assignments:</h4>
                            ${officer.shops.slice(0, 3).map(shop => `
                                <div class="shop-item p-3 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-medium text-gray-900">Shop ${shop.shop_no}</span>
                                            <span class="text-gray-600 ml-2">${shop.customer_name}</span>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">${shop.space_location}</div>
                                            <div class="text-sm font-medium text-green-600">₦${parseInt(shop.expected_rent).toLocaleString()}</div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                            ${officer.shops_count > 3 ? `
                                <p class="text-gray-500 text-sm">... and ${officer.shops_count - 3} more shops</p>
                            ` : ''}
                        </div>
                        
                        <button onclick="showShopDetails('${officer.officer_id}', '${officer.officer_name}')" 
                                class="w-full bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-eye mr-2"></i>View All Shops (${officer.shops_count})
                        </button>
                    ` : `
                        <div class="text-center py-4">
                            <i class="fas fa-store text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500">No shops assigned</p>
                        </div>
                    `}
                </div>
            `).join('');
            
            document.getElementById('officersGrid').innerHTML = officersHTML;
        }

        function showShopDetails(officerId, officerName) {
            const officer = officersData.find(o => o.officer_id == officerId);
            if (!officer) return;
            
            document.getElementById('modalTitle').textContent = `${officerName}'s Assigned Shops (${officer.shops_count})`;
            
            const shopsHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenancy</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Rent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Charge</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${officer.shops.map(shop => `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">${shop.shop_no}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-900">${shop.customer_name}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-900">${shop.space_location}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-900">${shop.space_size || 'N/A'}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-gray-900">${shop.current_tenancy}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-green-600 font-medium">₦${parseInt(shop.expected_rent || 0).toLocaleString()}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-blue-600 font-medium">₦${parseInt(shop.service_charge || 0).toLocaleString()}</div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('modalContent').innerHTML = shopsHTML;
            document.getElementById('shopModal').classList.remove('hidden');
        }

        function closeShopModal() {
            document.getElementById('shopModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('shopModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShopModal();
            }
        });
    </script>
</body>
</html>
