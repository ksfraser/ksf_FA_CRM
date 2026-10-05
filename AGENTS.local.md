# AGENTS.local.md — ksf_FA_CRM working notes

Findings that are specific to this repo. Cross-module decisions live in the
shared `AGENTS.md` / `AGENTS_ARCH.md`.

---

## `0_ksf_crm_*` is the only real prefix (bug fixed in `4db5a07`)

Every repository under `src/Ksfraser/FA/CRM/Repository/` declares its table
*unprefixed* and the code prepends `TB_PREF`, so the declared name must be
`ksf_crm_*`.

The repositories shipped `fa_crm_*`, producing `0_fa_crm_*` — tables that have
never existed. `FaRepositoryTrait` swallows the db error and returns an empty
row set, so every list tab rendered "no records" with **no diagnostic anywhere**
in the UI, the logs, or the test suite. This is the single most expensive class
of bug in this module: a silent empty result reads as "no data yet".

Rules:
- New repository → declare `'ksf_crm_<name>'`. `sql/` files use the literal
  `0_` form because FA's `db_import()` only rewrites `0_` → `TB_PREF`.
- `tests/Unit/RepositoryTableNameTest.php` locks this per repository and fails
  on any reintroduction of the `fa_crm_` literal. Run it when adding a repo.
- `TagsRepository` is deliberately **not** `ksf_crm_` — it targets FA core's
  `0_tags`. The test asserts this so the rename is not "completed" by accident.
- When a list looks empty, first `SHOW TABLES LIKE '0_%crm%'` and compare against
  the declared name. Do not trust the UI.

---

## Extension security area codes are REASSIGNED at runtime

`SS_CRM` is declared as `114 << 8` in `hooks.php`, and areas as `SS_CRM | 1 … |20`.
**Those are not the codes FA enforces.** `add_access_extensions()`
(`includes/access_levels.inc`) walks `$installed_extensions` and *reassigns*
every section and area code:

```
$scode = 100; $acode = 100; $extcode = $extid << 16;
section_code = ($scode++ << 8) | $extcode
area_code    = ($acode++ << 8) | $extcode
```

So on this pod `SS_CRM` 29184 → runtime section **1205248**, and the 20 CRM
areas land on **1205348…1205367** in `0_security_roles.areas`. Consequences:

- Never reason about `SS_CRM | N` when debugging a permission. Look up
  `$security_areas['SA_…'][0]` at runtime.
- The `SA_*` **string** is the stable contract; the integer is not.
- **`add_access_extensions()` must be called before `can_access()`**, or the
  area is simply `UNDEFINED` and `can_access()` returns `false`. Any throwaway
  probe script that includes only `session.inc` will wrongly conclude that a
  granted area is denied.
- `0_security_roles` also carries stale grants from an ancient numbering scheme
  (sections `6244<<8`, `4708<<8`, …). They match nothing in the current
  102–156 range. Role 2 holding them is harmless, but do not read them as
  evidence that a module's areas are granted.

---

## `crm_register_tabs` — how other modules add tabs

`AbstractAppShell::boot()` fires `hook_invoke_all('crm_register_tabs', $data)`
and merges whatever responders push into `$data['tabs']`. So a contributing
module implements (FA 2.4 class hooks):

```php
public function crm_register_tabs(&$data)
{
    $data['tabs'][] = [
        'key'             => 'insurance_policies',
        'label'           => _('Policies'),
        'security'        => 'SA_INSURANCE_VIEW',
        'controller_class'=> 'Insurance\\PoliciesTabController',
        'fa_type'         => 1,
    ];
}
```

**`index.php` must call `boot()` before it resolves `?view=` and assigns
`$page_security`.** That ordering was inverted (fixed in `4db5a07`), which made
a contributed tab unreachable by direct URL (it silently fell back to the CRM
default view) and left `$page_security` on the CRM default so the tab's own
access area was never enforced. `tests/Unit/TabExtensionTest.php` guards both
the behaviour and the source order of `index.php` itself.

Verified live on `ksfii_app-fa` (8090) with a temporary self-registering probe
responder: the tab merged, the deep link did not fall back, and pointing it at
an inaccessible area produced FA's real denial ("The security settings on your
account do not permit you to access this function"). The probe has been
removed; `hooks.php` contains no `crm_register_tabs` responder.

---

## Test-suite conventions

- PHPUnit **9.6** with a `coverage` element (`<source>` is PHPUnit 10 syntax and
  silently disables coverage here). `config.platform.php` is pinned to 7.4.33.
- `tests/stubs.php` supplies the FA globals plus `hook_invoke_all` and
  `add_access_extensions` stubs. `hook_invoke_all` dispatches to callables in
  `$GLOBALS['__fa_hook_responders'][$hook]`, so the extension path is testable
  without booting FA.
- Baseline at `4db5a07`: **148 tests, 339 assertions, green**.