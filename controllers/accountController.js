
const db = require('../config/mysql_db');

// Get pending transactions for account approval
exports.getPendingApprovals = (req, res) => {
  const query = `
    SELECT t.*, s.full_name as posting_officer_name, a1.acct_desc as debit_account_desc, 
           a2.acct_desc as credit_account_desc
    FROM account_general_transaction_new t
    LEFT JOIN staffs s ON t.posting_officer_id = s.user_id
    LEFT JOIN accounts a1 ON t.debit_account = a1.acct_id
    LEFT JOIN accounts a2 ON t.credit_account = a2.acct_id
    WHERE t.approval_status = 'Pending' AND t.leasing_post_status = 'approved'
    ORDER BY t.posting_time DESC
  `;

  db.query(query, (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
};

// Approve a transaction
exports.approveTransaction = (req, res) => {
  const { transaction_id } = req.params;
  const { approving_officer_id, approving_officer_name, approval_notes } = req.body;

  const query = `
    UPDATE account_general_transaction_new 
    SET approval_status = 'approved',
        approving_officer_id = ?,
        approving_officer_name = ?,
        approval_time = NOW(),
        approval_notes = ?
    WHERE id = ? AND approval_status = 'Pending'
  `;

  db.query(query, [approving_officer_id, approving_officer_name, approval_notes, transaction_id], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Transaction not found or already approved' });
    }
    
    res.json({ message: 'Transaction approved successfully' });
  });
};

// Reject a transaction
exports.rejectTransaction = (req, res) => {
  const { transaction_id } = req.params;
  const { approving_officer_id, approving_officer_name, rejection_reason } = req.body;

  const query = `
    UPDATE account_general_transaction_new 
    SET approval_status = 'rejected',
        approving_officer_id = ?,
        approving_officer_name = ?,
        approval_time = NOW(),
        approval_notes = ?
    WHERE id = ? AND approval_status = 'Pending'
  `;

  db.query(query, [approving_officer_id, approving_officer_name, rejection_reason, transaction_id], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Transaction not found or already processed' });
    }
    
    res.json({ message: 'Transaction rejected successfully' });
  });
};

// Get account approval statistics
exports.getApprovalStats = (req, res) => {
  const query = `
    SELECT 
      COUNT(*) as total_transactions,
      SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
      SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
      SUM(CASE WHEN approval_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
      SUM(CASE WHEN approval_status = 'approved' THEN amount_paid ELSE 0 END) as approved_amount,
      SUM(CASE WHEN approval_status = 'Pending' THEN amount_paid ELSE 0 END) as pending_amount
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

// Get chart of accounts
exports.getAccounts = (req, res) => {
  const { type } = req.query;
  
  let query = 'SELECT * FROM accounts WHERE status = "active"';
  const params = [];
  
  if (type) {
    query += ' AND acct_type = ?';
    params.push(type);
  }
  
  query += ' ORDER BY acct_desc ASC';

  db.query(query, params, (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
};
