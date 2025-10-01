<?php
require_once __DIR__ . '/../config/Database.php';

class GeneralLedger {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createJournalEntry($data) {
        try {
            $this->db->beginTransaction();

            $journal_code = $this->generateJournalCode($data['journal_type']);

            $this->db->query('INSERT INTO journal_entries
                              (journal_code, journal_date, fiscal_year, fiscal_period,
                               journal_type, reference_type, reference_id, description,
                               total_debit, total_credit, status, created_by)
                              VALUES (:journal_code, :journal_date, :fiscal_year, :fiscal_period,
                                      :journal_type, :reference_type, :reference_id, :description,
                                      :total_debit, :total_credit, :status, :created_by)');

            $this->db->bind(':journal_code', $journal_code);
            $this->db->bind(':journal_date', $data['journal_date']);
            $this->db->bind(':fiscal_year', $data['fiscal_year']);
            $this->db->bind(':fiscal_period', $data['fiscal_period']);
            $this->db->bind(':journal_type', $data['journal_type']);
            $this->db->bind(':reference_type', isset($data['reference_type']) ? $data['reference_type'] : null);
            $this->db->bind(':reference_id', isset($data['reference_id']) ? $data['reference_id'] : null);
            $this->db->bind(':description', $data['description']);
            $this->db->bind(':total_debit', $data['total_debit']);
            $this->db->bind(':total_credit', $data['total_credit']);
            $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'draft');
            $this->db->bind(':created_by', $data['created_by']);

            $this->db->execute();
            $journal_id = $this->db->lastInsertId();

            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $index => $line) {
                    $this->addJournalLine($journal_id, $index + 1, $line);
                }
            }

            $this->db->endTransaction();
            return $journal_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    private function addJournalLine($journal_id, $line_number, $line_data) {
        $this->db->query('INSERT INTO journal_entry_lines
                          (journal_id, line_number, account_code, debit_amount,
                           credit_amount, description, cost_center, department)
                          VALUES (:journal_id, :line_number, :account_code, :debit_amount,
                                  :credit_amount, :description, :cost_center, :department)');

        $this->db->bind(':journal_id', $journal_id);
        $this->db->bind(':line_number', $line_number);
        $this->db->bind(':account_code', $line_data['account_code']);
        $this->db->bind(':debit_amount', isset($line_data['debit_amount']) ? $line_data['debit_amount'] : 0.00);
        $this->db->bind(':credit_amount', isset($line_data['credit_amount']) ? $line_data['credit_amount'] : 0.00);
        $this->db->bind(':description', isset($line_data['description']) ? $line_data['description'] : null);
        $this->db->bind(':cost_center', isset($line_data['cost_center']) ? $line_data['cost_center'] : null);
        $this->db->bind(':department', isset($line_data['department']) ? $line_data['department'] : null);

        return $this->db->execute();
    }

    public function postJournalEntry($journal_id, $posted_by) {
        try {
            $this->db->beginTransaction();

            $journal = $this->getJournalEntry($journal_id);
            if (!$journal || $journal['status'] != 'draft') {
                throw new Exception('Journal entry cannot be posted');
            }

            if (abs($journal['total_debit'] - $journal['total_credit']) > 0.01) {
                throw new Exception('Journal entry is not balanced');
            }

            $this->db->query('UPDATE journal_entries
                              SET status = :status, posted_by = :posted_by, posted_at = NOW()
                              WHERE id = :journal_id');

            $this->db->bind(':journal_id', $journal_id);
            $this->db->bind(':status', 'posted');
            $this->db->bind(':posted_by', $posted_by);

            $this->db->execute();

            $this->updateAccountBalances($journal_id);

            $this->db->endTransaction();
            return true;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    private function updateAccountBalances($journal_id) {
        $this->db->query('SELECT account_code, debit_amount, credit_amount
                          FROM journal_entry_lines
                          WHERE journal_id = :journal_id');
        $this->db->bind(':journal_id', $journal_id);
        $lines = $this->db->resultSet();

        foreach ($lines as $line) {
            $net_change = $line['debit_amount'] - $line['credit_amount'];

            $this->db->query('UPDATE chart_of_accounts
                              SET current_balance = current_balance + :net_change
                              WHERE account_code = :account_code');

            $this->db->bind(':net_change', $net_change);
            $this->db->bind(':account_code', $line['account_code']);
            $this->db->execute();
        }
    }

    public function getJournalEntry($journal_id) {
        $this->db->query('SELECT je.*, u.full_name as created_by_name,
                          p.full_name as posted_by_name
                          FROM journal_entries je
                          LEFT JOIN users u ON je.created_by = u.id
                          LEFT JOIN users p ON je.posted_by = p.id
                          WHERE je.id = :journal_id');

        $this->db->bind(':journal_id', $journal_id);
        return $this->db->single();
    }

    public function getJournalLines($journal_id) {
        $this->db->query('SELECT jel.*, coa.account_name
                          FROM journal_entry_lines jel
                          JOIN chart_of_accounts coa ON jel.account_code = coa.account_code
                          WHERE jel.journal_id = :journal_id
                          ORDER BY jel.line_number');

        $this->db->bind(':journal_id', $journal_id);
        return $this->db->resultSet();
    }

    public function getAccountBalance($account_code) {
        $this->db->query('SELECT current_balance FROM chart_of_accounts
                          WHERE account_code = :account_code');

        $this->db->bind(':account_code', $account_code);
        $result = $this->db->single();

        return $result ? $result['current_balance'] : 0.00;
    }

    public function getAccountLedger($account_code, $date_from = null, $date_to = null) {
        $sql = 'SELECT je.journal_date, je.journal_code, je.journal_type,
                je.description, jel.debit_amount, jel.credit_amount, jel.description as line_description
                FROM journal_entry_lines jel
                JOIN journal_entries je ON jel.journal_id = je.id
                WHERE jel.account_code = :account_code AND je.status = "posted"';

        if ($date_from) {
            $sql .= ' AND je.journal_date >= :date_from';
        }
        if ($date_to) {
            $sql .= ' AND je.journal_date <= :date_to';
        }

        $sql .= ' ORDER BY je.journal_date, je.id, jel.line_number';

        $this->db->query($sql);
        $this->db->bind(':account_code', $account_code);

        if ($date_from) {
            $this->db->bind(':date_from', $date_from);
        }
        if ($date_to) {
            $this->db->bind(':date_to', $date_to);
        }

        return $this->db->resultSet();
    }

    public function getTrialBalance($as_of_date = null) {
        $sql = 'SELECT
                coa.account_code,
                coa.account_name,
                at.type_name as account_type,
                at.category,
                at.normal_balance,
                coa.opening_balance,
                IFNULL(SUM(jel.debit_amount), 0) as total_debit,
                IFNULL(SUM(jel.credit_amount), 0) as total_credit,
                CASE
                    WHEN at.normal_balance = "DEBIT" THEN
                        coa.opening_balance + IFNULL(SUM(jel.debit_amount), 0) - IFNULL(SUM(jel.credit_amount), 0)
                    ELSE
                        coa.opening_balance + IFNULL(SUM(jel.credit_amount), 0) - IFNULL(SUM(jel.debit_amount), 0)
                END as current_balance
                FROM chart_of_accounts coa
                LEFT JOIN account_types at ON coa.account_type_id = at.id
                LEFT JOIN journal_entry_lines jel ON coa.account_code = jel.account_code
                LEFT JOIN journal_entries je ON jel.journal_id = je.id AND je.status = "posted"';

        if ($as_of_date) {
            $sql .= ' AND je.journal_date <= :as_of_date';
        }

        $sql .= ' WHERE coa.is_active = 1 AND coa.allow_posting = 1
                  GROUP BY coa.account_code, coa.account_name, at.type_name, at.category, at.normal_balance, coa.opening_balance
                  ORDER BY coa.account_code';

        $this->db->query($sql);

        if ($as_of_date) {
            $this->db->bind(':as_of_date', $as_of_date);
        }

        return $this->db->resultSet();
    }

    public function getIncomeStatement($date_from, $date_to) {
        $this->db->query('SELECT
                          coa.account_code,
                          coa.account_name,
                          at.category,
                          IFNULL(SUM(jel.credit_amount), 0) - IFNULL(SUM(jel.debit_amount), 0) as amount
                          FROM chart_of_accounts coa
                          LEFT JOIN account_types at ON coa.account_type_id = at.id
                          LEFT JOIN journal_entry_lines jel ON coa.account_code = jel.account_code
                          LEFT JOIN journal_entries je ON jel.journal_id = je.id
                          WHERE je.status = "posted"
                          AND je.journal_date BETWEEN :date_from AND :date_to
                          AND at.category IN ("REVENUE", "EXPENSE")
                          AND coa.is_active = 1 AND coa.allow_posting = 1
                          GROUP BY coa.account_code, coa.account_name, at.category
                          ORDER BY at.category, coa.account_code');

        $this->db->bind(':date_from', $date_from);
        $this->db->bind(':date_to', $date_to);

        return $this->db->resultSet();
    }

    public function getBalanceSheet($as_of_date) {
        $this->db->query('SELECT
                          coa.account_code,
                          coa.account_name,
                          at.category,
                          at.normal_balance,
                          CASE
                              WHEN at.normal_balance = "DEBIT" THEN
                                  coa.opening_balance + IFNULL(SUM(jel.debit_amount), 0) - IFNULL(SUM(jel.credit_amount), 0)
                              ELSE
                                  coa.opening_balance + IFNULL(SUM(jel.credit_amount), 0) - IFNULL(SUM(jel.debit_amount), 0)
                          END as balance
                          FROM chart_of_accounts coa
                          LEFT JOIN account_types at ON coa.account_type_id = at.id
                          LEFT JOIN journal_entry_lines jel ON coa.account_code = jel.account_code
                          LEFT JOIN journal_entries je ON jel.journal_id = je.id AND je.status = "posted" AND je.journal_date <= :as_of_date
                          WHERE at.category IN ("ASSET", "LIABILITY", "EQUITY")
                          AND coa.is_active = 1 AND coa.allow_posting = 1
                          GROUP BY coa.account_code, coa.account_name, at.category, at.normal_balance, coa.opening_balance
                          ORDER BY at.category, coa.account_code');

        $this->db->bind(':as_of_date', $as_of_date);

        return $this->db->resultSet();
    }

    private function generateJournalCode($journal_type) {
        $prefix = $journal_type . '-' . date('Y');

        $this->db->query('SELECT journal_code FROM journal_entries
                          WHERE journal_code LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['journal_code'], -6));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . '-' . str_pad($new_number, 6, '0', STR_PAD_LEFT);
    }

    public function reverseJournalEntry($journal_id, $reversed_by, $reason) {
        try {
            $this->db->beginTransaction();

            $journal = $this->getJournalEntry($journal_id);
            if (!$journal || $journal['status'] != 'posted') {
                throw new Exception('Journal entry cannot be reversed');
            }

            $lines = $this->getJournalLines($journal_id);

            $reversal_data = array(
                'journal_date' => date('Y-m-d'),
                'fiscal_year' => date('Y'),
                'fiscal_period' => date('m'),
                'journal_type' => $journal['journal_type'],
                'reference_type' => 'reversal',
                'reference_id' => $journal_id,
                'description' => 'REVERSAL: ' . $journal['description'] . ' - ' . $reason,
                'total_debit' => $journal['total_credit'],
                'total_credit' => $journal['total_debit'],
                'status' => 'draft',
                'created_by' => $reversed_by,
                'lines' => array()
            );

            foreach ($lines as $line) {
                $reversal_data['lines'][] = array(
                    'account_code' => $line['account_code'],
                    'debit_amount' => $line['credit_amount'],
                    'credit_amount' => $line['debit_amount'],
                    'description' => 'REVERSAL: ' . $line['line_description']
                );
            }

            $reversal_id = $this->createJournalEntry($reversal_data);
            $this->postJournalEntry($reversal_id, $reversed_by);

            $this->db->query('UPDATE journal_entries
                              SET status = :status, reversed_by = :reversed_by,
                                  reversed_at = NOW(), reversal_reason = :reason
                              WHERE id = :journal_id');

            $this->db->bind(':journal_id', $journal_id);
            $this->db->bind(':status', 'reversed');
            $this->db->bind(':reversed_by', $reversed_by);
            $this->db->bind(':reason', $reason);
            $this->db->execute();

            $this->db->endTransaction();
            return $reversal_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function getChartOfAccounts($active_only = true) {
        if ($active_only) {
            $this->db->query('SELECT coa.*, at.type_name, at.category, at.normal_balance
                              FROM chart_of_accounts coa
                              JOIN account_types at ON coa.account_type_id = at.id
                              WHERE coa.is_active = 1
                              ORDER BY coa.account_code');
        } else {
            $this->db->query('SELECT coa.*, at.type_name, at.category, at.normal_balance
                              FROM chart_of_accounts coa
                              JOIN account_types at ON coa.account_type_id = at.id
                              ORDER BY coa.account_code');
        }

        return $this->db->resultSet();
    }

    public function getAccountByCode($account_code) {
        $this->db->query('SELECT coa.*, at.type_name, at.category, at.normal_balance
                          FROM chart_of_accounts coa
                          JOIN account_types at ON coa.account_type_id = at.id
                          WHERE coa.account_code = :account_code');

        $this->db->bind(':account_code', $account_code);
        return $this->db->single();
    }

    public function getFiscalPeriod($date) {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        return array(
            'fiscal_year' => $year,
            'fiscal_period' => $month
        );
    }
}
?>
