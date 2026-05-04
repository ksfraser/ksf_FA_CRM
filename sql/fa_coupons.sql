-- fa_coupons table
-- CRM coupons

CREATE TABLE IF NOT EXISTS `fa_coupons` (
    `coupon_id` INT(11) NOT NULL AUTO_INCREMENT,
    `coupon_code` VARCHAR(30) NOT NULL,
    `discount_type` VARCHAR(20) DEFAULT 'Percentage' COMMENT 'Percentage or Fixed',
    `discount_value` DECIMAL(15,2) DEFAULT 0,
    `valid_from` DATE DEFAULT NULL,
    `valid_to` DATE DEFAULT NULL,
    `max_uses` INT(11) DEFAULT 0 COMMENT '0 = unlimited',
    `used_count` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`coupon_id`),
    UNIQUE KEY `idx_code` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
