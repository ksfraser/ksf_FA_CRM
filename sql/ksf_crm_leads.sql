-- 0_ksf_crm_leads
-- Table definition + pre-seed data for ksf_crm_leads.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Leads

CREATE TABLE IF NOT EXISTS `0_ksf_crm_leads` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `debtor_no` VARCHAR(20) NOT NULL,
    `lead_source` VARCHAR(50) DEFAULT NULL,
    `lead_status` VARCHAR(30) DEFAULT 'new',
    `rating` VARCHAR(30) DEFAULT NULL,
    `annual_revenue` DECIMAL(15,2) DEFAULT NULL,
    `employee_count` INT(11) DEFAULT NULL,
    `industry` VARCHAR(50) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT,
    `assigned_to` VARCHAR(100) DEFAULT NULL,
    `campaign_id` INT(11) DEFAULT NULL,
    `converted_date` DATETIME DEFAULT NULL,
    `converted_to_debtor_no` VARCHAR(20) DEFAULT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_lead_status` (`lead_status`),
    KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

