-- 0_ksf_crm_territories
-- Table definition + pre-seed data for ksf_crm_territories.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Territories

CREATE TABLE IF NOT EXISTS `0_ksf_crm_territories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `region` VARCHAR(50) DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `0_ksf_crm_territories` (`name`, `description`, `region`, `sort_order`) VALUES
('North', 'Northern region', 'North', 1),
('South', 'Southern region', 'South', 2),
('East', 'Eastern region', 'East', 3),
('West', 'Western region', 'West', 4),
('Central', 'Central region', 'Central', 5);

