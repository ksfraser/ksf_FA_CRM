# FR-CRM-007-002 — Shared Persons / Contacts Layer (Cluster D)

**Status:** APPROVED — requirements ratified 2026-09 (BR-CRM-09); implementation pending
**Issues:** #14, #15, #30 (+ the I/F/B & consent product directive)
**Module:** ksf_FA_CRM
**Author:** KSF
**Date:** 2026-09-25
**Siblings:** FR-CRM-007-001 (contact classification), FR-CRM-007-003 (debtor
classification), FR-CRM-007-004 (opt-in consent), FR-CRM-002-001 (quote→order)

---

## 1. Problem statement

The CRM module stores contacts in its **own** table `0_fa_crm_contacts` (keyed by
`debtor_no`, flat `first_name` / `last_name` / `phone` / `email`). Native FA stores
the same real-world data in its **own** `0_crm_persons` + `0_crm_contacts` + `0_crm_categories`
model. The two are disconnected, which causes:

- **#14** — contacts created in the CRM do not appear on native FA
  `sales/manage/customer_branches.php`, and native contacts do not appear in the CRM.
  Root cause: two parallel stores for the same entity.
- **#15** — the CRM has no branch selector and writes a 2nd "customer" selector
  instead of following FA's convention (the data form belongs to the *selected
  customer branch*; disable the form when the customer is "ALL").

Additionally there is no shared person layer across CRM / HRM / Users, and no
I/F/B (individual / family / business) classification for approach + CASL
compliance.

---

## 2. Verified native FA contact model (system of record)

Confirmed against FA 2.4.3 source (`includes/db/crm_contacts_db.inc`,
`includes/ui/contacts_view.inc`) **and** the live integration DB (`ksf_fa`):

| Table | Role | Key columns |
|---|---|---|
| `0_crm_persons` | **Person** profile (an *individual*) | `id`, `ref`, `name`, `name2`, `address`, `phone`, `phone2`, `fax`, `email`, `lang`, `notes`, `inactive` |
| `0_crm_categories` | Contact role/context catalog | `type` (`customer`,`cust_branch`,`supplier`,`user`), `action` (`general`,`delivery`,`invoice`,`order`), `name`, `system` |
| `0_crm_contacts` | **XREF** person ↔ entity, via a role | `id`, `person_id`→`crm_persons.id`, `type`, `action`, `entity_id` (branch id / debtor_no / user id depending on `type`) |

Reusable native functions (`crm_contacts_db.inc`):
`add_crm_person(...)`, `update_crm_person(...)`, `get_crm_person($id)`,
`get_crm_persons($type,$action,$entity,$person,$unique)`, `get_person_contacts($id)`,
`add_crm_contact($type,$action,$entity_id,$person_id)`, `delete_crm_contact($id)`,
`delete_crm_contacts(...)`, `delete_entity_contacts($class,$entity)`.

Reusable native UI: `class contacts extends simple_crud`,
`__construct($name, $id, $class, $subclass=null)` — already instantiated natively as
`new contacts('contacts', $branch_id, 'cust_branch')` (`customer_branches.php:321`).
It is hard-wired to native `simple_crud` POST conventions, so the **data functions**
are the reusable part; the UI class is a reference for field order + validation.

**This native model already is the shared person/contact layer** the product wants:
a person (individual) with typed xref links to any number of customer / supplier /
user entities. The CRM should adopt it, not duplicate it.

> **Full reference:** `NATIVE-FA-CONTACTS-REFERENCE.md` documents the exact column
> sets, the complete native function signatures, the `ksfraser/fa-classes`
> DTO/DAO classes, the `ksf-modules-dao` adapter, and six verified hazards
> (H1–H6). Read it before implementing §3.1.

---

## 3. Target architecture

### 3.1 The CRM is a *client* of the native person/contact layer

- CRM Contacts tab **reads/writes native** `crm_persons` + `crm_contacts`. The flat
  `0_fa_crm_contacts` is retired (see §4 migration).
- **Data access goes through the `ksfraser/fa-classes` DTO/DAO layer, not raw SQL.**
  That package already ships `CrmPerson`/`CrmContact`/`CrmCategory` DTOs and
  `CrmPersonRepository`/`CrmContactRepository`/`CrmCategoryRepository`, plus
  `CustomerBranchRepository`/`DebtorMasterRepository`, on top of
  `Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter` (which delegates to native
  `db_*`, satisfying the "native db_* at runtime" rule).
- The new `PersonsRepository` is a thin **SRP translator** that composes those
  repositories, adds the person-profile side-table (§3.2), and issues **no SQL of
  its own** against `crm_*`. Full layering diagram: `NATIVE-FA-CONTACTS-REFERENCE.md` §5.
- Constructor-injects the `DbAdapterInterface`, so the module's PHPUnit suite stays
  database-free (inject a fake adapter).
- **Writes:** the `fa-classes` CRM repositories are read-only, so use
  `RepositoryTrait::insert()/update()/deleteWhere()` for single-table writes and the
  native functions only where their transaction + category-link semantics are needed
  (`add_crm_person`/`update_crm_person`, `update_person_contacts`), guarded by
  `function_exists()` and injected as callables for testability.
- Do **not** reference `Ksfraser\ModulesDAO\Factory\DatabaseAdapterFactory` — it uses
  a PHP 8.0 `match` expression and is a **parse error on FA's 7.4** (hazard H4).
  Use `new FrontAccountingDbAdapter(TB_PREF)`.
- Follow **FA convention** (#15): the data form belongs to the *selected customer
  branch*; remove the redundant 2nd customer selector; disable the form when the
  customer filter is "ALL"; offer a **branch selector** (checkbox across the
  debtor's branches) rather than re-entering a contact per branch.

### 3.2 Debtor classification + opt-in consent (side tables, no core ALTER)

Two **separate** side tables, because the two attributes answer different
questions and live on different subjects (BR-CRM-09). The earlier single
`0_fa_crm_person_profiles` sketch is withdrawn: it put I/F/B and an `opt_out`
honour flag on the *person*, which is the wrong axis and the wrong polarity.

**1. Classification belongs to the debtor** (FR-CRM-007-003). I/F/B describes the
account — individual, family (one bill, several people) or business — not any one
contact:

```sql
CREATE TABLE IF NOT EXISTS `0_fa_crm_account_types` (
  `debtor_no`    INT(11) NOT NULL COMMENT 'FK to debtors_master.debtor_no (native)',
  `account_type` CHAR(1) NOT NULL DEFAULT 'I' COMMENT 'I=Individual, F=Family, B=Business',
  `notes`        TEXT,
  `updated_by`   INT(11) NULL COMMENT 'user_id who set it',
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`debtor_no`),
  KEY `idx_account_type` (`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**2. One opt-in consent registry, reused for debtors and contacts**
(FR-CRM-007-004). A polymorphic `(subject_type, subject_id)` key lets the same
table and the same code path answer "may we email this account?" and "may we
email this person?":

```sql
CREATE TABLE IF NOT EXISTS `0_fa_crm_consents` (
  `consent_id`     INT(11) NOT NULL AUTO_INCREMENT,
  `subject_type`   VARCHAR(16) NOT NULL COMMENT 'debtor (debtors_master.debtor_no) | contact (crm_persons.id)',
  `subject_id`     INT(11) NOT NULL,
  `purpose`        VARCHAR(16) NOT NULL DEFAULT 'marketing' COMMENT 'marketing | service',
  `status`         VARCHAR(16) NOT NULL DEFAULT 'pending' COMMENT 'pending | opted_in | opted_out',
  `captured_at`    DATETIME NULL,
  `method`         VARCHAR(32) NULL COMMENT 'web_form | import | phone | counter | email',
  `source`         VARCHAR(120) NULL COMMENT 'form, URL, or staff note',
  `capture_ip`     VARCHAR(45) NULL,
  `notes`          TEXT,
  `updated_by`     INT(11) NULL,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`consent_id`),
  UNIQUE KEY `uq_subject` (`subject_type`, `subject_id`, `purpose`),
  KEY `idx_status_purpose` (`status`, `purpose`),
  KEY `idx_subject` (`subject_type`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Polarity is opt-in.** `status` is `pending` until someone affirmatively opts in.
A missing row, a `pending` row, and a lookup failure are all **not marketable** —
`isMarketingEligible()` must fail closed, never default to allow. There is no
`opt_out` boolean, because a boolean that defaults to 0 defaults to *permitted*,
which is the failure mode this replaces.

**Inheritance is asymmetric, keyed on the debtor's type** — this is where I/F/B
earns its place:

| Debtor type | Inherit account consent to a contact for marketing? |
|---|---|
| `B` business | **Yes**, but only at the debtor's *business address* |
| `F` family | **No** — each member is a separate natural person; children cannot consent at all |
| `I` individual | n/a — the debtor *is* the person, so their own record governs |

Order of resolution, short-circuiting:

1. Contact's own `opted_out` → **ineligible**. Absolute; inheritance never
   re-enables a stated refusal.
2. Contact's own `opted_in` → eligible.
3. Otherwise: debtor `B` **and** the address is the business address → eligible
   (inherited).
4. Otherwise → **ineligible**, with the reason naming the missing record.

Purpose stays orthogonal: `service` mail (invoices, statements, transactional
notices) is not gated by marketing consent, so a person with no marketing opt-in
is still receivable.

### 3.3 Lead = a debtor with an attached contact

Resolved answer to #30 ("what is a lead?"):

- A **lead** is a `debtors_master` row (a prospective account — a "baby company")
  that is **not yet an active customer**, plus **one or more attached contacts**
  (native `crm_persons` linked via `crm_contacts`, `type='customer'`/`'cust_branch'`,
  `action='general'`).
- This covers both lead flavours raised in #30:
  - *Research lead* (found a company) → create the debtor, attach the relevant contacts.
  - *Inbound/self-generated lead* (someone fills a form) → create the debtor from
    the form, attach that person as the contact.
- Lead-specific workflow data (status, source, rating, assigned_to, campaign)
  stays in `0_fa_crm_leads`, keyed by `debtor_no` (as today). Rich firmographics
  (revenue/employees/industry/website) belong on the **account** (customer), not
  the contact — so they move to / are read from the customer record, not the lead contact.

**Lead → Customer conversion:**
- The lead's `debtors_master` row is *activated* as a real customer (the lead's
  contacts and the person/contact xrefs **persist unchanged**).
- `0_fa_crm_leads.converted_date` / `converted_to_debtor_no` are stamped.
- The **I/F/B + CASL state lives on the person**, so it survives conversion and
  continues to govern how the now-customer is approached and whether they may be
  marketed to. Conversion does **not** reset consent.

### 3.4 Shared xref tables repointed to native

The module already declares FKs to "crm_persons" that were intended for the native
table. Repoint them to the native ids:

- `0_fa_crm_person_account_roles.person_id` → native `0_crm_persons.id`
- `0_fa_crm_contact_relationships.person_a_id` / `person_b_id` → native `0_crm_persons.id`
- `0_fa_crm_life_events.person_id` → native `0_crm_persons.id`
- `0_fa_crm_contact_accounts` (contact_id, debtor_no) → superseded by native
  `0_crm_contacts`; retire it and read the native xref instead.
- `0_fa_crm_opportunities.contact_id` → native `0_crm_persons.id` (or the contact row id).

> Note: because these are plain `INT` columns (no DB-level FK constraints in FA),
> "repointing" is a data-mapping concern handled by the migration, not a schema ALTER.

---

## 4. Migration plan (existing data)

Existing `0_fa_crm_contacts` rows (debtor_no + names + phone/email) must become
native persons + contacts:

1. For each CRM contact row, if a native `crm_persons` row already matches
   (email, else name+phone), reuse it; else `add_crm_person(...)` a new one.
2. Link it to the account's primary branch via `add_crm_contact('cust_branch','general',<branch_id>,<person_id>)`.
3. Create a `0_fa_crm_person_profiles` row (default `person_type='I'`, consent `unknown`).
4. Backfill I/F/B + consent (a mapping prompt / CSV for the business/family cases).
5. Only then retire the CRM contacts tab's writes to `0_fa_crm_contacts`.

Migration runs once, is idempotent (match-then-create), and is reversible (the
source table is retained read-only until sign-off).

---

## 5. What this resolves

| Issue | Resolution |
|---|---|
| #14 contacts not in native FA | CRM writes native `crm_persons`/`crm_contacts`; single source of truth. |
| #15 branch selector / 2nd customer DDL | Follow FA convention; branch selector; disable form on "ALL". |
| #30 lead definition | Lead = non-active debtor + attached contact(s); firmographics on the account. |
| I/F/B + CASL directive | `0_fa_crm_person_profiles.person_type` + `consent_*` on the person; survives conversion. |
| Shared CRM/HRM/Users persons | Native `crm_persons` + `crm_contacts` + reusable `contacts` UI, one person, typed xref links. |

---

## 6. Resolved questions (product decisions, 2026-09)

All four are settled in **BR-CRM-09**; this section records the answers and where
they are now specified.

1. **I/F/B default & derivation** — *resolved, axis corrected.* I/F/B is an
   attribute of the **debtor**, not the person: a customer can be one individual,
   one family account carrying several people (one bill, several renters/lines), or
   one business with staff contacts. It is **entered, not derived** — deriving it
   from contact structure would misread every family account as an individual.
   Default `I`, which is the strictest consent case. See FR-CRM-007-003.
2. **CASL scope** — *resolved, widened from the earlier recommendation.* Consent
   is captured at **both** levels but in **one shared opt-in table** keyed by
   `(subject_type, subject_id, purpose)`, so debtor and contact use the same code
   path. It is opt-in, not opt-out: absent ⇒ not marketable. Inheritance runs
   from a `B` debtor to its contacts at the business address only; `F` never
   inherits; an explicit per-person opt-out is an absolute override. See
   FR-CRM-007-004 and UC-CRM-007-003.
3. **HRM employees** — *resolved.* HRM keeps its **own** `persons` identity as the
   system of record and xrefs a native `crm_persons` row; HRM does not migrate.
   Merging employee identity into CRM is HRM's call, not CRM's. Coordinate the
   xref table name with the HRM owner before building the link.
4. **Migration backfill** — *resolved.* No curated sign-off is required to
   cut over: the legacy contact rows migrate idempotently, and every consent
   lands `pending` (not marketable). Classification defaults `I`. Operators
   curate afterwards through normal screens, and a later BR may add append-only
   consent events (explicitly out of scope of FR-CRM-007-004 §5).

---

## 7. Related (separate) work — explicitly NOT in this FR

- **#22** (opportunity ↔ quotes / campaign links) — opportunity enrichment; depends on
  a native campaign/quoted-entity, tracked separately.
- **#25** (Email Accounts vs Email module, "shared UI trait") — the *pattern* of a
  shared display/entry SRP is proven here by the native `contacts` UI and
  `simple_crud`; applying that to Email Accounts is its own change.
- **#16/#17/#18** — already landed (Cluster C).
