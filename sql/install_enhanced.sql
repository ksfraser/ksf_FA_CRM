-- ============================================================================
-- FA_CRM Module Enhanced Installation SQL
-- ============================================================================
-- This extends FrontAccounting's built-in CRM system with:
-- 1. PII (Personally Identifiable Information) separated for GDPR compliance
-- 2. Banking details for contacts (ACH/PAD/refunds)
-- 3. Employment details (links to HRM)
-- 4. Emergency contacts
-- 5. Position, Grade, and Salary structures
-- 
-- Uses FA's built-in tables:
--   - 0_crm_persons (person details: name, address, phone, email)
--   - 0_crm_contacts (linking table: person_id + type + action + entity_id)
--   - 0_crm_categories (defines valid type/action combinations)
-- ============================================================================

-- --------------------------------------------------------
-- Table: `fa_contacts_pii` (PII separated for GDPR compliance)
-- Links to FA's 0_crm_persons table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_contacts_pii` (
    `person_id` INT(11) NOT NULL COMMENT 'FK to 0_crm_persons.id',
    `gender` INT(11) DEFAULT NULL COMMENT '0=Male, 1=Female, 2=Other',
    `birth_date` DATE DEFAULT NULL,
    `nationality` VARCHAR(50) DEFAULT NULL,
    `national_id` VARCHAR(50) DEFAULT NULL COMMENT 'SSN, SIN, etc.',
    `passport` VARCHAR(50) DEFAULT NULL,
    `passport_expiry` DATE DEFAULT NULL,
    `marital_status` INT(11) DEFAULT NULL COMMENT '0=Single, 1=Married, etc.',
    `dependents_no` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`person_id`),
    CONSTRAINT `fk_pii_person` FOREIGN KEY (`person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_contacts_banking` (Banking details for contacts)
-- Reusable for employees AND customers (ACH/PAD/refunds)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_contacts_banking` (
    `banking_id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_id` INT(11) NOT NULL COMMENT 'FK to 0_crm_persons.id',
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `bank_branch` VARCHAR(100) DEFAULT NULL,
    `account_number` VARCHAR(50) DEFAULT NULL,
    `routing_number` VARCHAR(50) DEFAULT NULL COMMENT 'ACH routing number',
    `account_type` VARCHAR(20) DEFAULT NULL COMMENT 'Checking, Savings, etc.',
    `is_primary` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`banking_id`),
    UNIQUE KEY `idx_person_primary` (`person_id`, `is_primary`),
    KEY `idx_person` (`person_id`),
    CONSTRAINT `fk_banking_person` FOREIGN KEY (`person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_contacts_employment` (Employment details)
-- Links person to department, position, grade
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_contacts_employment` (
    `employment_id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_id` INT(11) NOT NULL COMMENT 'FK to 0_crm_persons.id (type=Employee)',
    `employee_code` VARCHAR(20) DEFAULT NULL COMMENT 'e.g., EMP001',
    `department_id` INT(11) DEFAULT NULL,
    `position_id` INT(11) DEFAULT NULL,
    `grade_id` INT(11) DEFAULT NULL,
    `employment_type` INT(11) DEFAULT NULL COMMENT '1=Full-time, 2=Part-time, 3=Contract, 4=Temporary',
    `hire_date` DATE DEFAULT NULL,
    `probation_end_date` DATE DEFAULT NULL,
    `confirmation_date` DATE DEFAULT NULL,
    `termination_date` DATE DEFAULT NULL,
    `separation_reason_id` INT(11) DEFAULT NULL,
    `salary_amount` DECIMAL(15,2) DEFAULT 0 COMMENT 'Personal override (NULL = use structure)',
    `login_id` VARCHAR(100) DEFAULT NULL COMMENT 'Links to FA user',
    `reports_to_person_id` INT(11) DEFAULT NULL COMMENT 'Manager (another person)',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`employment_id`),
    UNIQUE KEY `idx_employee_code` (`employee_code`),
    UNIQUE KEY `idx_person` (`person_id`),
    KEY `idx_department` (`department_id`),
    KEY `idx_position` (`position_id`),
    KEY `idx_grade` (`grade_id`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_employment_person` FOREIGN KEY (`person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_employment_manager` FOREIGN KEY (`reports_to_person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- NOTE: Emergency Contacts and Dependents are just contacts (0_crm_persons)
-- linked via 0_crm_contacts with new categories (see below)
-- --------------------------------------------------------

-- --------------------------------------------------------
-- Table: `fa_dependent_details` (Additional info for dependents)
-- Links to 0_crm_persons.id + relationship for benefits
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_dependent_details` (
    `dependent_id` INT(11) NOT NULL AUTO_INCREMENT,
    `person_id` INT(11) NOT NULL COMMENT 'FK to 0_crm_persons.id (the dependent)',
    `employee_person_id` INT(11) NOT NULL COMMENT 'FK to 0_crm_persons.id (the employee)',
    `relationship` VARCHAR(50) DEFAULT NULL COMMENT 'Spouse, Child, Parent, etc.',
    `date_of_birth` DATE DEFAULT NULL,
    `gender` INT(11) DEFAULT NULL,
    `eligible_for_benefits` TINYINT(1) DEFAULT 1,
    `benefit_start_date` DATE DEFAULT NULL,
    `benefit_end_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`dependent_id`),
    UNIQUE KEY `idx_person` (`person_id`),
    KEY `idx_employee` (`employee_person_id`),
    CONSTRAINT `fk_dependent_person` FOREIGN KEY (`person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dependent_employee` FOREIGN KEY (`employee_person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_departments` (Enhanced departments)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_departments` (
    `department_id` INT(11) NOT NULL AUTO_INCREMENT,
    `department_code` VARCHAR(20) DEFAULT NULL,
    `department_name` VARCHAR(100) NOT NULL,
    `manager_person_id` INT(11) DEFAULT NULL COMMENT 'FK to 0_crm_persons (type=Employee)',
    `parent_department_id` INT(11) DEFAULT NULL,
    `cost_center_id` INT(11) DEFAULT NULL COMMENT 'Links to FA dimensions',
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`department_id`),
    KEY `idx_parent` (`parent_department_id`),
    KEY `idx_manager` (`manager_person_id`),
    CONSTRAINT `fk_dept_manager` FOREIGN KEY (`manager_person_id`) REFERENCES `0_crm_persons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_positions` (Job positions - shared by HRM + Recruitment)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_positions` (
    `position_id` INT(11) NOT NULL AUTO_INCREMENT,
    `position_name` VARCHAR(100) NOT NULL,
    `position_code` VARCHAR(20) DEFAULT NULL,
    `job_class_id` INT(11) DEFAULT NULL,
    `basic_amount` DECIMAL(15,2) DEFAULT 0 COMMENT 'Base salary for this position',
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`position_id`),
    KEY `idx_code` (`position_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_grades` (Salary grades with hierarchy)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_grades` (
    `grade_id` INT(11) NOT NULL AUTO_INCREMENT,
    `grade_name` VARCHAR(50) NOT NULL,
    `position_id` INT(11) DEFAULT NULL COMMENT 'Which position this grade belongs to',
    `grade_level` INT(11) DEFAULT 0 COMMENT '1, 2, 3...',
    `min_salary` DECIMAL(15,2) DEFAULT 0,
    `mid_salary` DECIMAL(15,2) DEFAULT 0,
    `max_salary` DECIMAL(15,2) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`grade_id`),
    KEY `idx_position` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_pay_elements` (Salary components)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_pay_elements` (
    `element_id` INT(11) NOT NULL AUTO_INCREMENT,
    `element_name` VARCHAR(100) NOT NULL,
    `element_code` VARCHAR(20) DEFAULT NULL,
    `is_deduction` TINYINT(1) DEFAULT 0 COMMENT '0=Earning, 1=Deduction',
    `is_taxable` TINYINT(1) DEFAULT 0,
    `affects_gross` TINYINT(1) DEFAULT 1,
    `account_code` VARCHAR(20) DEFAULT NULL COMMENT 'GL account',
    `element_category` INT(11) DEFAULT 0,
    `display_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`element_id`),
    KEY `idx_code` (`element_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_salary_structure` (Position × Grade × Element)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_salary_structure` (
    `structure_id` INT(11) NOT NULL AUTO_INCREMENT,
    `position_id` INT(11) NOT NULL,
    `grade_id` INT(11) NOT NULL,
    `element_id` INT(11) NOT NULL COMMENT 'Links to fa_pay_elements',
    `pay_amount` DECIMAL(15,2) DEFAULT 0,
    `formula` TEXT COMMENT 'For calculated fields',
    `effective_from` DATE DEFAULT NULL,
    `effective_to` DATE DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`structure_id`),
    KEY `idx_position_grade` (`position_id`, `grade_id`),
    KEY `idx_element` (`element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_separation_reasons` (Termination reasons)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fa_separation_reasons` (
    `reason_id` INT(11) NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `fa_sales_commissions` (HRM + Sales linkage)
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table: `fa_customer_loyalty` (CRM loyalty program)
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table: `fa_coupons` (CRM coupons)
-- --------------------------------------------------------
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

-- ============================================================================
-- Insert Default Data
-- ============================================================================

-- Insert default separation reasons
INSERT INTO `fa_separation_reasons` (`description`, `sort_order`) VALUES
('Resignation', 1),
('Termination', 2),
('Retirement', 3),
('Layoff', 4),
('End of Contract', 5),
('Death', 6),
('Other', 99);

-- Insert default pay elements (basic earnings/deductions)
INSERT INTO `fa_pay_elements` (`element_name`, `element_code`, `is_deduction`, `is_taxable`, `affects_gross`, `display_order`) VALUES
('Basic Salary', 'BASIC', 0, 1, 1, 10),
('House Rent Allowance', 'HRA', 0, 1, 1, 20),
('Transport Allowance', 'TA', 0, 1, 1, 30),
('Medical Allowance', 'MA', 0, 1, 1, 40),
('Performance Bonus', 'BONUS', 0, 1, 1, 50),
('Overtime Pay', 'OT', 0, 1, 1, 60),
('Income Tax', 'TAX', 1, 0, 0, 10),
('Social Security', 'SS', 1, 0, 0, 20),
('Health Insurance', 'HI', 1, 0, 0, 30),
('Retirement Fund', 'RF', 1, 0, 0, 40);

-- ============================================================================
-- Extend FA's crm_categories with new contact types
-- All contacts (employees, leads, emergency contacts, dependents) are persons
-- in 0_crm_persons, linked via 0_crm_contacts with these categories
-- ============================================================================

-- Add new contact types to crm_categories
-- type = entity type, action = purpose/role
INSERT IGNORE INTO `0_crm_categories` (`type`, `action`, `name`, `description`, `system`) VALUES
('employee', 'general', 'Employee', 'Employee contact record', 0),
('employee', 'emergency', 'Emergency Contact', 'Emergency contact for employee', 0),
('employee', 'dependent', 'Dependent', 'Employee dependent for benefits', 0),
('lead', 'general', 'Lead', 'Sales lead contact', 0),
('opportunity', 'general', 'Opportunity', 'Sales opportunity contact', 0);

-- ============================================================================
-- Usage Examples:
-- ============================================================================
-- 1. Employee: person_id=10, type='employee', action='general', entity_id='EMP001'
-- 2. Emergency Contact: person_id=11, type='employee', action='emergency', entity_id='EMP001'
--    (entity_id links back to the employee's person_id or employee_code)
-- 3. Dependent: person_id=12, type='employee', action='dependent', entity_id='EMP001'
--    (additional details in fa_dependent_details table for benefits)
-- 4. Lead: person_id=20, type='lead', action='general', entity_id=NULL (or lead_id)
-- 5. Opportunity: person_id=30, type='opportunity', action='general', entity_id=NULL
-- ============================================================================

-- ============================================================================
-- Notes:
-- ============================================================================
-- 1. FA's 0_crm_persons table stores: id, ref, name, name2, address, phone, phone2, fax, email, lang, notes, inactive
-- 2. FA's 0_crm_contacts table links persons to entities: person_id + type + action + entity_id
-- 3. All people (employees, leads, emergency contacts, dependents) are contacts in 0_crm_persons
-- 4. Use 0_crm_contacts to link them via type/action:
--    - Employee: type='employee', action='general', entity_id='employee_code'
--    - Emergency Contact: type='employee', action='emergency', entity_id='employee_person_id'
--    - Dependent: type='employee', action='dependent', entity_id='employee_person_id'
--    - Lead: type='lead', action='general', entity_id=NULL or lead_id
-- 5. All new tables (fa_* except crm_persons/contacts) link to 0_crm_persons via person_id
-- 6. Dependents use fa_dependent_details for benefit-specific info (DOB, eligibility, etc.)
-- ============================================================================
