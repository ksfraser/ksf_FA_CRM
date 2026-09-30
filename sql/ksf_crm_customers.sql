-- 0_ksf_crm_customers
-- Table definition + pre-seed data for ksf_crm_customers.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ============================================================================
-- ksf_FA_CRM Module Installation SQL
-- ============================================================================
-- Uses 0_ prefix for table names (FA convention for install.sql)
-- ============================================================================
-- ============================================================================
-- CORE CRM TABLES (migrated from ksf_CRM)
-- ============================================================================
-- CRM Customers table (extends FA debtors)

CREATE TABLE IF NOT EXISTS `0_ksf_crm_customers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `debtor_no` VARCHAR(20) NOT NULL,
    `customer_type_id` INT(11) DEFAULT NULL,
    `customer_segment_id` INT(11) DEFAULT NULL,
    `territory_id` INT(11) DEFAULT NULL,
    `customer_since` DATE DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `industry` VARCHAR(100) DEFAULT NULL,
    `employee_count` INT(11) DEFAULT NULL,
    `annual_revenue` DECIMAL(15,2) DEFAULT NULL,
    `parent_company` VARCHAR(100) DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `edi_enabled` TINYINT(1) DEFAULT 0,
    `marketing_opt_out` TINYINT(1) DEFAULT 0,
    `preferred_contact_method` VARCHAR(20) DEFAULT 'email',
    `last_contact_date` DATETIME DEFAULT NULL,
    `next_followup_date` DATETIME DEFAULT NULL,
    `account_manager` VARCHAR(100) DEFAULT NULL,
    `credit_rating` VARCHAR(20) DEFAULT 'good',
    `payment_reliability` DECIMAL(5,2) DEFAULT 100.00,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_customer_type` (`customer_type_id`),
    KEY `idx_territory` (`territory_id`),
    KEY `idx_inactive` (`inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

