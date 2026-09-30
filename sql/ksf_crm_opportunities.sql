-- 0_ksf_crm_opportunities
-- Table definition + pre-seed data for ksf_crm_opportunities.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ============================================================================
-- EXISTING CRM TABLES (unchanged from original ksf_CRM)
-- ============================================================================
-- CRM Opportunities table

CREATE TABLE IF NOT EXISTS `0_ksf_crm_opportunities` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `opportunity_name` VARCHAR(100) NOT NULL,
    `debtor_no` VARCHAR(20) DEFAULT NULL,
    `contact_id` INT(11) DEFAULT NULL,
    `sales_person` VARCHAR(100) DEFAULT NULL,
    `opportunity_type` VARCHAR(50) DEFAULT NULL,
    `realm` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'prospecting',
    `stage` VARCHAR(30) DEFAULT 'qualification',
    `source` VARCHAR(50) DEFAULT NULL,
    `estimated_value` DECIMAL(15,2) DEFAULT NULL,
    `probability` DECIMAL(5,2) DEFAULT 0,
    `expected_close_date` DATE DEFAULT NULL,
    `actual_close_date` DATE DEFAULT NULL,
    `lost_reason` TEXT,
    `won_notes` TEXT,
    `notes` TEXT,
    `assigned_to` VARCHAR(100) DEFAULT NULL,
    `lead_id` INT(11) DEFAULT NULL,
    `campaign_id` INT(11) DEFAULT NULL,
    `quote_id` INT(11) DEFAULT NULL,
    `project_id` INT(11) DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_lead_id` (`lead_id`),
    KEY `idx_realm` (`realm`),
    KEY `idx_status` (`status`),
    KEY `idx_stage` (`stage`),
    KEY `idx_expected_close` (`expected_close_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

