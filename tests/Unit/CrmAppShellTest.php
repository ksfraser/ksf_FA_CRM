<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\CRM\App\CrmAppShell;
use Ksfraser\FA\CRM\Controller\AdminHubTabController;
use Ksfraser\FA\CRM\Controller\CustomersTabController;

/**
 * Unit tests for CrmAppShell — Cluster C admin sub-bar + native Customers tab.
 *
 * Covers:
 *  - the 'customers' key now resolves to the real read-only Customers tab
 *    (not Customer Types),
 *  - admin-grouped configuration tabs are tagged and exposed as a second
 *    sub-bar behind the Admin parent,
 *  - the Admin hub enumerates the admin sub-tabs and is excluded from the
 *    sub-bar itself.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-CRM-001
 */
class CrmAppShellTest extends TestCase
{
    private CrmAppShell $shell;

    protected function setUp(): void
    {
        $this->shell = new CrmAppShell();
    }

    public function testCustomersTabIsTheNativeReadOnlyCustomers(): void
    {
        $tab = $this->shell->getTab('customers');

        $this->assertNotNull($tab, 'customers tab must be registered');
        $this->assertSame('Customers', $tab->getLabel());
        $this->assertSame(CustomersTabController::class, $tab->getControllerClass());
        $this->assertSame('SA_CRM_CUSTOMER', $tab->getSecurity());
        $this->assertNull($tab->getOption('group'), 'Customers is operational, not admin');
    }

    public function testCustomerTypesIsItsOwnAdminTab(): void
    {
        $tab = $this->shell->getTab('customer_types');

        $this->assertNotNull($tab, 'customer_types must be a distinct tab key');
        $this->assertSame('Customer Types', $tab->getLabel());
        $this->assertSame('admin', $tab->getOption('group'));
    }

    public function testAdminSubTabsAreTagged(): void
    {
        foreach (['email_accounts', 'customer_types', 'territories',
                  'opportunity_sources', 'opportunity_types',
                  'opportunity_realms', 'opportunity_stages'] as $key) {
            $tab = $this->shell->getTab($key);
            $this->assertNotNull($tab, "{$key} must be registered");
            $this->assertSame('admin', $tab->getOption('group'), "{$key} must be admin-grouped");
        }
    }

    public function testAdminHubIsRegisteredAndGated(): void
    {
        $tab = $this->shell->getTab(CrmAppShell::ADMIN_VIEW);

        $this->assertNotNull($tab, 'admin parent must be registered');
        $this->assertSame(AdminHubTabController::class, $tab->getControllerClass());
        $this->assertSame('SA_CRM_SETUP', $tab->getSecurity());
        $this->assertTrue($tab->getOption('hub'));
    }

    public function testGetAdminTabsExcludesTheHubItself(): void
    {
        $adminTabs = $this->shell->getAdminTabs();
        $keys = array_map(function ($tab) {
            return $tab->getKey();
        }, $adminTabs);

        $this->assertNotContains(CrmAppShell::ADMIN_VIEW, $keys, 'hub must not list itself');
        $this->assertContains('customer_types', $keys);
        $this->assertContains('territories', $keys);
    }

    public function testIsAdminViewMatchesParentAndAdminTabs(): void
    {
        $this->assertTrue($this->shell->isAdminView(CrmAppShell::ADMIN_VIEW));
        $this->assertTrue($this->shell->isAdminView('customer_types'));
        $this->assertTrue($this->shell->isAdminView('opportunity_stages'));
        $this->assertFalse($this->shell->isAdminView('dashboard'));
        $this->assertFalse($this->shell->isAdminView('customers'));
        $this->assertFalse($this->shell->isAdminView('tags'));
    }

    public function testRenderMenuShowsMainBarForOperationalView(): void
    {
        $html = $this->shell->renderMenu('dashboard');

        $this->assertStringContainsString('view=dashboard', $html);
        $this->assertStringContainsString('view=customers', $html);
        $this->assertStringContainsString('view=admin', $html);
        // Admin sub-bar is hidden outside the admin group.
        $this->assertStringNotContainsString('view=territories', $html);
    }

    public function testRenderMenuShowsAdminSubBarForAdminView(): void
    {
        $html = $this->shell->renderMenu('customer_types');

        $this->assertStringContainsString('view=customer_types', $html);
        $this->assertStringContainsString('view=territories', $html);
        $this->assertStringContainsString('view=opportunity_stages', $html);
        // The hub is the main-bar entry, not a self-link in its own sub-bar.
        $this->assertSame(
            1,
            substr_count($html, 'view=' . CrmAppShell::ADMIN_VIEW),
            'admin parent appears only as the main-bar entry'
        );
    }
}
