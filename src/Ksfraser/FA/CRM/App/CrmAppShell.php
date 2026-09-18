<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use Ksfraser\FA\CRM\Controller\CustomerTypesTabController;

/**
 * CrmAppShell — CRM application shell SRP.
 *
 * Registers the CRM core tabs (dashboard, contacts, leads, ...), then fires the
 * `crm_register_tabs` register-with-me hook on boot() so other modules can
 * register their own tabs into the CRM app.
 *
 * Core tabs are registered at construction time so the host page can resolve
 * the per-view security area BEFORE session.inc. Legacy page-script tabs keep
 * their historical SA_CRM_DASHBOARD gate; Customer Types is controller-backed
 * (the CRM SRP pilot) and uses its own SA_CUSTOMER_TYPE area.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §7/§11 (app host)
 * @BABOK Related: FR-CRM-001 (App shell tab registration)
 */
class CrmAppShell extends AbstractAppShell
{
    /**
     * @param string $defaultView Fallback view key
     *
     * @since 1.0.0
     */
    public function __construct(string $defaultView = 'dashboard')
    {
        parent::__construct('crm', 'index.php', 'view', $defaultView);
        $this->registerCoreTabs();
    }

    /**
     * Core CRM tabs, each mapped to its page script + security area.
     * Customer Types is controller-backed (SRP pilot).
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function registerCoreTabs(): void
    {
        $root = dirname(__DIR__, 5);

        $tabs = [
            ['key' => 'dashboard',      'label' => '&Dashboard',      'page' => 'dashboard.php',              'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'contacts',       'label' => 'Contacts',        'page' => 'contact_relationships.php',  'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'customers',      'label' => 'Customers',       'page' => 'customer_types.php',         'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'leads',          'label' => 'Leads',           'page' => 'leads.php',                  'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'opportunities',  'label' => 'Opportunities',   'page' => 'opportunities.php',          'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'communications', 'label' => 'Communications',  'page' => 'communications.php',         'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'meetings',       'label' => 'Meetings',        'page' => 'meetings.php',               'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'quotes',         'label' => 'Quotes',          'page' => 'quotes.php',                 'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'customer_types', 'label' => 'Customer Types',  'page' => null,                         'security' => 'SA_CUSTOMER_TYPE'],
            ['key' => 'territories',    'label' => 'Territories',     'page' => 'territories.php',            'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'tags',           'label' => 'Tags',            'page' => 'crm_tags.php',               'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'email_accounts', 'label' => 'Email Accounts',  'page' => 'email_accounts.php',         'security' => 'SA_CRM_DASHBOARD'],
        ];

        $priority = 0;
        foreach ($tabs as $tab) {
            $controllerClass = ($tab['key'] === 'customer_types') ? CustomerTypesTabController::class : null;
            $pageFile = ($tab['page'] !== null) ? ($root . '/pages/' . $tab['page']) : null;
            $this->registerTab(new TabRegistration(
                $tab['key'],
                $tab['label'],
                $tab['security'],
                $controllerClass,
                $priority,
                0,
                $pageFile
            ));
            $priority += 10;
        }
    }
}
