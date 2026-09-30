-- 0_ksf_crm_quotes
-- Table definition + pre-seed data for ksf_crm_quotes.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Quotes

CREATE TABLE IF NOT EXISTS `0_ksf_crm_quotes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `quote_no` VARCHAR(30) NOT NULL,
    `opportunity_id` INT(11) DEFAULT NULL,
    `debtor_no` VARCHAR(20) DEFAULT NULL,
    `contact_id` INT(11) DEFAULT NULL,
    `quote_date` DATE DEFAULT NULL,
    `valid_until` DATE DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'draft',
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `tax_rate` DECIMAL(5,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `total` DECIMAL(15,2) DEFAULT 0,
    `notes` TEXT,
    `terms` TEXT,
    `created_by` VARCHAR(100) DEFAULT NULL,
    `approved_by` VARCHAR(100) DEFAULT NULL,
    `approved_date` DATETIME DEFAULT NULL,
    `sent_date` DATETIME DEFAULT NULL,
    `accepted_date` DATETIME DEFAULT NULL,
    `rejected_date` DATETIME DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_quote_no` (`quote_no`),
    KEY `idx_opportunity_id` (`opportunity_id`),
    KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

