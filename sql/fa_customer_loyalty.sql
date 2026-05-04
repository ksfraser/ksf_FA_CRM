-- fa_customer_loyalty table
-- CRM loyalty program

CREATE TABLE IF NOT EXISTS `fa_customer_loyalty` (
    `loyalty_id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_person_id` INT(11) NOT NULL COMMENT 'FK to 0_crm_persons',
    `points_balance` INT(11) DEFAULT 0,
    `tier_level` VARCHAR(20) DEFAULT 'Bronze',
    `enrollment_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`loyalty_id`),
    UNIQUE KEY `idx_customer` (`customer_person_id`),
    CONSTRAINT `fk_loyalty_customer` FOREIGN KEY (`customer_person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
