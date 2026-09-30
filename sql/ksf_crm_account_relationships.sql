-- 0_ksf_crm_account_relationships
-- Table definition + pre-seed data for ksf_crm_account_relationships.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- ============================================================================
-- ACCOUNT RELATIONSHIPS (account-to-account, nested entities)
-- ============================================================================
-- Links two debtor accounts (debtors_master) for ownership/subsidiary hierarchies.
-- Examples: Trust -> HoldCo -> OpCo, Parent Company -> Subsidiary

CREATE TABLE IF NOT EXISTS `0_ksf_crm_account_relationships` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `parent_debtor_no` VARCHAR(20) NOT NULL COMMENT 'FK to debtors_master.debtor_no',
    `child_debtor_no` VARCHAR(20) NOT NULL COMMENT 'FK to debtors_master.debtor_no',
    `relation_type` VARCHAR(30) NOT NULL COMMENT 'owns, subsidiary, trustee_of, beneficiary_of',
    `ownership_pct` DECIMAL(5,2) DEFAULT NULL COMMENT 'ownership percentage',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_parent_child_type` (`parent_debtor_no`, `child_debtor_no`, `relation_type`),
    KEY `idx_parent` (`parent_debtor_no`),
    KEY `idx_child` (`child_debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

