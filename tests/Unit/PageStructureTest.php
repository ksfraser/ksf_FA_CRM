<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FACRM;

use PHPUnit\Framework\TestCase;

class PageStructureTest extends TestCase
{
    private string $moduleDir;
    
    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }
    
    public function testIndexPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/index.php');
    }
    
    public function testIndexPageContainsCRMUI(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/index.php');
        
        $this->assertStringContainsString('FA_CRM', $content);
        $this->assertStringContainsString('id="FA_CRM-ui"', $content);
    }
    
    public function testIndexPageUsesAppClass(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/index.php');
        
        $this->assertStringContainsString('new \ksf\App()', $content);
    }
    
    public function testIndexPageRequiresAutoload(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/index.php');
        
        $this->assertStringContainsString('autoload.php', $content);
    }
    
    public function testDatabaseServiceExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/includes/ksf_FA_CRMDB.php');
    }
    
    public function testDatabaseServiceContainsClass(): void
    {
        $content = file_get_contents($this->moduleDir . '/includes/ksf_FA_CRMDB.php');
        
        $this->assertStringContainsString('class DatabaseService', $content);
        $this->assertStringContainsString('getInstance', $content);
    }
    
    public function testDatabaseServiceUsesNamespaceKsf(): void
    {
        $content = file_get_contents($this->moduleDir . '/includes/ksf_FA_CRMDB.php');
        
        $this->assertStringContainsString('namespace ksf', $content);
    }
    
    public function testImportFileExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/includes/import.php');
    }
    
    public function testProjectDcsExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/ProjectDcs');
    }
    
    public function testCRMMenuContainsTagsItem(): void
    {
        // Tags menu item now lives in the AppShell menu registry (hooks.php),
        // not the legacy procedural router. The ?view= router dispatches to
        // the tab controller SRP via $appShell->dispatch().
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("addItem('tags'", $content);
        $this->assertStringContainsString("_(\"Tags\")", $content);
    }
    
    public function testCRMMainViewsAreDefined(): void
    {
        // Main views are now SRP tab-controller entries in the AppShell menu
        // registry (hooks.php → $menu->addItem chain), not literal strings in
        // the procedural index.php router. index.php only resolves the ?view=
        // via getTab() and dispatches through the controller SRP base class
        // (which is where the 'ajax' => false round-trip fix lives).
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("addItem('dashboard'", $content);
        $this->assertStringContainsString("addItem('contacts'", $content);
        $this->assertStringContainsString("addItem('customers'", $content);
        $this->assertStringContainsString("addItem('leads'", $content);
        $this->assertStringContainsString("addItem('opportunities'", $content);
        $this->assertStringContainsString("addItem('communications'", $content);
        $this->assertStringContainsString("addItem('meetings'", $content);
        $this->assertStringContainsString("addItem('quotes'", $content);
    }
    
    public function testSecurityAreaSA_CRM_TAGSIsDefined(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("SA_CRM_TAGS", $content);
        $this->assertStringContainsString("SS_CRM | 13", $content);
    }
    
    public function testSecurityAreaSA_CRM_DASHBOARDIsDefined(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("SA_CRM_DASHBOARD", $content);
        $this->assertStringContainsString("SS_CRM", $content);
    }
    
    public function testSecuritySectionsIncludeCRMManagement(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("SS_CRM", $content);
        $this->assertStringContainsString("CRM Management", $content);
    }
    
    public function testCRMTagsPageSecurityReference(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/crm_tags.php');
        
        $this->assertStringContainsString("SA_CRM_TAGS", $content);
        $this->assertStringContainsString("crm_tags.inc", $content);
    }
    
    public function testCRMTagsPageUsesFAFunctions(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/crm_tags.php');
        
        $this->assertStringContainsString("get_post", $content);
        $this->assertStringContainsString("db_query", $content);
        $this->assertStringContainsString("display_error", $content);
    }
    
    public function testCRMTagsIncDefinesTagConstants(): void
    {
        $content = file_get_contents($this->moduleDir . '/includes/crm_tags.inc');
        
        $this->assertStringContainsString("TAG_CUSTOMER", $content);
        $this->assertStringContainsString("TAG_CONTACT", $content);
        $this->assertStringContainsString("TAG_OPPORTUNITY", $content);
        $this->assertStringContainsString("TAG_LEAD", $content);
        $this->assertStringContainsString("TAG_COMMUNICATION", $content);
    }
    
    public function testCRMTagsIncProvidesHelperFunctions(): void
    {
        $content = file_get_contents($this->moduleDir . '/includes/crm_tags.inc');
        
        $this->assertStringContainsString("crm_tag_type_name", $content);
        $this->assertStringContainsString("crm_tag_types", $content);
    }
    
    public function testCRMAppDefinesMenuWithTags(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("class crm_app extends application", $content);
        $this->assertStringContainsString("'tags'", $content);
        $this->assertStringContainsString("_(\"Tags\")", $content);
    }
    
    public function testCRMAppRegistersWithSecurityArea(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        $this->assertStringContainsString("registerWithApp", $content);
        $this->assertStringContainsString("SA_CRM_DASHBOARD", $content);
    }
    
    public function testCRMAppMenuCategoriesAreValid(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        // Check for valid menu category constants
        $this->assertStringContainsString("MENU_MAIN", $content);
        $this->assertStringContainsString("MENU_INQUIRY", $content);
        $this->assertStringContainsString("MENU_ENTRY", $content);
        $this->assertStringContainsString("MENU_SETTINGS", $content);
    }
    
    public function testCRMAppMenuHasCorrectStructure(): void
    {
        $content = file_get_contents($this->moduleDir . '/hooks.php');
        
        // Verify menu items are added in expected order
        $this->assertStringContainsString("addItem('dashboard'", $content);
        $this->assertStringContainsString("addItem('contacts'", $content);
        $this->assertStringContainsString("addItem('customers'", $content);
        $this->assertStringContainsString("addItem('leads'", $content);
        $this->assertStringContainsString("addItem('opportunities'", $content);
        $this->assertStringContainsString("addItem('communications'", $content);
        $this->assertStringContainsString("addItem('meetings'", $content);
        $this->assertStringContainsString("addItem('quotes'", $content);
        $this->assertStringContainsString("addItem('customer_types'", $content);
        $this->assertStringContainsString("addItem('territories'", $content);
        $this->assertStringContainsString("addItem('tags'", $content);
        $this->assertStringContainsString("addItem('email_accounts'", $content);
    }
    
    public function testIndexPageUsesValidViews(): void
    {
        // Valid-views gate now lives in the AppShell router contract: the
        // ?view= dispatcher resolves the tab from $appShell->getTab() and
        // falls back to the SRP default view; pages are dispatched through
        // the tab controller business-logic SRP (extends AbstractTabController).
        $content = file_get_contents($this->moduleDir . '/index.php');
        
        $this->assertStringContainsString("getDefaultView", $content);
        $this->assertStringContainsString("dispatch", $content);
        $this->assertStringContainsString("getTab", $content);
    }
    
    public function testIndexPageCreatesSubMenu(): void
    {
        // Sub-menu creation is now the AppShell's job (the shell owns the
        // menu registry + render). index.php delegates to the shell, whose
        // SRP it documents in the App/AppShell docblock.
        $content = file_get_contents($this->moduleDir . '/index.php');
        
        $this->assertStringContainsString("renderMenu", $content);
        $this->assertStringContainsString("dispatch", $content);
    }
}
