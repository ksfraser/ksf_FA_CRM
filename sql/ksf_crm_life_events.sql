-- 0_ksf_crm_life_events
-- Table definition + pre-seed data for ksf_crm_life_events.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ============================================================================
-- LIFE EVENTS (GEDCOM-style events for persons)
-- ============================================================================
-- Stores birth, death, marriage, divorce, and custom business events.
-- details_json holds free-form structured data for any event type.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_life_events` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_id` INT(11) NOT NULL COMMENT 'FK to crm_persons.id',
    `event_type` VARCHAR(20) NOT NULL COMMENT 'BIRT, DEAT, MARR, DIV, EDUC, RETI, CUST',
    `event_date` DATE DEFAULT NULL,
    `event_place` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `gedcom_tag` VARCHAR(30) DEFAULT NULL COMMENT 'original GEDCOM tag',
    `details_json` TEXT COMMENT 'free-form structured data per event type',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_person` (`person_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

