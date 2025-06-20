
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income ERP Dashboard - Remittance Nexus System</title>
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
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card-4 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
                    <a href="index.html" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="transactions.php" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-check-circle mr-1"></i> Verified
                    </a>
                    <a href="income_summary.html" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-chart-pie mr-1"></i> Revenue
                    </a>
                    <a href="#" class="text-white hover:text-gray-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Issues
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
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Income ERP Dashboard</h1>
            <p class="text-gray-600 text-lg">Comprehensive revenue management and monitoring system</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Total Revenue</p>
                        <h3 class="text-2xl font-bold">₦1,234,567</h3>
                        <p class="text-white/70 text-xs">+20.1% from last month</p>
                    </div>
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-2 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Pending Approvals</p>
                        <h3 class="text-2xl font-bold">25</h3>
                        <p class="text-white/70 text-xs">Transactions waiting approval</p>
                    </div>
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-3 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Active Customers</p>
                        <h3 class="text-2xl font-bold">152</h3>
                        <p class="text-white/70 text-xs">Across all income lines</p>
                    </div>
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card-4 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Issues</p>
                        <h3 class="text-2xl font-bold">12</h3>
                        <p class="text-white/70 text-xs">Need attention</p>
                    </div>
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Features Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Income Management -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <i class="fas fa-chart-pie text-blue-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Income Management</h2>
                </div>
                <p class="text-gray-600 mb-4">Comprehensive income tracking and analysis tools</p>
                <div class="space-y-3">
                    <a href="income_summary.html" class="card-hover block bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Income Summary</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">View detailed income analysis and reports</p>
                    </a>
                    <a href="mpr.php" class="card-hover block bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-file-alt text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Monthly Performance Report</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Generate and view MPR reports</p>
                    </a>
                </div>
            </div>

            <!-- Transaction Management -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exchange-alt text-green-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Transaction Management</h2>
                </div>
                <p class="text-gray-600 mb-4">Handle all financial transactions and approvals</p>
                <div class="space-y-3">
                    <a href="transactions.php" class="card-hover block bg-gradient-to-r from-purple-50 to-violet-50 p-4 rounded-lg border border-purple-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-list text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">All Transactions</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">View and manage all transactions</p>
                    </a>
                    <a href="approve_posts.php" class="card-hover block bg-gradient-to-r from-orange-50 to-amber-50 p-4 rounded-lg border border-orange-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-orange-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Approve Posts</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Review and approve pending transactions</p>
                    </a>
                    <a href="verify_transactions.php" class="card-hover block bg-gradient-to-r from-red-50 to-pink-50 p-4 rounded-lg border border-red-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-red-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Verify Transactions</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Audit and verify transactions</p>
                    </a>
                </div>
            </div>

            <!-- Power & Utilities -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-bolt text-yellow-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Power & Utilities</h2>
                </div>
                <p class="text-gray-600 mb-4">Monitor power consumption and utility management</p>
                <div class="space-y-3">
                    <a href="power_consumption.html" class="card-hover block bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-tachometer-alt text-yellow-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Power Consumption</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Track and analyze power usage</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Remittance & Collection -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-indigo-100 p-3 rounded-full mr-4">
                        <i class="fas fa-money-check-alt text-indigo-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Remittance Management</h2>
                </div>
                <p class="text-gray-600 mb-4">Handle cash remittances and collection posting</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <a href="remittance.php" class="card-hover block bg-gradient-to-r from-cyan-50 to-blue-50 p-4 rounded-lg border border-cyan-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-hand-holding-usd text-cyan-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Cash Remittance</span>
                        </div>
                    </a>
                    <a href="post_collection.php" class="card-hover block bg-gradient-to-r from-teal-50 to-green-50 p-4 rounded-lg border border-teal-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-receipt text-teal-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Post Collection</span>
                        </div>
                    </a>
                    <a href="post_remittance.php" class="card-hover block bg-gradient-to-r from-emerald-50 to-teal-50 p-4 rounded-lg border border-emerald-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-file-invoice-dollar text-emerald-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Post Remittance</span>
                        </div>
                    </a>
                    <a href="unposted_transactions.php" class="card-hover block bg-gradient-to-r from-rose-50 to-red-50 p-4 rounded-lg border border-rose-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-rose-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Unposted Transactions</span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-building text-purple-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Shop & Scroll Board</h2>
                </div>
                <p class="text-gray-600 mb-4">Manage shop rentals and scroll board operations</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <a href="post_remittance.php?type=shop" class="card-hover block bg-gradient-to-r from-violet-50 to-purple-50 p-4 rounded-lg border border-violet-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-store text-violet-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Shop Rent</span>
                        </div>
                    </a>
                    <a href="post_remittance.php?type=scroll_board" class="card-hover block bg-gradient-to-r from-fuchsia-50 to-pink-50 p-4 rounded-lg border border-fuchsia-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chalkboard text-fuchsia-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Scroll Board</span>
                        </div>
                    </a>
                    <a href="scroll_board_dashboard.php" class="card-hover block bg-gradient-to-r from-pink-50 to-rose-50 p-4 rounded-lg border border-pink-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-chart-bar text-pink-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Scroll Dashboard</span>
                        </div>
                    </a>
                    <a href="register.php" class="card-hover block bg-gradient-to-r from-indigo-50 to-blue-50 p-4 rounded-lg border border-indigo-100 transition-all duration-200">
                        <div class="flex items-center">
                            <i class="fas fa-user-plus text-indigo-600 mr-3"></i>
                            <span class="font-medium text-gray-900">User Registration</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-gray-100 p-3 rounded-full mr-4">
                    <i class="fas fa-rocket text-gray-600 text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Quick Actions</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="post_collection.php" class="card-hover flex flex-col items-center p-4 bg-blue-50 rounded-lg border border-blue-100 text-center transition-all duration-200">
                    <i class="fas fa-plus-circle text-blue-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">New Collection</span>
                </a>
                <a href="remittance.php" class="card-hover flex flex-col items-center p-4 bg-green-50 rounded-lg border border-green-100 text-center transition-all duration-200">
                    <i class="fas fa-money-bill text-green-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Cash Remit</span>
                </a>
                <a href="transactions.php" class="card-hover flex flex-col items-center p-4 bg-purple-50 rounded-lg border border-purple-100 text-center transition-all duration-200">
                    <i class="fas fa-search text-purple-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Search Trans.</span>
                </a>
                <a href="income_summary.html" class="card-hover flex flex-col items-center p-4 bg-orange-50 rounded-lg border border-orange-100 text-center transition-all duration-200">
                    <i class="fas fa-chart-pie text-orange-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Income Report</span>
                </a>
                <a href="power_consumption.html" class="card-hover flex flex-col items-center p-4 bg-yellow-50 rounded-lg border border-yellow-100 text-center transition-all duration-200">
                    <i class="fas fa-bolt text-yellow-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">Power Usage</span>
                </a>
                <a href="mpr.php" class="card-hover flex flex-col items-center p-4 bg-red-50 rounded-lg border border-red-100 text-center transition-all duration-200">
                    <i class="fas fa-file-alt text-red-600 text-2xl mb-2"></i>
                    <span class="text-sm font-medium text-gray-900">MPR Report</span>
                </a>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center py-6 border-t border-gray-200">
            <p class="text-gray-600">&copy; 2024 Income ERP System - Remittance Nexus. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Add smooth scrolling for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects and animations
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
