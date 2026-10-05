<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\CRM\App\CrmAppShell;

/**
 * Verifies that CRM tabs can be contributed by other modules (ksfii_app /
 * Insurance), rather than CRM hardcoding every tab itself.
 *
 * Contract, from AbstractAppShell:
 *   - the hook name is `crm_register_tabs` (getAppId() . '_register_tabs'),
 *   - a responder pushes TabRegistration instances or definition arrays into
 *     `$data['tabs']`,
 *   - a contributed tab is reachable by its own `?view=<key>` and its own
 *     `security` is what index.php assigns to $page_security.
 *
 * The last point is a regression guard: index.php used to resolve the view
 * and $page_security *before* boot(), so a contributed tab fell back to the
 * CRM default view on deep link and never had its access area enforced.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-CRM-001
 */
class TabExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__fa_hook_responders'] = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['__fa_hook_responders'] = [];
    }

    /**
     * Registers a fake Insurance module responder on the CRM register hook.
     */
    private function givenAnInsuranceModuleContributingATab(): void
    {
        $GLOBALS['__fa_hook_responders']['crm_register_tabs'] = [
            static function (array &$data): void {
                $data['tabs'][] = [
                    'key' => 'insurance_policies',
                    'label' => 'Policies',
                    'security' => 'SA_INSURANCE_VIEW',
                    'controller_class' => 'Insurance\\PoliciesTabController',
                    'fa_type' => 1,
                ];
            },
        ];
    }

    /**
     * Mirrors index.php's resolution order so the regression stays covered:
     * boot() first, then resolve the requested view and its security.
     *
     * @return array{0: string, 1: string} [resolved view, page_security]
     */
    private function resolveRequestAsIndexPageDoes(): array
    {
        $shell = new CrmAppShell();
        $shell->boot();

        $view = 'insurance_policies';
        if ($shell->getTab($view) === null) {
            $view = $shell->getDefaultView();
        }

        return [$view, $shell->getSecurity($view, 'SA_CRM_DASHBOARD')];
    }

    public function testRegisterHookIsTheCrmRegisterTabsHook(): void
    {
        $shell = new CrmAppShell();

        $this->assertSame('crm_register_tabs', $shell->getRegisterHook());
    }

    public function testContributedTabIsAbsentBeforeBoot(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        $shell = new CrmAppShell();

        $this->assertNull(
            $shell->getTab('insurance_policies'),
            'the hook must not fire before boot()'
        );
    }

    public function testBootMergesAContributedTab(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        $shell = new CrmAppShell();
        $shell->boot();

        $tab = $shell->getTab('insurance_policies');
        $this->assertNotNull($tab, 'crm_register_tabs responder must contribute a tab');
        $this->assertSame('Policies', $tab->getLabel());
        $this->assertSame('Insurance\\PoliciesTabController', $tab->getControllerClass());
    }

    public function testContributedTabIsReachableByDirectUrl(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        [$view] = $this->resolveRequestAsIndexPageDoes();

        $this->assertSame(
            'insurance_policies',
            $view,
            'a contributed tab must survive ?view= resolution instead of falling back'
        );
    }

    public function testContributedTabSuppliesItsOwnPageSecurity(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        [, $pageSecurity] = $this->resolveRequestAsIndexPageDoes();

        $this->assertSame(
            'SA_INSURANCE_VIEW',
            $pageSecurity,
            'index.php must enforce the contributed tab area, not the CRM default'
        );
    }

    public function testContributedTabAppearsInTheRenderedMenu(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        $shell = new CrmAppShell();
        $shell->boot();

        $this->assertStringContainsString(
            'view=insurance_policies',
            $shell->renderMenu('dashboard')
        );
    }

    /**
     * Boot is idempotent (AbstractAppShell guards on $booted), so index.php
     * may call it defensively without registering a tab twice.
     */
    public function testBootIsIdempotent(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        $shell = new CrmAppShell();
        $shell->boot();
        $shell->boot();

        $this->assertNotNull($shell->getTab('insurance_policies'));
    }

    /**
     * A contributed tab must not be able to displace the CRM's own core tabs.
     */
    public function testCoreTabsSurviveContribution(): void
    {
        $this->givenAnInsuranceModuleContributingATab();

        $shell = new CrmAppShell();
        $shell->boot();

        foreach (['dashboard', 'customers', 'leads'] as $core) {
            $this->assertNotNull($shell->getTab($core), "core tab {$core} must survive");
        }
    }

    /**
     * Source-order guard for the real entrypoint.
     *
     * The behavioural tests above re-implement the correct order, so on their
     * own they would stay green even if index.php were reverted. Assert the
     * actual file: boot() must precede both the ?view= resolution and the
     * authoritative $page_security assignment.
     */
    public function testIndexPageBootsBeforeResolvingViewAndSecurity(): void
    {
        $source = (string) file_get_contents($this->moduleDir() . '/index.php');

        $boot = strpos($source, '$appShell->boot()');
        $resolve = strpos($source, "\$view = isset(\$_GET['view'])");
        $security = strrpos($source, '$page_security = $appShell->getSecurity');

        $this->assertNotFalse($boot, 'index.php must call boot()');
        $this->assertNotFalse($resolve, 'index.php must resolve ?view=');
        $this->assertNotFalse($security, 'index.php must assign $page_security');

        $this->assertLessThan(
            $resolve,
            $boot,
            'boot() must run before the ?view= resolution or contributed tabs are unreachable'
        );
        $this->assertLessThan(
            $security,
            $boot,
            'boot() must run before $page_security or the contributed tab area is never enforced'
        );
    }

    /**
     * $page_security must still exist before session.inc is included: FA reads
     * it while building the page, and the extension list is not yet merged at
     * that point, so the assignment there is a provisional gate.
     */
    public function testIndexPageSetsProvisionalSecurityBeforeSessionInclude(): void
    {
        $source = (string) file_get_contents($this->moduleDir() . '/index.php');

        $provisional = strpos($source, '$page_security = ');
        $session = strpos($source, 'includes/session.inc');

        $this->assertNotFalse($provisional, 'index.php must set a provisional $page_security');
        $this->assertNotFalse($session, 'index.php must include session.inc');
        $this->assertLessThan($session, $provisional, 'a provisional gate is required before session.inc');
    }

    private function moduleDir(): string
    {
        return dirname(__DIR__, 2);
    }
}