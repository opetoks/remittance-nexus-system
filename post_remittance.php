
<?php
session_start();
require_once 'config/Database.php';
require_once 'models/Remittance.php';
require_once 'models/Transaction.php';
require_once 'models/Account.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize classes
$remittance = new Remittance();
$transaction = new Transaction();
$account = new Account();

// Get user details from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_department = $_SESSION['department'];

// Get remittances for dropdown - filter to only get what's necessary for performance
$remittances = $remittance->getRemittancesByOfficer($user_id);

// Get accounts for dropdown
$debit_accounts = $account->getDebitAccounts();
$credit_accounts = $account->getIncomeAccounts();

// Set default values
$error = false;
$success_message = "";
$error_message = "";
$receipt_error = "";
$amount_error = "";
$remittance_balance_error = "";
$debit_error = "";
$credit_error = "";

// If customer is provided in URL for preselection
$preselected_customer = isset($_GET['customer']) ? $_GET['customer'] : '';

// Determine income type from URL parameter
$incomeType = isset($_GET['type']) ? ucwords(str_replace('_', ' ', $_GET['type'])) : 'Car Park';

// Process form submission for regular transactions
if (isset($_POST['btn_post_transaction'])) {
    // Get remit ID based on department
    $remit_id = ($user_department == "Accounts") ? "" : (isset($_POST['remit_id']) ? trim($_POST['remit_id']) : "");
    
    // Check if remittance is required but not provided
    if ($user_department != "Accounts" && (empty($remit_id) || $remit_id == " ")) {
        $error = true;
        $error_message = "Please select a valid remittance ID";
    }
    
    // Generate transaction reference
    $txref = "TX" . time() . mt_rand(100, 999);
    
    // Process date
    $date_of_payment = $_POST['date_of_payment'];
    $formatted_date = date('Y-m-d', strtotime($date_of_payment));
    
    // Get and validate receipt number
    $receipt_no = trim($_POST['receipt_no']);
    
    // Check if receipt already exists
    $existing_transaction = $transaction->getTransactionByReceiptNo($receipt_no);
    if ($existing_transaction) {
        $error = true;
        $receipt_error = "<div class='alert alert-danger'>
            <strong>ATTENTION:</strong> Transaction failed! The receipt No: $receipt_no has already been used by 
            {$existing_transaction['posting_officer_name']} on {$existing_transaction['date_of_payment']}!
        </div>";
    }
    
    // Process amount
    $amount = $_POST['amount_paid'];
    $amount_paid = preg_replace('/[,]/', '', $amount);
    
    // Process remitting staff
    $remitting_post = $_POST['remitting_staff'];
    list($remitting_id, $remitting_type) = explode("-", $remitting_post);
    $remitting_staff = $_POST['remitting_name'];
    
    // Process transaction description
    $transaction_desc = trim($_POST['transaction_desc']);
    $transaction_desc = strip_tags($transaction_desc);
    $transaction_desc = htmlspecialchars($transaction_desc);
    
    // Set posting officer details
    $posting_officer_id = $user_id;
    $posting_officer_name = $user_name;
    
    // Process customer info for Scroll Board
    $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $start_date = isset($_POST['start_date']) ? date('Y-m-d', strtotime($_POST['start_date'])) : '';
    $end_date = isset($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $scroll_id = isset($_POST['scroll_id']) ? trim($_POST['scroll_id']) : '';
    
    // Check remittance balance if applicable for Wealth Creation department
    if ($user_department == "Wealth Creation" && !empty($remit_id)) {
        $selected_remittance = $remittance->getRemittanceByRemitId($remit_id);
        
        if ($selected_remittance) {
            // Get already posted transactions amount
            $posted_transactions = $transaction->getTransactionsByRemitId($remit_id);
            $posted_amount = 0;
            
            foreach ($posted_transactions as $posted) {
                $posted_amount += $posted['amount_paid'];
            }
            
            // Calculate remaining balance
            $remittance_total = $selected_remittance['amount_paid'];
            $unposted = $remittance_total - $posted_amount;
            
            // Check if amount exceeds remittance balance
            if ($amount_paid > $unposted) {
                $error = true;
                $amount_error = "<div class='alert alert-danger'>
                    <strong>ATTENTION:</strong> Transaction failed! The amount posted: ₦ {$amount_paid}
                    exceeds the remittance balance of ₦ {$unposted}!
                </div>";
            }
            
            // Validate that the displayed remittance balance matches the actual balance
            if (isset($_POST['amt_remitted']) && $_POST['amt_remitted'] > $unposted) {
                $error = true;
                $remittance_balance_error = "<div class='alert alert-danger'>
                    <strong>WARNING:</strong> Transaction failed! Your remittance balance is: ₦ {$unposted} 
                    and NOT ₦ {$_POST['amt_remitted']}. Please close all duplicate posting pages and re-open 
                    your posting page from the main navigation menu.
                </div>";
                
                // Could add email notification here if required
            }
        }
    }
    
    // Set transaction status based on department
    if ($user_department == "Accounts") {
        $leasing_post_status = "";
        $approval_status = "pending";
        $verification_status = "pending";
    } else {
        $leasing_post_status = "pending";
        $approval_status = "";
        $verification_status = "";
    }
    
    // Get account information
    $debit_alias = isset($_POST['debit_account']) ? $_POST['debit_account'] : "";
    $credit_alias = isset($_POST['credit_account']) ? $_POST['credit_account'] : "";
    
    // Validate account selections
    if (empty($debit_alias)) {
        $error = true;
        $debit_error = "Please select the debit account";
    }
    
    if (empty($credit_alias)) {
        $error = true;
        $credit_error = "Please select the credit account";
    }
    
    // Process ticket data
    $ticket_category = isset($_POST['ticket_category']) ? $_POST['ticket_category'] : null;
    $no_of_tickets = isset($_POST['no_of_tickets']) ? $_POST['no_of_tickets'] : null;
    
    // Get account details if no errors
    if (!$error) {
        // Get credit account details
        $credit_account = $account->getAccountByCode($credit_alias);
        
        // Set income line
        $income_line = isset($_POST['income_line']) ? $_POST['income_line'] : $incomeType . " Collection";
        
        // Prepare transaction data
        $transaction_data = [
            'ref_no' => $txref,
            'date_of_payment' => $formatted_date,
            'transaction_desc' => $transaction_desc,
            'receipt_no' => $receipt_no,
            'amount_paid' => $amount_paid,
            'remitting_id' => $remitting_id,
            'remitting_staff' => $remitting_staff,
            'posting_officer_id' => $posting_officer_id,
            'posting_officer_name' => $posting_officer_name,
            'leasing_post_status' => $leasing_post_status,
            'approval_status' => $approval_status,
            'verification_status' => $verification_status,
            'debit_account' => $debit_alias,
            'credit_account' => $credit_alias,
            'payment_category' => 'Other Collection',
            'remit_id' => $remit_id,
            'income_line' => $income_line
        ];
        
        // Add customer data for Scroll Board
        if ($incomeType == "Scroll Board") {
            $transaction_data['customer_name'] = $customer_name;
            $transaction_data['start_date'] = $start_date;
            $transaction_data['end_date'] = $end_date;
            $transaction_data['location'] = $location;
            $transaction_data['scroll_id'] = $scroll_id;
        } else {
            // Add car park specific data
            $transaction_data['ticket_category'] = $ticket_category;
            $transaction_data['no_of_tickets'] = $no_of_tickets;
            $transaction_data['plate_no'] = isset($_POST['plate_no']) ? $_POST['plate_no'] : '';
        }
        
        // Add transaction using our model
        $result = $transaction->addTransaction($transaction_data);
        
        if ($result) {
            $success_message = "<div class='alert alert-success'>
                <strong>Success!</strong> Payment successfully posted for approval!
            </div>";
            
            // Clear form data on success
            $_POST = array();
        } else {
            $error_message = "<div class='alert alert-danger'>
                <strong>Error!</strong> An error occurred while posting the transaction.
            </div>";
        }
    }
}

// Calculate remittance balance if remit_id is provided in query string
$selected_remittance = null;
$unposted_amount = 0;
$remittance_total = 0;

if (isset($_GET['remit_id']) && !empty($_GET['remit_id'])) {
    $selected_remit_id = $_GET['remit_id'];
    $selected_remittance = $remittance->getRemittanceByRemitId($selected_remit_id);
    
    if ($selected_remittance) {
        // Get already posted transactions
        $posted_transactions = $transaction->getTransactionsByRemitId($selected_remit_id);
        $posted_amount = 0;
        
        foreach ($posted_transactions as $posted) {
            $posted_amount += $posted['amount_paid'];
        }
        
        // Calculate remaining balance
        $remittance_total = $selected_remittance['amount_paid'];
        $unposted_amount = $remittance_total - $posted_amount;
    }
}

// Include header
include('include/header.php');
?>

<div class="container-fluid mt-3">
    <?php if(!empty($success_message)): ?>
        <?php echo $success_message; ?>
    <?php endif; ?>
    
    <?php if(!empty($error_message)): ?>
        <?php echo $error_message; ?>
    <?php endif; ?>
    
    <?php if(!empty($receipt_error)): ?>
        <?php echo $receipt_error; ?>
    <?php endif; ?>
    
    <?php if(!empty($amount_error)): ?>
        <?php echo $amount_error; ?>
    <?php endif; ?>
    
    <?php if(!empty($remittance_balance_error)): ?>
        <?php echo $remittance_balance_error; ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Income Lines Sidebar -->
        <div class="col-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Lines of Income</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><a href="?type=general">General</a></li>
                        <li class="list-group-item"><a href="?type=abattoir">Abattoir</a></li>
                        <li class="list-group-item"><a href="?type=car_loading">Car Loading Ticket</a></li>
                        <li class="list-group-item"><a href="?type=car_park" class="<?php echo ($incomeType == 'Car Park') ? 'fw-bold text-primary' : ''; ?>">Car Park Ticket</a></li>
                        <li class="list-group-item"><a href="?type=hawkers">Hawkers Ticket</a></li>
                        <li class="list-group-item"><a href="?type=wheelbarrow">WheelBarrow Ticket</a></li>
                        <li class="list-group-item"><a href="?type=daily_trade">Daily Trade</a></li>
                        <li class="list-group-item"><a href="?type=toilet">Toilet Collection</a></li>
                        <li class="list-group-item"><a href="?type=scroll_board" class="<?php echo ($incomeType == 'Scroll Board') ? 'fw-bold text-primary' : ''; ?>">Scroll Board</a></li>
                        <li class="list-group-item"><a href="?type=other_pos">Other POS Ticket</a></li>
                        <li class="list-group-item"><a href="?type=arrears">Daily Trade Arrears</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5><?php echo $incomeType; ?> <?php echo ($incomeType == 'Scroll Board') ? 'Collection' : 'Tickets'; ?></h5>
                </div>
                <div class="card-body">
                    <!-- Remittance Summary -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <?php if ($selected_remittance): ?>
                                            <strong>Remitted:</strong> ₦ <?php echo number_format($remittance_total, 2); ?> |
                                            <strong>Posted:</strong> ₦ <?php echo number_format($remittance_total - $unposted_amount, 2); ?> |
                                            <strong>Unposted:</strong> ₦ <?php echo number_format($unposted_amount, 2); ?>
                                        <?php else: ?>
                                            <strong>Remitted:</strong> ₦ 0.00 |
                                            <strong>Posted:</strong> ₦ 0.00 |
                                            <strong>Unposted:</strong> ₦ 0.00
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($user_department == "Accounts"): ?>
                                            <a href="post_past_payments.php" class="btn btn-sm btn-danger">Post Past Payments</a>
                                        <?php endif; ?>
                                        <?php if ($incomeType == "Scroll Board"): ?>
                                            <a href="scroll_board_dashboard.php" class="btn btn-sm btn-success">Scroll Board Dashboard</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Form -->
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_payment" class="form-label">Date of Payment:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="date_of_payment" name="date_of_payment" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                
                                <?php if ($user_department != "Accounts"): ?>
                                <div class="mb-3">
                                    <label for="remit_id" class="form-label">Remittances:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-file-invoice-dollar"></i></span>
                                        <select class="form-control" id="remit_id" name="remit_id" required>
                                            <option value="">Select...</option>
                                            <?php 
                                            // Only show remittances with unposted amounts to improve performance
                                            if (is_array($remittances)) {
                                                foreach($remittances as $remit): 
                                                    $posted_transactions = $transaction->getTransactionsByRemitId($remit['remit_id']);
                                                    $posted_amount = 0;
                                                    
                                                    foreach ($posted_transactions as $posted) {
                                                        $posted_amount += $posted['amount_paid'];
                                                    }
                                                    
                                                    $unposted = $remit['amount_paid'] - $posted_amount;
                                                    
                                                    // Only show remittances that have unposted amount
                                                    if ($unposted > 0):
                                            ?>
                                                <option value="<?php echo $remit['remit_id']; ?>" <?php echo (isset($_GET['remit_id']) && $_GET['remit_id'] == $remit['remit_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $remit['remit_id'] . ' - ₦' . number_format($unposted, 2) . ' remaining'; ?>
                                                </option>
                                            <?php 
                                                    endif;
                                                endforeach;
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <select class="form-control" id="category" name="category">
                                            <option value="Other Collection">Other Collection</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <?php if ($incomeType == "Scroll Board"): ?>
                                <!-- Scroll Board specific fields -->
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Customer Name:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                            value="<?php echo htmlspecialchars($preselected_customer); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="scroll_id" class="form-label">Scroll Board ID:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                        <input type="text" class="form-control" id="scroll_id" name="scroll_id">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="location" name="location">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Period Start Date:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-plus"></i></span>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Period End Date:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-minus"></i></span>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>
                                
                                <?php else: ?>
                                <!-- Car park ticket specific fields -->
                                <div class="mb-3">
                                    <label for="ticket_category" class="form-label">Ticket Category:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-ticket-alt"></i></span>
                                        <select class="form-control" id="ticket_category" name="ticket_category">
                                            <option value="500">500</option>
                                            <option value="200">200</option>
                                            <option value="100">100</option>
                                            <option value="50">50</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="no_of_tickets" class="form-label">Number of Tickets:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                                        <input type="number" class="form-control" id="no_of_tickets" name="no_of_tickets" min="1" onchange="calculateAmount()">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receipt_no" class="form-label">Receipt No:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-receipt"></i></span>
                                        <input type="text" class="form-control" id="receipt_no" name="receipt_no" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="amount_paid" class="form-label">Amount Remitted:</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input type="text" class="form-control" id="amount_paid" name="amount_paid" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="remitting_staff" class="form-label">Remitter's Name:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <select class="form-control" id="remitting_staff" name="remitting_staff" required>
                                            <option value="">Select...</option>
                                            <option value="1-wc">John Doe</option>
                                            <option value="2-wc">Jane Smith</option>
                                            <option value="3-ext">External Person</option>
                                        </select>
                                        <input type="hidden" id="remitting_name" name="remitting_name" value="">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="debit_account" class="form-label">Debit Account:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-minus-circle"></i></span>
                                        <select class="form-control" id="debit_account" name="debit_account" required>
                                            <option value="">Select...</option>
                                            <?php foreach($debit_accounts as $acct): ?>
                                            <option value="<?php echo $acct['acct_code']; ?>">
                                                <?php echo $acct['acct_desc']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if(!empty($debit_error)): ?>
                                        <span class="text-danger"><?php echo $debit_error; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="credit_account" class="form-label">Credit Account:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-plus-circle"></i></span>
                                        <select class="form-control" id="credit_account" name="credit_account" required>
                                            <option value="">Select...</option>
                                            <?php foreach($credit_accounts as $acct): ?>
                                            <option value="<?php echo $acct['acct_code']; ?>">
                                                <?php echo $acct['acct_desc']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if(!empty($credit_error)): ?>
                                        <span class="text-danger"><?php echo $credit_error; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="transaction_desc" class="form-label">Description:</label>
                                    <textarea class="form-control" id="transaction_desc" name="transaction_desc" rows="3" required></textarea>
                                </div>
                                
                                <input type="hidden" name="posting_officer_id" value="<?php echo $user_id; ?>">
                                <input type="hidden" name="posting_officer_name" value="<?php echo $user_name; ?>">
                                <input type="hidden" name="income_line" id="income_line" value="<?php echo $incomeType; ?> Collection">
                                <!-- Add this hidden field to track the actual remittance balance -->
                                <?php if ($selected_remittance): ?>
                                <input type="hidden" name="amt_remitted" value="<?php echo $unposted_amount; ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <button type="submit" name="btn_post_transaction" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Post Transaction
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto calculate amount based on ticket category and number of tickets
    if (document.getElementById('no_of_tickets')) {
        document.getElementById('no_of_tickets').addEventListener('change', calculateAmount);
        document.getElementById('ticket_category').addEventListener('change', calculateAmount);
    }
    
    // Update remitter name when selection changes
    document.getElementById('remitting_staff').addEventListener('change', function() {
        const selectBox = document.getElementById('remitting_staff');
        const selectedText = selectBox.options[selectBox.selectedIndex].text;
        document.getElementById('remitting_name').value = selectedText;
    });
    
    // Handle remittance selection change
    const remitSelect = document.getElementById('remit_id');
    if (remitSelect) {
        remitSelect.addEventListener('change', function() {
            const remitId = this.value;
            if (remitId) {
                window.location.href = 'post_remittance.php?type=<?php echo strtolower(str_replace(' ', '_', $incomeType)); ?>&remit_id=' + remitId;
            }
        });
    }
    
    // Set appropriate credit account based on income type
    const incomeType = "<?php echo $incomeType; ?>";
    const creditSelect = document.getElementById('credit_account');
    
    if (incomeType && creditSelect) {
        for (let i = 0; i < creditSelect.options.length; i++) {
            if (creditSelect.options[i].text.includes(incomeType)) {
                creditSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    // Calculate rental period
    if (document.getElementById('start_date')) {
        document.getElementById('start_date').addEventListener('change', calculateEndDate);
    }
    
    // Initialize any existing values
    if (document.getElementById('no_of_tickets')) {
        calculateAmount();
    }
});

function calculateAmount() {
    const ticketValue = parseInt(document.getElementById('ticket_category').value) || 0;
    const ticketCount = parseInt(document.getElementById('no_of_tickets').value) || 0;
    
    if (ticketValue && ticketCount) {
        const totalAmount = ticketValue * ticketCount;
        document.getElementById('amount_paid').value = totalAmount.toFixed(2);
    }
}

function calculateEndDate() {
    const startDate = document.getElementById('start_date').value;
    if (startDate) {
        // Default to 1 month rental period
        const endDateObj = new Date(startDate);
        endDateObj.setMonth(endDateObj.getMonth() + 1);
        endDateObj.setDate(endDateObj.getDate() - 1); // One day less for exact month
        
        const year = endDateObj.getFullYear();
        const month = String(endDateObj.getMonth() + 1).padStart(2, '0');
        const day = String(endDateObj.getDate()).padStart(2, '0');
        
        document.getElementById('end_date').value = `${year}-${month}-${day}`;
    }
}
</script>

<?php include('include/footer.php'); ?>
