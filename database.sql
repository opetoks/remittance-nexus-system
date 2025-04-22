
-- Insert sample users with different roles
INSERT INTO users (username, password, full_name, role, email) VALUES 
('leasing1', '$2y$10$8tL3VNHf.iONvRsZ5/lVkevjWuYHBGQgI0mJmSkx.N876YUxFrjqG', 'John Leasing', 'leasing_officer', 'john@example.com'),
('leasing2', '$2y$10$8tL3VNHf.iONvRsZ5/lVkevjWuYHBGQgI0mJmSkx.N876YUxFrjqG', 'Jane Leasing', 'leasing_officer', 'jane@example.com'),
('account1', '$2y$10$8tL3VNHf.iONvRsZ5/lVkevjWuYHBGQgI0mJmSkx.N876YUxFrjqG', 'Alice Account', 'account_officer', 'alice@example.com'),
('auditor1', '$2y$10$8tL3VNHf.iONvRsZ5/lVkevjWuYHBGQgI0mJmSkx.N876YUxFrjqG', 'Bob Auditor', 'auditor', 'bob@example.com');

-- Insert sample remittances
INSERT INTO cash_remittance (remit_id, date, amount_paid, no_of_receipts, category, remitting_officer_id, remitting_officer_name, posting_officer_id, posting_officer_name) VALUES
('RMT-2025-001', '2025-04-22', 50000.00, 5, 'Shop Rent', 2, 'John Leasing', 3, 'Alice Account'),
('RMT-2025-002', '2025-04-22', 25000.00, 10, 'Daily Trade', 2, 'Jane Leasing', 3, 'Alice Account');

-- Insert sample transactions for the first remittance
INSERT INTO account_general_transaction_new (
    shop_id, customer_name, shop_no, date_of_payment, payment_type,
    receipt_no, amount_paid, posting_officer_id, posting_officer_name,
    remit_id, income_line, leasing_post_status, debit_account, credit_account
) VALUES 
('SH001', 'John Doe', 'A101', '2025-04-22', 'cash',
'RCP-001', 15000.00, 2, 'John Leasing',
'RMT-2025-001', 'Shop Rent', 'pending', 'TILL-001', 'SHR-001'),

('SH002', 'Jane Smith', 'A102', '2025-04-22', 'transfer',
'RCP-002', 12000.00, 2, 'John Leasing',
'RMT-2025-001', 'Shop Rent', 'pending', 'TILL-001', 'SHR-001'),

('SH003', 'Alice Johnson', 'A103', '2025-04-22', 'cash',
'RCP-003', 8000.00, 2, 'John Leasing',
'RMT-2025-001', 'Shop Rent', 'pending', 'TILL-001', 'SHR-001');

-- Insert sample unposted transactions
INSERT INTO unposted_transactions (
    remit_id, trans_id, shop_id, shop_no, customer_name,
    date_of_payment, amount_paid, receipt_no, income_line,
    posting_officer_id, posting_officer_name, reason
) VALUES
('RMT-2025-001', 'TRANS-001', 'SH004', 'A104', 'Robert Brown',
'2025-04-22', 15000.00, 'RCP-004', 'Shop Rent',
2, 'John Leasing', 'System downtime during posting hours');

