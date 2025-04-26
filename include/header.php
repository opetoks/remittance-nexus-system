<header class="header">
    <div class="header-left">
        <button class="toggle-sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h4 class="page-title">Dashboard</h4>
    </div>
    
    <div class="header-right">
        <div class="user-dropdown">
            <button class="user-dropdown-toggle">
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span class="name"><?php echo $currentUser['full_name']; ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            
            <div class="user-dropdown-menu">
                <a href="profile.php" class="user-dropdown-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="change_password.php" class="user-dropdown-item">
                    <i class="fas fa-key"></i> Change Password
                </a>
                <div class="user-dropdown-divider"></div>
                <a href="logout.php" class="user-dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>