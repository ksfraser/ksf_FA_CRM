-- 0_ksf_crm_opportunity_stages
-- Table definition + pre-seed data for ksf_crm_opportunity_stages.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Opportunity stage catalogue.
--
-- A stage is a CRM-domain record (a label), not a customer or a contact.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_opportunity_stages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stage_name` VARCHAR(100) NOT NULL,
    `probability` DECIMAL(5,2) DEFAULT 0,
    `description` TEXT,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_inactive` (`inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
