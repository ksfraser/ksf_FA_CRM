<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\CRM\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\AbstractFrontController;
use ksfraser\FrontAccounting\Common\App\TabRegistration;

/**
 * CrmFrontController — CRM's front controller, reduced to a declaration.
 *
 * Everything the old index.php preamble did by hand — resolve the tab, resolve
 * the per-tab security area, boot the shell so `crm_register_tabs` can
 * contribute tabs, render the sub-menu, enforce access, dispatch, close the
 * page — now lives in {@see AbstractFrontController} (package
 * `ksfraser/ksf-fa-common`). What is left in the subclass is what is genuinely
 * CRM-specific:
 *
 *   - the page title ("CRM");
 *   - the <head> date-picker bundle, which the CRM entry forms use;
 *   - the action verb, which CRM has always left to the tab controller.
 *
 * The four constructor defaults below are the whole reason this subclass exists
 * beyond the title and the JavaScript. They pin CRM's published URL surface:
 *
 *   tab_param      'view' — the parameter CRM has always emitted. The menu
 *                   (CrmAppShell → FAModuleMenu), the RBAC application menu in
 *                   hooks.php and AdminHubTabController all mint `?view=`, so
 *                   that stays canonical for CRM. `?tab=` is accepted as the
 *                   house-standard alias.
 *   alias_param    'tab'  — accepted, never emitted.
 *   normalise_get  false  — AbstractTabController::preserveParams() defaults to
 *                   ['view'] and MasterSummaryTable copies those $_GET keys
 *                   verbatim onto pager links. Normalising the request would
 *                   rewrite $_GET['tab'] and unset $_GET['view'], so every CRM
 *                   pager link would silently lose the tab and land on the
 *                   default view. OptionListsTabController likewise reads
 *                   $_GET['view'] to tell the four option-list tabs apart.
 *   load_ui_includes false — index.php includes includes/ui.inc at GLOBAL scope
 *                   (see that file for why it cannot move into run()).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.1.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (host page = thin router)
 * @BABOK Related: FR-CRM-001 (app shell front controller)
 */
class CrmFrontController extends AbstractFrontController
{
    /**
     * CRM's controller options, overridable per instance.
     *
     * @return array<string, mixed>
     *
     * @since 1.1.0
     */
    public static function defaultOptions(): array
    {
        return [
            'tab_param'        => 'view',
            'alias_param'      => 'tab',
            'normalise_get'    => false,
            'load_ui_includes' => false,
        ];
    }

    /**
     * @param AbstractAppShell   $appShell         CRM application shell
     * @param string              $fallbackSecurity Security area for a tab that
     *                                             declares none; never empty
     * @param string              $pathToRoot       Path to the FA root
     * @param array<string, mixed> $options         Overrides for the CRM defaults
     *
     * @throws \ksfraser\FrontAccounting\Common\App\FrontControllerSecurityException
     *         When $fallbackSecurity is empty
     *
     * @since 1.1.0
     */
    public function __construct(
        AbstractAppShell $appShell,
        string $fallbackSecurity,
        string $pathToRoot = '',
        array $options = []
    ) {
        parent::__construct(
            $appShell,
            $fallbackSecurity,
            $pathToRoot,
            $options + self::defaultOptions()
        );
    }

    /**
     * FA page title.
     *
     * @return string
     *
     * @since 1.1.0
     */
    protected function getPageTitle(): string
    {
        return _("CRM");
    }

    /**
     * Extra <head> JavaScript the resolved tab needs.
     *
     * CRM has always shipped the date picker on every tab (several entry forms
     * render date_row() controls), so this is deliberately unconditional on the
     * tab key — narrowing it would be a behaviour change, not a cleanup.
     *
     * @param TabRegistration $tab Resolved tab
     *
     * @return string
     *
     * @since 1.1.0
     */
    protected function getPageJs(TabRegistration $tab): string
    {
        if (!function_exists('user_use_date_picker') || !user_use_date_picker()) {
            return '';
        }

        return function_exists('get_js_date_picker') ? get_js_date_picker() : '';
    }

    /**
     * Route an action verb.
     *
     * CRM's tab controllers have never implemented runAction(): they own their
     * verbs inside run() → handlePost() (the `edit_<pk>` / `delete_<pk>` submit
     * buttons) and index.php has always dispatched the tab regardless of
     * `action`. The base class would otherwise route a verb to a controller
     * method that does not exist and render "action unavailable", which would
     * break every POST save. Dispatching the tab unconditionally reproduces
     * today's behaviour exactly.
     *
     * Safe to override in the way the base class documents: access has already
     * been enforced by run() before this is reached.
     *
     * @param TabRegistration $tab    Resolved tab
     * @param string          $action Action verb
     *
     * @return void
     *
     * @since 1.1.0
     */
    protected function dispatchAction(TabRegistration $tab, string $action): void
    {
        $this->getAppShell()->dispatch($tab->getKey());
    }
}