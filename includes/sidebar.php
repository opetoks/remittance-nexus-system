
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-chart-line"></i> Income ERP
        </div>
    </div>
    
    <div class="sidebar-menu">
        <div class="sidebar-menu-title">MAIN MENU</div>
        
        <a href="index.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <?php if(hasDepartment('IT/E-Business') || hasDepartment('Accounts')): ?>
        <a href="remittance.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'remittance.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> Remittances
        </a>
        <?php endif; ?>
        
        <?php if(hasDepartment('Wealth Creation')): ?>
        <a href="post_collection.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'post_collection.php' ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i> Post Collections
        </a>
        <?php endif; ?>
        
        <?php if(hasDepartment('Accounts')): ?>
        <a href="approve_posts.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'approve_posts.php' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Approve Posts
        </a>
        <?php endif; ?>
        
        <?php if(hasDepartment('Audit/Inspections')): ?>
        <a href="verify_transactions.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'verify_transactions.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> Verify Transactions
        </a>
        <?php endif; ?>
        
        <a href="transactions.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i> Transactions
        </a>
        
        <a href="mpr.php" class="sidebar-menu-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['mpr.php', 'mpr_officers.php']) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> MPR
        </a>
        
        <?php if(hasDepartment('IT/E-Business')): ?>
        <div class="sidebar-menu-title">ADMINISTRATION</div>
        
        <a href="accounts.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Chart of Accounts
        </a>
        
        <a href="users.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> User Management
        </a>
        
        <a href="reports.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Reports
        </a>
        
        <a href="settings.php" class="sidebar-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <?php endif; ?>
    </div>
</aside>
