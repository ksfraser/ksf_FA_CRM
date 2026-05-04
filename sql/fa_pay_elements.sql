-- fa_pay_elements table
-- Salary components (earnings/deductions)

CREATE TABLE IF NOT EXISTS `fa_pay_elements` (
    `element_id` INT(11) NOT NULL AUTO_INCREMENT,
    `element_name` VARCHAR(100) NOT NULL,
    `element_code` VARCHAR(20) DEFAULT NULL,
    `is_deduction` TINYINT(1) DEFAULT 0 COMMENT '0=Earning, 1=Deduction',
    `is_taxable` TINYINT(1) DEFAULT 0,
    `affects_gross` TINYINT(1) DEFAULT 1,
    `account_code` VARCHAR(20) DEFAULT NULL COMMENT 'GL account',
    `element_category` INT(11) DEFAULT 0,
    `display_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`element_id`),
    KEY `idx_code` (`element_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
