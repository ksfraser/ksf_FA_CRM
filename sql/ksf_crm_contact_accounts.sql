-- 0_ksf_crm_contact_accounts
-- Table definition + pre-seed data for ksf_crm_contact_accounts.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Contact Accounts (cross-account contact assignments)

CREATE TABLE IF NOT EXISTS `0_ksf_crm_contact_accounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contact_id` INT(11) NOT NULL,
    `debtor_no` VARCHAR(20) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `role` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_contact_debtor` (`contact_id`, `debtor_no`),
    KEY `idx_debtor_no` (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

