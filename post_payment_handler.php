<?php
session_start();
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Transaction.php';
require_once 'models/Remittance.php';
require_once 'models/Account.php';
require_once 'helpers/session_helper.php';

// Check if user is logged in
requireLogin();

$userId = getLoggedInUserId();

// Initialize objects
$db = new Database();
$user = new User();
$transaction = new Transaction();
$remittance = new Remittance();
$account = new Account();

$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get form data
        $income_line = sanitize($_POST['income_line']);
        $department = sanitize($_POST['department']);
        $posting_officer_id = sanitize($_POST['posting_officer_id']);
        $posting_officer_name = sanitize($_POST['posting_officer_name']);
        $posting_officer_dept = sanitize($_POST['posting_officer_dept']);
        $amount_paid = floatval(sanitize($_POST['amount_paid']));
        $date_of_payment = sanitize($_POST['date_of_payment']);
        
        // Common fields
        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $receipt_no = sanitize($_POST['receipt_no'] ?? '');
        $transaction_descr = sanitize($_POST['transaction_descr'] ?? '');
        
        // Car Loading specific fields
        $no_of_tickets = intval(sanitize($_POST['no_of_tickets'] ?? 0));
        $remitting_staff = sanitize($_POST['remitting_staff'] ?? '');
        $remit_id = sanitize($_POST['remit_id'] ?? '');
        $amt_remitted = floatval(sanitize($_POST['amt_remitted'] ?? 0));
        
        // Account fields (for Accounts department)
        $debit_account = sanitize($_POST['debit_account'] ?? 'till');
        $credit_account = sanitize($_POST['credit_account'] ?? $income_line);
        
        // Validation
        $errors = [];
        
        if (empty($income_line)) {
            $errors[] = "Income line is required";
        }
        
        if ($amount_paid <= 0) {
            $errors[] = "Amount must be greater than zero";
        }
        
        if (empty($date_of_payment)) {
            $errors[] = "Date of payment is required";
        }
        
        // Car Loading specific validation
        if ($income_line === 'Car Loading Ticket') {
            if (empty($receipt_no)) {
                $errors[] = "Receipt number is required for Car Loading";
            }
            
            if ($no_of_tickets <= 0) {
                $errors[] = "Number of tickets must be greater than zero";
            }
            
            if (empty($remitting_staff)) {
                $errors[] = "Remitting staff is required for Car Loading";
            }
            
            if ($department === 'Wealth Creation' && empty($remit_id)) {
                $errors[] = "Remittance ID is required for Wealth Creation department";
            }
        }
        
        if (empty($errors)) {
            // Prepare transaction data
            $transactionData = [
                'income_line' => $income_line,
                'customer_name' => $customer_name,
                'amount_paid' => $amount_paid,
                'date_of_payment' => $date_of_payment,
                'posting_officer_id' => $posting_officer_id,
                'posting_officer_name' => $posting_officer_name,
                'posting_officer_dept' => $posting_officer_dept,
                'receipt_no' => $receipt_no,
                'transaction_descr' => $transaction_descr,
                'no_of_tickets' => $no_of_tickets,
                'remitting_staff' => $remitting_staff,
                'remit_id' => $remit_id,
                'amt_remitted' => $amt_remitted,
                'debit_account' => $debit_account,
                'credit_account' => $credit_account
            ];
            
            // Add transaction to database
            $conn = $db->getConnection();
            
            // Insert into appropriate table based on income line
            if ($income_line === 'Car Loading Ticket') {
                $sql = "INSERT INTO car_loading_transactions 
                        (income_line, receipt_no, transaction_descr, no_of_tickets, amount_paid, 
                         remitting_staff, remit_id, date_of_payment, posting_officer_id, 
                         posting_officer_name, posting_officer_dept, amt_remitted, debit_account, credit_account) 
                        VALUES 
                        (:income_line, :receipt_no, :transaction_descr, :no_of_tickets, :amount_paid, 
                         :remitting_staff, :remit_id, :date_of_payment, :posting_officer_id, 
                         :posting_officer_name, :posting_officer_dept, :amt_remitted, :debit_account, :credit_account)";
            } else {
                $sql = "INSERT INTO general_transactions 
                        (income_line, customer_name, amount_paid, date_of_payment, posting_officer_id, 
                         posting_officer_name, posting_officer_dept, debit_account, credit_account) 
                        VALUES 
                        (:income_line, :customer_name, :amount_paid, :date_of_payment, :posting_officer_id, 
                         :posting_officer_name, :posting_officer_dept, :debit_account, :credit_account)";
            }
            
            $stmt = $conn->prepare($sql);
            
            if ($income_line === 'Car Loading Ticket') {
                $stmt->execute([
                    ':income_line' => $income_line,
                    ':receipt_no' => $receipt_no,
                    ':transaction_descr' => $transaction_descr,
                    ':no_of_tickets' => $no_of_tickets,
                    ':amount_paid' => $amount_paid,
                    ':remitting_staff' => $remitting_staff,
                    ':remit_id' => $remit_id,
                    ':date_of_payment' => $date_of_payment,
                    ':posting_officer_id' => $posting_officer_id,
                    ':posting_officer_name' => $posting_officer_name,
                    ':posting_officer_dept' => $posting_officer_dept,
                    ':amt_remitted' => $amt_remitted,
                    ':debit_account' => $debit_account,
                    ':credit_account' => $credit_account
                ]);
            } else {
                $stmt->execute([
                    ':income_line' => $income_line,
                    ':customer_name' => $customer_name,
                    ':amount_paid' => $amount_paid,
                    ':date_of_payment' => $date_of_payment,
                    ':posting_officer_id' => $posting_officer_id,
                    ':posting_officer_name' => $posting_officer_name,
                    ':posting_officer_dept' => $posting_officer_dept,
                    ':debit_account' => $debit_account,
                    ':credit_account' => $credit_account
                ]);
            }
            
            $success_msg = "Payment posted successfully for " . $income_line;
            
            // Redirect back with success message
            $_SESSION['success_msg'] = $success_msg;
            header('Location: post_collections.php');
            exit;
            
        } else {
            $error_msg = implode('<br>', $errors);
            $_SESSION['error_msg'] = $error_msg;
            header('Location: post_collections.php');
            exit;
        }
        
    } catch (Exception $e) {
        $error_msg = "Error processing payment: " . $e->getMessage();
        $_SESSION['error_msg'] = $error_msg;
        header('Location: post_collections.php');
        exit;
    }
} else {
    header('Location: post_collections.php');
    exit;
}
?>