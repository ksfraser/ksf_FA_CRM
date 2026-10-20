-- 0_ksf_crm_campaigns
-- Table definition + pre-seed data for ksf_crm_campaigns.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Campaign catalogue.
--
-- A campaign is a CRM-domain record; the contacts and opportunities that hang
-- off it live in the CRM's own tables and in FA core's contact registry.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_campaigns` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `campaign_name` VARCHAR(255) NOT NULL,
    `campaign_type` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(30) DEFAULT 'active',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `budget` DECIMAL(15,2) DEFAULT 0,
    `owner_id` VARCHAR(100) DEFAULT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
