<?php

declare(strict_types=1);

/**
 * ksf_FA_CRM Module Hooks for FrontAccounting
 *
 * CRM adapter: app tab, security areas, DB installation, workflow hooks,
 * and reference-data DDL hooks (CustomerType, Territory, Realm).
 *
 * @package ksf_FA_CRM
 * @version 1.0.0
 */

// Load ksf_FA_Common's ComposerDependencies utility.
$composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
if (file_exists($composerDepsPath)) {
    require_once $composerDepsPath;
    \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
}

define('SS_CRM', 114 << 8);

require_once dirname(__FILE__) . '/includes/crm_tags.inc';

class hooks_ksf_FA_CRM extends hooks {

    var $module_name = 'ksf_FA_CRM';
    var $version = '1.0.0';

    /**
     * Install the CRM application tab in FA sidebar.
     *
     * @param application $app FA application instance
     */
    function install_tabs($app) {
        set_ext_domain('modules/ksf_FA_CRM');
        $app->add_application(new crm_app());
        set_ext_domain();
    }

    /**
     * Define security areas and sections.
     *
     * @return array [0] => $security_areas, [1] => $security_sections
     */
    function install_access() {
        $security_sections[SS_CRM] = _("CRM Management");

        $security_areas['SA_CRM_DASHBOARD'] = array(SS_CRM | 1, _("CRM Dashboard"));
        $security_areas['SA_CRM_CUSTOMER'] = array(SS_CRM | 2, _("CRM Customers"));
        $security_areas['SA_CRM_OPPORTUNITY'] = array(SS_CRM | 3, _("CRM Opportunities"));
        $security_areas['SA_CRM_COMMUNICATION'] = array(SS_CRM | 4, _("CRM Communications"));
        $security_areas['SA_CRM_SETUP'] = array(SS_CRM | 5, _("CRM Setup"));
        $security_areas['SA_CUSTOMER_TYPE'] = array(SS_CRM | 6, _("Customer Types"));
        $security_areas['SA_TERRITORY'] = array(SS_CRM | 7, _("Territories"));
        $security_areas['SA_CRM_LEAD'] = array(SS_CRM | 8, _("CRM Leads"));
        $security_areas['SA_CRM_QUOTE'] = array(SS_CRM | 9, _("CRM Quotes"));
        $security_areas['SA_CRM_REALM'] = array(SS_CRM | 10, _("CRM Realms"));
        $security_areas['SA_CRM_MEETING'] = array(SS_CRM | 11, _("CRM Meetings"));
        $security_areas['SA_CRM_EMAIL_ACCOUNT'] = array(SS_CRM | 12, _("CRM Email Accounts"));
        $security_areas['SA_CRM_TAGS'] = array(SS_CRM | 13, _("CRM Tags"));
        $security_areas['SA_CRM_GEDCOM'] = array(SS_CRM | 14, _("GEDCOM Import/Export"));
        $security_areas['SA_CRM_ORG_CHART'] = array(SS_CRM | 15, _("Organization Chart"));
        $security_areas['SA_CRM_LIFE_EVENTS'] = array(SS_CRM | 16, _("Life Events"));
        $security_areas['SA_CRM_PERSON_ACCOUNT_ROLES'] = array(SS_CRM | 17, _("Person Account Roles"));
        $security_areas['SA_CRM_ACCOUNT_RELATIONSHIPS'] = array(SS_CRM | 18, _("Account Relationships"));
        $security_areas['SA_CRM_CONTACT_RELATIONSHIPS'] = array(SS_CRM | 19, _("Contact Relationships"));
        $security_areas['SA_CRM_REPORTS'] = array(SS_CRM | 20, _("CRM Reports"));

        return array($security_areas, $security_sections);
    }

    /**
     * Advertise module capabilities for other modules (RBAC, Calendar, etc.).
     *
     * @return array Namespaced key-value pairs
     */
    protected function _getAdvertisedValues()
    {
        return array(
            'crm.hooks_version' => '1.0',
            'crm.module_version' => '1.0.0',
            'crm.tag_types' => array(TAG_CUSTOMER, TAG_CONTACT, TAG_OPPORTUNITY, TAG_LEAD, TAG_COMMUNICATION),
            'crm.features' => array('customers', 'contacts', 'opportunities', 'communications', 'leads', 'quotes', 'tags', 'calendar'),
        );
    }

    /**
     * Activate extension — runs SQL installation.
     *
     * @param int $company Company number
     * @param bool $check_only Only check if activation possible
     * @return bool Success
     */
    function activate_extension($company, $check_only=true) {
        $this->ensure_composer_dependencies();
        $updates = array(
            'install.sql'             => array('fa_crm_customers'),
            'retag_contact_types.sql' => array('ksf_contact_types'),
        );
        $ok = $this->update_databases($company, $updates, $check_only);

        if (!$check_only && $ok) {
            $this->register_contact_types();
        }

        return $ok;
    }

    /**
     * Register the contact types owned by this module (idempotent).
     */
    private function register_contact_types() {
        $autoload = dirname(__FILE__) . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        if (!class_exists('\\ksfraser\\FrontAccounting\\Common\\ContactType\\ContactTypeRegistry')) {
            return;
        }

        \ksfraser\FrontAccounting\Common\ContactType\ContactTypeRegistry::registerTypes(array(
            new \ksfraser\FrontAccounting\Common\ContactType\ContactType(
                'crm_contact', 'CRM Contact', $this->module_name,
                'Customer or contact managed by the CRM module'
            ),
            new \ksfraser\FrontAccounting\Common\ContactType\ContactType(
                'lead', 'CRM Lead', $this->module_name,
                'Sales lead tracked by the CRM module'
            ),
            new \ksfraser\FrontAccounting\Common\ContactType\ContactType(
                'opportunity', 'CRM Opportunity', $this->module_name,
                'Sales opportunity tracked by the CRM module'
            ),
        ));
    }

    function deactivate_extension($company, $check_only=true) {
        if (!$check_only
            && class_exists('\\ksfraser\\FrontAccounting\\Common\\ContactType\\ContactTypeRegistry')) {
            \ksfraser\FrontAccounting\Common\ContactType\ContactTypeRegistry::unregisterModule($this->module_name);
        }

        return true;
    }

    /**
     * Install composer dependencies if vendor/ is missing.
     */
    private function ensure_composer_dependencies() {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';

        if (file_exists($autoload_path)) {
            return;
        }

        $composer_path = $module_dir . '/composer.json';
        if (!file_exists($composer_path)) {
            return;
        }

        chdir($module_dir);
        $output = array();
        $return_code = 0;
        exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            error_log('ksf_FA_CRM: composer install failed: ' . implode("\n", $output));
        }
    }

    // ─── Customer Type Hooks ───────────────────────────────────────

    function getCustomerTypes(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\CustomerTypeService();
        return $service->hookGetCustomerTypes($data, $opts);
    }

    function getCustomerTypeDDL(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\CustomerTypeService();
        return $service->hookGetCustomerTypeDDL($data, $opts);
    }

    function getCustomerTypeHtmlOptions(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\CustomerTypeService();
        return $service->hookGetCustomerTypeHtmlOptions($data, $opts);
    }

    // ─── Territory Hooks ───────────────────────────────────────────

    function getTerritories(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\TerritoryService();
        return $service->hookGetTerritories($data, $opts);
    }

    function getTerritoryDDL(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\TerritoryService();
        return $service->hookGetTerritoryDDL($data, $opts);
    }

    function getTerritoryHtmlOptions(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\TerritoryService();
        return $service->hookGetTerritoryHtmlOptions($data, $opts);
    }

    // ─── Realm Hooks ───────────────────────────────────────────────

    function getRealms(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\RealmService();
        return $service->hookGetRealms($data, $opts);
    }

    function getRealmDDL(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\RealmService();
        return $service->hookGetRealmDDL($data, $opts);
    }

    function getRealmHtmlOptions(&$data, $opts = null)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) { return []; }
        require_once $autoload;
        $service = new \Ksfraser\FA\CRM\Service\RealmService();
        return $service->hookGetRealmHtmlOptions($data, $opts);
    }
}

class crm_app extends application {
    function __construct() {
        parent::__construct("CRM", _($this->help_context = "&CRM"));

        $this->add_module(_("CRM"));

        $menu = new \ksfraser\FrontAccounting\Common\Menu\FAModuleMenu(
            'modules/ksf_FA_CRM/index.php',
            'view',
            ''
        );

        $menu->addItem('dashboard',        _("&Dashboard"),        MENU_MAIN)
             ->addItem('contacts',          _("Contacts"),          MENU_INQUIRY)
             ->addItem('customers',         _("Customers"),         MENU_INQUIRY)
             ->addItem('leads',             _("Leads"),             MENU_ENTRY)
             ->addItem('opportunities',     _("Opportunities"),     MENU_ENTRY)
             ->addItem('communications',    _("Communications"),    MENU_INQUIRY)
             ->addItem('meetings',          _("Meetings"),          MENU_ENTRY)
             ->addItem('quotes',            _("Quotes"),            MENU_ENTRY)
             ->addItem('customer_types',    _("Customer Types"),    MENU_SETTINGS)
             ->addItem('territories',       _("Territories"),       MENU_SETTINGS)
             ->addItem('tags',              _("Tags"),              MENU_SETTINGS)
             ->addItem('email_accounts',    _("Email Accounts"),    MENU_SETTINGS);

        $menu->registerWithApp($this, 'SA_CRM_DASHBOARD');

        $this->add_extensions();
    }
}
