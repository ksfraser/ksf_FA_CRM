-- fa_sales_commissions table
-- HRM + Sales linkage

CREATE TABLE IF NOT EXISTS `fa_sales_commissions` (
    `commission_id` INT(11) NOT NULL AUTO_INCREMENT,
    `salesperson_person_id` INT(11) NOT NULL COMMENT 'Employee (type=Employee)',
    `customer_person_id` INT(11) DEFAULT NULL,
    `invoice_id` INT(11) DEFAULT NULL,
    `commission_rate` DECIMAL(5,2) DEFAULT 0 COMMENT 'Percentage',
    `commission_amount` DECIMAL(15,2) DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'Pending' COMMENT 'Pending, Paid, Cancelled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`commission_id`),
    KEY `idx_salesperson` (`salesperson_person_id`),
    KEY `idx_customer` (`customer_person_id`),
    CONSTRAINT `fk_commission_salesperson` FOREIGN KEY (`salesperson_person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
