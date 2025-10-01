<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/GeneralLedger.php';
require_once __DIR__ . '/TillManagement.php';
require_once __DIR__ . '/ShopManagement.php';

class CollectionManagement {
    private $db;
    private $gl;
    private $tillManager;
    private $shopManager;

    public function __construct() {
        $this->db = new Database();
        $this->gl = new GeneralLedger();
        $this->tillManager = new TillManagement();
        $this->shopManager = new ShopManagement();
    }

    public function createRemittance($data) {
        try {
            $this->db->beginTransaction();

            $remit_code = $this->generateRemitCode();

            $this->db->query('INSERT INTO cash_remittance
                              (remit_code, remit_date, officer_id, officer_name, till_id,
                               total_amount, total_receipts, cash_amount, cheque_amount,
                               transfer_amount, pos_amount, status, submitted_at)
                              VALUES (:remit_code, :remit_date, :officer_id, :officer_name, :till_id,
                                      :total_amount, :total_receipts, :cash_amount, :cheque_amount,
                                      :transfer_amount, :pos_amount, :status, NOW())');

            $this->db->bind(':remit_code', $remit_code);
            $this->db->bind(':remit_date', $data['remit_date']);
            $this->db->bind(':officer_id', $data['officer_id']);
            $this->db->bind(':officer_name', $data['officer_name']);
            $this->db->bind(':till_id', $data['till_id']);
            $this->db->bind(':total_amount', $data['total_amount']);
            $this->db->bind(':total_receipts', $data['total_receipts']);
            $this->db->bind(':cash_amount', isset($data['cash_amount']) ? $data['cash_amount'] : 0.00);
            $this->db->bind(':cheque_amount', isset($data['cheque_amount']) ? $data['cheque_amount'] : 0.00);
            $this->db->bind(':transfer_amount', isset($data['transfer_amount']) ? $data['transfer_amount'] : 0.00);
            $this->db->bind(':pos_amount', isset($data['pos_amount']) ? $data['pos_amount'] : 0.00);
            $this->db->bind(':status', 'submitted');

            $this->db->execute();
            $remit_id = $this->db->lastInsertId();

            $this->db->endTransaction();
            return $remit_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function recordCollection($data) {
        try {
            $this->db->beginTransaction();

            $transaction_code = $this->generateTransactionCode();

            $this->db->query('INSERT INTO collection_transactions
                              (transaction_code, receipt_number, transaction_date, remit_id,
                               customer_id, shop_id, income_line_id, amount, payment_method,
                               cheque_number, bank_name, reference_number, description,
                               collected_by, till_id, status)
                              VALUES (:transaction_code, :receipt_number, :transaction_date, :remit_id,
                                      :customer_id, :shop_id, :income_line_id, :amount, :payment_method,
                                      :cheque_number, :bank_name, :reference_number, :description,
                                      :collected_by, :till_id, :status)');

            $this->db->bind(':transaction_code', $transaction_code);
            $this->db->bind(':receipt_number', $data['receipt_number']);
            $this->db->bind(':transaction_date', $data['transaction_date']);
            $this->db->bind(':remit_id', isset($data['remit_id']) ? $data['remit_id'] : null);
            $this->db->bind(':customer_id', isset($data['customer_id']) ? $data['customer_id'] : null);
            $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
            $this->db->bind(':income_line_id', $data['income_line_id']);
            $this->db->bind(':amount', $data['amount']);
            $this->db->bind(':payment_method', $data['payment_method']);
            $this->db->bind(':cheque_number', isset($data['cheque_number']) ? $data['cheque_number'] : null);
            $this->db->bind(':bank_name', isset($data['bank_name']) ? $data['bank_name'] : null);
            $this->db->bind(':reference_number', isset($data['reference_number']) ? $data['reference_number'] : null);
            $this->db->bind(':description', isset($data['description']) ? $data['description'] : null);
            $this->db->bind(':collected_by', $data['collected_by']);
            $this->db->bind(':till_id', $data['till_id']);
            $this->db->bind(':status', 'collected');

            $this->db->execute();
            $transaction_id = $this->db->lastInsertId();

            $this->tillManager->updateTillBalance($data['till_id'], $data['amount'], 'add');

            $this->db->endTransaction();
            return $transaction_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function postCollection($transaction_id, $posted_by) {
        try {
            $this->db->beginTransaction();

            $transaction = $this->getCollection($transaction_id);
            if (!$transaction || $transaction['status'] != 'collected') {
                throw new Exception('Transaction cannot be posted');
            }

            $income_line = $this->getIncomeLine($transaction['income_line_id']);
            if (!$income_line) {
                throw new Exception('Income line not found');
            }

            $till = $this->tillManager->getOfficerTill($transaction['collected_by']);
            if (!$till) {
                throw new Exception('Officer till not found');
            }

            $fiscal = $this->gl->getFiscalPeriod($transaction['transaction_date']);

            $journal_data = array(
                'journal_date' => $transaction['transaction_date'],
                'fiscal_year' => $fiscal['fiscal_year'],
                'fiscal_period' => $fiscal['fiscal_period'],
                'journal_type' => 'CR',
                'reference_type' => 'collection',
                'reference_id' => $transaction_id,
                'description' => 'Collection: ' . $transaction['receipt_number'] . ' - ' . $income_line['income_name'],
                'total_debit' => $transaction['amount'],
                'total_credit' => $transaction['amount'],
                'status' => 'draft',
                'created_by' => $posted_by,
                'lines' => array(
                    array(
                        'account_code' => $till['account_code'],
                        'debit_amount' => $transaction['amount'],
                        'credit_amount' => 0.00,
                        'description' => 'Cash collected in till'
                    ),
                    array(
                        'account_code' => $income_line['account_code'],
                        'debit_amount' => 0.00,
                        'credit_amount' => $transaction['amount'],
                        'description' => $income_line['income_name']
                    )
                )
            );

            $journal_id = $this->gl->createJournalEntry($journal_data);
            if (!$journal_id) {
                throw new Exception('Failed to create journal entry');
            }

            $this->gl->postJournalEntry($journal_id, $posted_by);

            $this->db->query('UPDATE collection_transactions
                              SET status = :status, journal_id = :journal_id, posted_at = NOW()
                              WHERE id = :transaction_id');

            $this->db->bind(':transaction_id', $transaction_id);
            $this->db->bind(':status', 'posted');
            $this->db->bind(':journal_id', $journal_id);
            $this->db->execute();

            if ($transaction['customer_id']) {
                $ledger_data = array(
                    'transaction_date' => $transaction['transaction_date'],
                    'customer_id' => $transaction['customer_id'],
                    'shop_id' => $transaction['shop_id'],
                    'transaction_type' => 'payment',
                    'reference_type' => 'collection',
                    'reference_id' => $transaction_id,
                    'debit_amount' => 0.00,
                    'credit_amount' => $transaction['amount'],
                    'description' => 'Payment: ' . $transaction['receipt_number']
                );

                $this->shopManager->addCustomerLedgerEntry($ledger_data);
            }

            $this->db->endTransaction();
            return true;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function postRemittance($remit_id, $posted_by) {
        try {
            $this->db->beginTransaction();

            $remittance = $this->getRemittance($remit_id);
            if (!$remittance || $remittance['status'] != 'approved') {
                throw new Exception('Remittance cannot be posted');
            }

            $till = $this->tillManager->getOfficerTill($remittance['officer_id']);
            if (!$till) {
                throw new Exception('Officer till not found');
            }

            $fiscal = $this->gl->getFiscalPeriod($remittance['remit_date']);

            $journal_data = array(
                'journal_date' => $remittance['remit_date'],
                'fiscal_year' => $fiscal['fiscal_year'],
                'fiscal_period' => $fiscal['fiscal_period'],
                'journal_type' => 'CR',
                'reference_type' => 'remittance',
                'reference_id' => $remit_id,
                'description' => 'Remittance: ' . $remittance['remit_code'],
                'total_debit' => $remittance['total_amount'],
                'total_credit' => $remittance['total_amount'],
                'status' => 'draft',
                'created_by' => $posted_by,
                'lines' => array(
                    array(
                        'account_code' => '1015',
                        'debit_amount' => $remittance['total_amount'],
                        'credit_amount' => 0.00,
                        'description' => 'Transfer from till to main cash'
                    ),
                    array(
                        'account_code' => $till['account_code'],
                        'debit_amount' => 0.00,
                        'credit_amount' => $remittance['total_amount'],
                        'description' => 'Remittance from ' . $remittance['officer_name']
                    )
                )
            );

            $journal_id = $this->gl->createJournalEntry($journal_data);
            if (!$journal_id) {
                throw new Exception('Failed to create journal entry');
            }

            $this->gl->postJournalEntry($journal_id, $posted_by);

            $this->tillManager->updateTillBalance($remittance['till_id'], $remittance['total_amount'], 'subtract');

            $this->db->query('UPDATE cash_remittance
                              SET status = :status, posted_by = :posted_by, posted_at = NOW()
                              WHERE id = :remit_id');

            $this->db->bind(':remit_id', $remit_id);
            $this->db->bind(':status', 'posted');
            $this->db->bind(':posted_by', $posted_by);
            $this->db->execute();

            $this->db->endTransaction();
            return true;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function approveRemittance($remit_id, $approved_by) {
        try {
            $this->db->query('UPDATE cash_remittance
                              SET status = :status, approved_by = :approved_by, approved_at = NOW()
                              WHERE id = :remit_id AND status = "submitted"');

            $this->db->bind(':remit_id', $remit_id);
            $this->db->bind(':status', 'approved');
            $this->db->bind(':approved_by', $approved_by);

            return $this->db->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    public function getCollection($transaction_id) {
        $this->db->query('SELECT ct.*, il.income_name, il.income_code, il.account_code,
                          u.full_name as collected_by_name, c.customer_name, s.shop_number
                          FROM collection_transactions ct
                          JOIN income_lines il ON ct.income_line_id = il.id
                          JOIN users u ON ct.collected_by = u.id
                          LEFT JOIN customers c ON ct.customer_id = c.id
                          LEFT JOIN shops s ON ct.shop_id = s.id
                          WHERE ct.id = :transaction_id');

        $this->db->bind(':transaction_id', $transaction_id);
        return $this->db->single();
    }

    public function getRemittance($remit_id) {
        $this->db->query('SELECT * FROM cash_remittance WHERE id = :remit_id');
        $this->db->bind(':remit_id', $remit_id);
        return $this->db->single();
    }

    public function getRemittanceByCode($remit_code) {
        $this->db->query('SELECT * FROM cash_remittance WHERE remit_code = :remit_code');
        $this->db->bind(':remit_code', $remit_code);
        return $this->db->single();
    }

    public function getOfficerCollections($officer_id, $date_from = null, $date_to = null) {
        $sql = 'SELECT ct.*, il.income_name, c.customer_name, s.shop_number
                FROM collection_transactions ct
                JOIN income_lines il ON ct.income_line_id = il.id
                LEFT JOIN customers c ON ct.customer_id = c.id
                LEFT JOIN shops s ON ct.shop_id = s.id
                WHERE ct.collected_by = :officer_id';

        if ($date_from) {
            $sql .= ' AND ct.transaction_date >= :date_from';
        }
        if ($date_to) {
            $sql .= ' AND ct.transaction_date <= :date_to';
        }

        $sql .= ' ORDER BY ct.transaction_date DESC, ct.id DESC';

        $this->db->query($sql);
        $this->db->bind(':officer_id', $officer_id);

        if ($date_from) {
            $this->db->bind(':date_from', $date_from);
        }
        if ($date_to) {
            $this->db->bind(':date_to', $date_to);
        }

        return $this->db->resultSet();
    }

    public function getRemittanceCollections($remit_id) {
        $this->db->query('SELECT ct.*, il.income_name, c.customer_name, s.shop_number
                          FROM collection_transactions ct
                          JOIN income_lines il ON ct.income_line_id = il.id
                          LEFT JOIN customers c ON ct.customer_id = c.id
                          LEFT JOIN shops s ON ct.shop_id = s.id
                          WHERE ct.remit_id = :remit_id
                          ORDER BY ct.id');

        $this->db->bind(':remit_id', $remit_id);
        return $this->db->resultSet();
    }

    public function getIncomeLine($income_line_id) {
        $this->db->query('SELECT il.*, coa.account_name
                          FROM income_lines il
                          JOIN chart_of_accounts coa ON il.account_code = coa.account_code
                          WHERE il.id = :income_line_id');

        $this->db->bind(':income_line_id', $income_line_id);
        return $this->db->single();
    }

    public function getAllIncomeLines($active_only = true) {
        if ($active_only) {
            $this->db->query('SELECT il.*, coa.account_name
                              FROM income_lines il
                              JOIN chart_of_accounts coa ON il.account_code = coa.account_code
                              WHERE il.is_active = 1
                              ORDER BY il.income_name');
        } else {
            $this->db->query('SELECT il.*, coa.account_name
                              FROM income_lines il
                              JOIN chart_of_accounts coa ON il.account_code = coa.account_code
                              ORDER BY il.income_name');
        }

        return $this->db->resultSet();
    }

    public function getDailyCollectionSummary($date, $officer_id = null) {
        $sql = 'SELECT
                il.income_name,
                il.category,
                COUNT(ct.id) as transaction_count,
                IFNULL(SUM(ct.amount), 0) as total_amount,
                IFNULL(SUM(CASE WHEN ct.payment_method = "cash" THEN ct.amount ELSE 0 END), 0) as cash_amount,
                IFNULL(SUM(CASE WHEN ct.payment_method = "cheque" THEN ct.amount ELSE 0 END), 0) as cheque_amount,
                IFNULL(SUM(CASE WHEN ct.payment_method = "transfer" THEN ct.amount ELSE 0 END), 0) as transfer_amount,
                IFNULL(SUM(CASE WHEN ct.payment_method = "pos" THEN ct.amount ELSE 0 END), 0) as pos_amount
                FROM collection_transactions ct
                JOIN income_lines il ON ct.income_line_id = il.id
                WHERE ct.transaction_date = :date
                AND ct.status IN ("collected", "posted")';

        if ($officer_id) {
            $sql .= ' AND ct.collected_by = :officer_id';
        }

        $sql .= ' GROUP BY il.income_name, il.category
                  ORDER BY il.category, il.income_name';

        $this->db->query($sql);
        $this->db->bind(':date', $date);

        if ($officer_id) {
            $this->db->bind(':officer_id', $officer_id);
        }

        return $this->db->resultSet();
    }

    public function getPendingRemittances() {
        $this->db->query('SELECT cr.*, u.full_name as officer_name
                          FROM cash_remittance cr
                          JOIN users u ON cr.officer_id = u.id
                          WHERE cr.status IN ("submitted")
                          ORDER BY cr.remit_date DESC, cr.id DESC');

        return $this->db->resultSet();
    }

    private function generateRemitCode() {
        $prefix = 'RMT' . date('Y');

        $this->db->query('SELECT remit_code FROM cash_remittance
                          WHERE remit_code LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['remit_code'], -6));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . '-' . str_pad($new_number, 6, '0', STR_PAD_LEFT);
    }

    private function generateTransactionCode() {
        $prefix = 'TXN' . date('Ymd');

        $this->db->query('SELECT transaction_code FROM collection_transactions
                          WHERE transaction_code LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['transaction_code'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }

    public function generateReceiptNumber() {
        $prefix = 'RCP' . date('Ymd');

        $this->db->query('SELECT receipt_number FROM collection_transactions
                          WHERE receipt_number LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['receipt_number'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}
?>
