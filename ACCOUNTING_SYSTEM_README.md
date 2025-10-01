# International Standard Accounting System
## MySQL 5.6 / XAMPP Compatible

---

## System Overview

This is a comprehensive accounting and income collection management system built for XAMPP 5.6 (MySQL 5.6) compatibility. The system follows international accounting standards as implemented in **Sage**, **Peachtree**, and standard banking practices.

### Key Features

1. **Double-Entry Bookkeeping System**
   - Complete General Ledger with Chart of Accounts
   - Journal Entry posting with automatic account balance updates
   - Sub-ledger for customers/tenants
   - Audit trail for all transactions

2. **Till Management System**
   - Individual officer tills with separate GL accounts
   - Daily till balancing with variance tracking
   - Mandatory balance verification before posting
   - Flags any imbalance until resolved

3. **Shop/Property Management**
   - Complete customer/tenant management
   - Shop/unit master with occupancy tracking
   - Lease agreement management with automatic renewal alerts
   - Rent and service charge tracking per customer

4. **Income Collection System**
   - Multiple variable income lines (configurable)
   - Receipt generation with auto-numbering
   - Cash remittance with approval workflow
   - Automatic posting to General Ledger

5. **Financial Reporting**
   - Trial Balance
   - Income Statement (Profit & Loss)
   - Balance Sheet
   - Customer Ledger Reports
   - Daily Collection Reports (MPR)

---

## Database Architecture

### Core Tables Structure

#### 1. Chart of Accounts System
- `account_types` - Account classification (Asset, Liability, Equity, Revenue, Expense)
- `chart_of_accounts` - General Ledger accounts with hierarchical structure
- Account codes follow standard format:
  - 1000-1999: Assets
  - 2000-2999: Liabilities
  - 3000-3999: Equity
  - 4000-4999: Revenue
  - 5000-5999: Expenses

#### 2. Double Entry System
- `journal_entries` - Main journal entry header
- `journal_entry_lines` - Journal entry detail lines (debits and credits)
- Every transaction creates balanced journal entries
- Status workflow: draft → posted → reversed (if needed)

#### 3. Till Management
- `officer_tills` - Individual officer cash tills linked to GL accounts
- `till_balancing` - Daily balancing records with variance tracking
- Till must balance before collections can be posted

#### 4. Shop/Property Management
- `properties` - Building/market master
- `shops` - Individual units/shops with rental rates
- `customers` - Tenant/customer master
- `lease_agreements` - Lease contracts with renewal tracking
- `lease_renewals` - Automatic renewal monitoring
- `customer_ledger` - Customer account sub-ledger

#### 5. Income Collection
- `income_lines` - Configurable income types linked to GL accounts
- `collection_transactions` - Individual collection records
- `cash_remittance` - Daily officer remittance summary

#### 6. Fiscal Management
- `fiscal_years` - Financial year setup
- `fiscal_periods` - Monthly period management
- Supports period opening/closing

---

## Installation Instructions

### Prerequisites
- XAMPP 5.6 or higher
- PHP 5.6+ with PDO extension
- MySQL 5.6+

### Installation Steps

1. **Database Setup**
   ```bash
   # Start XAMPP services
   # Open phpMyAdmin (http://localhost/phpmyadmin)

   # Create database
   CREATE DATABASE income_erp_system;

   # Import schema
   # Use the file: database_schema_v2.sql
   ```

2. **Configure Database Connection**
   Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'income_erp_system');
   ```

3. **Set Permissions**
   ```bash
   # Ensure proper permissions on config folder
   chmod 755 config/
   chmod 644 config/config.php
   ```

4. **Access System**
   ```
   http://localhost/your-project-folder/
   ```

---

## System Workflow

### 1. Collection Posting Workflow

```
Officer Collects Money
    ↓
Record in Officer Till (Debit: Till Account)
    ↓
Create Collection Transaction (Receipt)
    ↓
End of Day: Create Remittance
    ↓
Account Officer Approves Remittance
    ↓
Till Must Balance (Expected vs Actual)
    ↓
If Balanced → Post to General Ledger
    ↓
Update Customer Ledger (if applicable)
    ↓
Transfer from Till to Main Cash Account
```

### 2. Daily Till Balancing Process

**CRITICAL: Till MUST balance before posting is allowed**

```php
Opening Balance:          1,000.00
+ Collections:           50,000.00
- Remittances:         (45,000.00)
= Expected Balance:       6,000.00

Actual Cash Count:        5,950.00
Variance:                   (50.00) ← FLAGGED!
```

**System Behavior:**
- If variance > 0: Flag for investigation
- Variance must be explained and approved
- No posting until balance is approved
- All variances are tracked and auditable

### 3. Journal Entry Structure

**Example: Collection Posting**
```
Date: 2025-10-01
Description: Collection from Shop Rent

Debit:  Officer Till (1012)          15,000.00
Credit: Shop Rent Income (4101)                 15,000.00
```

**Example: Remittance Posting**
```
Date: 2025-10-01
Description: Remittance to Main Cash

Debit:  Main Cash Account (1015)     50,000.00
Credit: Officer Till (1012)                     50,000.00
```

---

## Key Models and Usage

### 1. TillManagement.php

```php
$tillManager = new TillManagement();

// Get officer's till
$till = $tillManager->getOfficerTill($officer_id);

// Create daily balancing
$balancing_data = array(
    'balance_date' => date('Y-m-d'),
    'till_id' => $till['id'],
    'officer_id' => $officer_id,
    'opening_balance' => 1000.00,
    'total_collections' => 50000.00,
    'total_payments' => 0.00,
    'expected_balance' => 51000.00,
    'actual_balance' => 50950.00,
    'variance_reason' => 'Short change error'
);

$balance_id = $tillManager->createTillBalancing($balancing_data);

// Check if till is balanced
$is_balanced = $tillManager->checkTillBalanced($till['id'], date('Y-m-d'));
```

### 2. GeneralLedger.php

```php
$gl = new GeneralLedger();

// Create journal entry
$journal_data = array(
    'journal_date' => '2025-10-01',
    'fiscal_year' => 2025,
    'fiscal_period' => 10,
    'journal_type' => 'GJ', // GJ, CR, CP, SR, SI
    'description' => 'Monthly rent collection',
    'total_debit' => 15000.00,
    'total_credit' => 15000.00,
    'created_by' => $user_id,
    'lines' => array(
        array(
            'account_code' => '1012',
            'debit_amount' => 15000.00,
            'credit_amount' => 0.00,
            'description' => 'Cash collected'
        ),
        array(
            'account_code' => '4101',
            'debit_amount' => 0.00,
            'credit_amount' => 15000.00,
            'description' => 'Rent income'
        )
    )
);

$journal_id = $gl->createJournalEntry($journal_data);
$gl->postJournalEntry($journal_id, $user_id);

// Get trial balance
$trial_balance = $gl->getTrialBalance(date('Y-m-d'));

// Get income statement
$income_statement = $gl->getIncomeStatement('2025-01-01', '2025-12-31');
```

### 3. ShopManagement.php

```php
$shopManager = new ShopManagement();

// Create customer
$customer_data = array(
    'customer_name' => 'John Doe',
    'phone' => '08012345678',
    'email' => 'john@example.com',
    'customer_type' => 'individual'
);

$customer_id = $shopManager->createCustomer($customer_data);

// Create lease agreement
$lease_data = array(
    'shop_id' => 1,
    'customer_id' => $customer_id,
    'lease_start_date' => '2025-01-01',
    'lease_end_date' => '2025-12-31',
    'lease_duration_months' => 12,
    'monthly_rent' => 50000.00,
    'service_charge' => 10000.00,
    'security_deposit' => 100000.00,
    'agreement_date' => '2024-12-15',
    'status' => 'active',
    'created_by' => $user_id
);

$lease_id = $shopManager->createLeaseAgreement($lease_data);

// Get expiring leases (next 90 days)
$expiring = $shopManager->getExpiringLeases(90);

// Update customer account
$shopManager->addCustomerLedgerEntry(array(
    'transaction_date' => date('Y-m-d'),
    'customer_id' => $customer_id,
    'shop_id' => 1,
    'transaction_type' => 'payment',
    'debit_amount' => 0.00,
    'credit_amount' => 50000.00,
    'description' => 'Rent payment for October 2025'
));
```

### 4. CollectionManagement.php

```php
$collectionManager = new CollectionManagement();

// Record a collection
$collection_data = array(
    'receipt_number' => 'RCP20251001-0001',
    'transaction_date' => '2025-10-01',
    'customer_id' => $customer_id,
    'shop_id' => $shop_id,
    'income_line_id' => 1, // Shop Rent
    'amount' => 50000.00,
    'payment_method' => 'cash',
    'description' => 'October 2025 rent',
    'collected_by' => $officer_id,
    'till_id' => $till_id
);

$transaction_id = $collectionManager->recordCollection($collection_data);

// Create remittance
$remit_data = array(
    'remit_date' => date('Y-m-d'),
    'officer_id' => $officer_id,
    'officer_name' => 'John Officer',
    'till_id' => $till_id,
    'total_amount' => 150000.00,
    'total_receipts' => 5,
    'cash_amount' => 100000.00,
    'transfer_amount' => 50000.00
);

$remit_id = $collectionManager->createRemittance($remit_data);

// Approve and post
$collectionManager->approveRemittance($remit_id, $approver_id);
$collectionManager->postRemittance($remit_id, $poster_id);
```

---

## MySQL 5.6 Compatibility Notes

### Functions NOT Used (Incompatible with MySQL 5.6)
- ❌ `COALESCE()` - Replaced with `IFNULL()`
- ❌ JSON functions - Not available
- ❌ Window functions - Not available
- ❌ CTEs (Common Table Expressions) - Not available

### Compatible Alternatives Used
- ✅ `IFNULL()` instead of `COALESCE()`
- ✅ `CASE WHEN` for conditional logic
- ✅ Subqueries instead of CTEs
- ✅ Traditional joins
- ✅ `DATE_FORMAT()` for date manipulation
- ✅ `DATEDIFF()` for date calculations

---

## Income Line Configuration

The system supports unlimited income types. Each income line must be linked to a GL account.

### Standard Income Lines Included:

1. **Rental Income**
   - Shop Rent (4101)
   - Service Charge (4102)
   - Lock-up Store Rent (4103)

2. **Trading Income**
   - Daily Trade Levy (4201)
   - Loading Bay Charges (4202)
   - Wheelbarrow Fees (4203)
   - Hawkers Permit (4204)

3. **Parking Income**
   - Car Park Daily (4301)
   - Car Park Monthly (4302)

4. **Other Income**
   - Billboard Rental (4401)
   - Penalty Charges (4501)
   - Connection Fees (4502)

### Adding New Income Line

```sql
INSERT INTO income_lines
(income_code, income_name, account_code, category, is_recurring, requires_customer, requires_shop)
VALUES
('INC013', 'Water Supply', '4503', 'utilities', 1, 1, 1);
```

---

## Financial Reports

### 1. Trial Balance
Shows all GL accounts with debit/credit balances

```php
$trial_balance = $gl->getTrialBalance('2025-10-01');

foreach($trial_balance as $account) {
    echo $account['account_code'] . ' - ' . $account['account_name'];
    echo ' Balance: ' . $account['current_balance'];
}
```

### 2. Income Statement (Profit & Loss)
Shows revenue and expenses for a period

```php
$income_statement = $gl->getIncomeStatement('2025-01-01', '2025-10-01');

$total_revenue = 0;
$total_expenses = 0;

foreach($income_statement as $line) {
    if($line['category'] == 'REVENUE') {
        $total_revenue += $line['amount'];
    } else {
        $total_expenses += $line['amount'];
    }
}

$net_profit = $total_revenue - $total_expenses;
```

### 3. Balance Sheet
Shows Assets, Liabilities, and Equity at a point in time

```php
$balance_sheet = $gl->getBalanceSheet('2025-10-01');

$total_assets = 0;
$total_liabilities = 0;
$total_equity = 0;

foreach($balance_sheet as $line) {
    switch($line['category']) {
        case 'ASSET':
            $total_assets += $line['balance'];
            break;
        case 'LIABILITY':
            $total_liabilities += $line['balance'];
            break;
        case 'EQUITY':
            $total_equity += $line['balance'];
            break;
    }
}
```

---

## Security Features

1. **Transaction Integrity**
   - All financial transactions use database transactions (BEGIN/COMMIT/ROLLBACK)
   - Ensures data consistency

2. **Audit Trail**
   - All changes are logged in `audit_log` table
   - Who, what, when tracking

3. **Access Control**
   - Role-based permissions
   - Separation of duties (collection vs posting vs approval)

4. **Data Validation**
   - Journal entries must balance (debit = credit)
   - Till must balance before posting
   - No backdating beyond fiscal period

---

## Best Practices

### 1. Daily Operations
- Officers record collections during the day
- End of day: Create remittance
- Balance till (must match expected)
- Submit for approval
- Account officer approves
- System posts to GL

### 2. Month-End Process
- Reconcile all tills
- Review all unposted transactions
- Generate trial balance
- Close fiscal period
- Generate monthly reports

### 3. Lease Management
- Review expiring leases weekly
- Send renewal notices 90 days before expiry
- Update lease status promptly
- Keep customer information current

### 4. Backup Strategy
- Daily database backup
- Keep backup for minimum 7 years
- Test restoration periodically

---

## Support and Maintenance

### Common Issues

1. **Till Won't Balance**
   - Check all collection entries
   - Verify payment methods match physical cash
   - Review variance reason
   - Get supervisor approval for variance

2. **Journal Entry Won't Post**
   - Verify debits = credits
   - Check account codes exist
   - Ensure fiscal period is open

3. **Customer Balance Issues**
   - Review customer ledger
   - Check for missed payments
   - Reconcile with lease agreement

---

## Version Information

- **Database Schema Version**: 2.0
- **MySQL Compatibility**: 5.6+
- **PHP Version**: 5.6+
- **Architecture**: MVC Pattern with PDO
- **Accounting Standard**: Double-Entry Bookkeeping (Sage/Peachtree Compatible)

---

## Contact and Credits

This system implements international standard accounting practices suitable for:
- Market management
- Shopping complex administration
- Property leasing and management
- Multiple income source tracking
- Standard banking reconciliation

For customization and support, refer to the system administrator.

---

**END OF DOCUMENTATION**
