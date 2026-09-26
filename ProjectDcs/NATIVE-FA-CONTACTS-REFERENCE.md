# Native FA Contacts Reference & DAO Layering

**Status:** Reference — verified against FA 2.4.3 source + the live `ksf_fa` DB (2026-09-25).
**Purpose:** Record the native persons/contacts model, the native function API, and the
`ksfraser/fa-classes` DTO/DAO layer that the CRM Repository must wrap — so Cluster D
(#14, #15, #30) does not re-invent an abstraction that already exists.
**BABOK Related:** FR-CRM-007-002, FR-CRM-007-001

---

## 1. Native tables (system of record)

FA 2.4.3 already implements the shared person/contact layer. The CRM must be a
*client* of it, not a second store (the flat `0_fa_crm_contacts` table is the
#14/#15 duplication bug).

### 1.1 `TB_PREF."crm_persons"` — the person profile

| Column | Type | Notes |
|---|---|---|
| `id` | int PK | referenced by `crm_contacts.person_id` |
| `ref` | varchar | **required** by native editor; user-visible reference |
| `name` | varchar | **First name** (native editor labels it "First Name") |
| `name2` | varchar | **Last name** (native editor labels it "Last Name") |
| `address` | text | free-form, single field |
| `phone`, `phone2`, `fax` | varchar | `phone2` = secondary |
| `email` | varchar | |
| `lang` | varchar | document language |
| `notes` | text | **no DEFAULT** (see hazard H1) |
| `inactive` | tinyint | soft delete |

**There are no I/F/B or CASL/consent columns.** Native FA has no consent concept.

### 1.2 `TB_PREF."crm_contacts"` — the person↔entity XREF

| Column | Type | Notes |
|---|---|---|
| `id` | int PK | a single *link* row |
| `person_id` | int | → `crm_persons.id` |
| `type` | varchar | category type, e.g. `customer`, `cust_branch`, `supplier`, `user` |
| `action` | varchar | e.g. `general`, `delivery`, `invoice`, `order` |
| `entity_id` | varchar | the entity the person is linked to |

This **is** the xref (not a plain FK column), which is what #15 asked about. One
person can hold **many** `crm_contacts` rows → the same person can be attached to
several customer branches without re-keying. Native FA's per-branch contact editor
re-enters a person per branch; the xref removes that duplication.

### 1.3 `TB_PREF."crm_categories"` — the role catalog

`(id, type, action, name, description, system, inactive)`. Rows define the legal
`(type, action)` pairs; `crm_contacts` rows reference them. `system=1` rows are
protected from deletion (`delete_crm_category` refuses `system<>0`).

Verified seed values: `type` ∈ `customer`, `cust_branch`, `supplier`, `user`;
`action` ∈ `general`, `delivery`, `invoice`, `order`.

### 1.4 Related native tables

- `TB_PREF."cust_branch"` — native **customer branches** (NOT `debtor_branch`).
  This is the entity a `cust_branch`-type contact attaches to.
- `TB_PREF."debtors_master"` — the customer/account record.

---

## 2. Native function API — `includes/db/crm_contacts_db.inc`

### 2.1 Person lifecycle

```php
add_crm_person($ref, $name, $name2, $address, $phone, $phone2, $fax,
               $email, $lang, $notes, $cat_ids = null, $entity = null): ?int
update_crm_person($id, $ref, $name, $name2, $address, $phone, $phone2, $fax,
                  $email, $lang, $notes, $cat_ids, $entity = null, $type = null): ?int
delete_crm_person($person, $with_contacts = false)
```

`add_crm_person` / `update_crm_person` wrap their work in
`begin_transaction()` / `commit_transaction()` and, when `$cat_ids` is supplied,
call `update_person_contacts()` to (re)write the xref rows.

### 2.2 Reads

```php
get_crm_person($id)                                  // full person + ['contacts' => category ids]
get_crm_persons($type = null, $action = null, $entity = null,
                $person = null, $unique = false)    // mysqli result set (not an array!)
get_person_contacts($id)                             // int[] of crm_categories.id
```

> `get_crm_persons()` returns a **mysqli result handle**, not rows — callers must
> `db_fetch()` in a loop. It also silently **falls back to `action='general'`** when a
> non-general action yields no rows (line ~115). Row shape is a 3-table join
> (`crm_persons p, crm_categories t, crm_contacts r`) with `p.*` and `t.*` both
> selected — **column names collide** (`id`, `type`, `action`, `name`, `inactive`…).
> Read `person_id`/`contact_id` carefully; do not assume `id` is the person.

### 2.3 Contact (xref) manipulation

```php
add_crm_contact($type, $action, $entity_id, $person_id)
delete_crm_contact($id)                                       // by xref row id
delete_crm_contacts($person_id = null, $type = null, $entity_id = null, $action = null)
delete_entity_contacts($class, $entity)                       // + orphan person cleanup
update_person_contacts($id, $cat_ids, $entity_id = null, $type = null)
get_crm_contact($id)
is_crm_category_used($id)
```

`update_person_contacts()` **deletes then re-inserts** the xref rows for a person
(optionally scoped by `$type`). Because the INSERT derives `type`/`action` from
`crm_categories` via `SELECT ... WHERE t.id IN (...)`, the `$cat_ids` must be
**category ids**, not raw type/action strings.

### 2.4 Category CRUD

```php
add_crm_category($type, $action, $name, $description)
update_crm_category($selected_id, $type, $action, $name, $description)
delete_crm_category($selected_id)      // refuses system categories
get_crm_categories($show_inactive)     // mysqli result set
get_crm_category($selected_id)         // assoc row
get_crm_category_name($id)
```

### 2.5 Native UI SRP — `includes/ui/contacts_view.inc`

`class contacts extends simple_crud`, constructed as
`new contacts($name, $entityId, $class, $subclass = null)`.
Fields: `ref, name, name2, address, phone, phone2, fax, email, lang, notes`, plus
`'assgn' => ['fld' => 'contacts']` bound to `crm_category_types_list_row(..., ['multi' => true])`.

> **Not directly reusable inside our AppShell tabs.** It is hard-wired to native
> `simple_crud` POST conventions (`{$this->name}Edit[{$id}]`, `$_POST['assgn']`) and
> to native `page()`/`end_page()`. The **data functions** (§2.1–2.4) are the
> reusable part; the UI class is a reference for field order and validation rules
> (`insert_check()` requires non-empty `name`, non-empty `ref`, and ≥1 category).

---

## 3. `ksfraser/fa-classes` — the DTO/DAO layer (USE THIS)

**Package:** `ksfraser/fa-classes` (Packagist) · dev tree `~/Documents/ksf_FA_Classes`
**Namespaces:** `FrontAccounting\` → `src/FrontAccounting/`, `Ksfraser\FA\` → `src/Ksfraser/FA/`
**Deps:** `ksfraser/ksf-modules-dao ^0.5`, `ksfraser/validation ^0.1`, `php >= 7.4`
Also vendored by `ksf_FA_DataIntegrity` and `ksf_FA_InvoiceAllocation`.

### 3.1 CRM DTOs (immutable value objects, getters only)

| DTO | File | Fields |
|---|---|---|
| `FrontAccounting\DTO\CrmPerson` | `DTO/CrmPerson.php` | `id, ref, name, name2, address, phone, phone2, fax, email, lang, notes, inactive` |
| `FrontAccounting\DTO\CrmContact` | `DTO/CrmContact.php` | `id, personId, type, action, entityId` |
| `FrontAccounting\DTO\CrmCategory` | `DTO/CrmCategory.php` | category catalog row |
| `FrontAccounting\DTO\CustomerBranch` | `DTO/CustomerBranch.php` | native customer branch |
| `FrontAccounting\DTO\DebtorMaster` | `DTO/DebtorMaster.php` | customer/account record |

`CrmPerson` and `CrmContact` map 1:1 onto the native tables in §1. `name`=first,
`name2`=last, matching the native editor.

### 3.2 CRM Repositories

`FrontAccounting\Repository\CrmPersonRepository` (`tableName = 'crm_persons'`):

```php
findById(int $id): ?CrmPerson
findByRef(string $ref): ?CrmPerson
findByEmail(string $email): array   // CrmPerson[]
findActive(): array                 // CrmPerson[]  (inactive = 0, name ASC)
search(string $query): array        // CrmPerson[]  (name/ref/email LIKE, LIMIT 50)
```

`FrontAccounting\Repository\CrmContactRepository`:

```php
findById(int $id): ?CrmContact
findByPerson(int $personId): array                              // CrmContact[]
findByEntity(string $type, string $action, string $entityId)    // CrmContact[]
findByType(string $type): array                                 // CrmContact[]
findPersonContacts(int $personId): array                       // category ids
```

Also available: `CrmCategoryRepository`, `CustomerBranchRepository`,
`DebtorMasterRepository`.

**Gap (H2):** these repositories are **read-only**. There are no
`createPerson`/`updatePerson`/`addContact`/`deleteContact` methods. Writes must go
through the generic `RepositoryTrait` primitives (§3.3) or the native functions
(§2) — see the layering decision in §5.

### 3.3 Generic write/query primitives — `FrontAccounting\Repository\BaseRepository` + `RepositoryTrait`

```php
__construct(DbAdapterInterface $db)          // sets $this->prefix = $db->getTablePrefix()

find(array $conditions, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
findOne(array $conditions): ?object
findWhere(...): array          findOneWhere(array $conditions): ?array
countWhere(array $conditions): int
existsWhere(array $conditions): bool
paginate(array $conditions, int $page = 1, int $perPage = 25, array $orderBy = []): PaginatedResult
insert(array $data): int       // returns new id
update(array $data, array $conditions): int
deleteWhere(array $conditions): int
```

`getTableName()` reads `$this->tableName`; throws `BadMethodCallException` if a
subclass sets neither the property nor overrides it.

---

## 4. `ksfraser/ksf-modules-dao` — the DB adapter (runtime transport)

**Namespace:** `Ksfraser\ModulesDAO\` → `src/Ksfraser/` (so `Ksfraser\ModulesDAO\Db\…`
lives in `src/Ksfraser/Db/`). *The directory name and the namespace differ — this is
not a bug, do not "fix" it.*

### 4.1 `DbAdapterInterface` (`Db/DbAdapterInterface.php`)

```php
getDialect(): string
getTablePrefix(): string
escape(string $value): string
query(string $sql, array $params = []): array
execute(string $sql, array $params = []): int
lastInsertId(): ?int
```

### 4.2 `FrontAccountingDbAdapter` (`Db/FrontAccountingDbAdapter.php`) — the FA runtime impl

Satisfies the hard rule "FA modules MUST use native `db_*` at runtime": it delegates
to `db_query()`, `db_fetch_assoc()`, `db_num_affected_rows()`, `db_insert_id()`.
It never touches a PDO handle or raw mysqli. `getDialect()` returns `'mysql'`.
If `db_query` is not defined (non-FA context) it degrades to `[]` / `0` / `null`
— which is what makes the DTO/DAO layer unit-testable standalone.

Placeholder binding: `?` params are substituted with `addslashes($param)` in
document order (FA has no prepared statements).

> **Hazard H3 — `addslashes` instead of `db_escape`.** The adapter's `escape()` and
> its `?`-binding both use `addslashes()`. FA's own `db_escape()`
> (`includes/db/connect_db_mysqli.inc:135`) does `html_entity_decode()` first and
> then escapes against the connection charset. `addslashes` is **not**
> charset-safe (e.g. GBK/multi-byte boundary cases). Prefer FA's `db_escape()` for
> any value that reaches SQL through a path you control; treat the adapter as
> convenience-only until it delegates to `db_escape`.

### 4.3 `DatabaseAdapterFactory` — DO NOT USE AT RUNTIME (hazard H4)

`Factory/DatabaseAdapterFactory.php` provides
`create(string $driver = 'fa', string $tablePrefix = '')`.

It is written with a **`match` expression (PHP 8.0+)**:

```php
return match ($driver) {
    'fa' => new FrontAccountingDbAdapter($tablePrefix),
    default => throw new InvalidArgumentException(...),
};
```

**Verified: this file is a parse error on the FA container's PHP 7.4.33.**

```
$ podman exec ksfii_app-fa php -l .../DatabaseAdapterFactory.php
Parse error: syntax error, unexpected '=>' (T_DOUBLE_ARROW) on line 14
```

Because PSR-4 autoloading is lazy, this only bites if the class is actually
referenced — but referencing it inside FA is an immediate fatal. **Instantiate
`new FrontAccountingDbAdapter(TB_PREF)` directly.** The factory should be
rewritten with `switch` (or the package's PHP floor raised to 8.0, which the
cross-module 7.3/7.4 floor forbids).

---

## 5. Layering decision for the CRM

```
Ksfraser\FA\CRM\Controller\ContactsTabController
        │  (SRP: tab/UI only — no SQL)
        ▼
Ksfraser\FA\CRM\Service\ContactsService
        │  (SRP: use-cases, validation, I/F-B + CASL rules)
        ▼
Ksfraser\FA\CRM\Repository\PersonsRepository        <-- NEW, Cluster D
        │  (SRP: translates CRM concepts ⇄ native DTOs;
        │   adds the person-profile side-table; owns no SQL of its own)
        ├──────────────────────────────► FrontAccounting\Repository\CrmPersonRepository
        ├──────────────────────────────► FrontAccounting\Repository\CrmContactRepository
        │                                        │
        │                                        ▼
        │                            Ksfraser\ModulesDAO\Db\DbAdapterInterface
        │                                        │  (DI)
        │                                        ▼
        │                            Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter
        │                                        │  → db_query / db_fetch_assoc /
        │                                        │    db_num_affected_rows / db_insert_id
        │                                        ▼
        │                              FA 2.4.3  crm_persons / crm_contacts / crm_categories
        │
        └──────────────────────────────► 0_fa_crm_person_profiles  (I/F-B + CASL, module-owned)
```

**Rules that follow from this:**

1. `PersonsRepository` composes the `fa-classes` repositories. It must **not** issue
   raw SQL against `crm_*` — that is exactly the duplication being removed.
2. It is constructed with the `DbAdapterInterface` (DI), so unit tests inject a
   fake adapter and never touch a database. This is how the module's PHPUnit suite
   stays database-free.
3. **Writes:** because `CrmPersonRepository`/`CrmContactRepository` are read-only
   (H2), the Repository uses `RepositoryTrait::insert()/update()/deleteWhere()` for
   single-table writes, and calls the **native** functions from §2 only where their
   transaction + category-link semantics are genuinely required
   (`add_crm_person`/`update_crm_person` for create/edit-with-links,
   `update_person_contacts` for re-linking). Native functions must be guarded with
   `function_exists()` and injected as callables so tests stay database-free.
4. `0_fa_crm_person_profiles` (I/F-B + CASL) stays **module-owned** — native FA has
   no consent concept, and this must not become a core-table ALTER.
5. Do **not** reference `DatabaseAdapterFactory` (H4). Do **not** let
   `addslashes`-escaped values be the last line of defence (H3).

---

## 6. Hazards summary

| # | Hazard | Impact | Mitigation |
|---|---|---|---|
| H1 | `crm_persons.notes` (and other columns) have **no DEFAULT** | `INSERT INTO crm_persons (ref,name,email,inactive) VALUES (...)` throws `Field 'notes' doesn't have a default value` (observed in `ksf_FA_HRM/e2e_hrm_event_windows.php`) | Always pass the full column set, or use `add_crm_person()` |
| H2 | `CrmPersonRepository`/`CrmContactRepository` are **read-only** | No create/update/delete for persons or xrefs | Use `RepositoryTrait` writes + native fns per §5 rule 3 |
| H3 | `FrontAccountingDbAdapter` escapes with `addslashes`, not `db_escape` | Not charset-safe | Prefer `db_escape()` for controlled paths |
| H4 | `DatabaseAdapterFactory` uses PHP 8 `match` | **Parse error / fatal on FA's PHP 7.4** | Never reference it; `new FrontAccountingDbAdapter(TB_PREF)` |
| H5 | `get_crm_persons()` returns a **mysqli result set** and falls back to `action='general'`; its 3-table `SELECT p.*, t.*` **collides column names** | Silent wrong data if consumed as arrays/assumed keys | Use the `fa-classes` repositories (typed DTOs) instead |
| H6 | `ksf-fa-common` ships `src/template_hooks.php` — an **unsubstituted template** (`<section_num>`, `ksf_FA_<Module>`) that is **not valid PHP** | Any scanner/`PluginRegistry::discover()`/`require` that sweeps `src/` fatals with a parse error | Rename to a non-`.php` extension (e.g. `template_hooks.php.dist`) so it is not loadable |
