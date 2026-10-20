-- 0_ksf_crm_contact_roles
-- Table definition + pre-seed data for ksf_crm_contact_roles.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

-- Contact role catalogue.
--
-- A role is a CRM-domain record (a label with optional default contact
-- details), not a person: the people themselves live in FA core's contact
-- registry (0_crm_persons / 0_crm_contacts).

CREATE TABLE IF NOT EXISTS `0_ksf_crm_contact_roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `default_phone` VARCHAR(30) DEFAULT NULL,
    `default_email` VARCHAR(100) DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_inactive` (`inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
