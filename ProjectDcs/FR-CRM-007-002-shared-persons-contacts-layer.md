# FR-CRM-007-002 — Shared Persons / Contacts Layer (Cluster D)

**Status:** DRAFT — design for review (not yet implemented)
**Issues:** #14, #15, #30 (+ the I/F/B & CASL product directive)
**Module:** ksf_FA_CRM
**Author:** (drafted for product sign-off)
**Date:** 2026-09-25
**Siblings:** FR-CRM-007-001 (contact classification), FR-CRM-002-001 (quote→order)

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

**This native model already is the shared person/contact layer** the product wants:
a person (individual) with typed xref links to any number of customer / supplier /
user entities. The CRM should adopt it, not duplicate it.

---

## 3. Target architecture

### 3.1 The CRM is a *client* of the native person/contact layer

- CRM Contacts tab **reads/writes native** `crm_persons` + `crm_contacts` via the
  functions above. The flat `0_fa_crm_contacts` is retired (see §4 migration).
- Reuse the native `contacts` UI SRP for list/entry, scoped per entity+type, so
  CRM, HRM and Users all present the same shared contact widget.
- Follow **FA convention** (#15): the data form belongs to the *selected customer
  branch*; remove the redundant 2nd customer selector; disable the form when the
  customer filter is "ALL"; offer a **branch selector** (checkbox across the
  debtor's branches) rather than re-entering a contact per branch.

### 3.2 Person extension for I/F/B + CASL (side table, no core ALTER)

Native `crm_persons` has no I/F/B or consent columns. Rather than ALTER a core FA
table (upgrade-fragile), add a CRM-owned **side table** keyed by the native person id:

```sql
CREATE TABLE IF NOT EXISTS `0_fa_crm_person_profiles` (
  `person_id`      INT(11) NOT NULL COMMENT 'FK to crm_persons.id (native)',
  `person_type`    CHAR(1) NOT NULL DEFAULT 'I' COMMENT 'I=Individual, F=Family, B=Business',
  `consent_status` VARCHAR(20) NOT NULL DEFAULT 'unknown' COMMENT 'unknown,pending,granted,revoked',
  `consent_date`   DATE DEFAULT NULL COMMENT 'CASL consent date',
  `consent_source` VARCHAR(100) DEFAULT NULL COMMENT 'where consent was captured',
  `consent_ip`     VARCHAR(45) DEFAULT NULL,
  `marketing_opt_out` TINYINT(1) DEFAULT 0 COMMENT 'CASL/unsubscribe honour-list',
  `preferred_contact_method` VARCHAR(20) DEFAULT 'email',
  `notes`          TEXT,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`person_id`),
  KEY `idx_person_type` (`person_type`),
  KEY `idx_consent_status` (`consent_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **I/F/B** is a *person* classification: how we approach them (a business is
  approached at the company/role level; a family/individual personally) and a CASL
  input (consent is captured from a named person).
- **CASL** (Canada's anti-spam law): `consent_status` + `consent_date` +
  `consent_source` give the auditable consent record; `marketing_opt_out` is the
  honour list. Marketing sends must filter on `consent_status='granted'` and
  `marketing_opt_out=0` (deferred to the Email/Marketing module).

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

## 6. Open questions (need product decision before implementation)

1. **I/F/B default & derivation** — is `person_type` entered manually, or derived
   (a person with an account-role link ⇒ B; solo person ⇒ I)? Default to `I`?
2. **CASL scope** — is consent captured only on the person, or also at the account
   level (one consent for the whole company)? Recommended: person-level only.
3. **HRM employees** — should the employee record be the same `crm_persons` row
   (linked with a `type='user'`/new `employee` category), or a separate `0_person`
   HRM identity that merely xrefs a `crm_persons` row? This affects HRM, so coordinate
   with the HRM owner before building the employee link.
4. **Migration backfill** — is a default `person_type`/consent acceptable, or must an
   operator curate I/F/B for existing records before cut-over?

---

## 7. Related (separate) work — explicitly NOT in this FR

- **#22** (opportunity ↔ quotes / campaign links) — opportunity enrichment; depends on
  a native campaign/quoted-entity, tracked separately.
- **#25** (Email Accounts vs Email module, "shared UI trait") — the *pattern* of a
  shared display/entry SRP is proven here by the native `contacts` UI and
  `simple_crud`; applying that to Email Accounts is its own change.
- **#16/#17/#18** — already landed (Cluster C).
