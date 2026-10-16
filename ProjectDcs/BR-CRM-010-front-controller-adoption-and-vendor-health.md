# BR-CRM-010 — CRM Front Controller Adoption & Vendor Health

**Modules:** `ksf_FA_CRM`
**Shared dependency:** `ksfraser/ksf-fa-common` **v1.0.14**
**Status:** Approved and **implemented** — commits `f3ee56d` (namespace refactor, the 500s' true origin), `dd94bc4` (`$path_to_root` depth), `a81db58` (adopt `AbstractFrontController`).
**Source of record:** `ksf_Architecture_Docs/CRM_FrontController_Adoption.md`.
**Target:** `http://localhost:8090`, container `ksfii_app-fa`, PHP **7.4.33**.
**Date:** 2026-10-13

---

> **Repository note.** This document lives in `~/Documents/ksf_FA_CRM/ProjectDcs/`
> because that is the repo that owns `index.php`, `src/FrontAccounting/CRM/App/`
> and the vendor tree the 500s came from. `~/Documents/ksf_CRM/` is the separate
> **framework-agnostic business-logic library** (`ksfraser/ksf-crm`, PSR-4
> `ksfraser\CRM\`) and holds none of this code. Anyone looking for the CRM
> requirements in `ksf_CRM/ProjectDcs/` will not find them there.

---

## Business Need (BABOK What)

CRM's whole user-facing surface — sixteen views — hangs off a single
`modules/ksf_FA_CRM/index.php`. In production that page was returning **HTTP 500
with an empty body on every URL, authenticated or not**, because the deployed
Composer's PSR-4 map predated the namespace refactor and could not resolve
`CrmAppShell`. `index.php` constructs the shell *before* `session.inc`, so the
fatal happened ahead of any access check.

Separately, CRM's own `index.php` preamble was the same hand-rolled sequence every
other module carried: resolve `?view=`, decide a security area, include
`session.inc`, render the sub-menu, dispatch. That preamble hard-coded
`$page_security = 'SA_CRM_DASHBOARD'` for **all sixteen** views, so a user holding
a tab's own area but not the dashboard area was refused — by accident, and then
allowed nowhere.

## Business Requirement

1. **No CRM URL may return a 500.** The deployed vendor must resolve the
   module's own namespaces.
2. **One front controller** for the whole CRM app, adopting
   `ksfraser\FrontAccounting\Common\App\AbstractFrontController` rather than
   hand-rolling the preamble.
3. **CRM's published URL surface must not change.** `?view=` is what CRM has
   always emitted (the in-page sub-menu, `AdminHubTabController`, and the RBAC
   application menu in `hooks.php` all mint it). `?tab=` is accepted as the
   house-standard alias but never emitted.
4. **CRM pagination must not regress.** `AbstractTabController::preserveParams()`
   defaults to `['view']` and `MasterSummaryTable::pagerUrl()` copies those
   `$_GET` keys verbatim; normalising the request would silently strip the tab off
   every pager link, and `OptionListsTabController` reads `$_GET['view']` directly
   to tell the four option-list tabs apart.
5. **Access is resolved against the requested tab's own area**, pre-session —
   which is what the old file's own comment said it wanted.

## Root cause of the 500s — stated plainly

The 500 was a **stale deployed `vendor/`**, not a routing bug and not the
controller adoption. **Adopting the controller neither caused nor cured it;
`composer install --no-dev` in the deployed tree cured it.** The controller work
is independent: the old `index.php` would have worked just as well with a fresh
vendor, and the new one needs it just as much.

The stale map still carried the pre-refactor entries and had **no** entry for
`ksfraser\FrontAccounting\CRM\` at all — it predated commit `f3ee56d`:

```php
'Ksfraser\\FA\\CRM\\Services\\' => $baseDir . '/includes',
'Ksfraser\\FA\\CRM\\'           => $baseDir . '/src/Ksfraser/FA/CRM',
'Ksfraser\\FA\\'                => vendor/ksfraser/fa-classes/src/Ksfraser/FA,
// ...and no 'ksfraser\\FrontAccounting\\CRM\\' => src/FrontAccounting/CRM
```

After `composer install --no-dev`:

```php
'ksfraser\\FrontAccounting\\CRM\\'    => $baseDir . '/src/FrontAccounting/CRM'
'ksfraser\\FrontAccounting\\Common\\' => vendor/ksfraser/ksf-fa-common/src
```

**Diagnostic rule:** a module that returns a bare 500 with an empty body on every
URL, *before* any access check, is an **autoloader** problem until proven
otherwise. Read `vendor/composer/autoload_psr4.php` in the **deployed** tree
(`fa_modules/<module>/vendor/`), not the dev tree — they diverge routinely, and
`src/Ksfraser/FA/CRM/Services/` still exists in the module as an empty leftover
directory from the refactor, which makes the stale map look plausible.

## Two other 500s that are NOT CRM

| URL | Cause | Owner |
|---|---|---|
| `access/login.php` (requested directly) | `die(_("Restricted access"))` fires the guard before gettext is loaded → `Call to undefined function _()`. Pre-existing core FA behaviour; the file is meant to be `include`d by `session.inc`. | core `FA/2.4.3` — separate repo, **not fixed** |
| `modules/ksf_FA_CRM/pages/index.php` | Autoloader symptom with a **different** root cause underneath: an untouched scaffold stub whose `new \ksf\App()` names a class that does not exist. Refreshing the vendor cannot fix it. `tests/Unit/PageStructureTest.php` even pins `new \ksf\App()`. | **left alone deliberately** — 500 before, nothing links to it, and "fixing" it means deciding whether the page should exist |

## Acceptance

| ID | Criterion |
|---|---|
| ARI-1 | `index.php` and all fifteen `?view=` URLs return 200 (FA's inline login form counts — that proves the pre-session path). |
| ARI-2 | `?view=bogus_tab` returns 200, falling back to `dashboard`. |
| ARI-3 | `?view=contacts&page=2` returns 200. |
| ARI-4 | `?tab=contacts` (house alias) returns 200. |
| ARI-5 | The nineteen standalone `pages/*.php` URLs are unchanged at 200. |
| ARI-6 | Site root and unrelated core pages stay 200 throughout the change. |
| ARI-7 | `$_GET` is **not** normalised; `['view','page']` survive verbatim. |
| ARI-8 | `getSecurityArea()` returns the requested tab's own area, falling back to `SA_CRM_DASHBOARD`. |
| ARI-9 | A tab contributed through `crm_register_tabs` is reachable and checked against **its own** area (`SA_INSURANCE_VIEW`, not the fallback). |
| ARI-10 | Every CRM POST save still works — i.e. `dispatchAction()` dispatches the tab. |
| ARI-11 | The deployed vendor carries **no** dev packages. |
| ARI-12 | `hooks.php` is untouched (26 functions before and after). |

## Traceability

- FR-CRM-010-001 — front controller adoption
- FR-CRM-010-002 — deployed vendor integrity / the 500s
- Implemented by: `src/FrontAccounting/CRM/App/CrmFrontController.php` (new, 156 lines), `index.php` (68 → 93 lines), `tests/Unit/CrmFrontControllerTest.php` (new, 16 cases), `composer.lock` (v1.0.12 → v1.0.14)