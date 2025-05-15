
<?php
session_start();
require_once 'config/Database.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize Transaction model
$transaction = new Transaction();

// Get user details from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_department = $_SESSION['department'];

// Get scroll board transactions for the dashboard
$scrollBoardTransactions = $transaction->getScrollBoardTransactions(50);

// Get customer payment summary
$customerSummary = $transaction->getScrollBoardCustomerSummary();

// Include header
include('include/header.php');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-chalkboard"></i> Scroll Board Dashboard</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="scrollBoardTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="customer-summary-tab" data-toggle="tab" href="#customerSummary" role="tab">
                                <i class="fas fa-users"></i> Customer Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="transactions-tab" data-toggle="tab" href="#transactions" role="tab">
                                <i class="fas fa-list"></i> Recent Transactions
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="scrollBoardTabContent">
                        <!-- Customer Summary Tab -->
                        <div class="tab-pane fade show active" id="customerSummary" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="customersTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Customer Name</th>
                                            <th>Payments Count</th>
                                            <th>Total Amount</th>
                                            <th>Current End Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($customerSummary) > 0): ?>
                                            <?php foreach ($customerSummary as $customer): ?>
                                                <?php
                                                // Calculate status based on end date
                                                $endDate = new DateTime($customer['latest_end_date']);
                                                $today = new DateTime();
                                                $diff = $today->diff($endDate);
                                                $status = '';
                                                
                                                if ($endDate < $today) {
                                                    $status = '<span class="badge badge-danger">Expired</span>';
                                                } elseif ($diff->days <= 30) {
                                                    $status = '<span class="badge badge-warning">Expiring Soon</span>';
                                                } else {
                                                    $status = '<span class="badge badge-success">Active</span>';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                                    <td><?php echo $customer['total_payments']; ?></td>
                                                    <td><?php echo '₦' . number_format($customer['total_amount'], 2); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($customer['latest_end_date'])); ?></td>
                                                    <td><?php echo $status; ?></td>
                                                    <td>
                                                        <a href="post_remittance.php?type=scroll_board&customer=<?php echo urlencode($customer['customer_name']); ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-dollar-sign"></i> Post Payment
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No scroll board customers found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Transactions Tab -->
                        <div class="tab-pane fade" id="transactions" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="transactionsTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Receipt No</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Payment Date</th>
                                            <th>Period Start</th>
                                            <th>Period End</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($scrollBoardTransactions) > 0): ?>
                                            <?php foreach ($scrollBoardTransactions as $transaction): ?>
                                                <?php
                                                // Determine transaction status
                                                $statusText = 'Pending';
                                                $statusClass = 'warning';
                                                
                                                if ($transaction['verification_status'] == 'verified') {
                                                    $statusText = 'Verified';
                                                    $statusClass = 'success';
                                                } elseif ($transaction['approval_status'] == 'approved') {
                                                    $statusText = 'Approved';
                                                    $statusClass = 'info';
                                                } elseif ($transaction['leasing_post_status'] == 'approved') {
                                                    $statusText = 'Leasing Approved';
                                                    $statusClass = 'primary';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                                    <td><?php echo '₦' . number_format($transaction['amount_paid'], 2); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($transaction['date_of_payment'])); ?></td>
                                                    <td><?php echo !empty($transaction['start_date']) ? date('d-m-Y', strtotime($transaction['start_date'])) : '—'; ?></td>
                                                    <td><?php echo !empty($transaction['end_date']) ? date('d-m-Y', strtotime($transaction['end_date'])) : '—'; ?></td>
                                                    <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No transactions found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables for better user experience
    $('#customersTable').DataTable({
        "order": [[3, "desc"]],  // Sort by end date by default
        "pageLength": 10,
        "responsive": true
    });
    
    $('#transactionsTable').DataTable({
        "order": [[3, "desc"]],  // Sort by payment date by default
        "pageLength": 10,
        "responsive": true
    });
});
</script>

<?php include('include/footer.php'); ?>
