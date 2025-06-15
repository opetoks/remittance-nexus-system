
const db = require('../config/mysql_db');

// Get all shops with their current allocation status
exports.getAllShops = (req, res) => {
  const query = `
    SELECT s.*, 
           sa.customer_name, sa.start_date, sa.end_date, sa.monthly_rent, sa.service_charge,
           sa.status as allocation_status,
           CASE 
             WHEN sa.end_date < CURDATE() THEN 'Expired'
             WHEN sa.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
             ELSE 'Active'
           END as rental_status
    FROM shops s
    LEFT JOIN shop_allocations sa ON s.shop_id = sa.shop_id AND sa.status = 'active'
    ORDER BY s.shop_number ASC
  `;

  db.query(query, (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
};

// Allocate a shop to a customer
exports.allocateShop = (req, res) => {
  const {
    shop_id, customer_name, start_date, end_date, 
    monthly_rent, service_charge, allocated_by_id, allocated_by_name
  } = req.body;

  db.beginTransaction((err) => {
    if (err) return res.status(500).json({ error: err.message });

    // First, deactivate any existing allocation for this shop
    const deactivateQuery = 'UPDATE shop_allocations SET status = "inactive" WHERE shop_id = ? AND status = "active"';
    
    db.query(deactivateQuery, [shop_id], (err, result) => {
      if (err) {
        return db.rollback(() => {
          res.status(500).json({ error: err.message });
        });
      }

      // Create new allocation
      const allocateQuery = `
        INSERT INTO shop_allocations 
        (shop_id, customer_name, start_date, end_date, monthly_rent, service_charge, 
         allocated_by_id, allocated_by_name, allocation_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
      `;

      db.query(allocateQuery, [
        shop_id, customer_name, start_date, end_date, 
        monthly_rent, service_charge, allocated_by_id, allocated_by_name
      ], (err, result) => {
        if (err) {
          return db.rollback(() => {
            res.status(500).json({ error: err.message });
          });
        }

        // Update shop status
        const updateShopQuery = 'UPDATE shops SET status = "occupied" WHERE shop_id = ?';
        
        db.query(updateShopQuery, [shop_id], (err, result) => {
          if (err) {
            return db.rollback(() => {
              res.status(500).json({ error: err.message });
            });
          }

          db.commit((err) => {
            if (err) {
              return db.rollback(() => {
                res.status(500).json({ error: err.message });
              });
            }
            res.status(201).json({ message: 'Shop allocated successfully' });
          });
        });
      });
    });
  });
};

// Renew rent for a shop
exports.renewRent = (req, res) => {
  const { allocation_id } = req.params;
  const { new_end_date, new_monthly_rent, renewed_by_id, renewed_by_name } = req.body;

  const query = `
    UPDATE shop_allocations 
    SET end_date = ?, monthly_rent = ?, renewed_by_id = ?, renewed_by_name = ?, renewal_date = NOW()
    WHERE id = ? AND status = 'active'
  `;

  db.query(query, [new_end_date, new_monthly_rent, renewed_by_id, renewed_by_name, allocation_id], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Allocation not found or inactive' });
    }
    
    res.json({ message: 'Rent renewed successfully' });
  });
};

// Update service charge (handles advance payment adjustments)
exports.updateServiceCharge = (req, res) => {
  const { allocation_id } = req.params;
  const { new_service_charge, effective_date, updated_by_id, updated_by_name } = req.body;

  db.beginTransaction((err) => {
    if (err) return res.status(500).json({ error: err.message });

    // Get current allocation details
    const getAllocationQuery = `
      SELECT * FROM shop_allocations 
      WHERE id = ? AND status = 'active'
    `;

    db.query(getAllocationQuery, [allocation_id], (err, allocations) => {
      if (err) {
        return db.rollback(() => {
          res.status(500).json({ error: err.message });
        });
      }

      if (allocations.length === 0) {
        return db.rollback(() => {
          res.status(404).json({ error: 'Allocation not found' });
        });
      }

      const allocation = allocations[0];
      const oldServiceCharge = allocation.service_charge;

      // Check for advance payments
      const advancePaymentQuery = `
        SELECT SUM(amount_paid) as total_advance
        FROM account_general_transaction_new
        WHERE customer_name = ? AND income_line LIKE '%Service Charge%' 
        AND date_of_payment > end_date AND verification_status = 'verified'
      `;

      db.query(advancePaymentQuery, [allocation.customer_name], (err, advanceResults) => {
        if (err) {
          return db.rollback(() => {
            res.status(500).json({ error: err.message });
          });
        }

        const advanceAmount = advanceResults[0].total_advance || 0;
        
        // Update service charge
        const updateQuery = `
          UPDATE shop_allocations 
          SET service_charge = ?, service_charge_updated_date = ?, 
              updated_by_id = ?, updated_by_name = ?
          WHERE id = ?
        `;

        db.query(updateQuery, [new_service_charge, effective_date, updated_by_id, updated_by_name, allocation_id], (err, result) => {
          if (err) {
            return db.rollback(() => {
              res.status(500).json({ error: err.message });
            });
          }

          // If there are advance payments, calculate adjustment
          if (advanceAmount > 0) {
            const serviceDifference = new_service_charge - oldServiceCharge;
            const monthsFromEffectiveDate = Math.ceil((new Date(allocation.end_date) - new Date(effective_date)) / (30 * 24 * 60 * 60 * 1000));
            const adjustmentAmount = serviceDifference * monthsFromEffectiveDate;

            // Record the adjustment transaction
            const adjustmentQuery = `
              INSERT INTO service_charge_adjustments
              (allocation_id, customer_name, old_service_charge, new_service_charge, 
               effective_date, months_affected, adjustment_amount, created_by_id, created_by_name, created_date)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            `;

            db.query(adjustmentQuery, [
              allocation_id, allocation.customer_name, oldServiceCharge, new_service_charge,
              effective_date, monthsFromEffectiveDate, adjustmentAmount, updated_by_id, updated_by_name
            ], (err, result) => {
              if (err) {
                return db.rollback(() => {
                  res.status(500).json({ error: err.message });
                });
              }

              db.commit((err) => {
                if (err) {
                  return db.rollback(() => {
                    res.status(500).json({ error: err.message });
                  });
                }
                res.json({ 
                  message: 'Service charge updated successfully',
                  adjustment_amount: adjustmentAmount,
                  months_affected: monthsFromEffectiveDate
                });
              });
            });
          } else {
            db.commit((err) => {
              if (err) {
                return db.rollback(() => {
                  res.status(500).json({ error: err.message });
                });
              }
              res.json({ message: 'Service charge updated successfully' });
            });
          }
        });
      });
    });
  });
};

// Get expiring shops
exports.getExpiringShops = (req, res) => {
  const { days = 30 } = req.query;
  
  const query = `
    SELECT s.shop_number, sa.customer_name, sa.end_date, sa.monthly_rent, sa.service_charge
    FROM shop_allocations sa
    JOIN shops s ON sa.shop_id = s.shop_id
    WHERE sa.status = 'active' 
    AND sa.end_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    AND sa.end_date >= CURDATE()
    ORDER BY sa.end_date ASC
  `;

  db.query(query, [days], (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
};

// Get advance payment details for a customer
exports.getAdvancePayments = (req, res) => {
  const { customer_name } = req.params;
  
  const query = `
    SELECT t.*, sa.end_date as current_end_date
    FROM account_general_transaction_new t
    LEFT JOIN shop_allocations sa ON t.customer_name = sa.customer_name AND sa.status = 'active'
    WHERE t.customer_name = ? 
    AND t.income_line LIKE '%Service Charge%'
    AND t.date_of_payment > COALESCE(sa.end_date, CURDATE())
    AND t.verification_status = 'verified'
    ORDER BY t.date_of_payment DESC
  `;

  db.query(query, [customer_name], (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
};
