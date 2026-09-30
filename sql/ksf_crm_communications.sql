-- 0_ksf_crm_communications
-- Table definition + pre-seed data for ksf_crm_communications.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- CRM Communications table

CREATE TABLE IF NOT EXISTS `0_ksf_crm_communications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `debtor_no` VARCHAR(20) DEFAULT NULL,
    `contact_id` INT(11) DEFAULT NULL,
    `opportunity_id` INT(11) DEFAULT NULL,
    `communication_type` VARCHAR(20) NOT NULL,
    `direction` VARCHAR(10) DEFAULT 'outbound',
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT,
    `email_from` VARCHAR(100) DEFAULT NULL,
    `email_to` VARCHAR(100) DEFAULT NULL,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `duration_minutes` INT(11) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'completed',
    `scheduled_date` DATETIME DEFAULT NULL,
    `completed_date` DATETIME DEFAULT NULL,
    `assigned_to` VARCHAR(100) DEFAULT NULL,
    `priority` VARCHAR(10) DEFAULT 'medium',
    `follow_up_required` TINYINT(1) DEFAULT 0,
    `follow_up_date` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `email_message_id` VARCHAR(255) DEFAULT NULL,
    `attachment_path` VARCHAR(500) DEFAULT NULL,
    `created_by` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_follow_up` (`follow_up_required`, `follow_up_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

