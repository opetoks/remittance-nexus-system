<?php
require_once 'config/Database.php';
require_once 'models/Account.php';

class Transaction {
    private $db;
    private $account;
    
    public function __construct() {
        $this->db = new Database();
        $this->account = new Account();
    }
    
    // Add new transaction with optimizations for performance
    public function addTransaction($data) {
        try {
            // Start transaction for data integrity
            $this->db->beginTransaction();
            
            // Prepare query for main transaction - use single query with proper binding for security and performance
            $this->db->query('INSERT INTO account_general_transaction_new (
                shop_id, customer_name, shop_no, shop_size, date_of_payment, date_on_receipt, 
                start_date, end_date, payment_type, ticket_category, transaction_desc, 
                bank_name, cheque_no, teller_no, receipt_no, amount_paid, 
                remitting_customer, remitting_id, remitting_staff, 
                posting_officer_id, posting_officer_name, 
                debit_account, credit_account, customer_status, payment_category, 
                entry_status, no_of_tickets, plate_no, no_of_nights, 
                it_status, sticker_no, no_of_days, ref_no, remit_id, income_line,
                leasing_post_status, approval_status, verification_status
            ) VALUES (
                :shop_id, :customer_name, :shop_no, :shop_size, :date_of_payment, :date_on_receipt, 
                :start_date, :end_date, :payment_type, :ticket_category, :transaction_desc, 
                :bank_name, :cheque_no, :teller_no, :receipt_no, :amount_paid, 
                :remitting_customer, :remitting_id, :remitting_staff, 
                :posting_officer_id, :posting_officer_name, 
                :debit_account, :credit_account, :customer_status, :payment_category, 
                :entry_status, :no_of_tickets, :plate_no, :no_of_nights, 
                :it_status, :sticker_no, :no_of_days, :ref_no, :remit_id, :income_line,
                :leasing_post_status, :approval_status, :verification_status
            )');
            
            // Bind values - only bind what's provided and use default values for others
            $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
            $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
            $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
            $this->db->bind(':shop_size', isset($data['shop_size']) ? $data['shop_size'] : null);
            $this->db->bind(':date_of_payment', $data['date_of_payment']);
            $this->db->bind(':date_on_receipt', isset($data['date_on_receipt']) ? $data['date_on_receipt'] : null);
            $this->db->bind(':start_date', isset($data['start_date']) ? $data['start_date'] : null);
            $this->db->bind(':end_date', isset($data['end_date']) ? $data['end_date'] : null);
            $this->db->bind(':payment_type', isset($data['payment_type']) ? $data['payment_type'] : null);
            $this->db->bind(':ticket_category', isset($data['ticket_category']) ? $data['ticket_category'] : null);
            $this->db->bind(':transaction_desc', isset($data['transaction_desc']) ? $data['transaction_desc'] : null);
            $this->db->bind(':bank_name', isset($data['bank_name']) ? $data['bank_name'] : null);
            $this->db->bind(':cheque_no', isset($data['cheque_no']) ? $data['cheque_no'] : null);
            $this->db->bind(':teller_no', isset($data['teller_no']) ? $data['teller_no'] : null);
            $this->db->bind(':receipt_no', isset($data['receipt_no']) ? $data['receipt_no'] : null);
            $this->db->bind(':amount_paid', $data['amount_paid']);
            $this->db->bind(':remitting_customer', isset($data['remitting_customer']) ? $data['remitting_customer'] : null);
            $this->db->bind(':remitting_id', isset($data['remitting_id']) ? $data['remitting_id'] : null);
            $this->db->bind(':remitting_staff', isset($data['remitting_staff']) ? $data['remitting_staff'] : null);
            $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
            $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
            $this->db->bind(':debit_account', $data['debit_account']);
            $this->db->bind(':credit_account', $data['credit_account']);
            $this->db->bind(':customer_status', isset($data['customer_status']) ? $data['customer_status'] : null);
            $this->db->bind(':payment_category', isset($data['payment_category']) ? $data['payment_category'] : 'Other Collection');
            $this->db->bind(':entry_status', isset($data['entry_status']) ? $data['entry_status'] : null);
            $this->db->bind(':no_of_tickets', isset($data['no_of_tickets']) ? $data['no_of_tickets'] : null);
            $this->db->bind(':plate_no', isset($data['plate_no']) ? $data['plate_no'] : null);
            $this->db->bind(':no_of_nights', isset($data['no_of_nights']) ? $data['no_of_nights'] : null);
            $this->db->bind(':it_status', isset($data['it_status']) ? $data['it_status'] : null);
            $this->db->bind(':sticker_no', isset($data['sticker_no']) ? $data['sticker_no'] : null);
            $this->db->bind(':no_of_days', isset($data['no_of_days']) ? $data['no_of_days'] : null);
            $this->db->bind(':ref_no', isset($data['ref_no']) ? $data['ref_no'] : null);
            $this->db->bind(':remit_id', isset($data['remit_id']) ? $data['remit_id'] : null);
            $this->db->bind(':income_line', $data['income_line']);
            $this->db->bind(':leasing_post_status', isset($data['leasing_post_status']) ? $data['leasing_post_status'] : null);
            $this->db->bind(':approval_status', isset($data['approval_status']) ? $data['approval_status'] : null);
            $this->db->bind(':verification_status', isset($data['verification_status']) ? $data['verification_status'] : null);
            
            // Execute with error handling
            if(!$this->db->execute()) {
                $this->db->cancelTransaction();
                return false;
            }
            
            // Get the last insert ID
            $transaction_id = $this->db->lastInsertId();
            
            // Get the credit account details - use prepared statement for security
            $creditAccount = $this->account->getAccountByCode($data['credit_account']);
            
            // If this is an income line transaction, add to the specific income table
            if($creditAccount && !empty($creditAccount['acct_table_name'])) {
                $table_name = $creditAccount['acct_table_name'];
                
                // Optimize car park ticket processing
                if ($data['income_line'] == 'Car Park Collection' || strpos($data['income_line'], 'Car Park') !== false) {
                    $this->processParkingTickets($transaction_id, $table_name, $data);
                } 
                // Process scroll board rentals
                else if ($data['income_line'] == 'Scroll Board Collection' || strpos($data['income_line'], 'Scroll Board') !== false) {
                    $this->processScrollBoardIncome($transaction_id, $table_name, $data);
                }
                // Process other income types
                else {
                    $this->processGeneralIncome($transaction_id, $table_name, $data);
                }
            }
            
            // Commit the transaction if everything worked
            $this->db->endTransaction();
            
            return $transaction_id;
            
        } catch (Exception $e) {
            // If something goes wrong, rollback changes
            $this->db->cancelTransaction();
            // Could log the error here
            return false;
        }
    }
    
    // Process car park tickets with optimized approach
    private function processParkingTickets($transaction_id, $table_name, $data) {
        // Make sure this function works efficiently for car park tickets
        $this->db->query("INSERT INTO $table_name (
            transaction_id, description, amount, date, ticket_category, no_of_tickets
        ) VALUES (
            :transaction_id, :description, :amount, :date, :ticket_category, :no_of_tickets
        )");
        
        $this->db->bind(':transaction_id', $transaction_id);
        $this->db->bind(':description', isset($data['transaction_desc']) ? $data['transaction_desc'] : 'Car Park Collection');
        $this->db->bind(':amount', $data['amount_paid']);
        $this->db->bind(':date', $data['date_of_payment']);
        $this->db->bind(':ticket_category', isset($data['ticket_category']) ? $data['ticket_category'] : null);
        $this->db->bind(':no_of_tickets', isset($data['no_of_tickets']) ? $data['no_of_tickets'] : null);
        
        // Execute the specific income table insert
        return $this->db->execute();
    }
    
    // Process scroll board income specifically
    private function processScrollBoardIncome($transaction_id, $table_name, $data) {
        // Make sure this function works efficiently for scroll board income
        $this->db->query("INSERT INTO $table_name (
            transaction_id, customer_name, description, amount, date, 
            start_date, end_date, location, scroll_id
        ) VALUES (
            :transaction_id, :customer_name, :description, :amount, :date,
            :start_date, :end_date, :location, :scroll_id
        )");
        
        $this->db->bind(':transaction_id', $transaction_id);
        $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
        $this->db->bind(':description', isset($data['transaction_desc']) ? $data['transaction_desc'] : 'Scroll Board Rental');
        $this->db->bind(':amount', $data['amount_paid']);
        $this->db->bind(':date', $data['date_of_payment']);
        $this->db->bind(':start_date', isset($data['start_date']) ? $data['start_date'] : null);
        $this->db->bind(':end_date', isset($data['end_date']) ? $data['end_date'] : null);
        $this->db->bind(':location', isset($data['location']) ? $data['location'] : null);
        $this->db->bind(':scroll_id', isset($data['scroll_id']) ? $data['scroll_id'] : null);
        
        // Execute the specific income table insert
        return $this->db->execute();
    }
    
    // Process general income types
    private function processGeneralIncome($transaction_id, $table_name, $data) {
        // Use a switch to optimize for different income types
        switch($table_name) {
            case 'income_shop_rent':
                $this->processShopRentIncome($transaction_id, $table_name, $data);
                break;
                
            case 'income_service_charge':
                $this->processServiceChargeIncome($transaction_id, $table_name, $data);
                break;
                
            default:
                // Generic insert for other income tables
                $this->processDefaultIncome($transaction_id, $table_name, $data);
                break;
        }
    }
    
    // Process shop rent income
    private function processShopRentIncome($transaction_id, $table_name, $data) {
        $this->db->query("INSERT INTO $table_name (
            transaction_id, shop_id, shop_no, shop_size, customer_name, 
            rent_start_date, rent_end_date, amount
        ) VALUES (
            :transaction_id, :shop_id, :shop_no, :shop_size, :customer_name, 
            :start_date, :end_date, :amount
        )");
        
        $this->db->bind(':transaction_id', $transaction_id);
        $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
        $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
        $this->db->bind(':shop_size', isset($data['shop_size']) ? $data['shop_size'] : null);
        $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
        $this->db->bind(':start_date', isset($data['start_date']) ? $data['start_date'] : null);
        $this->db->bind(':end_date', isset($data['end_date']) ? $data['end_date'] : null);
        $this->db->bind(':amount', $data['amount_paid']);
        
        return $this->db->execute();
    }
    
    // Process service charge income
    private function processServiceChargeIncome($transaction_id, $table_name, $data) {
        // Extract month/year from dates
        $month = date('F', strtotime(isset($data['start_date']) ? $data['start_date'] : date('Y-m-d')));
        $year = date('Y', strtotime(isset($data['start_date']) ? $data['start_date'] : date('Y-m-d')));
    
        $this->db->query("INSERT INTO $table_name (
            transaction_id, shop_id, shop_no, customer_name, 
            month, year, amount
        ) VALUES (
            :transaction_id, :shop_id, :shop_no, :customer_name, 
            :month, :year, :amount
        )");
    
        $this->db->bind(':transaction_id', $transaction_id);
        $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
        $this->db->bind(':shop_no', isset($data['shop_no']) ? $data['shop_no'] : null);
        $this->db->bind(':customer_name', isset($data['customer_name']) ? $data['customer_name'] : null);
        $this->db->bind(':month', $month);
        $this->db->bind(':year', $year);
        $this->db->bind(':amount', $data['amount_paid']);
        
        return $this->db->execute();
    }
    
    // Process default income type
    private function processDefaultIncome($transaction_id, $table_name, $data) {
        $this->db->query("INSERT INTO $table_name (
            transaction_id, description, amount, date
        ) VALUES (
            :transaction_id, :description, :amount, :date
        )");
        
        $this->db->bind(':transaction_id', $transaction_id);
        $this->db->bind(':description', isset($data['transaction_desc']) ? $data['transaction_desc'] : '');
        $this->db->bind(':amount', $data['amount_paid']);
        $this->db->bind(':date', $data['date_of_payment']);
        
        return $this->db->execute();
    }
    
    // Get all transactions - with optimized query for better performance
    public function getTransactions($limit = 100) {
        // Prepare query with limit to prevent loading too many records at once
        $this->db->query('SELECT * FROM account_general_transaction_new ORDER BY posting_time DESC LIMIT :limit');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get transaction by ID
    public function getTransactionById($id) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE id = :id');
        
        // Bind value
        $this->db->bind(':id', $id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get transactions by remittance ID - optimized for performance with indexing
    public function getTransactionsByRemitId($remit_id) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE remit_id = :remit_id ORDER BY posting_time DESC');
        
        // Bind value
        $this->db->bind(':remit_id', $remit_id);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get transaction by receipt number
    public function getTransactionByReceiptNo($receipt_no) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE receipt_no = :receipt_no');
        
        // Bind value
        $this->db->bind(':receipt_no', $receipt_no);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get scroll board transactions for dashboard
    public function getScrollBoardTransactions($limit = 100, $date_from = null, $date_to = null) {
        $whereClause = "WHERE income_line = 'Scroll Board Collection'";
        
        if ($date_from && $date_to) {
            $whereClause .= " AND date_of_payment BETWEEN :date_from AND :date_to";
        }
        
        $query = "SELECT t.*, 
                 DATE_FORMAT(t.start_date, '%d-%m-%Y') as formatted_start_date,
                 DATE_FORMAT(t.end_date, '%d-%m-%Y') as formatted_end_date
                 FROM account_general_transaction_new t
                 $whereClause
                 ORDER BY t.posting_time DESC
                 LIMIT :limit";
        
        $this->db->query($query);
        
        if ($date_from && $date_to) {
            $this->db->bind(':date_from', $date_from);
            $this->db->bind(':date_to', $date_to);
        }
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    // Get scroll board customer payments summary
    public function getScrollBoardCustomerSummary() {
        $query = "SELECT 
                customer_name, 
                COUNT(*) as total_payments,
                SUM(amount_paid) as total_amount,
                MAX(end_date) as latest_end_date
                FROM account_general_transaction_new
                WHERE income_line = 'Scroll Board Collection'
                AND customer_name IS NOT NULL
                GROUP BY customer_name
                ORDER BY latest_end_date DESC";
                
        $this->db->query($query);
        return $this->db->resultSet();
    }
    
    // Get pending transactions for leasing approval - limit results for performance
    public function getPendingTransactionsForLeasingApproval($limit = 500) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE leasing_post_status = "pending" ORDER BY posting_time DESC LIMIT :limit');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get pending transactions for account approval - limit results for performance
    public function getPendingTransactionsForAccountApproval($limit = 500) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE leasing_post_status = "approved" AND approval_status = "pending" ORDER BY posting_time DESC LIMIT :limit');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get pending transactions for audit verification - limit results for performance
    public function getPendingTransactionsForAuditVerification($limit = 500) {
        // Prepare query
        $this->db->query('SELECT * FROM account_general_transaction_new WHERE approval_status = "approved" AND verification_status = "pending" ORDER BY posting_time DESC LIMIT :limit');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Approve leasing post
    public function approveLeasingPost($transaction_id, $officer_id, $officer_name) {
        // Prepare query
        $this->db->query('UPDATE account_general_transaction_new SET 
            leasing_post_status = "approved", 
            leasing_post_approving_officer_id = :officer_id, 
            leasing_post_approving_officer_name = :officer_name, 
            leasing_post_approval_time = NOW() 
        WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':officer_name', $officer_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Approve account transaction
    public function approveTransaction($transaction_id, $officer_id, $officer_name) {
        // Prepare query
        $this->db->query('UPDATE account_general_transaction_new SET 
            approval_status = "approved", 
            approving_acct_officer_id = :officer_id, 
            approving_acct_officer_name = :officer_name, 
            approval_time = NOW() 
        WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':officer_name', $officer_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Verify transaction by auditor
    public function verifyTransaction($transaction_id, $auditor_id, $auditor_name) {
        // Prepare query
        $this->db->query('UPDATE account_general_transaction_new SET 
            verification_status = "verified", 
            verifying_auditor_id = :auditor_id, 
            verifying_auditor_name = :auditor_name, 
            verification_time = NOW() 
        WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':auditor_id', $auditor_id);
        $this->db->bind(':auditor_name', $auditor_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Reject transaction (any stage)
    public function rejectTransaction($transaction_id, $stage, $officer_id, $officer_name) {
        switch($stage) {
            case 'leasing':
                $sql = 'UPDATE account_general_transaction_new SET 
                    leasing_post_status = "rejected", 
                    leasing_post_approving_officer_id = :officer_id, 
                    leasing_post_approving_officer_name = :officer_name, 
                    leasing_post_approval_time = NOW() 
                WHERE id = :id';
                break;
                
            case 'account':
                $sql = 'UPDATE account_general_transaction_new SET 
                    approval_status = "rejected", 
                    approving_acct_officer_id = :officer_id, 
                    approving_acct_officer_name = :officer_name, 
                    approval_time = NOW() 
                WHERE id = :id';
                break;
                
            case 'audit':
                $sql = 'UPDATE account_general_transaction_new SET 
                    verification_status = "rejected", 
                    verifying_auditor_id = :officer_id, 
                    verifying_auditor_name = :officer_name, 
                    verification_time = NOW() 
                WHERE id = :id';
                break;
                
            default:
                return false;
        }
        
        // Prepare query
        $this->db->query($sql);
        
        // Bind values
        $this->db->bind(':id', $transaction_id);
        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':officer_name', $officer_name);
        
        // Execute
        return $this->db->execute();
    }
    
    // Get transaction statistics for dashboard - with optimized queries
    public function getTransactionStats() {
        $stats = [];
        
        // Today's transactions
        $this->db->query('SELECT COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE DATE(posting_time) = CURDATE()');
        $stats['today'] = $this->db->single();
        
        // This week's transactions
        $this->db->query('SELECT COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE YEARWEEK(posting_time, 1) = YEARWEEK(CURDATE(), 1)');
        $stats['week'] = $this->db->single();
        
        // This month's transactions
        $this->db->query('SELECT COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE YEAR(posting_time) = YEAR(CURDATE()) AND MONTH(posting_time) = MONTH(CURDATE())');
        $stats['month'] = $this->db->single();
        
        // Income line breakdown (this month)
        $this->db->query('SELECT income_line, COUNT(*) as count, SUM(amount_paid) as total FROM account_general_transaction_new WHERE YEAR(posting_time) = YEAR(CURDATE()) AND MONTH(posting_time) = MONTH(CURDATE()) GROUP BY income_line');
        $stats['income_lines'] = $this->db->resultSet();
        
        return $stats;
    }
}
?>
