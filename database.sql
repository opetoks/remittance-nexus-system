-- Create database
CREATE DATABASE IF NOT EXISTS income_erp;
USE income_erp;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'leasing_officer', 'account_officer', 'auditor') NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Account table (Chart of Accounts)
CREATE TABLE account (
    acct_id INT AUTO_INCREMENT PRIMARY KEY,
    gl_code VARCHAR(20) NOT NULL UNIQUE,
    acct_code VARCHAR(20) NOT NULL UNIQUE,
    acct_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    acct_class VARCHAR(50) NOT NULL,
    acct_class_type VARCHAR(50) NOT NULL,
    acct_desc VARCHAR(255) NOT NULL,
    acct_alias VARCHAR(100) NOT NULL,
    acct_table_name VARCHAR(100),
    balance_sheet_report BOOLEAN DEFAULT FALSE,
    profit_loss_report BOOLEAN DEFAULT FALSE,
    negative_acct BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    page_visibility BOOLEAN DEFAULT TRUE,
    audit_position INT,
    income_line BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Cash Remittance table
CREATE TABLE cash_remittance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remit_id VARCHAR(20) NOT NULL UNIQUE,
    date DATE NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    no_of_receipts INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    remitting_officer_id INT NOT NULL,
    remitting_officer_name VARCHAR(100) NOT NULL,
    posting_officer_id INT NOT NULL,
    posting_officer_name VARCHAR(100) NOT NULL,
    remitting_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remitting_officer_id) REFERENCES users(id),
    FOREIGN KEY (posting_officer_id) REFERENCES users(id)
);

-- Account General Transaction table
CREATE TABLE account_general_transaction_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id VARCHAR(50),
    customer_name VARCHAR(100),
    shop_no VARCHAR(50),
    shop_size VARCHAR(50),
    date_of_payment DATE NOT NULL,
    date_on_receipt DATE,
    start_date DATE,
    end_date DATE,
    payment_type ENUM('cash', 'transfer', 'cheque', 'pos') NOT NULL,
    ticket_category VARCHAR(50),
    transaction_desc TEXT,
    bank_name VARCHAR(100),
    cheque_no VARCHAR(50),
    teller_no VARCHAR(50),
    receipt_no VARCHAR(50),
    amount_paid DECIMAL(10,2) NOT NULL,
    remitting_customer VARCHAR(100),
    remitting_id INT,
    remitting_staff VARCHAR(100),
    posting_officer_id INT NOT NULL,
    posting_officer_name VARCHAR(100) NOT NULL,
    posting_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leasing_post_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    leasing_post_approving_officer_id INT,
    leasing_post_approving_officer_name VARCHAR(100),
    leasing_post_approval_time TIMESTAMP NULL,
    approving_acct_officer_id INT,
    approving_acct_officer_name VARCHAR(100),
    approval_time TIMESTAMP NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verifying_auditor_id INT,
    verifying_auditor_name VARCHAR(100),
    verification_time TIMESTAMP NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    debit_account VARCHAR(20) NOT NULL,
    credit_account VARCHAR(20) NOT NULL,
    customer_status VARCHAR(50),
    payment_category VARCHAR(50),
    entry_status VARCHAR(50),
    no_of_tickets INT,
    plate_no VARCHAR(20),
    no_of_nights INT,
    it_status VARCHAR(50),
    sticker_no VARCHAR(50),
    no_of_days INT,
    ref_no VARCHAR(50),
    remit_id VARCHAR(20) NOT NULL,
    flag_status ENUM('active', 'inactive') DEFAULT 'active',
    income_line VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posting_officer_id) REFERENCES users(id),
    FOREIGN KEY (leasing_post_approving_officer_id) REFERENCES users(id),
    FOREIGN KEY (approving_acct_officer_id) REFERENCES users(id),
    FOREIGN KEY (verifying_auditor_id) REFERENCES users(id)
);

-- Income Line Tables (dynamically created based on the account table)
-- Example for Abattoir
CREATE TABLE income_abattoir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES account_general_transaction_new(id)
);

-- Example for Shop Rent
CREATE TABLE income_shop_rent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    shop_id VARCHAR(50) NOT NULL,
    shop_no VARCHAR(50) NOT NULL,
    shop_size VARCHAR(50),
    customer_name VARCHAR(100) NOT NULL,
    rent_start_date DATE NOT NULL,
    rent_end_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'pending') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES account_general_transaction_new(id)
);

-- Example for Service Charge
CREATE TABLE income_service_charge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    shop_id VARCHAR(50) NOT NULL,
    shop_no VARCHAR(50) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    month VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'partial', 'pending') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES account_general_transaction_new(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role, email) 
VALUES ('admin', '$2y$10$8tL3VNHf.iONvRsZ5/lVkevjWuYHBGQgI0mJmSkx.N876YUxFrjqG', 'System Administrator', 'admin', 'admin@incomeerp.com');

-- Insert sample account records
INSERT INTO account (gl_code, acct_code, acct_type, acct_class, acct_class_type, acct_desc, acct_alias, acct_table_name, income_line) 
VALUES 
('1000', 'TILL-001', 'asset', 'Current Asset', 'Till Account', 'Account Till', 'Account Till', NULL, FALSE),
('4000', 'ABT-001', 'revenue', 'Income', 'Operating Income', 'Abattoir Income', 'Abattoir', 'income_abattoir', TRUE),
('4001', 'CLT-001', 'revenue', 'Income', 'Operating Income', 'Car Loading Ticket', 'Car Loading', 'income_car_loading', TRUE),
('4002', 'CPT-001', 'revenue', 'Income', 'Operating Income', 'Car Park Ticket', 'Car Park', 'income_car_park', TRUE),
('4003', 'HWT-001', 'revenue', 'Income', 'Operating Income', 'Hawkers Ticket', 'Hawkers', 'income_hawkers', TRUE),
('4004', 'WBT-001', 'revenue', 'Income', 'Operating Income', 'WheelBarrow Ticket', 'WheelBarrow', 'income_wheelbarrow', TRUE),
('4005', 'DTR-001', 'revenue', 'Income', 'Operating Income', 'Daily Trade', 'Daily Trade', 'income_daily_trade', TRUE),
('4006', 'TCL-001', 'revenue', 'Income', 'Operating Income', 'Toilet Collection', 'Toilet', 'income_toilet_collection', TRUE),
('4007', 'SCB-001', 'revenue', 'Income', 'Operating Income', 'Scroll Board', 'Scroll Board', 'income_scroll_board', TRUE),
('4008', 'POS-001', 'revenue', 'Income', 'Operating Income', 'Other POS Ticket', 'POS Ticket', 'income_pos_ticket', TRUE),
('4009', 'DTA-001', 'revenue', 'Income', 'Operating Income', 'Daily Trade Arrears', 'Trade Arrears', 'income_trade_arrears', TRUE),
('4010', 'SHR-001', 'revenue', 'Income', 'Operating Income', 'Shop Rent', 'Shop Rent', 'income_shop_rent', TRUE),
('4011', 'SVC-001', 'revenue', 'Income', 'Operating Income', 'Service Charge', 'Service Charge', 'income_service_charge', TRUE);

-- Unposted Transactions table
CREATE TABLE unposted_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remit_id VARCHAR(20) NOT NULL,
    trans_id VARCHAR(50),
    shop_id VARCHAR(50),
    shop_no VARCHAR(50),
    customer_name VARCHAR(100),
    date_of_payment DATE NOT NULL,
    transaction_desc TEXT,
    amount_paid DECIMAL(10,2) NOT NULL,
    receipt_no VARCHAR(50),
    category VARCHAR(50),
    income_line VARCHAR(50) NOT NULL,
    posting_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    posting_officer_id INT NOT NULL,
    posting_officer_name VARCHAR(100) NOT NULL,
    payment_status ENUM('pending', 'reposted') DEFAULT 'pending',
    reason TEXT,
    reposting_time TIMESTAMP NULL,
    FOREIGN KEY (posting_officer_id) REFERENCES users(id),
    FOREIGN KEY (remit_id) REFERENCES cash_remittance(remit_id)
);
