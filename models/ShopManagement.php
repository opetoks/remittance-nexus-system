<?php
require_once __DIR__ . '/../config/Database.php';

class ShopManagement {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createCustomer($data) {
        try {
            $this->db->beginTransaction();

            $customer_code = $this->generateCustomerCode();

            $this->db->query('INSERT INTO customers
                              (customer_code, customer_name, business_name, customer_type,
                               phone, email, address, id_type, id_number, business_reg_no,
                               contact_person, credit_limit)
                              VALUES (:customer_code, :customer_name, :business_name, :customer_type,
                                      :phone, :email, :address, :id_type, :id_number, :business_reg_no,
                                      :contact_person, :credit_limit)');

            $this->db->bind(':customer_code', $customer_code);
            $this->db->bind(':customer_name', $data['customer_name']);
            $this->db->bind(':business_name', isset($data['business_name']) ? $data['business_name'] : null);
            $this->db->bind(':customer_type', isset($data['customer_type']) ? $data['customer_type'] : 'individual');
            $this->db->bind(':phone', isset($data['phone']) ? $data['phone'] : null);
            $this->db->bind(':email', isset($data['email']) ? $data['email'] : null);
            $this->db->bind(':address', isset($data['address']) ? $data['address'] : null);
            $this->db->bind(':id_type', isset($data['id_type']) ? $data['id_type'] : null);
            $this->db->bind(':id_number', isset($data['id_number']) ? $data['id_number'] : null);
            $this->db->bind(':business_reg_no', isset($data['business_reg_no']) ? $data['business_reg_no'] : null);
            $this->db->bind(':contact_person', isset($data['contact_person']) ? $data['contact_person'] : null);
            $this->db->bind(':credit_limit', isset($data['credit_limit']) ? $data['credit_limit'] : 0.00);

            $this->db->execute();
            $customer_id = $this->db->lastInsertId();

            $this->db->endTransaction();
            return $customer_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function getCustomer($customer_id) {
        $this->db->query('SELECT * FROM customers WHERE id = :customer_id');
        $this->db->bind(':customer_id', $customer_id);
        return $this->db->single();
    }

    public function getAllCustomers($active_only = true) {
        if ($active_only) {
            $this->db->query('SELECT * FROM customers WHERE is_active = 1 ORDER BY customer_name');
        } else {
            $this->db->query('SELECT * FROM customers ORDER BY customer_name');
        }

        return $this->db->resultSet();
    }

    public function createShop($data) {
        try {
            $this->db->beginTransaction();

            $shop_code = $this->generateShopCode();

            $this->db->query('INSERT INTO shops
                              (shop_code, shop_number, property_id, shop_type, shop_size,
                               square_meters, floor_level, location_description,
                               monthly_rent, service_charge, status)
                              VALUES (:shop_code, :shop_number, :property_id, :shop_type, :shop_size,
                                      :square_meters, :floor_level, :location_description,
                                      :monthly_rent, :service_charge, :status)');

            $this->db->bind(':shop_code', $shop_code);
            $this->db->bind(':shop_number', $data['shop_number']);
            $this->db->bind(':property_id', $data['property_id']);
            $this->db->bind(':shop_type', $data['shop_type']);
            $this->db->bind(':shop_size', isset($data['shop_size']) ? $data['shop_size'] : null);
            $this->db->bind(':square_meters', isset($data['square_meters']) ? $data['square_meters'] : null);
            $this->db->bind(':floor_level', isset($data['floor_level']) ? $data['floor_level'] : null);
            $this->db->bind(':location_description', isset($data['location_description']) ? $data['location_description'] : null);
            $this->db->bind(':monthly_rent', $data['monthly_rent']);
            $this->db->bind(':service_charge', $data['service_charge']);
            $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'vacant');

            $this->db->execute();
            $shop_id = $this->db->lastInsertId();

            $this->db->endTransaction();
            return $shop_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function getShop($shop_id) {
        $this->db->query('SELECT s.*, p.property_name
                          FROM shops s
                          LEFT JOIN properties p ON s.property_id = p.id
                          WHERE s.id = :shop_id');

        $this->db->bind(':shop_id', $shop_id);
        return $this->db->single();
    }

    public function getAllShops($status = null) {
        if ($status) {
            $this->db->query('SELECT s.*, p.property_name
                              FROM shops s
                              LEFT JOIN properties p ON s.property_id = p.id
                              WHERE s.status = :status AND s.is_active = 1
                              ORDER BY s.shop_number');
            $this->db->bind(':status', $status);
        } else {
            $this->db->query('SELECT s.*, p.property_name
                              FROM shops s
                              LEFT JOIN properties p ON s.property_id = p.id
                              WHERE s.is_active = 1
                              ORDER BY s.shop_number');
        }

        return $this->db->resultSet();
    }

    public function createLeaseAgreement($data) {
        try {
            $this->db->beginTransaction();

            $lease_code = $this->generateLeaseCode();

            $this->db->query('INSERT INTO lease_agreements
                              (lease_code, shop_id, customer_id, lease_start_date, lease_end_date,
                               lease_duration_months, monthly_rent, service_charge,
                               security_deposit, deposit_paid, agreement_date, status,
                               auto_renew, terms_conditions, created_by)
                              VALUES (:lease_code, :shop_id, :customer_id, :lease_start_date, :lease_end_date,
                                      :lease_duration_months, :monthly_rent, :service_charge,
                                      :security_deposit, :deposit_paid, :agreement_date, :status,
                                      :auto_renew, :terms_conditions, :created_by)');

            $this->db->bind(':lease_code', $lease_code);
            $this->db->bind(':shop_id', $data['shop_id']);
            $this->db->bind(':customer_id', $data['customer_id']);
            $this->db->bind(':lease_start_date', $data['lease_start_date']);
            $this->db->bind(':lease_end_date', $data['lease_end_date']);
            $this->db->bind(':lease_duration_months', $data['lease_duration_months']);
            $this->db->bind(':monthly_rent', $data['monthly_rent']);
            $this->db->bind(':service_charge', $data['service_charge']);
            $this->db->bind(':security_deposit', isset($data['security_deposit']) ? $data['security_deposit'] : 0.00);
            $this->db->bind(':deposit_paid', isset($data['deposit_paid']) ? $data['deposit_paid'] : 0.00);
            $this->db->bind(':agreement_date', $data['agreement_date']);
            $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'draft');
            $this->db->bind(':auto_renew', isset($data['auto_renew']) ? $data['auto_renew'] : 0);
            $this->db->bind(':terms_conditions', isset($data['terms_conditions']) ? $data['terms_conditions'] : null);
            $this->db->bind(':created_by', $data['created_by']);

            $this->db->execute();
            $lease_id = $this->db->lastInsertId();

            if (isset($data['status']) && $data['status'] == 'active') {
                $this->db->query('UPDATE shops SET status = :status WHERE id = :shop_id');
                $this->db->bind(':status', 'occupied');
                $this->db->bind(':shop_id', $data['shop_id']);
                $this->db->execute();
            }

            $this->db->endTransaction();
            return $lease_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function getLease($lease_id) {
        $this->db->query('SELECT la.*, s.shop_number, s.shop_type, c.customer_name,
                          c.phone, c.email, u.full_name as created_by_name
                          FROM lease_agreements la
                          JOIN shops s ON la.shop_id = s.id
                          JOIN customers c ON la.customer_id = c.id
                          LEFT JOIN users u ON la.created_by = u.id
                          WHERE la.id = :lease_id');

        $this->db->bind(':lease_id', $lease_id);
        return $this->db->single();
    }

    public function getActiveLeaseByShop($shop_id) {
        $this->db->query('SELECT la.*, c.customer_name, c.phone
                          FROM lease_agreements la
                          JOIN customers c ON la.customer_id = c.id
                          WHERE la.shop_id = :shop_id
                          AND la.status = "active"
                          ORDER BY la.lease_end_date DESC
                          LIMIT 1');

        $this->db->bind(':shop_id', $shop_id);
        return $this->db->single();
    }

    public function getExpiringLeases($days = 90) {
        $future_date = date('Y-m-d', strtotime("+{$days} days"));

        $this->db->query('SELECT la.*, s.shop_number, c.customer_name, c.phone, c.email,
                          DATEDIFF(la.lease_end_date, CURDATE()) as days_to_expiry
                          FROM lease_agreements la
                          JOIN shops s ON la.shop_id = s.id
                          JOIN customers c ON la.customer_id = c.id
                          WHERE la.status = "active"
                          AND la.lease_end_date BETWEEN CURDATE() AND :future_date
                          ORDER BY la.lease_end_date');

        $this->db->bind(':future_date', $future_date);
        return $this->db->resultSet();
    }

    public function updateLeaseStatus($lease_id, $status) {
        try {
            $this->db->query('UPDATE lease_agreements SET status = :status WHERE id = :lease_id');
            $this->db->bind(':lease_id', $lease_id);
            $this->db->bind(':status', $status);

            if ($this->db->execute()) {
                if ($status == 'terminated' || $status == 'expired') {
                    $lease = $this->getLease($lease_id);
                    if ($lease) {
                        $this->db->query('UPDATE shops SET status = :status WHERE id = :shop_id');
                        $this->db->bind(':status', 'vacant');
                        $this->db->bind(':shop_id', $lease['shop_id']);
                        $this->db->execute();
                    }
                }
                return true;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    public function createRenewal($lease_id, $new_lease_data) {
        try {
            $this->db->beginTransaction();

            $old_lease = $this->getLease($lease_id);
            if (!$old_lease) {
                throw new Exception('Original lease not found');
            }

            $new_lease_id = $this->createLeaseAgreement($new_lease_data);
            if (!$new_lease_id) {
                throw new Exception('Failed to create new lease');
            }

            $this->db->query('INSERT INTO lease_renewals
                              (old_lease_id, new_lease_id, shop_id, customer_id,
                               expiry_date, renewal_status, renewed_date, days_to_expiry)
                              VALUES (:old_lease_id, :new_lease_id, :shop_id, :customer_id,
                                      :expiry_date, :renewal_status, :renewed_date, :days_to_expiry)');

            $this->db->bind(':old_lease_id', $lease_id);
            $this->db->bind(':new_lease_id', $new_lease_id);
            $this->db->bind(':shop_id', $old_lease['shop_id']);
            $this->db->bind(':customer_id', $old_lease['customer_id']);
            $this->db->bind(':expiry_date', $old_lease['lease_end_date']);
            $this->db->bind(':renewal_status', 'renewed');
            $this->db->bind(':renewed_date', date('Y-m-d'));
            $this->db->bind(':days_to_expiry', 0);

            $this->db->execute();

            $this->updateLeaseStatus($lease_id, 'renewed');

            $this->db->endTransaction();
            return $new_lease_id;

        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }

    public function getCustomerBalance($customer_id) {
        $this->db->query('SELECT account_balance FROM customers WHERE id = :customer_id');
        $this->db->bind(':customer_id', $customer_id);
        $result = $this->db->single();

        return $result ? $result['account_balance'] : 0.00;
    }

    public function updateCustomerBalance($customer_id, $amount, $transaction_type = 'debit') {
        try {
            if ($transaction_type == 'debit') {
                $this->db->query('UPDATE customers
                                  SET account_balance = account_balance + :amount
                                  WHERE id = :customer_id');
            } else {
                $this->db->query('UPDATE customers
                                  SET account_balance = account_balance - :amount
                                  WHERE id = :customer_id');
            }

            $this->db->bind(':customer_id', $customer_id);
            $this->db->bind(':amount', $amount);

            return $this->db->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    public function addCustomerLedgerEntry($data) {
        try {
            $current_balance = $this->getCustomerBalance($data['customer_id']);
            $new_balance = $current_balance + $data['debit_amount'] - $data['credit_amount'];

            $this->db->query('INSERT INTO customer_ledger
                              (transaction_date, customer_id, shop_id, transaction_type,
                               reference_type, reference_id, debit_amount, credit_amount,
                               balance, description)
                              VALUES (:transaction_date, :customer_id, :shop_id, :transaction_type,
                                      :reference_type, :reference_id, :debit_amount, :credit_amount,
                                      :balance, :description)');

            $this->db->bind(':transaction_date', $data['transaction_date']);
            $this->db->bind(':customer_id', $data['customer_id']);
            $this->db->bind(':shop_id', isset($data['shop_id']) ? $data['shop_id'] : null);
            $this->db->bind(':transaction_type', $data['transaction_type']);
            $this->db->bind(':reference_type', isset($data['reference_type']) ? $data['reference_type'] : null);
            $this->db->bind(':reference_id', isset($data['reference_id']) ? $data['reference_id'] : null);
            $this->db->bind(':debit_amount', $data['debit_amount']);
            $this->db->bind(':credit_amount', $data['credit_amount']);
            $this->db->bind(':balance', $new_balance);
            $this->db->bind(':description', isset($data['description']) ? $data['description'] : null);

            $this->db->execute();

            $this->db->query('UPDATE customers SET account_balance = :balance WHERE id = :customer_id');
            $this->db->bind(':balance', $new_balance);
            $this->db->bind(':customer_id', $data['customer_id']);
            $this->db->execute();

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            return false;
        }
    }

    public function getCustomerLedger($customer_id, $date_from = null, $date_to = null) {
        $sql = 'SELECT cl.*, s.shop_number
                FROM customer_ledger cl
                LEFT JOIN shops s ON cl.shop_id = s.id
                WHERE cl.customer_id = :customer_id';

        if ($date_from) {
            $sql .= ' AND cl.transaction_date >= :date_from';
        }
        if ($date_to) {
            $sql .= ' AND cl.transaction_date <= :date_to';
        }

        $sql .= ' ORDER BY cl.transaction_date, cl.id';

        $this->db->query($sql);
        $this->db->bind(':customer_id', $customer_id);

        if ($date_from) {
            $this->db->bind(':date_from', $date_from);
        }
        if ($date_to) {
            $this->db->bind(':date_to', $date_to);
        }

        return $this->db->resultSet();
    }

    private function generateCustomerCode() {
        $prefix = 'CUS' . date('Y');

        $this->db->query('SELECT customer_code FROM customers
                          WHERE customer_code LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['customer_code'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }

    private function generateShopCode() {
        $prefix = 'SHP';

        $this->db->query('SELECT shop_code FROM shops
                          WHERE shop_code LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['shop_code'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }

    private function generateLeaseCode() {
        $prefix = 'LSE' . date('Y');

        $this->db->query('SELECT lease_code FROM lease_agreements
                          WHERE lease_code LIKE :prefix
                          ORDER BY id DESC LIMIT 1');

        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();

        if ($result) {
            $last_number = intval(substr($result['lease_code'], -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}
?>
