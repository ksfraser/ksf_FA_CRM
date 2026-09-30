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
     * One sql/<tablename>.sql per table, each holding that table's definition
     * plus any pre-seed data. update_databases() gates each file on its own
     * table, so a partially-installed database gets the missing tables
     * individually. (A single install.sql gated on one table is unsound: if
     * that one table existed, the rest were never created.)
     *
     * @param int $company Company number
     * @param bool $check_only Only check if activation possible
     * @return bool Success
     */
    function activate_extension($company, $check_only=true) {
        $this->ensure_composer_dependencies();
        $updates = array(
            'ksf_crm_customers.sql'            => array('ksf_crm_customers'),
            'ksf_crm_contacts.sql'             => array('ksf_crm_contacts'),
            'ksf_crm_contact_relationships.sql' => array('ksf_crm_contact_relationships'),
            'ksf_crm_account_relationships.sql' => array('ksf_crm_account_relationships'),
            'ksf_crm_person_account_roles.sql'  => array('ksf_crm_person_account_roles'),
            'ksf_crm_life_events.sql'          => array('ksf_crm_life_events'),
            'ksf_crm_opportunities.sql'        => array('ksf_crm_opportunities'),
            'ksf_crm_communications.sql'       => array('ksf_crm_communications'),
            'ksf_crm_customer_types.sql'       => array('ksf_crm_customer_types'),
            'ksf_crm_territories.sql'          => array('ksf_crm_territories'),
            'ksf_crm_activity_log.sql'         => array('ksf_crm_activity_log'),
            'ksf_crm_leads.sql'                => array('ksf_crm_leads'),
            'ksf_crm_contact_accounts.sql'     => array('ksf_crm_contact_accounts'),
            'ksf_crm_realms.sql'               => array('ksf_crm_realms'),
            'ksf_crm_quotes.sql'               => array('ksf_crm_quotes'),
            'ksf_crm_quote_items.sql'          => array('ksf_crm_quote_items'),
            'ksf_crm_meetings.sql'             => array('ksf_crm_meetings'),
            'ksf_crm_meeting_attendees.sql'    => array('ksf_crm_meeting_attendees'),
            'ksf_crm_option_lists.sql'         => array('ksf_crm_option_lists'),
            'retag_contact_types.sql'          => array('ksf_contact_types'),
        );
        $ok = $this->update_databases($company, $updates, $check_only);

        if (!$check_only && $ok) {
            // Order matters: the handover copies mailboxes out of the retired
            // 0_fa_crm_email_accounts before retire_misnamed_tables() retires it.
            $this->migrate_email_accounts();
            $this->register_contact_types();
            $this->retire_misnamed_tables($company);
        }

        return $ok;
    }

    /**
     * Run sql/upgrade_2.4.3-1.sql for installations that still hold misnamed
     * 0_fa_crm_* tables.
     *
     * The upgrade file's DROP statements were removed once every installation
     * had been cut over, so this is now a belt-and-braces no-op that only fires
     * on a table that predates the cutover. It is deliberately NOT part of the
     * update_databases() map, which gates on "table missing => run this file" —
     * the inverse of what a cleanup script needs — and is driven explicitly,
     * and only when there is something to do.
     *
     * @param int $company Company number
     * @return bool
     */
    private function retire_misnamed_tables($company) {
        global $db_connections;

        $legacy = array('fa_crm_customers', 'fa_crm_contacts',
            'fa_crm_contact_relationships', 'fa_crm_account_relationships',
            'fa_crm_person_account_roles', 'fa_crm_life_events',
            'fa_crm_opportunities', 'fa_crm_communications',
            'fa_crm_customer_types', 'fa_crm_territories', 'fa_crm_activity_log',
            'fa_crm_leads', 'fa_crm_contact_accounts', 'fa_crm_realms',
            'fa_crm_quotes', 'fa_crm_quote_items', 'fa_crm_meetings',
            'fa_crm_meeting_attendees', 'fa_crm_option_lists',
            'fa_crm_email_accounts');

        $present = false;
        foreach ($legacy as $table) {
            if ($this->crm_table_exists($table)) {
                $present = true;
                break;
            }
        }
        if (!$present) {
            return true; // already cut over; nothing to retire
        }

        $file = dirname(__FILE__) . '/sql/upgrade_2.4.3-1.sql';
        if (!file_exists($file)) {
            return true;
        }

        $conn = ($company == -1) ? $db_connections
            : array($company => $db_connections[$company]);
        foreach ($conn as $comp => $con) {
            set_global_connection($comp);
            if (!db_import($file, $con)) {
                db_close();
                return false;
            }
            db_close();
        }
        return true;
    }

    /**
     * Hand CRM email accounts over to ksf_FA_EmailManager (ksfraser/ksf_FA_CRM#25).
     *
     * EmailManager owns the mailbox-of-record; the CRM's own
     * 0_fa_crm_email_accounts is retired. The INSERT is guarded by NOT EXISTS on
     * email_address, so it is idempotent and self-healing: re-running copies only
     * accounts EmailManager has not already taken. No marker table is needed.
     *
     * The source table is retired by retire_misnamed_tables(), which runs after
     * this, so the copy always sees it.
     */
    private function migrate_email_accounts() {
        if (!$this->crm_table_exists('fa_crm_email_accounts')) {
            return; // fresh install, or the handover has already happened
        }

        $target = $this->email_manager_accounts_table();
        if ($target === false) {
            return; // EmailManager not installed yet; retried on its activation
        }

        $this->add_email_scheduling_columns($target);

        db_query("INSERT INTO " . TB_PREF . $target . "
            (account_name, email_address, account_type, server_host, server_port, encryption,
             username, password, sync_folder, is_active, auto_import, import_frequency,
             last_import, last_sync)
            SELECT c.account_name, c.email_address, 'imap', c.server_host, c.server_port,
             c.encryption, c.username, c.password, 'INBOX', IF(c.inactive = 1, 0, 1),
             c.auto_import, c.import_frequency, c.last_import, c.last_import
            FROM " . TB_PREF . "fa_crm_email_accounts c
            WHERE NOT EXISTS (
                SELECT 1 FROM " . TB_PREF . $target . " e WHERE e.email_address = c.email_address
            )", 'Could not migrate CRM email accounts');
    }

    /**
     * EmailManager's accounts table, preferring the convention-compliant name
     * and falling back to the pre-rename one so the handover still works on an
     * installation where only the legacy table has been created so far.
     *
     * @return string|false Unprefixed table name, or false if neither exists
     */
    private function email_manager_accounts_table() {
        foreach (array('ksf_em_accounts', 'fa_em_accounts') as $table) {
            if ($this->crm_table_exists($table)) {
                return $table;
            }
        }
        return false;
    }

    /**
     * Installations provisioned by the retired PHP-side ensure_email_schema()
     * predate the scheduling columns the CRM owned, so add whichever are absent
     * before the copy above references them.
     */
    private function add_email_scheduling_columns($target) {
        $present = array();
        $res = db_query("SHOW COLUMNS FROM " . TB_PREF . $target, 'Cannot inspect email accounts');
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
            db_query("ALTER TABLE " . TB_PREF . $target . " " . implode(', ', $add),
                'Could not add email scheduling columns');
        }
    }

    private function crm_table_exists($table) {
        // Resolve the prefix to a concrete name before escaping. db_escape()
        // runs html_entity_decode() then html_specials_encode(), which turns
        // the TB_PREF placeholder '&TB_PREF&' into '&amp;TB_PREF&amp;'. That
        // breaks the str_replace(TB_PREF, ...) substitution db_query() relies
        // on, so the LIKE pattern would never match anything. (FA core's
        // check_table() avoids this by concatenating the prefix into quoted
        // SQL instead of escaping it.)
        global $db_connections;
        $comp = isset($_SESSION['wa_current_user']->cur_con)
            ? $_SESSION['wa_current_user']->cur_con : 0;
        $prefix = $db_connections[$comp]['tbpref'];
        $res = db_query("SHOW TABLES LIKE " . db_escape($prefix . $table), 'Cannot check table');
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
