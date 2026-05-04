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
        // FA's update_databases handles multiple SQL files automatically
        // Files are processed in order: table creation, then data
        $updates = array(
            'sql/fa_contacts_pii.sql' => array($this->module_name),
            'sql/fa_contacts_banking.sql' => array($this->module_name),
            'sql/fa_contacts_employment.sql' => array($this->module_name),
            'sql/fa_dependent_details.sql' => array($this->module_name),
            'sql/fa_departments.sql' => array($this->module_name),
            'sql/fa_positions.sql' => array($this->module_name),
            'sql/fa_grades.sql' => array($this->module_name),
            'sql/fa_pay_elements.sql' => array($this->module_name),
            'sql/fa_salary_structure.sql' => array($this->module_name),
            'sql/fa_separation_reasons.sql' => array($this->module_name),
            'sql/fa_sales_commissions.sql' => array($this->module_name),
            'sql/fa_customer_loyalty.sql' => array($this->module_name),
            'sql/fa_coupons.sql' => array($this->module_name),
            'sql/crm_categories.sql' => array($this->module_name)
        );
        return $this->update_databases($company, $updates, $check_only);
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if CRM module tracks financial transactions
    }
}
?>
