<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use ksfraser\FrontAccounting\Common\Menu\FAModuleMenu;
use Ksfraser\FA\CRM\Controller\AdminHubTabController;
use Ksfraser\FA\CRM\Controller\CommunicationsTabController;
use Ksfraser\FA\CRM\Controller\ContactsTabController;
use Ksfraser\FA\CRM\Controller\CustomerTypesTabController;
use Ksfraser\FA\CRM\Controller\CustomersTabController;
use Ksfraser\FA\CRM\Controller\EmailAccountsTabController;
use Ksfraser\FA\CRM\Controller\LeadsTabController;
use Ksfraser\FA\CRM\Controller\MeetingsTabController;
use Ksfraser\FA\CRM\Controller\OptionListsTabController;
use Ksfraser\FA\CRM\Controller\OpportunitiesTabController;
use Ksfraser\FA\CRM\Controller\QuotesTabController;
use Ksfraser\FA\CRM\Controller\TagsTabController;
use Ksfraser\FA\CRM\Controller\TerritoriesTabController;

/**
 * CrmAppShell — CRM application shell SRP.
 *
 * Registers the CRM core tabs (dashboard, contacts, customers, leads, ...),
 * then fires the `crm_register_tabs` register-with-me hook on boot() so other
 * modules can register their own tabs into the CRM app.
 *
 * Core tabs are registered at construction time so the host page can resolve
 * the per-view security area BEFORE session.inc. The in-page sub-menu is
 * rendered by renderMenu(): the operational tabs sit on the main bar, while
 * the "admin"-grouped configuration tabs (customer types, territories, the
 * opportunity option lists) live behind an Admin parent as a second sub-bar
 * (the Product Attributes admin-page pattern).
 *
 * The Customers tab is read-only: it lists native FA customers and deep-links
 * into the native editor in a new window (see CustomersTabController).
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
    /** Tab option value marking a tab as part of the admin sub-bar. */
    public const ADMIN_GROUP = 'admin';

    /** The admin parent view key. */
    public const ADMIN_VIEW = 'admin';

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
     * Tabs tagged with the 'admin' group option are rendered on a second
     * sub-bar behind the Admin parent; the rest sit on the main bar.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function registerCoreTabs(): void
    {
        $root = dirname(__DIR__, 5);
        $admin = ['group' => self::ADMIN_GROUP];

        $tabs = [
            ['key' => 'dashboard',      'label' => '&Dashboard',      'page' => 'dashboard.php',  'security' => 'SA_CRM_DASHBOARD'],
            ['key' => 'contacts',       'label' => 'Contacts',        'controller' => ContactsTabController::class,     'security' => 'SA_CRM_CONTACT_RELATIONSHIPS'],
            ['key' => 'customers',      'label' => 'Customers',       'controller' => CustomersTabController::class,    'security' => 'SA_CRM_CUSTOMER'],
            ['key' => 'leads',          'label' => 'Leads',           'controller' => LeadsTabController::class,          'security' => 'SA_CRM_LEAD'],
            ['key' => 'opportunities',  'label' => 'Opportunities',   'controller' => OpportunitiesTabController::class,  'security' => 'SA_CRM_OPPORTUNITY'],
            ['key' => 'communications', 'label' => 'Communications',  'controller' => CommunicationsTabController::class, 'security' => 'SA_CRM_COMMUNICATION'],
            ['key' => 'meetings',       'label' => 'Meetings',        'controller' => MeetingsTabController::class,       'security' => 'SA_CRM_MEETING'],
            ['key' => 'quotes',         'label' => 'Quotes',          'controller' => QuotesTabController::class,         'security' => 'SA_CRM_QUOTE'],
            ['key' => 'tags',           'label' => 'Tags',            'controller' => TagsTabController::class,           'security' => 'SA_CRM_TAGS'],
            ['key' => 'email_accounts', 'label' => 'Email Accounts',  'controller' => EmailAccountsTabController::class,  'security' => 'SA_CRM_EMAIL_ACCOUNT', 'options' => $admin],
            ['key' => 'customer_types', 'label' => 'Customer Types',  'controller' => CustomerTypesTabController::class, 'security' => 'SA_CUSTOMER_TYPE',     'options' => $admin],
            ['key' => 'territories',    'label' => 'Territories',     'controller' => TerritoriesTabController::class,    'security' => 'SA_TERRITORY',         'options' => $admin],
            ['key' => 'opportunity_sources', 'label' => 'Sources',    'controller' => OptionListsTabController::class,    'security' => 'SA_CRM_OPPORTUNITY',    'options' => $admin],
            ['key' => 'opportunity_types',   'label' => 'Types',      'controller' => OptionListsTabController::class,    'security' => 'SA_CRM_OPPORTUNITY',    'options' => $admin],
            ['key' => 'opportunity_realms',  'label' => 'Realms',     'controller' => OptionListsTabController::class,    'security' => 'SA_CRM_OPPORTUNITY',    'options' => $admin],
            ['key' => 'opportunity_stages',  'label' => 'Stages',     'controller' => OptionListsTabController::class,    'security' => 'SA_CRM_OPPORTUNITY',    'options' => $admin],
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
                $pageFile,
                null,
                $tab['options'] ?? []
            ));
            $priority += 10;
        }

        // The Admin parent (hub) view. Its access gate is the broadest CRM
        // admin area; each admin sub-tab re-checks its own security on
        // dispatch, so the hub only gates the list of section names.
        $this->registerTab(new TabRegistration(
            self::ADMIN_VIEW,
            'Admin',
            'SA_CRM_SETUP',
            AdminHubTabController::class,
            9990,
            0,
            null,
            null,
            ['group' => self::ADMIN_GROUP, 'hub' => true]
        ));
    }

    /**
     * Render the in-page sub-menu: operational tabs on the main bar, and the
     * admin-grouped configuration tabs on a second sub-bar shown only while an
     * admin view (or the Admin parent) is active.
     *
     * @param string $currentView Active view key
     * @return string HTML
     *
     * @since 1.0.0
     */
    public function renderMenu(string $currentView): string
    {
        $adminTabs = [];
        $mainTabs = [];
        foreach ($this->getTabs() as $tab) {
            if ($tab->getOption('group') === self::ADMIN_GROUP) {
                // The Admin parent itself is rendered as the main-bar entry,
                // not as a link inside its own sub-bar.
                if ($tab->getOption('hub') === true) {
                    continue;
                }
                $adminTabs[] = $tab;
            } else {
                $mainTabs[] = $tab;
            }
        }

        $isAdminView = $this->isAdminView($currentView);

        // Main bar: operational tabs + an Admin entry (highlighted while any
        // admin-grouped view is active).
        $mainMenu = new FAModuleMenu($this->baseUrl, $this->viewParam, $isAdminView ? self::ADMIN_VIEW : $currentView);
        foreach ($mainTabs as $tab) {
            $label = $tab->getLabel();
            if (function_exists('_')) {
                $label = _($label);
            }
            $mainMenu->addItem($tab->getKey(), $label, $tab->getFaType());
        }
        $adminLabel = function_exists('_') ? _('Admin') : 'Admin';
        $mainMenu->addItem(self::ADMIN_VIEW, $adminLabel, null);

        $html = $mainMenu->render();

        // Admin sub-bar: only while an admin view (or the Admin parent) is
        // active. The Admin parent itself renders a hub listing these links.
        if ($isAdminView && !empty($adminTabs)) {
            $subMenu = new FAModuleMenu($this->baseUrl, $this->viewParam, $currentView);
            foreach ($adminTabs as $tab) {
                $label = $tab->getLabel();
                if (function_exists('_')) {
                    $label = _($label);
                }
                $subMenu->addItem($tab->getKey(), $label, null);
            }
            $heading = function_exists('_') ? _('Admin') : 'Admin';
            $html .= '<div style="margin:-4px 0 12px 0;"><em>'
                . htmlspecialchars($heading . ':', ENT_QUOTES, 'UTF-8')
                . '</em></div>' . $subMenu->render();
        }

        return $html;
    }

    /**
     * Instantiate a tab controller. The Admin hub needs the owning shell so it
     * can enumerate the admin sub-tabs; every other controller is constructed
     * with no arguments (their services are default-constructed).
     *
     * @param TabRegistration $tab
     * @return object|null
     *
     * @since 1.0.0
     */
    protected function createController(TabRegistration $tab)
    {
        if ($tab->getControllerClass() === AdminHubTabController::class) {
            $controller = new AdminHubTabController();
            $controller->setShell($this);
            return $controller;
        }
        return parent::createController($tab);
    }

    /**
     * Whether the given view is the Admin parent or an admin-grouped tab.
     *
     * @param string $view View key
     * @return bool
     *
     * @since 1.0.0
     */
    public function isAdminView(string $view): bool
    {
        if ($view === self::ADMIN_VIEW) {
            return true;
        }
        $tab = $this->getTab($view);
        return $tab !== null && $tab->getOption('group') === self::ADMIN_GROUP;
    }

    /**
     * The admin-grouped tab registrations (for the Admin hub).
     *
     * @return TabRegistration[]
     *
     * @since 1.0.0
     */
    public function getAdminTabs(): array
    {
        $admin = [];
        foreach ($this->getTabs() as $tab) {
            if ($tab->getOption('group') === self::ADMIN_GROUP
                && $tab->getOption('hub') !== true
            ) {
                $admin[] = $tab;
            }
        }
        return $admin;
    }
}
