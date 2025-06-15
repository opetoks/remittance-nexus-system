
const db = require('../config/mysql_db');

// Get pending transactions for audit verification
exports.getPendingVerifications = (req, res) => {
  const query = `
    SELECT t.*, s.full_name as posting_officer_name, a1.acct_desc as debit_account_desc, 
           a2.acct_desc as credit_account_desc
    FROM account_general_transaction_new t
    LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
    LEFT JOIN accounts a1 ON t.debit_account = a1.acct_id
    LEFT JOIN accounts a2 ON t.credit_account = a2.acct_id
    WHERE t.verification_status = 'Pending' AND t.approval_status = 'approved'
    ORDER BY t.posting_time DESC
  `;

  db.query(query, (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
};

// Verify a transaction
exports.verifyTransaction = (req, res) => {
  const { transaction_id } = req.params;
  const { verifying_officer_id, verifying_officer_name, verification_notes } = req.body;

  const query = `
    UPDATE account_general_transaction_new 
    SET verification_status = 'verified',
        verifying_officer_id = ?,
        verifying_officer_name = ?,
        verification_time = NOW(),
        verification_notes = ?
    WHERE id = ? AND verification_status = 'Pending'
  `;

  db.query(query, [verifying_officer_id, verifying_officer_name, verification_notes, transaction_id], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Transaction not found or already verified' });
    }
    
    res.json({ message: 'Transaction verified successfully' });
  });
};

// Reject a transaction
exports.rejectTransaction = (req, res) => {
  const { transaction_id } = req.params;
  const { verifying_officer_id, verifying_officer_name, rejection_reason } = req.body;

  const query = `
    UPDATE account_general_transaction_new 
    SET verification_status = 'rejected',
        verifying_officer_id = ?,
        verifying_officer_name = ?,
        verification_time = NOW(),
        verification_notes = ?
    WHERE id = ? AND verification_status = 'Pending'
  `;

  db.query(query, [verifying_officer_id, verifying_officer_name, rejection_reason, transaction_id], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Transaction not found or already processed' });
    }
    
    res.json({ message: 'Transaction rejected successfully' });
  });
};

// Get audit statistics
exports.getAuditStats = (req, res) => {
  const query = `
    SELECT 
      COUNT(*) as total_transactions,
      SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified_count,
      SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
      SUM(CASE WHEN verification_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
      SUM(CASE WHEN verification_status = 'verified' THEN amount_paid ELSE 0 END) as verified_amount,
      SUM(CASE WHEN verification_status = 'Pending' THEN amount_paid ELSE 0 END) as pending_amount
    FROM account_general_transaction_new
    WHERE DATE(posting_time) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  `;

  db.query(query, (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results[0]);
  });
};
