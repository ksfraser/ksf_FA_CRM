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

// Composer autoloader + vendored ksfraser/ksf-fa-common prefixes.
if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
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
            $this->migrate_email_accounts();
            $this->register_contact_types();
        }

        return $ok;
    }

    /**
     * Hand CRM email accounts over to ksf_FA_EmailManager (ksfraser/ksf_FA_CRM#25).
     *
     * EmailManager owns the mailbox-of-record (0_fa_em_accounts); the CRM's own
     * 0_fa_crm_email_accounts is retired. The INSERT is guarded by NOT EXISTS on
     * email_address, so it is idempotent and self-healing: re-running copies only
     * accounts EmailManager has not already taken. No marker table is needed.
     *
     * The source table is intentionally NOT dropped here -- that stays a
     * deliberate, separately-reviewed step once the handover is signed off.
     */
    private function migrate_email_accounts() {
        foreach (array('fa_crm_email_accounts', 'fa_em_accounts') as $table) {
            if (!$this->crm_table_exists($table)) {
                return; // fresh install, or EmailManager not installed yet
            }
        }

        $this->add_email_scheduling_columns();

        db_query("INSERT INTO " . TB_PREF . "fa_em_accounts
            (account_name, email_address, account_type, server_host, server_port, encryption,
             username, password, sync_folder, is_active, auto_import, import_frequency,
             last_import, last_sync)
            SELECT c.account_name, c.email_address, 'imap', c.server_host, c.server_port,
             c.encryption, c.username, c.password, 'INBOX', IF(c.inactive = 1, 0, 1),
             c.auto_import, c.import_frequency, c.last_import, c.last_import
            FROM " . TB_PREF . "fa_crm_email_accounts c
            WHERE NOT EXISTS (
                SELECT 1 FROM " . TB_PREF . "fa_em_accounts e WHERE e.email_address = c.email_address
            )", 'Could not migrate CRM email accounts');
    }

    /**
     * Installations provisioned by the retired PHP-side ensure_email_schema()
     * predate the scheduling columns the CRM owned, so add whichever are absent
     * before the copy below references them.
     */
    private function add_email_scheduling_columns() {
        $present = array();
        $res = db_query("SHOW COLUMNS FROM " . TB_PREF . "fa_em_accounts", 'Cannot inspect fa_em_accounts');
        while ($col = db_fetch_assoc($res)) {
            $present[$col['Field']] = true;
        }

        $wanted = array(
            'auto_import'      => 'ADD COLUMN `auto_import` TINYINT(1) DEFAULT 0',
            'import_frequency' => 'ADD COLUMN `import_frequency` INT(11) DEFAULT 60',
            'last_import'      => 'ADD COLUMN `last_import` DATETIME DEFAULT NULL',
        );

        $add = array();
        foreach ($wanted as $column => $clause) {
            if (!isset($present[$column])) {
                $add[] = $clause;
            }
        }

        if ($add) {
            db_query("ALTER TABLE " . TB_PREF . "fa_em_accounts " . implode(', ', $add),
                'Could not add email scheduling columns');
        }
    }

    private function crm_table_exists($table) {
        $res = db_query("SHOW TABLES LIKE " . db_escape(TB_PREF . $table), 'Cannot check table');
        return db_num_rows($res) > 0;
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
             ->addItem('tags',              _("Tags"),              MENU_SETTINGS);

        $menu->registerWithApp($this, 'SA_CRM_DASHBOARD');

        $this->add_extensions();
    }
}
