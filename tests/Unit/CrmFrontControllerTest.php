<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use ksfraser\FrontAccounting\CRM\App\CrmAppShell;
use ksfraser\FrontAccounting\CRM\App\CrmFrontController;

/**
 * A tab controller that records that it ran, so dispatch can be observed
 * without booting FA or touching a database.
 */
class RecordingTabController
{
    /** @var string|null Key the front controller resolved. */
    public static $dispatched = null;

    /** @var string[] $_GET as the controller saw it. */
    public static $get = [];

    public function run(): void
    {
        self::$dispatched = 'recording';
        self::$get        = $_GET;
    }
}

/**
 * Minimal shell: app id 'crm' (so boot() fires `crm_register_tabs`), the same
 * view parameter CRM publishes, and two tabs — one controller-backed, one that
 * is a page fragment.
 */
class TestCrmShell extends AbstractAppShell
{
    public function __construct()
    {
        parent::__construct('crm', 'index.php', 'view', 'dashboard');
    }
}

/**
 * CrmFrontController — the CRM adapter of the shared AbstractFrontController.
 *
 * These tests pin the three things that make the adoption behaviour-preserving
 * rather than merely tidy: `?view=` is still the canonical tab selector, `?tab=`
 * is accepted as the house-standard alias, `page` is never a tab selector, the
 * pager parameter survives verbatim, an action verb still reaches the tab
 * controller (CRM's controllers own their verbs), and access is enforced once,
 * against the resolved tab's own area.
 *
 * @BABOK Related: FR-CRM-001
 */
class CrmFrontControllerTest extends TestCase
{
    protected function setUp(): void
    {
        RecordingTabController::$dispatched = null;
        RecordingTabController::$get        = [];
        $GLOBALS['__fa_hook_responders']    = [];
        $_GET                              = [];
        $_REQUEST                          = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['__fa_hook_responders'] = [];
        $_GET                           = [];
        $_REQUEST                       = [];
    }

    /**
     * run() echoes the sub-menu; swallow it so the test output stays readable.
     *
     * @param CrmFrontController $controller Controller under test
     * @return void
     */
    private function runSilently(CrmFrontController $controller): void
    {
        ob_start();
        try {
            $controller->run();
        } finally {
            ob_end_clean();
        }
    }

    private function controller(AbstractAppShell $shell, array $options = []): CrmFrontController
    {
        return new CrmFrontController(
            $shell,
            'SA_CRM_DASHBOARD',
            '',
            $options + ['security_checker' => static function (string $area): void {
                $GLOBALS['__fa_checked_area'] = $area;
            }]
        );
    }

    /**
     * @return TestCrmShell Shell carrying one controller tab and one page tab
     */
    private function shell(): TestCrmShell
    {
        $shell = new TestCrmShell();
        $shell->registerTab(new TabRegistration(
            'dashboard',
            '&Dashboard',
            'SA_CRM_DASHBOARD',
            RecordingTabController::class
        ));
        $shell->registerTab(new TabRegistration(
            'contacts',
            'Contacts',
            'SA_CRM_CONTACT_RELATIONSHIPS',
            RecordingTabController::class
        ));
        $shell->registerTab(new TabRegistration('page_tab', 'Page Tab', '', null));

        return $shell;
    }

    public function testViewParameterIsTheCanonicalTabSelector(): void
    {
        $_GET = ['view' => 'contacts'];

        $this->assertSame('contacts', $this->controller($this->shell())->resolveTabKey());
    }

    public function testTabParameterIsAcceptedAsAnAlias(): void
    {
        $_GET = ['tab' => 'contacts'];

        $this->assertSame('contacts', $this->controller($this->shell())->resolveTabKey());
    }

    public function testViewWinsOverTheAlias(): void
    {
        $_GET = ['view' => 'contacts', 'tab' => 'page_tab'];

        $this->assertSame('contacts', $this->controller($this->shell())->resolveTabKey());
    }

    public function testUnrecognisedViewFallsBackToTheDefaultTab(): void
    {
        $_GET = ['view' => 'no_such_tab'];

        $this->assertSame('dashboard', $this->controller($this->shell())->resolveTabKey());
    }

    public function testPageIsNeverATabSelector(): void
    {
        $_GET = ['page' => 'contacts'];

        $this->assertSame(
            'dashboard',
            $this->controller($this->shell())->resolveTabKey(),
            'the pager position must never be read as a tab selector'
        );
    }

    public function testGetIsNotNormalisedSoPagerLinksKeepTheView(): void
    {
        $_GET = ['view' => 'contacts', 'page' => '3'];

        $controller = $this->controller($this->shell());
        $controller->normaliseRequest();

        $this->assertSame(
            ['view' => 'contacts', 'page' => '3'],
            $_GET,
            'AbstractTabController::preserveParams() copies $_GET[\'view\'] onto pager links'
        );
    }

    public function testSecurityAreaIsTheResolvedTabsOwnArea(): void
    {
        $_GET = ['view' => 'contacts'];

        $this->assertSame(
            'SA_CRM_CONTACT_RELATIONSHIPS',
            $this->controller($this->shell())->getSecurityArea(),
            'the pre-session area must be the tab area, not the CRM fallback'
        );
    }

    public function testSecurityAreaFallsBackForATabWithoutOne(): void
    {
        $_GET = ['view' => 'page_tab'];

        $this->assertSame(
            'SA_CRM_DASHBOARD',
            $this->controller($this->shell())->getSecurityArea()
        );
    }

    public function testRunBootsTheShellThenEnforcesTheTabsOwnArea(): void
    {
        $GLOBALS['__fa_hook_responders']['crm_register_tabs'] = [
            static function (array &$data): void {
                $data['tabs'][] = new TabRegistration(
                    'insurance_policies',
                    'Policies',
                    'SA_INSURANCE_VIEW',
                    RecordingTabController::class
                );
            },
        ];
        $_GET = ['view' => 'insurance_policies'];

        $controller = $this->controller($this->shell());
        $this->runSilently($controller);

        $this->assertSame(
            'SA_INSURANCE_VIEW',
            $GLOBALS['__fa_checked_area'] ?? null,
            'a contributed tab must be reachable and checked against its own area'
        );
        $this->assertSame('recording', RecordingTabController::$dispatched);
        $this->assertSame('insurance_policies', $controller->getResolvedTab());
    }

    public function testRunDispatchesTheRequestedTab(): void
    {
        $_GET = ['view' => 'contacts'];

        $this->runSilently($this->controller($this->shell()));

        $this->assertSame('SA_CRM_CONTACT_RELATIONSHIPS', $GLOBALS['__fa_checked_area'] ?? null);
        $this->assertSame('recording', RecordingTabController::$dispatched);
    }

    public function testRunLeavesGetAloneForTheTabController(): void
    {
        $_GET = ['view' => 'contacts', 'page' => '2'];

        $this->runSilently($this->controller($this->shell()));

        $this->assertSame(
            ['view' => 'contacts', 'page' => '2'],
            RecordingTabController::$get
        );
    }

    public function testActionVerbStillReachesTheTabController(): void
    {
        $_GET     = ['view' => 'contacts'];
        $_REQUEST = ['view' => 'contacts', 'action' => 'save'];

        $this->runSilently($this->controller($this->shell()));

        $this->assertSame(
            'recording',
            RecordingTabController::$dispatched,
            'CRM tab controllers own their verbs; a verb must not short-circuit the tab'
        );
        $this->assertSame(
            'SA_CRM_CONTACT_RELATIONSHIPS',
            $GLOBALS['__fa_checked_area'] ?? null,
            'the access check happens before the action path'
        );
    }

    public function testTheRealShellPublishesViewAsItsParameter(): void
    {
        $shell = new CrmAppShell();

        $this->assertSame('crm_register_tabs', $shell->getRegisterHook());
        $this->assertSame('dashboard', $shell->getDefaultView());
        $this->assertNotNull($shell->getTab('contacts'));
    }

    public function testDefaultOptionsPinCrmsPublishedUrlSurface(): void
    {
        $options = CrmFrontController::defaultOptions();

        $this->assertSame('view', $options['tab_param']);
        $this->assertSame('tab', $options['alias_param']);
        $this->assertFalse($options['normalise_get']);
        $this->assertFalse($options['load_ui_includes']);
    }

    public function testExplicitOptionsOverrideTheCrmDefaults(): void
    {
        $_GET = ['selector' => 'contacts'];

        $controller = $this->controller(
            $this->shell(),
            ['tab_param' => 'selector', 'alias_param' => '']
        );

        $this->assertSame('contacts', $controller->resolveTabKey());
    }
}