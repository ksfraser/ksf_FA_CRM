-- 0_ksf_crm_contacts
-- Table definition + pre-seed data for ksf_crm_contacts.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Contacts table

CREATE TABLE IF NOT EXISTS `0_ksf_crm_contacts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `debtor_no` VARCHAR(20) NOT NULL,
    `contact_role_id` INT(11) DEFAULT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `title` VARCHAR(50) DEFAULT NULL,
    `department` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `mobile` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT,
    `notes` TEXT,
    `is_primary` TINYINT(1) DEFAULT 0,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_is_primary` (`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

