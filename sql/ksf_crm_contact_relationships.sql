-- 0_ksf_crm_contact_relationships
-- Table definition + pre-seed data for ksf_crm_contact_relationships.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ============================================================================
-- CONTACT RELATIONSHIPS (person-to-person)
-- ============================================================================
-- Links two crm_persons with a relationship type.
-- Directed relationships (parent->child) use is_directed=1.
-- Undirected relationships (spouse) use is_directed=0.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_contact_relationships` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_a_id` INT(11) NOT NULL COMMENT 'FK to crm_persons.id',
    `person_b_id` INT(11) NOT NULL COMMENT 'FK to crm_persons.id',
    `relation_type` VARCHAR(30) NOT NULL COMMENT 'parent, child, spouse, sibling, etc.',
    `is_directed` TINYINT(1) DEFAULT 0 COMMENT '0=undirected (spouse), 1=directed (parent->child)',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_pair_type` (`person_a_id`, `person_b_id`, `relation_type`),
    KEY `idx_person_a` (`person_a_id`),
    KEY `idx_person_b` (`person_b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

