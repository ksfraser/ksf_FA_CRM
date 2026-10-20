-- 0_ksf_crm_meeting_rooms
-- Table definition + pre-seed data for ksf_crm_meeting_rooms.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Meeting rooms.
--
-- A room is a CRM-domain record; the people who attend a meeting live in
-- FA core's contact registry (0_crm_persons / 0_crm_contacts).

CREATE TABLE IF NOT EXISTS `0_ksf_crm_meeting_rooms` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `room_name` VARCHAR(100) NOT NULL,
    `room_type` VARCHAR(50) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `capacity` INT(11) DEFAULT 0,
    `equipment` TEXT,
    `phone_number` VARCHAR(30) DEFAULT NULL,
    `conference_url` VARCHAR(255) DEFAULT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
