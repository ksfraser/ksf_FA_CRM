<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use Ksfraser\FA\CRM\Controller\CommunicationsTabController;
use Ksfraser\FA\CRM\Controller\ContactsTabController;
use Ksfraser\FA\CRM\Controller\CustomerTypesTabController;
use Ksfraser\FA\CRM\Controller\EmailAccountsTabController;
use Ksfraser\FA\CRM\Controller\LeadsTabController;
use Ksfraser\FA\CRM\Controller\MeetingsTabController;
use Ksfraser\FA\CRM\Controller\OpportunitiesTabController;
use Ksfraser\FA\CRM\Controller\QuotesTabController;
use Ksfraser\FA\CRM\Controller\TagsTabController;
use Ksfraser\FA\CRM\Controller\TerritoriesTabController;

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
     * Core CRM tabs. Controller-backed tabs use the app-shell SRP; anything
     * still listed as a page must be a shell-compatible fragment (inherits
     * $path_to_root, no own page()/end_page()).
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function registerCoreTabs(): void
    {
        $root = dirname(__DIR__, 5);

        $tabs = [
            ['key' => 'dashboard',      'label' => '&Dashboard',      'page' => 'dashboard.php',  'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'contacts',       'label' => 'Contacts',        'controller' => ContactsTabController::class,     'security' => 'SA_CRM_CONTACT_RELATIONSHIPS'],
            ['key' => 'customers',      'label' => 'Customers',       'controller' => CustomerTypesTabController::class, 'security' => 'SA_CUSTOMER_TYPE'],
            ['key' => 'leads',          'label' => 'Leads',           'controller' => LeadsTabController::class,          'security' => 'SA_CRM_LEAD'],
            ['key' => 'opportunities',  'label' => 'Opportunities',   'controller' => OpportunitiesTabController::class,  'security' => 'SA_CRM_OPPORTUNITY'],
            ['key' => 'communications', 'label' => 'Communications',  'controller' => CommunicationsTabController::class, 'security' => 'SA_CRM_COMMUNICATION'],
            ['key' => 'meetings',       'label' => 'Meetings',        'controller' => MeetingsTabController::class,       'security' => 'SA_CRM_MEETING'],
            ['key' => 'quotes',         'label' => 'Quotes',          'controller' => QuotesTabController::class,         'security' => 'SA_CRM_QUOTE'],
            ['key' => 'customer_types', 'label' => 'Customer Types',  'controller' => CustomerTypesTabController::class,  'security' => 'SA_CUSTOMER_TYPE'],
            ['key' => 'territories',    'label' => 'Territories',     'controller' => TerritoriesTabController::class,    'security' => 'SA_TERRITORY'],
            ['key' => 'tags',           'label' => 'Tags',            'controller' => TagsTabController::class,           'security' => 'SA_CRM_TAGS'],
            ['key' => 'email_accounts', 'label' => 'Email Accounts',  'controller' => EmailAccountsTabController::class,  'security' => 'SA_CRM_EMAIL_ACCOUNT'],
        ];

        $priority = 0;
        foreach ($tabs as $tab) {
            $controllerClass = $tab['controller'] ?? null;
            $pageFile = isset($tab['page']) && $tab['page'] !== null
                ? ($root . '/pages/' . $tab['page'])
                : null;
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
