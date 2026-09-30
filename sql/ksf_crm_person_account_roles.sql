-- 0_ksf_crm_person_account_roles
-- Table definition + pre-seed data for ksf_crm_person_account_roles.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ============================================================================
-- PERSON-ACCOUNT ROLES (person-to-account)
-- ============================================================================
-- Links a person to an account with a specific role.
-- Examples: director, beneficiary, trustee, employee, owner, signatory

CREATE TABLE IF NOT EXISTS `0_ksf_crm_person_account_roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_id` INT(11) NOT NULL COMMENT 'FK to crm_persons.id',
    `debtor_no` VARCHAR(20) NOT NULL COMMENT 'FK to debtors_master.debtor_no',
    `role` VARCHAR(30) NOT NULL COMMENT 'director, beneficiary, trustee, employee, owner',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_person_account_role` (`person_id`, `debtor_no`, `role`),
    KEY `idx_person` (`person_id`),
    KEY `idx_account` (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

