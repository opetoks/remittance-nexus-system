
<?php
// Include necessary files
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

// Page title
$page_title = "Officer Performance Summary";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income ERP | <?php echo $page_title; ?></title>
    <meta name="description" content="Income ERP | Officer Performance Report">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mpr.css">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="toggle-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="page-title">MPR | Officer by Officer Summary</h4>
                </div>
                
                <div class="header-right">
                    <?php include_once 'includes/user_dropdown.php'; ?>
                </div>
            </header>
            
            <!-- Content Body -->
            <div class="content-body">
                <!-- Period Selection Form -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form id="mprOfficerForm" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="smonth">Month</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            <select name="month" class="form-control" id="smonth" required>
                                                <option value="">Select month...</option>
                                                <?php
                                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                                          'July', 'August', 'September', 'October', 'November', 'December'];
                                                $current_month = date('F');
                                                foreach ($months as $month) {
                                                    $selected = ($current_month == $month) ? 'selected' : '';
                                                    echo "<option value=\"{$month}\" {$selected}>{$month}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label for="syear">Year</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            <select name="year" class="form-control" id="syear" required>
                                                <option value="">Select year...</option>
                                                <?php
                                                $current_year = date('Y');
                                                for ($year = $current_year - 2; $year <= $current_year + 2; $year++) {
                                                    $selected = ($current_year == $year) ? 'selected' : '';
                                                    echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-sync-alt"></i> Load
                                    </button>
                                    <a href="mpr.php" class="btn btn-primary">
                                        <i class="fas fa-table"></i> View General Summary
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- MPR Summary Card -->
                <div class="card mb-4">
                    <div class="card-header bg-blue-100 text-blue-800">
                        <h5 class="card-title mb-0" id="officerPeriodDisplay">
                            <i class="fas fa-users me-2"></i>
                            Loading...
                        </h5>
                    </div>
                </div>
                
                <!-- Search & Export Controls -->
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <button id="copyButton" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button id="excelButton" class="btn btn-outline-success">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <label for="tableSearch" class="me-2">Search:</label>
                        <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Filter officers...">
                    </div>
                </div>
                
                <!-- Officer Performance Table Container -->
                <div id="officerTableContainer" class="table-responsive">
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="assets/js/mpr_officers.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
