# FR-CRM-010-002 — Deployed vendor integrity (CRM 500s)

@BABOK Related: BR-CRM-010; AGENTS_ARCH.md §7 (dev/deploy workflow) and §12
          (FA module packaging).
@UML : n/a — operational requirement, no class in the shared package.
Status: Approved — BABOK; **implemented** (`composer install --no-dev` in the
       deployed tree; `composer update ksfraser/ksf-fa-common` in the dev tree).
Module: ksf_FA_CRM.

---

## Need (BABOK What-not-How)

Every CRM URL — `index.php`, all sixteen `?view=` values, and an unknown-tab
probe — returned **HTTP 500 with an empty body**, authenticated or not, while the
rest of the site stayed healthy. The business impact is that CRM was entirely
unusable, and the visible symptom (a bare 500 on an authenticated page) pointed
somewhere other than the truth.

## Requirement

1. **No module URL may return a bare 500 ahead of the access check.** A fatal
   before `session.inc` is an application-construction failure, not an access
   decision.
2. **The deployed `vendor/` shall be regenerated from the module's committed
   `composer.json`** — `composer install --no-dev` in
   `fa_modules/ksf_FA_CRM`. The dev tree and the deployed tree are **separate
   Composer installations** and diverge routinely.
3. **The deployed vendor shall contain no dev packages.** `--no-dev` means no
   PHPUnit, no `sebastian/*`, no `doctrine/instantiator`, no `myclabs/deep-copy`,
   no `nikic/php-parser`, no `theseer/*`, no `phar-io/*`.
4. **`config.platform.php` shall pin `7.4.33`** so the vendor resolves for the
   container's PHP and not the host's (8.1). This is the estate-wide rule in
   `AGENTS_ARCH.md` §7 — a vendor resolved for the wrong PHP is the top source of
   site-wide 500s, and `platform_check.php` failing with `E_USER_ERROR` is one of
   its shapes.
5. **A module whose vendor cannot resolve its own namespace shall be diagnosed
   from `vendor/composer/autoload_psr4.php` in the deployed tree**, not inferred
   from the class-not-found message and not read from the dev tree.

## Root cause

The deployed `vendor/composer/autoload_psr4.php` still carried the **pre-refactor**
map and had **no** entry for `ksfraser\FrontAccounting\CRM\` at all — it predated
commit `f3ee56d` ("refactor(crm): namespace to `ksfraser\FrontAccounting\CRM`"):

```php
'Ksfraser\\FA\\CRM\\Services\\' => $baseDir . '/includes',
'Ksfraser\\FA\\CRM\\'            => $baseDir . '/src/Ksfraser/FA/CRM',
'Ksfraser\\FA\\'                 => vendor/ksfraser/fa-classes/src/Ksfraser/FA,
```

`index.php:24` constructs `CrmAppShell` **before** `session.inc`, so:

```
PHP Fatal error: Uncaught Error: Class 'ksfraser\FrontAccounting\CRM\App\CrmAppShell' not found
  in /var/www/html/modules/ksf_FA_CRM/index.php:24
```

was thrown before any access check — hence a bare 500 with an empty body,
authenticated or not.

`src/Ksfraser/FA/CRM/Services/` still exists in the module as **empty leftover
directories** from the refactor. That is what made the stale map look plausible
when read from the module tree.

**Honest attribution:** adopting `AbstractFrontController` neither caused nor
cured this. `composer install --no-dev` cured it. See FR-CRM-010-001.

## How the fatal was observed

`podman logs` is **empty** on `ksfii_app-fa` — the container's log driver is
`journald`. The fatal was read from:

```bash
journalctl -t ksfii_app-fa
```

**This is the general trap: a module that fatals inside a container with a
`journald` log driver produces no output from `podman logs`, which reads as "no
error".**

## Acceptance

| ID | Criterion |
|---|---|
| ARI-1 | `index.php` and all fifteen `?view=` URLs return 200. |
| ARI-11 | `vendor/bin/` in the deployed tree is empty/absent; `vendor/composer/installed.json` → `dev-package-names` is `[]`. |
| ARI-11a | A `find vendor -maxdepth 2` for `phpunit`, `sebastian`, `doctrine`, `myclabs`, `nikic`, `php-parser`, `theseer`, `phar-io` returns nothing at the top level. |
| ARI-11b | `platform_check.php` is consistent with `config.platform.php = 7.4.33`. |
| ARI-13 | `vendor/composer/autoload_psr4.php` contains `'ksfraser\\FrontAccounting\\CRM\\' => $baseDir . '/src/FrontAccounting/CRM'` and `'ksfraser\\FrontAccounting\\Common\\' => vendor/ksfraser/ksf-fa-common/src`. |
| ARI-14 | The deployed tree is **byte-identical** to the committed files (`diff -rq` reports only the hardlinked shared docs). |

## Package inventory after the fix (deployed)

| Check | Result |
|---|---|
| Installed packages | `ksfraser/exceptions@1.3.0`, `fa-classes@1.7.0`, `gedcom@dev-main`, `html@2.0.7`, **`ksf-fa-common@1.0.14`**, `ksf-modules-dao@0.5.3`, `rbac@dev-master`, `traits@1.3.0`, `validation@0.1.0`, `psr/event-dispatcher@1.0.0`, `psr/log@1.1.4` — 11 packages, no dev |
| `dev-package-names` | `[]` |
| `vendor/bin/` | empty / absent |

## Known gap

`vendor/ksfraser/html/vendor/` — the `ksfraser/html` **package's own nested**
vendor tree — ships `phpunit 9.6.34`, `sebastian/*`, `doctrine/instantiator`,
`myclabs/deep-copy`, `nikic`, `theseer`, `phar-io`. It came in with the published
`ksfraser/html 2.0.7` dist, nothing in `vendor/ksfraser/html/src` references it,
and it is **not** reachable from CRM's `ClassLoader`. Pre-existing, proved by a
`cp -a` of the vendor taken before any of this work. Left in place: deleting
files from a Composer-managed package creates drift the next `composer install`
silently reintroduces. **The fix belongs in `ksfraser/html`'s packaging
(`.gitattributes` `export-ignore`), not in CRM.**