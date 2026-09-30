-- 0_ksf_crm_customer_types
-- Table definition + pre-seed data for ksf_crm_customer_types.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Customer Types
-- ============================================================================
-- Insert Initial Data
-- ============================================================================

CREATE TABLE IF NOT EXISTS `0_ksf_crm_customer_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `0_ksf_crm_customer_types` (`name`, `description`, `sort_order`) VALUES
('Prospect', 'Potential new customer', 1),
('Active', 'Current active customer', 2),
('Inactive', 'Former customer', 3),
('VIP', 'High-value customer', 4),
('Partner', 'Business partner', 5);

