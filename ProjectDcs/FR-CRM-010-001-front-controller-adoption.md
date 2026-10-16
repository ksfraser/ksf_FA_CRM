# FR-CRM-010-001 — CRM front controller adoption

@BABOK Related: BR-CRM-010; FR-006-007 (field metadata consumed by the tab
          controllers); AGENTS_ARCH.md §9 (FA page security).
@UML : APP_TAB_ARCHITECTURE.md §11 (host page = thin router);
        `ksfraser\FrontAccounting\Common\App\AbstractFrontController`.
Status: Approved — BABOK; **implemented**, commit `a81db58` on `ksf_FA_CRM` main
       (pushed). Package `ksfraser/ksf-fa-common` v1.0.14.
Module: ksf_FA_CRM (dev tree `~/Documents/ksf_FA_CRM`, deployed
       `~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_CRM`).

---

## Need (BABOK What-not-How)

CRM's `index.php` was 68 lines of preamble duplicated from every other module in
the estate, and it enforced one hard-coded security area for all sixteen views.
The preamble must go; the URL surface must survive.

## Requirement

1. **`index.php` shall adopt
   `ksfraser\FrontAccounting\Common\App\AbstractFrontController`** via a thin
   subclass `src/FrontAccounting/CRM/App/CrmFrontController.php`, which supplies
   only what is genuinely CRM-specific: the page title (`_("CRM")`), the
   unconditional `<head>` date-picker bundle, the option overrides, and
   `dispatchAction()`.
2. **Two-phase bootstrap**, identical in shape to HRM's: `getSecurityArea()` →
   `$page_security` → `include session.inc` → `add_access_extensions()` →
   `include includes/ui.inc` at global scope → `run()`.
3. **Four option overrides are mandatory** to keep CRM's behaviour identical.
   `CrmFrontController::defaultOptions()` pins them and the unit tests assert
   them:

   | Option | Value | Why |
   |---|---|---|
   | `tab_param` | `'view'` | CRM has always published `?view=`. Three emitters would break otherwise: `CrmAppShell::renderMenu()` → `FAModuleMenu`; `AdminHubTabController` (`'index.php?view=' . rawurlencode(...)`); the RBAC application menu in `hooks.php` (`new FAModuleMenu('modules/ksf_FA_CRM/index.php', 'view', '')`). |
   | `alias_param` | `'tab'` | The house-standard selector is accepted so `?tab=` resolves — but never emitted. |
   | `normalise_get` | `false` | `AbstractTabController::preserveParams()` defaults to `['view']` and `MasterSummaryTable::pagerUrl()` copies those `$_GET` keys **verbatim**. Normalising would rewrite `$_GET['tab']` and unset `$_GET['view']`, so **every CRM pager link would lose the tab and land on the dashboard**. `OptionListsTabController` also reads `$_GET['view']` directly (line 44) to tell the four option-list tabs apart — the same break. Leaving `$_GET` alone is what makes the base's "`page` is never a tab selector" guarantee CRM's *only* new routing rule. |
   | `load_ui_includes` | `false` | `includes/ui.inc` reads `$path_to_root` as a global in its including scope, so it is included by `index.php` at global scope (the HRM pattern). v1.0.14 now also seeds the variable inside `loadUiIncludes()`, so this could be left on, but CRM's file already does it at global scope and the global include is the proven path. |

4. **`dispatchAction()` shall be overridden** (see Implementation notes).
5. **`getSecurityArea()` shall resolve the *requested* tab's own area
   pre-session**, not the dashboard area for everything.
6. **Registration hook.** `CrmAppShell` uses `appId = 'crm'`, so `boot()` fires
   `hook_invoke_all('crm_register_tabs', $data)`.

## Implementation notes (recorded because they are easy to undo by accident)

- **`dispatchAction()` is overridden deliberately.** `AbstractFrontController::run()`
  routes a non-empty `action` verb to `$controller->runAction($action)` and renders
  "this action is not available" if the controller has no such method.
  **CRM's tab controllers never implemented `runAction()`** — they own their verbs
  inside `run() → handlePost()` (`edit_<pk>` / `delete_<pk>` submit buttons) and the
  old `index.php` dispatched the tab unconditionally. Adopting the base unmodified
  would have turned **every POST save into a silent no-op**.
  `CrmFrontController::dispatchAction()` therefore dispatches the tab, reproducing
  today's behaviour exactly. Overriding it is safe in the way the base documents:
  `enforceSecurity()` has already run.

- **One intentional behaviour delta, in the safe direction.** The old `index.php`
  hard-coded `$page_security = 'SA_CRM_DASHBOARD'` before `session.inc`, so every
  CRM tab opened its session under the dashboard area and the tab's own area was
  enforced only later by `AbstractAppShell::dispatch()`. `getSecurityArea()` now
  resolves the requested tab's own area pre-session. Net effect: a user holding a
  tab's area but not `SA_CRM_DASHBOARD` is no longer refused at `session.inc` —
  they were refused there by accident and allowed nowhere anyway. A user holding
  neither is refused at the same place as before. Enforced once by the base and
  again by `dispatch()`; both are `check_page_security()` on the same area, so the
  double call is idempotent.

- **The source-order guard was re-pointed, not deleted.**
  `TabExtensionTest::testIndexPageBootsBeforeResolvingViewAndSecurity` used to
  assert that `index.php` calls `$appShell->boot()` before resolving `?view=` and
  before assigning `$page_security`. Those statements no longer exist in
  `index.php` — the ordering now lives inside `run()`. The invariant is pinned
  harder: `getSecurityArea()` appears before `session.inc` in the source and
  `run()` after it, and is called exactly once; `AbstractFrontController::run()` is
  `final`; `CrmFrontController::run()` is **inherited** (reflection on the
  declaring class), so boot-then-enforce cannot be reordered or dropped.

## Acceptance

See BR-CRM-010 ARI-1 … ARI-4, ARI-7 … ARI-10, ARI-12. Specifically:

- `?view=` is canonical; `?tab=` resolves; `view` beats `tab`.
- Unknown view falls back to `dashboard`; **`page` is never a tab selector**.
- `$_GET` is not normalised, so `['view','page']` survive verbatim for pager links.
- `getSecurityArea()` returns the tab's own area, falling back to `SA_CRM_DASHBOARD`.
- A tab contributed through `crm_register_tabs` is reachable **and** checked against `SA_INSURANCE_VIEW` (not the fallback); `$_GET` is handed to the tab controller unchanged.
- An `action` verb still reaches the tab controller, and the access check has already run by then.
- `CrmFrontController::run()` is inherited, not overridden (reflection).
- `hooks.php` unchanged: 26 functions before and after, verified in both trees.

## Verification performed (2026-10-13)

| Check | Result |
|---|---|
| `vendor/bin/phpunit` (PHPUnit 9.6.37, host PHP 8.1) | **OK (211 tests, 605 assertions)** — 195 pre-existing + 16 added |
| `?view=` × 17 URLs unauthenticated | all 200 (was 500) |
| `?view=bogus_tab` | 200, falls back to dashboard |
| 19 standalone `pages/*.php` | 200, unchanged |
| Site root, `/index.php`, `/admin/inst_module.php`, `/sales/sales_order_entry.php` | 200 throughout, including during the deploy and the `git stash`/fast-forward window |
| Neighbour-module sweep (every `fa_modules/` root entry point) | 200 except two non-entry points already fatal before the work |
| `php -l` host PHP 8.1 and container PHP 7.4.33 | clean on all five changed/added files |
| Container CLI run (PHP 7.4.33) | `tabs=16`, `hook=crm_register_tabs`, `area=SA_CRM_DASHBOARD`, `view=contacts -> contacts`, `tab=contacts -> contacts`, `page=contacts -> dashboard` |
| `hooks.php` | `git diff HEAD -- hooks.php` = 0 lines; 26 functions both trees |

## Known gaps / deferred

1. **Authenticated rendering was not exercised over HTTP.** No credentials are
   available for company 0, and FA's `SessionManager::preventHijacking()` binds a
   session to `REMOTE_ADDR` + user-agent, so the live browser sessions in
   `/tmp/sess_*` cannot be replayed from `curl` (`10.89.1.1` ≠ `192.168.1.147`) —
   they are wiped on use. **Every "200" above is FA's inline login form**, proving
   the pre-session path (the one that used to fatal) and nothing more. Coverage of
   the authenticated path comes from the 16 unit tests and the container CLI run.
   **Recommended follow-up: one authenticated browser pass over the CRM tabs on
   8090.**
2. **`pages/index.php` remains a 500** — scaffold, needs an owner decision.
3. **`access/login.php` 500s when requested directly** — core FA fix, separate
   repo, out of scope.
4. **`ksfraser/html`'s nested vendor** still ships dev packages (`phpunit 9.6.34`,
   `sebastian/*`, `doctrine/instantiator`, `myclabs/deep-copy`, `nikic`, `theseer`,
   `phar-io`) inside `vendor/ksfraser/html/vendor/`. Pre-existing and proved
   pre-existing (present in a `cp -a` taken before the work), not reachable from
   CRM's `ClassLoader`, so dead weight rather than the eager-autoload
   `Parse error` hazard. Left in place — deleting files from a Composer-managed
   package creates drift the next `composer install` reintroduces. Fix belongs in
   `ksfraser/html`'s own `.gitattributes` export-ignore.
5. **`src/Ksfraser/FA/CRM/Services/`** — empty leftover directories from the
   `f3ee56d` namespace refactor. Harmless, but they are what made the stale
   autoloader look plausible. Worth deleting in a separate cleanup.
6. **No CSRF token** anywhere in the adoption; not a regression, see BR-CRM-010.
7. **`APP_TAB_ARCHITECTURE.md` §11 was not updated** with the CRM adoption row at
   the time of the code change — it is hardlinked across the module repos and
   follows the intentional-write ritual, so it is a cross-repo edit, not a local
   one.