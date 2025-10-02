-- =====================================================
-- INSERT DEMO USERS FOR TESTING
-- Default Password for all users: password123
-- =====================================================

-- Delete existing demo users if any
DELETE FROM users WHERE username IN ('leasing1', 'leasing2', 'account1', 'auditor1', 'admin1', 'cashier1');

-- Insert Demo Users
-- Password: password123 (hashed using PHP password_hash)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (username, password, full_name, email, role, department, employee_id, phone, is_active) VALUES
('admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@example.com', 'admin', 'Administration', 'EMP001', '08012345601', 1),

('leasing1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Leasing', 'john.leasing@example.com', 'leasing_officer', 'Wealth Creation', 'EMP002', '08012345602', 1),

('leasing2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Leasing', 'jane.leasing@example.com', 'leasing_officer', 'Wealth Creation', 'EMP003', '08012345603', 1),

('account1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Account', 'alice.account@example.com', 'account_officer', 'Accounts', 'EMP004', '08012345604', 1),

('auditor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Auditor', 'bob.auditor@example.com', 'auditor', 'Audit', 'EMP005', '08012345605', 1),

('cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Cashier', 'mary.cashier@example.com', 'cashier', 'Accounts', 'EMP006', '08012345606', 1);

-- Create Officer Tills for collection officers
INSERT INTO officer_tills (till_code, till_name, officer_id, account_code, opening_balance, current_balance, is_active) VALUES
('TILL-001', 'Leasing Officer 1 Till', (SELECT id FROM users WHERE username = 'leasing1'), '1012', 1000.00, 1000.00, 1),
('TILL-002', 'Leasing Officer 2 Till', (SELECT id FROM users WHERE username = 'leasing2'), '1013', 1000.00, 1000.00, 1),
('TILL-003', 'Cashier Till', (SELECT id FROM users WHERE username = 'cashier1'), '1014', 5000.00, 5000.00, 1);

-- Create sample property
INSERT INTO properties (property_code, property_name, property_type, address, total_units, occupied_units, is_active) VALUES
('PROP001', 'Main Market Complex', 'market', 'Central Business District', 100, 75, 1);

-- Create sample shops
INSERT INTO shops (shop_code, shop_number, property_id, shop_type, shop_size, square_meters, floor_level, monthly_rent, service_charge, status, is_active) VALUES
('SHP0001', 'A-001', 1, 'shop', 'Medium', 15.50, 'Ground Floor', 50000.00, 10000.00, 'occupied', 1),
('SHP0002', 'A-002', 1, 'shop', 'Large', 25.00, 'Ground Floor', 75000.00, 15000.00, 'occupied', 1),
('SHP0003', 'A-003', 1, 'shop', 'Small', 10.00, 'Ground Floor', 35000.00, 7000.00, 'vacant', 1),
('SHP0004', 'B-001', 1, 'kiosk', 'Small', 6.00, 'Ground Floor', 20000.00, 5000.00, 'occupied', 1),
('SHP0005', 'B-002', 1, 'kiosk', 'Small', 6.00, 'Ground Floor', 20000.00, 5000.00, 'vacant', 1);

-- Create sample customers
INSERT INTO customers (customer_code, customer_name, business_name, customer_type, phone, email, address, id_type, id_number, is_active) VALUES
('CUS20250001', 'Emeka Okafor', 'Emeka Electronics', 'individual', '08012345678', 'emeka@example.com', '123 Market Street', 'National ID', 'A12345678', 1),
('CUS20250002', 'Ngozi Adewale', 'Ngozi Fashion Store', 'individual', '08023456789', 'ngozi@example.com', '456 Market Street', 'National ID', 'B23456789', 1),
('CUS20250003', 'Chukwudi Industries Ltd', 'Chukwudi Industries Ltd', 'corporate', '08034567890', 'info@chukwudi.com', '789 Business Avenue', 'CAC', 'RC123456', 1),
('CUS20250004', 'Fatima Hassan', 'Fatima Food Supplies', 'individual', '08045678901', 'fatima@example.com', '321 Trade Road', 'National ID', 'C34567890', 1);

-- Create sample lease agreements
INSERT INTO lease_agreements (lease_code, shop_id, customer_id, lease_start_date, lease_end_date, lease_duration_months, monthly_rent, service_charge, security_deposit, deposit_paid, agreement_date, status, created_by) VALUES
('LSE20250001', 1, 1, '2025-01-01', '2025-12-31', 12, 50000.00, 10000.00, 100000.00, 100000.00, '2024-12-15', 'active', 1),
('LSE20250002', 2, 2, '2025-01-01', '2025-12-31', 12, 75000.00, 15000.00, 150000.00, 150000.00, '2024-12-15', 'active', 1),
('LSE20250003', 4, 3, '2025-02-01', '2026-01-31', 12, 20000.00, 5000.00, 40000.00, 40000.00, '2025-01-20', 'active', 1);

-- Create current fiscal year
INSERT INTO fiscal_years (year_code, year_name, start_date, end_date, status) VALUES
('FY2025', 'Fiscal Year 2025', '2025-01-01', '2025-12-31', 'open');

-- Create fiscal periods for 2025
INSERT INTO fiscal_periods (fiscal_year_id, period_number, period_name, start_date, end_date, status) VALUES
(1, 1, 'January 2025', '2025-01-01', '2025-01-31', 'open'),
(1, 2, 'February 2025', '2025-02-01', '2025-02-28', 'open'),
(1, 3, 'March 2025', '2025-03-01', '2025-03-31', 'open'),
(1, 4, 'April 2025', '2025-04-01', '2025-04-30', 'open'),
(1, 5, 'May 2025', '2025-05-01', '2025-05-31', 'open'),
(1, 6, 'June 2025', '2025-06-01', '2025-06-30', 'open'),
(1, 7, 'July 2025', '2025-07-01', '2025-07-31', 'open'),
(1, 8, 'August 2025', '2025-08-01', '2025-08-31', 'open'),
(1, 9, 'September 2025', '2025-09-01', '2025-09-30', 'open'),
(1, 10, 'October 2025', '2025-10-01', '2025-10-31', 'open'),
(1, 11, 'November 2025', '2025-11-01', '2025-11-30', 'open'),
(1, 12, 'December 2025', '2025-12-01', '2025-12-31', 'open');

-- Display login credentials
SELECT
    '=== DEMO USER CREDENTIALS ===' as '',
    '' as '';

SELECT
    username as 'Username',
    role as 'Role',
    full_name as 'Full Name',
    'password123' as 'Password'
FROM users
WHERE username IN ('admin1', 'leasing1', 'leasing2', 'account1', 'auditor1', 'cashier1')
ORDER BY
    CASE role
        WHEN 'admin' THEN 1
        WHEN 'leasing_officer' THEN 2
        WHEN 'account_officer' THEN 3
        WHEN 'cashier' THEN 4
        WHEN 'auditor' THEN 5
        ELSE 6
    END;

SELECT
    '' as '',
    '=== SYSTEM IS READY ===' as '';
