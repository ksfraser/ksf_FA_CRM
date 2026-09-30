-- 0_ksf_crm_realms
-- Table definition + pre-seed data for ksf_crm_realms.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Realms (opportunity realms/categories)

CREATE TABLE IF NOT EXISTS `0_ksf_crm_realms` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `requires_quote` TINYINT(1) DEFAULT 0,
    `requires_project` TINYINT(1) DEFAULT 0,
    `default_stage` VARCHAR(30) DEFAULT 'qualification',
    `stages_json` TEXT,
    `inactive` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

