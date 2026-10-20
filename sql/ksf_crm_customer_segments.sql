-- 0_ksf_crm_customer_segments
-- Table definition + pre-seed data for ksf_crm_customer_segments.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Customer segment catalogue.
--
-- A segment is a CRM-domain record (a label plus the criteria that puts a
-- customer in it), not a customer: the customers themselves live in FA core's
-- debtor record (0_debtors_master).

CREATE TABLE IF NOT EXISTS `0_ksf_crm_customer_segments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `segment_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `criteria` TEXT,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_inactive` (`inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
