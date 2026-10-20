-- 0_ksf_crm_customer_analytics
-- Table definition + pre-seed data for ksf_crm_customer_analytics.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Per-customer analytics snapshot.
--
-- The snapshot is a CRM-domain record; the customer it belongs to is FA
-- core's debtor record (0_debtors_master) and is referenced here by its
-- debtor_no - there is no foreign key to a FA core table.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_customer_analytics` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` VARCHAR(20) NOT NULL COMMENT 'FA debtor_no',
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `total_sales` DECIMAL(15,2) DEFAULT 0,
    `total_payments` DECIMAL(15,2) DEFAULT 0,
    `outstanding_balance` DECIMAL(15,2) DEFAULT 0,
    `payment_days_avg` INT(11) DEFAULT 0,
    `customer_lifetime_value` DECIMAL(15,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_customer_period` (`customer_id`, `period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
