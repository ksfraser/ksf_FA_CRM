<?php
/**
 * FA_CRM Module Hooks for FrontAccounting
 * Enhanced CRM with unified contact system
 */

define('SS_CRM', 116 << 8);

class hooks_fa_crm extends hooks {
    var $module_name = 'fa_crm';

    function install_options($app) {
        global $path_to_root;

        switch($app->id) {
            case 'CRM':
                $app->add_lapp_function(0, _("Customers"),
                    $path_to_root."/modules/".$this->module_name."/customers.php", 'SA_CRMVIEW', MENU_ENTRY);
                $app->add_lapp_function(1, _("Leads"),
                    $path_to_root."/modules/".$this->module_name."/leads.php", 'SA_CRMCREATE', MENU_ENTRY);
                $app->add_lapp_function(2, _("Opportunities"),
                    $path_to_root."/modules/".$this->module_name."/opportunities.php", 'SA_CRMEDIT', MENU_ENTRY);
                $app->add_rapp_function(3, _("CRM Reports"),
                    $path_to_root."/modules/".$this->module_name."/reports.php", 'SA_CRMVIEW', MENU_REPORT);
                $app->add_rapp_function(4, _("Loyalty Program"),
                    $path_to_root."/modules/".$this->module_name."/loyalty.php", 'SA_CRMMAINTENANCE', MENU_ENTRY);
                $app->add_rapp_function(5, _("Coupons"),
                    $path_to_root."/modules/".$this->module_name."/coupons.php", 'SA_CRMMAINTENANCE', MENU_ENTRY);
                break;
        }
    }

    function install_access() {
        $security_sections[SS_CRM] = _("CRM Management");
        $security_areas['SA_CRMVIEW'] = array(SS_CRM | 1, _("View CRM Records"));
        $security_areas['SA_CRMCREATE'] = array(SS_CRM | 2, _("Create CRM Records"));
        $security_areas['SA_CRMEDIT'] = array(SS_CRM | 3, _("Edit CRM Records"));
        $security_areas['SA_CRMMAINTENANCE'] = array(SS_CRM | 4, _("Manage CRM Maintenance"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        $updates = array('sql/update.sql' => array($this->module_name));
        $ok = $this->update_databases($company, $updates, $check_only);
        if ($check_only || !$ok) {
            return $ok;
        }
        $this->ensure_crm_schema();
        $this->ensure_crm_categories();
        return $ok;
    }

    private function table_exists($table) {
        $sql = "SHOW TABLES LIKE " . db_escape($table);
        $res = db_query($sql, 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    private function ensure_crm_schema() {
        $tables = array(
            TB_PREF . "fa_contacts_pii" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_contacts_pii` (
                    `person_id` INT(11) NOT NULL,
                    `gender` INT(11) DEFAULT NULL,
                    `birth_date` DATE DEFAULT NULL,
                    `nationality` VARCHAR(50) DEFAULT NULL,
                    `national_id` VARCHAR(50) DEFAULT NULL,
                    `passport` VARCHAR(50) DEFAULT NULL,
                    `passport_expiry` DATE DEFAULT NULL,
                    `marital_status` INT(11) DEFAULT NULL,
                    `dependents_no` INT(11) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`person_id`),
                    CONSTRAINT `fk_pii_person` FOREIGN KEY (`person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_contacts_banking" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_contacts_banking` (
                    `banking_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `person_id` INT(11) NOT NULL,
                    `bank_name` VARCHAR(100) DEFAULT NULL,
                    `bank_branch` VARCHAR(100) DEFAULT NULL,
                    `account_number` VARCHAR(50) DEFAULT NULL,
                    `routing_number` VARCHAR(50) DEFAULT NULL,
                    `account_type` VARCHAR(20) DEFAULT NULL,
                    `is_primary` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`banking_id`),
                    UNIQUE KEY `idx_person_primary` (`person_id`, `is_primary`),
                    KEY `idx_person` (`person_id`),
                    CONSTRAINT `fk_banking_person` FOREIGN KEY (`person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_contacts_employment" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_contacts_employment` (
                    `employment_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `person_id` INT(11) NOT NULL,
                    `employee_code` VARCHAR(20) DEFAULT NULL,
                    `department_id` INT(11) DEFAULT NULL,
                    `position_id` INT(11) DEFAULT NULL,
                    `grade_id` INT(11) DEFAULT NULL,
                    `employment_type` INT(11) DEFAULT NULL,
                    `hire_date` DATE DEFAULT NULL,
                    `probation_end_date` DATE DEFAULT NULL,
                    `confirmation_date` DATE DEFAULT NULL,
                    `termination_date` DATE DEFAULT NULL,
                    `separation_reason_id` INT(11) DEFAULT NULL,
                    `salary_amount` DECIMAL(15,2) DEFAULT 0,
                    `login_id` VARCHAR(100) DEFAULT NULL,
                    `reports_to_person_id` INT(11) DEFAULT NULL,
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
                    CONSTRAINT `fk_employment_person` FOREIGN KEY (`person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_employment_manager` FOREIGN KEY (`reports_to_person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_dependent_details" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_dependent_details` (
                    `dependent_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `person_id` INT(11) NOT NULL,
                    `employee_person_id` INT(11) NOT NULL,
                    `relationship` VARCHAR(50) DEFAULT NULL,
                    `date_of_birth` DATE DEFAULT NULL,
                    `gender` INT(11) DEFAULT NULL,
                    `eligible_for_benefits` TINYINT(1) DEFAULT 1,
                    `benefit_start_date` DATE DEFAULT NULL,
                    `benefit_end_date` DATE DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`dependent_id`),
                    UNIQUE KEY `idx_person` (`person_id`),
                    KEY `idx_employee` (`employee_person_id`),
                    CONSTRAINT `fk_dependent_person` FOREIGN KEY (`person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_dependent_employee` FOREIGN KEY (`employee_person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_departments" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_departments` (
                    `department_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `department_code` VARCHAR(20) DEFAULT NULL,
                    `department_name` VARCHAR(100) NOT NULL,
                    `manager_person_id` INT(11) DEFAULT NULL,
                    `parent_department_id` INT(11) DEFAULT NULL,
                    `cost_center_id` INT(11) DEFAULT NULL,
                    `description` TEXT,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`department_id`),
                    KEY `idx_parent` (`parent_department_id`),
                    KEY `idx_manager` (`manager_person_id`),
                    CONSTRAINT `fk_dept_manager` FOREIGN KEY (`manager_person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_positions" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_positions` (
                    `position_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `position_name` VARCHAR(100) NOT NULL,
                    `position_code` VARCHAR(20) DEFAULT NULL,
                    `job_class_id` INT(11) DEFAULT NULL,
                    `basic_amount` DECIMAL(15,2) DEFAULT 0,
                    `description` TEXT,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`position_id`),
                    KEY `idx_code` (`position_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_grades" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_grades` (
                    `grade_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `grade_name` VARCHAR(50) NOT NULL,
                    `position_id` INT(11) DEFAULT NULL,
                    `grade_level` INT(11) DEFAULT 0,
                    `min_salary` DECIMAL(15,2) DEFAULT 0,
                    `mid_salary` DECIMAL(15,2) DEFAULT 0,
                    `max_salary` DECIMAL(15,2) DEFAULT 0,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`grade_id`),
                    KEY `idx_position` (`position_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_pay_elements" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_pay_elements` (
                    `element_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `element_name` VARCHAR(100) NOT NULL,
                    `element_code` VARCHAR(20) DEFAULT NULL,
                    `is_deduction` TINYINT(1) DEFAULT 0,
                    `is_taxable` TINYINT(1) DEFAULT 0,
                    `affects_gross` TINYINT(1) DEFAULT 1,
                    `account_code` VARCHAR(20) DEFAULT NULL,
                    `element_category` INT(11) DEFAULT 0,
                    `display_order` INT(11) DEFAULT 0,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`element_id`),
                    KEY `idx_code` (`element_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_salary_structure" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_salary_structure` (
                    `structure_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `position_id` INT(11) NOT NULL,
                    `grade_id` INT(11) NOT NULL,
                    `element_id` INT(11) NOT NULL,
                    `pay_amount` DECIMAL(15,2) DEFAULT 0,
                    `formula` TEXT,
                    `effective_from` DATE DEFAULT NULL,
                    `effective_to` DATE DEFAULT NULL,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`structure_id`),
                    KEY `idx_position_grade` (`position_id`, `grade_id`),
                    KEY `idx_element` (`element_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_separation_reasons" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_separation_reasons` (
                    `reason_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `description` VARCHAR(255) NOT NULL,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `sort_order` INT(11) DEFAULT 0,
                    PRIMARY KEY (`reason_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_sales_commissions" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_sales_commissions` (
                    `commission_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `salesperson_person_id` INT(11) NOT NULL,
                    `customer_person_id` INT(11) DEFAULT NULL,
                    `invoice_id` INT(11) DEFAULT NULL,
                    `commission_rate` DECIMAL(5,2) DEFAULT 0,
                    `commission_amount` DECIMAL(15,2) DEFAULT 0,
                    `status` VARCHAR(20) DEFAULT 'Pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `paid_at` TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (`commission_id`),
                    KEY `idx_salesperson` (`salesperson_person_id`),
                    KEY `idx_customer` (`customer_person_id`),
                    CONSTRAINT `fk_commission_salesperson` FOREIGN KEY (`salesperson_person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_customer_loyalty" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_customer_loyalty` (
                    `loyalty_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `customer_person_id` INT(11) NOT NULL,
                    `points_balance` INT(11) DEFAULT 0,
                    `tier_level` VARCHAR(20) DEFAULT 'Bronze',
                    `enrollment_date` DATE DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`loyalty_id`),
                    UNIQUE KEY `idx_customer` (`customer_person_id`),
                    CONSTRAINT `fk_loyalty_customer` FOREIGN KEY (`customer_person_id`) REFERENCES `" . TB_PREF . "crm_persons`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_coupons" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_coupons` (
                    `coupon_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `coupon_code` VARCHAR(30) NOT NULL,
                    `discount_type` VARCHAR(20) DEFAULT 'Percentage',
                    `discount_value` DECIMAL(15,2) DEFAULT 0,
                    `valid_from` DATE DEFAULT NULL,
                    `valid_to` DATE DEFAULT NULL,
                    `max_uses` INT(11) DEFAULT 0,
                    `used_count` INT(11) DEFAULT 0,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`coupon_id`),
                    UNIQUE KEY `idx_code` (`coupon_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        foreach ($tables as $table_name => $sql) {
            db_query($sql, "Could not create CRM table: $table_name");
        }

        $this->insert_default_data();
    }

    private function ensure_crm_categories() {
        $categories = array(
            array('employee', 'general', 'Employee', 'Employee contact record', 0),
            array('employee', 'emergency', 'Emergency Contact', 'Emergency contact for employee', 0),
            array('employee', 'dependent', 'Dependent', 'Employee dependent for benefits', 0),
            array('lead', 'general', 'Lead', 'Sales lead contact', 0),
            array('opportunity', 'general', 'Opportunity', 'Sales opportunity contact', 0)
        );

        foreach ($categories as $cat) {
            $sql = "INSERT IGNORE INTO `" . TB_PREF . "crm_categories` (`type`, `action`, `name`, `description`, `system`) VALUES (" .
                db_escape($cat[0]) . ", " . db_escape($cat[1]) . ", " . db_escape($cat[2]) . ", " . db_escape($cat[3]) . ", " . $cat[4] . ")";
            db_query($sql, "Failed to insert CRM category");
        }
    }

    private function insert_default_data() {
        // Insert default separation reasons
        $reasons = array('Resignation', 'Termination', 'Retirement', 'Layoff', 'End of Contract', 'Death', 'Other');
        $sort = 1;
        foreach ($reasons as $reason) {
            $sql = "INSERT IGNORE INTO `" . TB_PREF . "fa_separation_reasons` (`description`, `sort_order`) VALUES (" .
                db_escape($reason) . ", " . $sort . ")";
            db_query($sql, "Failed to insert separation reason");
            $sort++;
        }

        // Insert default pay elements
        $elements = array(
            array('Basic Salary', 'BASIC', 0, 1, 1, 10),
            array('House Rent Allowance', 'HRA', 0, 1, 1, 20),
            array('Transport Allowance', 'TA', 0, 1, 1, 30),
            array('Medical Allowance', 'MA', 0, 1, 1, 40),
            array('Performance Bonus', 'BONUS', 0, 1, 1, 50),
            array('Overtime Pay', 'OT', 0, 1, 1, 60),
            array('Income Tax', 'TAX', 1, 0, 0, 10),
            array('Social Security', 'SS', 1, 0, 0, 20),
            array('Health Insurance', 'HI', 1, 0, 0, 30),
            array('Retirement Fund', 'RF', 1, 0, 0, 40)
        );

        foreach ($elements as $elem) {
            $sql = "INSERT IGNORE INTO `" . TB_PREF . "fa_pay_elements` (`element_name`, `element_code`, `is_deduction`, `is_taxable`, `affects_gross`, `display_order`) VALUES (" .
                db_escape($elem[0]) . ", " . db_escape($elem[1]) . ", " . $elem[2] . ", " . $elem[3] . ", " . $elem[4] . ", " . $elem[5] . ")";
            db_query($sql, "Failed to insert pay element");
        }
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if CRM module tracks financial transactions
    }
}
?>
