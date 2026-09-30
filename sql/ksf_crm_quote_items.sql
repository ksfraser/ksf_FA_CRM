-- 0_ksf_crm_quote_items
-- Table definition + pre-seed data for ksf_crm_quote_items.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Quote Items

CREATE TABLE IF NOT EXISTS `0_ksf_crm_quote_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `quote_id` INT(11) NOT NULL,
    `line_number` INT(11) DEFAULT 0,
    `item_description` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `unit` VARCHAR(20) DEFAULT NULL,
    `discount_percent` DECIMAL(5,2) DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `line_total` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_quote_id` (`quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

