-- =====================================================
-- INTERNATIONAL STANDARD ACCOUNTING SYSTEM DATABASE
-- Compatible with MySQL 5.6 (XAMPP)
-- Based on: Sage, Peachtree, Standard Banking Structure
-- =====================================================

-- Drop existing tables if needed (be careful in production)
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CORE SYSTEM TABLES
-- =====================================================

-- Users and Authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'leasing_officer', 'account_officer', 'auditor', 'cashier', 'accountant', 'manager') NOT NULL,
    department VARCHAR(100),
    employee_id VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- CHART OF ACCOUNTS - International Standard
-- =====================================================

-- Account Types Classification (Sage/Peachtree style)
CREATE TABLE IF NOT EXISTS account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(10) UNIQUE NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    category ENUM('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE') NOT NULL,
    normal_balance ENUM('DEBIT', 'CREDIT') NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert standard account types
INSERT INTO account_types (type_code, type_name, category, normal_balance, sort_order) VALUES
('1000', 'Current Assets', 'ASSET', 'DEBIT', 1),
('1100', 'Bank Accounts', 'ASSET', 'DEBIT', 2),
('1200', 'Accounts Receivable', 'ASSET', 'DEBIT', 3),
('1300', 'Inventory', 'ASSET', 'DEBIT', 4),
('1500', 'Fixed Assets', 'ASSET', 'DEBIT', 5),
('2000', 'Current Liabilities', 'LIABILITY', 'CREDIT', 6),
('2100', 'Accounts Payable', 'LIABILITY', 'CREDIT', 7),
('2500', 'Long Term Liabilities', 'LIABILITY', 'CREDIT', 8),
('3000', 'Equity', 'EQUITY', 'CREDIT', 9),
('4000', 'Revenue', 'REVENUE', 'CREDIT', 10),
('5000', 'Cost of Sales', 'EXPENSE', 'DEBIT', 11),
('6000', 'Operating Expenses', 'EXPENSE', 'DEBIT', 12);

-- Chart of Accounts (General Ledger Accounts)
CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type_id INT NOT NULL,
    parent_account_id INT NULL,
    account_level INT DEFAULT 1,
    is_header TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    allow_posting TINYINT(1) DEFAULT 1,
    description TEXT,
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id),
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    INDEX idx_account_code (account_code),
    INDEX idx_account_type (account_type_id),
    INDEX idx_parent (parent_account_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert standard chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type_id, is_header, allow_posting, description) VALUES
-- Assets
('1000', 'ASSETS', 1, 1, 0, 'All Assets'),
('1010', 'Cash and Bank', 1, 1, 0, 'Cash and Bank Accounts'),
('1011', 'Petty Cash', 1, 0, 1, 'Petty Cash on Hand'),
('1012', 'Cash in Till - Officer 1', 1, 0, 1, 'Cash Till for Collection Officer 1'),
('1013', 'Cash in Till - Officer 2', 1, 0, 1, 'Cash Till for Collection Officer 2'),
('1014', 'Cash in Till - Officer 3', 1, 0, 1, 'Cash Till for Collection Officer 3'),
('1015', 'Main Cash Account', 1, 0, 1, 'Main Cash Account after posting'),
('1100', 'Bank Accounts', 1, 1, 0, 'All Bank Accounts'),
('1101', 'Bank Account - Main', 1, 0, 1, 'Main Operating Bank Account'),
('1102', 'Bank Account - Collections', 1, 0, 1, 'Collection Deposits Account'),
('1200', 'Accounts Receivable', 1, 1, 0, 'All Receivables'),
('1201', 'Trade Debtors', 1, 0, 1, 'Trade Accounts Receivable'),
('1202', 'Rent Receivable', 1, 0, 1, 'Outstanding Rent Payments'),
('1203', 'Service Charge Receivable', 1, 0, 1, 'Outstanding Service Charges'),

-- Liabilities
('2000', 'LIABILITIES', 2, 1, 0, 'All Liabilities'),
('2100', 'Current Liabilities', 2, 1, 0, 'Current Liabilities'),
('2101', 'Accounts Payable', 2, 0, 1, 'Trade Accounts Payable'),
('2102', 'Accrued Expenses', 2, 0, 1, 'Accrued Expenses'),
('2200', 'Deposits and Advances', 2, 1, 0, 'Customer Deposits'),
('2201', 'Tenant Security Deposits', 2, 0, 1, 'Security Deposits from Tenants'),
('2202', 'Advance Rent Payments', 2, 0, 1, 'Rent Paid in Advance'),

-- Equity
('3000', 'EQUITY', 3, 1, 0, 'Owner Equity'),
('3100', 'Capital', 3, 0, 1, 'Owner Capital'),
('3200', 'Retained Earnings', 3, 0, 1, 'Accumulated Profits'),
('3900', 'Current Year Earnings', 3, 0, 0, 'Current Year Profit/Loss'),

-- Revenue
('4000', 'REVENUE', 4, 1, 0, 'All Revenue'),
('4100', 'Rental Income', 4, 1, 0, 'All Rental Income'),
('4101', 'Shop Rent Income', 4, 0, 1, 'Income from Shop Rentals'),
('4102', 'Service Charge Income', 4, 0, 1, 'Service Charge Collections'),
('4103', 'Lock-up Store Rent', 4, 0, 1, 'Lock-up Store Rentals'),
('4200', 'Trading Income', 4, 1, 0, 'Trading Related Income'),
('4201', 'Daily Trade Levy', 4, 0, 1, 'Daily Market Trade Levy'),
('4202', 'Loading Bay Charges', 4, 0, 1, 'Loading Bay Usage Charges'),
('4203', 'Wheelbarrow Fees', 4, 0, 1, 'Wheelbarrow Usage Fees'),
('4204', 'Hawkers Permit Fees', 4, 0, 1, 'Hawkers Trading Permit'),
('4300', 'Parking Income', 4, 1, 0, 'Parking Related Income'),
('4301', 'Car Park Daily', 4, 0, 1, 'Daily Car Park Tickets'),
('4302', 'Car Park Monthly', 4, 0, 1, 'Monthly Car Park Subscriptions'),
('4400', 'Advertising Income', 4, 1, 0, 'Advertising Revenue'),
('4401', 'Billboard Rentals', 4, 0, 1, 'Billboard Advertising Space'),
('4402', 'Signage Fees', 4, 0, 1, 'Shop Signage Fees'),
('4500', 'Other Income', 4, 1, 0, 'Other Revenue Sources'),
('4501', 'Penalty Charges', 4, 0, 1, 'Late Payment Penalties'),
('4502', 'Connection Fees', 4, 0, 1, 'New Connection Fees'),
('4503', 'Reconnection Fees', 4, 0, 1, 'Service Reconnection Fees'),

-- Expenses
('5000', 'EXPENSES', 5, 1, 0, 'All Expenses'),
('5100', 'Operating Expenses', 5, 1, 0, 'Operating Expenses'),
('5101', 'Salaries and Wages', 5, 0, 1, 'Staff Salaries'),
('5102', 'Utilities', 5, 0, 1, 'Electricity, Water, etc'),
('5103', 'Repairs and Maintenance', 5, 0, 1, 'Maintenance Costs'),
('5104', 'Security Services', 5, 0, 1, 'Security Personnel Costs'),
('5105', 'Cleaning Services', 5, 0, 1, 'Cleaning and Sanitation'),
('5200', 'Administrative Expenses', 5, 1, 0, 'Administrative Costs'),
('5201', 'Office Supplies', 5, 0, 1, 'Office Consumables'),
('5202', 'Printing and Stationery', 5, 0, 1, 'Printing Costs'),
('5203', 'Telephone and Internet', 5, 0, 1, 'Communication Costs');

-- =====================================================
-- TILL MANAGEMENT SYSTEM
-- =====================================================

-- Officer Till Accounts
CREATE TABLE IF NOT EXISTS officer_tills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    till_code VARCHAR(20) UNIQUE NOT NULL,
    till_name VARCHAR(100) NOT NULL,
    officer_id INT NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    last_balanced_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (officer_id) REFERENCES users(id),
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code),
    INDEX idx_officer (officer_id),
    INDEX idx_till_code (till_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Daily Till Balancing
CREATE TABLE IF NOT EXISTS till_balancing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    balance_date DATE NOT NULL,
    till_id INT NOT NULL,
    officer_id INT NOT NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_collections DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_payments DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    expected_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    actual_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    variance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    variance_reason TEXT NULL,
    is_balanced TINYINT(1) DEFAULT 0,
    balanced_by INT NULL,
    balanced_at TIMESTAMP NULL,
    status ENUM('pending', 'balanced', 'variance_pending', 'approved', 'rejected') DEFAULT 'pending',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (till_id) REFERENCES officer_tills(id),
    FOREIGN KEY (officer_id) REFERENCES users(id),
    FOREIGN KEY (balanced_by) REFERENCES users(id),
    INDEX idx_balance_date (balance_date),
    INDEX idx_till (till_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- SHOP/PROPERTY MANAGEMENT
-- =====================================================

-- Property/Building Master
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_code VARCHAR(20) UNIQUE NOT NULL,
    property_name VARCHAR(200) NOT NULL,
    property_type ENUM('market', 'mall', 'complex', 'standalone') NOT NULL,
    address TEXT,
    total_units INT DEFAULT 0,
    occupied_units INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_property_code (property_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Shops/Units Master
CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_code VARCHAR(20) UNIQUE NOT NULL,
    shop_number VARCHAR(50) NOT NULL,
    property_id INT NOT NULL,
    shop_type ENUM('shop', 'lockup', 'kiosk', 'stall', 'office') NOT NULL,
    shop_size VARCHAR(50),
    square_meters DECIMAL(10,2),
    floor_level VARCHAR(20),
    location_description TEXT,
    monthly_rent DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    service_charge DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('vacant', 'occupied', 'reserved', 'maintenance') DEFAULT 'vacant',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    INDEX idx_shop_code (shop_code),
    INDEX idx_shop_number (shop_number),
    INDEX idx_property (property_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Customers/Tenants
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(200) NOT NULL,
    business_name VARCHAR(200),
    customer_type ENUM('individual', 'corporate') DEFAULT 'individual',
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    id_type VARCHAR(50),
    id_number VARCHAR(50),
    business_reg_no VARCHAR(50),
    contact_person VARCHAR(100),
    account_balance DECIMAL(15,2) DEFAULT 0.00,
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_code (customer_code),
    INDEX idx_customer_name (customer_name),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Lease Agreements
CREATE TABLE IF NOT EXISTS lease_agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lease_code VARCHAR(20) UNIQUE NOT NULL,
    shop_id INT NOT NULL,
    customer_id INT NOT NULL,
    lease_start_date DATE NOT NULL,
    lease_end_date DATE NOT NULL,
    lease_duration_months INT NOT NULL,
    monthly_rent DECIMAL(15,2) NOT NULL,
    service_charge DECIMAL(15,2) NOT NULL,
    security_deposit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    deposit_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    agreement_date DATE NOT NULL,
    status ENUM('draft', 'active', 'expired', 'terminated', 'renewed') DEFAULT 'draft',
    renewal_notice_sent TINYINT(1) DEFAULT 0,
    renewal_notice_date DATE NULL,
    auto_renew TINYINT(1) DEFAULT 0,
    terms_conditions TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_lease_code (lease_code),
    INDEX idx_shop (shop_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_end_date (lease_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Lease Renewal Tracking
CREATE TABLE IF NOT EXISTS lease_renewals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    old_lease_id INT NOT NULL,
    new_lease_id INT NULL,
    shop_id INT NOT NULL,
    customer_id INT NOT NULL,
    expiry_date DATE NOT NULL,
    renewal_status ENUM('pending', 'reminded', 'renewed', 'not_renewed', 'vacated') DEFAULT 'pending',
    reminder_sent_date DATE NULL,
    renewed_date DATE NULL,
    days_to_expiry INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (old_lease_id) REFERENCES lease_agreements(id),
    FOREIGN KEY (new_lease_id) REFERENCES lease_agreements(id),
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (renewal_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- INCOME COLLECTION MANAGEMENT
-- =====================================================

-- Income Lines (Variable Income Types)
CREATE TABLE IF NOT EXISTS income_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    income_code VARCHAR(20) UNIQUE NOT NULL,
    income_name VARCHAR(200) NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    category ENUM('rental', 'trading', 'parking', 'utilities', 'other') NOT NULL,
    is_recurring TINYINT(1) DEFAULT 0,
    requires_customer TINYINT(1) DEFAULT 1,
    requires_shop TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code),
    INDEX idx_income_code (income_code),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert standard income lines
INSERT INTO income_lines (income_code, income_name, account_code, category, is_recurring, requires_customer, requires_shop) VALUES
('INC001', 'Shop Rent', '4101', 'rental', 1, 1, 1),
('INC002', 'Service Charge', '4102', 'rental', 1, 1, 1),
('INC003', 'Lock-up Store Rent', '4103', 'rental', 1, 1, 1),
('INC004', 'Daily Trade Levy', '4201', 'trading', 0, 0, 0),
('INC005', 'Loading Bay Charges', '4202', 'trading', 0, 0, 0),
('INC006', 'Wheelbarrow Fees', '4203', 'trading', 0, 0, 0),
('INC007', 'Hawkers Permit', '4204', 'trading', 0, 1, 0),
('INC008', 'Car Park Daily', '4301', 'parking', 0, 0, 0),
('INC009', 'Car Park Monthly', '4302', 'parking', 1, 1, 0),
('INC010', 'Billboard Rental', '4401', 'other', 1, 1, 0),
('INC011', 'Penalty Charges', '4501', 'other', 0, 1, 0),
('INC012', 'Connection Fees', '4502', 'other', 0, 1, 0);

-- Cash Remittance (Daily Collection Summary by Officer)
CREATE TABLE IF NOT EXISTS cash_remittance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remit_code VARCHAR(20) UNIQUE NOT NULL,
    remit_date DATE NOT NULL,
    officer_id INT NOT NULL,
    officer_name VARCHAR(100) NOT NULL,
    till_id INT NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_receipts INT NOT NULL DEFAULT 0,
    cash_amount DECIMAL(15,2) DEFAULT 0.00,
    cheque_amount DECIMAL(15,2) DEFAULT 0.00,
    transfer_amount DECIMAL(15,2) DEFAULT 0.00,
    pos_amount DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('draft', 'submitted', 'approved', 'posted', 'rejected') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    posted_by INT NULL,
    posted_at TIMESTAMP NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (officer_id) REFERENCES users(id),
    FOREIGN KEY (till_id) REFERENCES officer_tills(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (posted_by) REFERENCES users(id),
    INDEX idx_remit_code (remit_code),
    INDEX idx_remit_date (remit_date),
    INDEX idx_officer (officer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- TRANSACTION TABLES (Double Entry Bookkeeping)
-- =====================================================

-- General Journal Entries
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_code VARCHAR(20) UNIQUE NOT NULL,
    journal_date DATE NOT NULL,
    fiscal_year INT NOT NULL,
    fiscal_period INT NOT NULL,
    journal_type ENUM('GJ', 'CR', 'CP', 'SR', 'SI') NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    description TEXT NOT NULL,
    total_debit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft', 'posted', 'reversed') DEFAULT 'draft',
    posted_by INT NULL,
    posted_at TIMESTAMP NULL,
    reversed_by INT NULL,
    reversed_at TIMESTAMP NULL,
    reversal_reason TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (posted_by) REFERENCES users(id),
    INDEX idx_journal_code (journal_code),
    INDEX idx_journal_date (journal_date),
    INDEX idx_fiscal (fiscal_year, fiscal_period),
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Journal Entry Lines (Double Entry)
CREATE TABLE IF NOT EXISTS journal_entry_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_id INT NOT NULL,
    line_number INT NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    debit_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    cost_center VARCHAR(50),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code),
    INDEX idx_journal (journal_id),
    INDEX idx_account (account_code),
    UNIQUE KEY unique_line (journal_id, line_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Collection Transactions (Sub-Ledger for Collections)
CREATE TABLE IF NOT EXISTS collection_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(20) UNIQUE NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    remit_id INT NULL,
    journal_id INT NULL,
    customer_id INT NULL,
    shop_id INT NULL,
    income_line_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'cheque', 'transfer', 'pos') NOT NULL,
    cheque_number VARCHAR(50) NULL,
    bank_name VARCHAR(100) NULL,
    reference_number VARCHAR(100) NULL,
    description TEXT,
    collected_by INT NOT NULL,
    till_id INT NOT NULL,
    status ENUM('draft', 'collected', 'posted', 'cancelled') DEFAULT 'collected',
    posted_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remit_id) REFERENCES cash_remittance(id),
    FOREIGN KEY (journal_id) REFERENCES journal_entries(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    FOREIGN KEY (income_line_id) REFERENCES income_lines(id),
    FOREIGN KEY (collected_by) REFERENCES users(id),
    FOREIGN KEY (till_id) REFERENCES officer_tills(id),
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_receipt (receipt_number),
    INDEX idx_date (transaction_date),
    INDEX idx_remit (remit_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Customer Account Ledger (Sub-Ledger for Customer Balances)
CREATE TABLE IF NOT EXISTS customer_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    customer_id INT NOT NULL,
    shop_id INT NULL,
    transaction_type ENUM('invoice', 'payment', 'credit_note', 'debit_note', 'opening') NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    debit_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    INDEX idx_customer (customer_id),
    INDEX idx_date (transaction_date),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- FINANCIAL PERIOD MANAGEMENT
-- =====================================================

-- Fiscal Years
CREATE TABLE IF NOT EXISTS fiscal_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_code VARCHAR(10) UNIQUE NOT NULL,
    year_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    closed_by INT NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (closed_by) REFERENCES users(id),
    INDEX idx_year_code (year_code),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Fiscal Periods (Monthly)
CREATE TABLE IF NOT EXISTS fiscal_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fiscal_year_id INT NOT NULL,
    period_number INT NOT NULL,
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    closed_by INT NULL,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years(id),
    FOREIGN KEY (closed_by) REFERENCES users(id),
    INDEX idx_fiscal_year (fiscal_year_id),
    INDEX idx_period (period_number),
    UNIQUE KEY unique_period (fiscal_year_id, period_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- REPORTING AND AUDIT TRAIL
-- =====================================================

-- Audit Log
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_table (table_name),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- System Configuration
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type VARCHAR(50),
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert default system configuration
INSERT INTO system_config (config_key, config_value, config_type, description) VALUES
('company_name', 'Income Collection ERP System', 'string', 'Company Name'),
('fiscal_year_start_month', '1', 'integer', 'Fiscal Year Start Month (1-12)'),
('currency_symbol', '₦', 'string', 'Currency Symbol'),
('currency_code', 'NGN', 'string', 'Currency Code'),
('receipt_prefix', 'RCP', 'string', 'Receipt Number Prefix'),
('remit_prefix', 'RMT', 'string', 'Remittance Code Prefix'),
('journal_prefix', 'JE', 'string', 'Journal Entry Prefix'),
('enable_till_balancing', '1', 'boolean', 'Enable Daily Till Balancing'),
('enable_lease_renewal_alerts', '1', 'boolean', 'Enable Lease Renewal Alerts'),
('renewal_alert_days', '90', 'integer', 'Days before lease expiry to send alerts');

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Account Balances View (Trial Balance)
CREATE OR REPLACE VIEW view_account_balances AS
SELECT
    coa.account_code,
    coa.account_name,
    at.type_name as account_type,
    at.category,
    at.normal_balance,
    coa.opening_balance,
    CASE
        WHEN at.normal_balance = 'DEBIT' THEN
            coa.opening_balance + IFNULL(SUM(jel.debit_amount), 0) - IFNULL(SUM(jel.credit_amount), 0)
        ELSE
            coa.opening_balance + IFNULL(SUM(jel.credit_amount), 0) - IFNULL(SUM(jel.debit_amount), 0)
    END as current_balance
FROM chart_of_accounts coa
LEFT JOIN account_types at ON coa.account_type_id = at.id
LEFT JOIN journal_entry_lines jel ON coa.account_code = jel.account_code
LEFT JOIN journal_entries je ON jel.journal_id = je.id AND je.status = 'posted'
WHERE coa.is_active = 1 AND coa.allow_posting = 1
GROUP BY coa.account_code, coa.account_name, at.type_name, at.category, at.normal_balance, coa.opening_balance;

-- Daily Collection Summary View
CREATE OR REPLACE VIEW view_daily_collections AS
SELECT
    ct.transaction_date,
    u.full_name as collected_by,
    il.income_name,
    il.category,
    COUNT(ct.id) as transaction_count,
    SUM(ct.amount) as total_amount,
    SUM(CASE WHEN ct.payment_method = 'cash' THEN ct.amount ELSE 0 END) as cash_amount,
    SUM(CASE WHEN ct.payment_method = 'cheque' THEN ct.amount ELSE 0 END) as cheque_amount,
    SUM(CASE WHEN ct.payment_method = 'transfer' THEN ct.amount ELSE 0 END) as transfer_amount,
    SUM(CASE WHEN ct.payment_method = 'pos' THEN ct.amount ELSE 0 END) as pos_amount
FROM collection_transactions ct
JOIN users u ON ct.collected_by = u.id
JOIN income_lines il ON ct.income_line_id = il.id
WHERE ct.status IN ('collected', 'posted')
GROUP BY ct.transaction_date, u.full_name, il.income_name, il.category;

-- Shop Occupancy View
CREATE OR REPLACE VIEW view_shop_occupancy AS
SELECT
    p.property_name,
    COUNT(s.id) as total_shops,
    SUM(CASE WHEN s.status = 'occupied' THEN 1 ELSE 0 END) as occupied_shops,
    SUM(CASE WHEN s.status = 'vacant' THEN 1 ELSE 0 END) as vacant_shops,
    SUM(CASE WHEN s.status = 'occupied' THEN s.monthly_rent ELSE 0 END) as potential_rent_income,
    SUM(CASE WHEN s.status = 'occupied' THEN s.service_charge ELSE 0 END) as potential_service_charge
FROM properties p
LEFT JOIN shops s ON p.id = s.property_id AND s.is_active = 1
GROUP BY p.property_name;

-- Lease Expiry Report View
CREATE OR REPLACE VIEW view_lease_expiry AS
SELECT
    la.lease_code,
    s.shop_number,
    c.customer_name,
    c.phone,
    la.lease_start_date,
    la.lease_end_date,
    DATEDIFF(la.lease_end_date, CURDATE()) as days_to_expiry,
    la.monthly_rent,
    la.status
FROM lease_agreements la
JOIN shops s ON la.shop_id = s.id
JOIN customers c ON la.customer_id = c.id
WHERE la.status = 'active' AND la.lease_end_date >= CURDATE()
ORDER BY days_to_expiry;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- END OF DATABASE SCHEMA
-- =====================================================
