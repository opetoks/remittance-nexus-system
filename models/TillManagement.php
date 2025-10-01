<?php
require_once __DIR__ . '/../config/Database.php';

class TillManagement {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getOfficerTill($officer_id) {
        $this->db->query('SELECT ot.*, u.full_name as officer_name, coa.account_name
                          FROM officer_tills ot
                          JOIN users u ON ot.officer_id = u.id
                          JOIN chart_of_accounts coa ON ot.account_code = coa.account_code
                          WHERE ot.officer_id = :officer_id AND ot.is_active = 1');
        $this->db->bind(':officer_id', $officer_id);
        return $this->db->single();
    }

    public function createOfficerTill($data) {
        try {
            $this->db->beginTransaction();

            $this->db->query('INSERT INTO officer_tills
                              (till_code, till_name, officer_id, account_code, opening_balance)
                              VALUES (:till_code, :till_name, :officer_id, :account_code, :opening_balance)');

            $this->db->bind(':till_code', $data['till_code']);
            $this->db->bind(':till_name', $data['till_name']);
            $this->db->bind(':officer_id', $data['officer_id']);
            $this->db->bind(':account_code', $data['account_code']);
            $this->db->bind(':opening_balance', isset($data['opening_balance']) ? $data['opening_balance'] : 0.00);

            $this->db->execute();
            $till_id = $this->db->lastInsertId();

            $this->db->endTransaction();
            return $till_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function getTillBalance($till_id) {
        $this->db->query('SELECT current_balance FROM officer_tills WHERE id = :till_id');
        $this->db->bind(':till_id', $till_id);
        $result = $this->db->single();
        return $result ? $result['current_balance'] : 0.00;
    }

    public function updateTillBalance($till_id, $amount, $transaction_type = 'add') {
        try {
            if ($transaction_type == 'add') {
                $this->db->query('UPDATE officer_tills
                                  SET current_balance = current_balance + :amount
                                  WHERE id = :till_id');
            } else {
                $this->db->query('UPDATE officer_tills
                                  SET current_balance = current_balance - :amount
                                  WHERE id = :till_id');
            }

            $this->db->bind(':till_id', $till_id);
            $this->db->bind(':amount', $amount);

            return $this->db->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    public function getDailyTillBalance($till_id, $date) {
        $this->db->query('SELECT * FROM till_balancing
                          WHERE till_id = :till_id AND balance_date = :date');
        $this->db->bind(':till_id', $till_id);
        $this->db->bind(':date', $date);
        return $this->db->single();
    }

    public function createTillBalancing($data) {
        try {
            $this->db->beginTransaction();

            $variance = $data['actual_balance'] - $data['expected_balance'];
            $is_balanced = (abs($variance) < 0.01) ? 1 : 0;

            $this->db->query('INSERT INTO till_balancing
                              (balance_date, till_id, officer_id, opening_balance,
                               total_collections, total_payments, expected_balance,
                               actual_balance, variance, is_balanced, status, variance_reason)
                              VALUES (:balance_date, :till_id, :officer_id, :opening_balance,
                                      :total_collections, :total_payments, :expected_balance,
                                      :actual_balance, :variance, :is_balanced, :status, :variance_reason)');

            $this->db->bind(':balance_date', $data['balance_date']);
            $this->db->bind(':till_id', $data['till_id']);
            $this->db->bind(':officer_id', $data['officer_id']);
            $this->db->bind(':opening_balance', $data['opening_balance']);
            $this->db->bind(':total_collections', $data['total_collections']);
            $this->db->bind(':total_payments', isset($data['total_payments']) ? $data['total_payments'] : 0.00);
            $this->db->bind(':expected_balance', $data['expected_balance']);
            $this->db->bind(':actual_balance', $data['actual_balance']);
            $this->db->bind(':variance', $variance);
            $this->db->bind(':is_balanced', $is_balanced);
            $this->db->bind(':status', $is_balanced ? 'balanced' : 'variance_pending');
            $this->db->bind(':variance_reason', isset($data['variance_reason']) ? $data['variance_reason'] : null);

            $this->db->execute();
            $balance_id = $this->db->lastInsertId();

            $this->db->query('UPDATE officer_tills
                              SET last_balanced_date = :balance_date
                              WHERE id = :till_id');
            $this->db->bind(':balance_date', $data['balance_date']);
            $this->db->bind(':till_id', $data['till_id']);
            $this->db->execute();

            $this->db->endTransaction();
            return $balance_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function approveTillBalancing($balance_id, $approved_by) {
        try {
            $this->db->query('UPDATE till_balancing
                              SET status = :status,
                                  balanced_by = :approved_by,
                                  balanced_at = NOW()
                              WHERE id = :balance_id');

            $this->db->bind(':balance_id', $balance_id);
            $this->db->bind(':status', 'approved');
            $this->db->bind(':approved_by', $approved_by);

            return $this->db->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    public function getPendingBalances($officer_id = null) {
        if ($officer_id) {
            $this->db->query('SELECT tb.*, ot.till_name, u.full_name as officer_name
                              FROM till_balancing tb
                              JOIN officer_tills ot ON tb.till_id = ot.id
                              JOIN users u ON tb.officer_id = u.id
                              WHERE tb.officer_id = :officer_id
                              AND tb.status IN ("pending", "variance_pending")
                              ORDER BY tb.balance_date DESC');
            $this->db->bind(':officer_id', $officer_id);
        } else {
            $this->db->query('SELECT tb.*, ot.till_name, u.full_name as officer_name
                              FROM till_balancing tb
                              JOIN officer_tills ot ON tb.till_id = ot.id
                              JOIN users u ON tb.officer_id = u.id
                              WHERE tb.status IN ("pending", "variance_pending")
                              ORDER BY tb.balance_date DESC');
        }

        return $this->db->resultSet();
    }

    public function getOfficerDailyCollections($officer_id, $date) {
        $this->db->query('SELECT
                          IFNULL(SUM(amount), 0) as total_collections,
                          COUNT(id) as transaction_count,
                          IFNULL(SUM(CASE WHEN payment_method = "cash" THEN amount ELSE 0 END), 0) as cash_amount,
                          IFNULL(SUM(CASE WHEN payment_method = "cheque" THEN amount ELSE 0 END), 0) as cheque_amount,
                          IFNULL(SUM(CASE WHEN payment_method = "transfer" THEN amount ELSE 0 END), 0) as transfer_amount,
                          IFNULL(SUM(CASE WHEN payment_method = "pos" THEN amount ELSE 0 END), 0) as pos_amount
                          FROM collection_transactions
                          WHERE collected_by = :officer_id
                          AND transaction_date = :date
                          AND status IN ("collected", "posted")');

        $this->db->bind(':officer_id', $officer_id);
        $this->db->bind(':date', $date);

        return $this->db->single();
    }

    public function checkTillBalanced($till_id, $date) {
        $this->db->query('SELECT id, status FROM till_balancing
                          WHERE till_id = :till_id
                          AND balance_date = :date
                          AND status IN ("balanced", "approved")');

        $this->db->bind(':till_id', $till_id);
        $this->db->bind(':date', $date);

        $result = $this->db->single();
        return !empty($result);
    }

    public function getAllOfficerTills($active_only = true) {
        if ($active_only) {
            $this->db->query('SELECT ot.*, u.full_name as officer_name, u.role
                              FROM officer_tills ot
                              JOIN users u ON ot.officer_id = u.id
                              WHERE ot.is_active = 1
                              ORDER BY ot.till_code');
        } else {
            $this->db->query('SELECT ot.*, u.full_name as officer_name, u.role
                              FROM officer_tills ot
                              JOIN users u ON ot.officer_id = u.id
                              ORDER BY ot.till_code');
        }

        return $this->db->resultSet();
    }
}
?>
