
<?php
require_once 'config/Database.php';

class UnpostedTransaction {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Add unposted transaction
    public function addUnpostedTransaction($data) {
        $this->db->query('INSERT INTO unposted_transactions (
            remit_id, trans_id, shop_id, shop_no, customer_name, date_of_payment, 
            transaction_desc, amount_paid, receipt_no, category, income_line,
            posting_officer_id, posting_officer_name, reason
        ) VALUES (
            :remit_id, :trans_id, :shop_id, :shop_no, :customer_name, :date_of_payment, 
            :transaction_desc, :amount_paid, :receipt_no, :category, :income_line,
            :posting_officer_id, :posting_officer_name, :reason
        )');
        
        $this->db->bind(':remit_id', $data['remit_id']);
        $this->db->bind(':trans_id', $data['trans_id'] ?? null);
        $this->db->bind(':shop_id', $data['shop_id'] ?? null);
        $this->db->bind(':shop_no', $data['shop_no'] ?? null);
        $this->db->bind(':customer_name', $data['customer_name'] ?? null);
        $this->db->bind(':date_of_payment', $data['date_of_payment']);
        $this->db->bind(':transaction_desc', $data['transaction_desc'] ?? null);
        $this->db->bind(':amount_paid', $data['amount_paid']);
        $this->db->bind(':receipt_no', $data['receipt_no']);
        $this->db->bind(':category', $data['category'] ?? null);
        $this->db->bind(':income_line', $data['income_line']);
        $this->db->bind(':posting_officer_id', $data['posting_officer_id']);
        $this->db->bind(':posting_officer_name', $data['posting_officer_name']);
        $this->db->bind(':reason', $data['reason']);
        
        return $this->db->execute();
    }
    
    // Get unposted transactions by remittance ID
    public function getUnpostedTransactionsByRemitId($remit_id) {
        $this->db->query('SELECT * FROM unposted_transactions WHERE remit_id = :remit_id ORDER BY posting_time DESC');
        $this->db->bind(':remit_id', $remit_id);
        return $this->db->resultSet();
    }
    
    // Get unposted transactions by officer
    public function getUnpostedTransactionsByOfficer($officer_id) {
        $this->db->query('SELECT * FROM unposted_transactions WHERE posting_officer_id = :officer_id AND payment_status = "pending" ORDER BY posting_time DESC');
        $this->db->bind(':officer_id', $officer_id);
        return $this->db->resultSet();
    }
    
    // Update transaction status to reposted
    public function markAsReposted($id) {
        $this->db->query('UPDATE unposted_transactions SET payment_status = "reposted", reposting_time = NOW() WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
?>
