-- 0_ksf_crm_edi_config
-- Table definition + pre-seed data for ksf_crm_edi_config.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Per-customer EDI configuration.
--
-- The configuration is a CRM-domain record; the customer it belongs to is
-- FA core's debtor record (0_debtors_master) and is referenced here by its
-- debtor_no - there is no foreign key to a FA core table.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_edi_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` VARCHAR(20) NOT NULL COMMENT 'FA debtor_no',
    `edi_type` VARCHAR(30) DEFAULT NULL,
    `edi_code` VARCHAR(50) DEFAULT NULL,
    `ftp_host` VARCHAR(255) DEFAULT NULL,
    `ftp_username` VARCHAR(100) DEFAULT NULL,
    `ftp_password` VARCHAR(255) DEFAULT NULL,
    `email_address` VARCHAR(255) DEFAULT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
