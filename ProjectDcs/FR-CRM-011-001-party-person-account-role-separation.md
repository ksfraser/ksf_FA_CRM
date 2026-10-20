# FR-CRM-011-001 — Party, person, account and role separation

@BABOK Related: BR-CRM-011; FR-CRM-007-002; BR-CRM-09; UC-CRM-011-001;
          PARTY-ACCOUNT-MODEL-NOTES §1.1, §1.2, §2; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM (the CRM's view; the platform contracts live in ksf_FA_Common)
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

The CRM cannot answer "who is this?" for a human being, because the estate
carries one record type for four different subjects. The owner's statement is the
requirement in one sentence:

> "A physical person can be a customer, a supplier, a user and an employee. So we
> overloaded the PERSON definition."

> "A customer (debtor) can be either a company, a family or an individual."

> "An individual is a family case with 1 family member (contact)."

Three consequences in the CRM today:

1. **One human being is two or three `0_crm_persons` rows.** FA's customer
   screen writes two relations for one person row
   (`sales/manage/customers.php:120-126`) and the branch screen writes another
   person row (`sales/manage/customer_branches.php:101-105`). Nothing links them.
2. **Five CRM screens read FA's person table with columns it does not have**
   (`first_name` / `last_name`; FA has `name` / `name2`,
   `sql/alter2.3.sql:190-207`) and die loudly:
   `includes/crm_relationships_db.inc:60` and `:123-125`,
   `pages/contact_relationships.php:121-122`, `pages/life_events.php:104`,
   `pages/person_account_roles.php:126`.
3. **The CRM cannot express a family.** `BR-CRM-09` classifies a debtor as I / F / B
   but has nowhere to put the *members* of an F account.

## 2. Requirement

### 2.1 The four concepts

| Concept | Answers | Carries | Must NOT carry |
|---|---|---|---|
| **PERSON** | *who is this human being?* | identity (given name, family name, reference, date of birth), lineage, lifecycle state, the links to its cards | email, phone, fax, address, company, payment terms, credit limit |
| **PARTY** | *what legal or social entity is this?* | name, type (company / sole trader / household / trust), tax identity, lifecycle state | contact details (they live on cards), commercial terms |
| **ACCOUNT** | *who do we trade with?* | **FA's `0_debtors_master`** — currency, credit status, payment terms, discount, credit limit, sales type, notes, inactive | email, phone (FA's debtor table has neither) |
| **ACCOUNT-PARTY ROLE** | *in what capacity?* | subject (person or party), role code, optional branch scope, start/end dates, status | the person's identity, the account's commercial terms |

The CRM shall **never** merge two of these into one record, and shall never
render one where another is meant.

### 2.2 An individual is a household of one — an invariant, not a type

The shared model expresses a household as a party with one or more members, and
a **single individual as a household with exactly one member**. The CRM shall
present it that way:

- A party's type distinguishes a **household** from a **company**, a **sole
  trader** and a **trust**. "Individual" is **not** a fifth kind of entity: an
  individual *is* a household of one member.
- The invariant `party_type = household ⟹ exactly one member row` **is
  enforceable** and the CRM shall show it: a party with no members is an error,
  and a household with two members is a family.

**Why this matters to the CRM and not just to the platform.** `BR-CRM-09` says
I/F/B is a property of the **debtor**, and it is right: the *account* is the
thing that carries the classification. The **party** is what carries the
membership. Both are needed, and they are not the same axis — a family account
(I/F/B = F) whose members are a household party, and a company account
(I/F/B = B) whose members are directors and shareholders.

### 2.3 One human, several cards

- The CRM shall treat FA's `0_crm_persons` row as a **card** — a role-scoped
  contact record — and never as "the person".
- Every card the estate knows about is linked to exactly one **person**, and one
  person may have several cards.
- The root person may be created **lazily**: the first time the estate touches an
  FA person that has no person record, one is created and the card is registered
  as that person's primary card. This is what makes adoption possible without a
  big-bang migration.

### 2.4 The CRM reads people and accounts through the shared seams

| Need | The seam | Rule |
|---|---|---|
| a person's name / email / phone | `ContactResolverInterface::findById()` (already shipped: `ksf_FA_Common/src/ContactResolver/Contract/ContactResolverInterface.php:61`) | never a CRM-side `SELECT … FROM crm_persons` |
| a person's cards, including one with **no** FA relation | the shared **person directory** | the resolver cannot see relation-less people — `get_crm_persons()` is an INNER JOIN (`includes/db/crm_contacts_db.inc:89-91`) |
| an account's name / terms / balance | `AccountResolverInterface` / FA's native `get_customer()`, `get_customer_name()`, `get_customer_details()` | never a CRM-side `SELECT … FROM debtors_master` |
| an account's contacts | `ContactResolverInterface::findByCustomer()` (debtor-level) and `findByEntity('cust_branch', …)` (branch-level); the union is FA's own `get_branch_contacts()` (`sales/includes/db/branches_db.inc:181-217`) | the caller must say **which** set it wants |
| roles a person holds on accounts | the shared **account-role** index | never the CRM's `0_ksf_crm_person_account_roles` once repointed |
| which accounts a person is on | the shared **account-role** index | FA cannot answer this natively — `get_crm_contact()` never exposes `crm_contacts.entity_id` (`includes/db/crm_contacts_db.inc:291-301`) |

### 2.5 Two id spaces, and the rule for choosing

`0_crm_persons.id` (a **card**) and the shared model's person id (the **human
being**) are **different numbers for different things**. The rule the CRM shall
apply to every table it owns or reads:

> A table that means *"the human being"* keys on the **person id**. A table that
> means *"the contact record for a role"* keys on the **card id**.

The CRM's tables that must be re-pointed:

| CRM table | Column | Becomes |
|---|---|---|
| `0_ksf_crm_life_events` | `person_id` | the **person id** |
| `0_ksf_crm_contact_relationships` | `person_a_id`, `person_b_id` | the **person id** on both sides |
| `0_ksf_crm_person_account_roles` | `person_id` | *retired* — superseded by the shared account-role index |

**The order is not negotiable.** A table must not be re-pointed until the person
id exists for **every** person it references. A half-re-pointed table is worse
than a consistently wrong one, because two id spaces then overlap silently.

### 2.6 Lifecycle states are not FA's `inactive`

FA's `inactive` flag means *excluded from lists*. The model needs an
**identity lifecycle** that is a different fact:

- a **person** may be `active`, `merged` (into another person) or `deceased`;
- a **party** may be `active`, `merged`, `dissolved` or `split`;
- an **account** may be `active`, `merged`, `split` or `closed`.

They are separate columns and separate facts. `update_record_status()`
(`includes/db/sql_functions.inc:58-63`) is how FA's `inactive` is set, and it is
what a retired account uses; it is **not** how a merged person is recorded.

### 2.7 No `is_primary`

Per ambiguity 8 the estate has **no** primary-contact concept, and this
requirement does not reopen it. The CRM's `is_primary` column
(`sql/ksf_crm_person_account_roles.sql:19`) is not carried into the shared
role index; it is dropped or repurposed when the CRM's pages are repointed.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | The CRM's data model holds a person, a party, an account and a role as four distinct subjects; no CRM surface renders an account's name in a contact-name column, and no surface renders a person's name as an account's. |
| **AC-2** | An individual is represented as a household of exactly one member. A party with zero members is refused, and a household with two members is presented as a family. |
| **AC-3** | One human being with two FA contact records resolves to **one** person with two cards, and the CRM can show both cards and which role each carries. |
| **AC-4** | A person with **no** FA contact relation is still listable in a CRM picker (I6 of the incomplete-handling note). |
| **AC-5** | The five sites that query `0_crm_persons` for `first_name` / `last_name` no longer do so, and their pages render instead of dying with a DB error. |
| **AC-6** | `0_ksf_crm_life_events.person_id` and `0_ksf_crm_contact_relationships.person_a_id` / `person_b_id` resolve to the **person**, not the card, and the CRM's existing rows still render correctly after the re-pointing. |
| **AC-7** | The CRM's role listing states **"unseeded"** rather than "no roles" for records the estate has not yet migrated. |
| **AC-8** | The CRM does not mark a primary contact anywhere, and the `is_primary` column is absent from the shared role index. |
| **AC-9** | A retired account is deactivated through FA's own `update_record_status()`; FA's `inactive` flag and the model's account lifecycle state are separate, and both are reported. |
| **AC-10** | Every CRM read of FA core data goes through FA's native functions or the shared contracts; no CRM code contains a `SELECT` against `0_crm_persons`, `0_crm_contacts`, `0_crm_categories`, `0_debtors_master`, `0_cust_branch`, `0_suppliers` or `0_users`. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `0_crm_persons` columns: `id, ref, name, name2, address, phone, phone2, fax, email, lang, notes, tmp_id, tmp_class, inactive`; `KEY(ref)`; **no `first_name` / `last_name`; no email index** | `sql/alter2.3.sql:190-207` |
| `0_crm_contacts`: `id, person_id, type, action, entity_id` | `sql/alter2.3.sql:211-219` |
| `0_crm_categories`: the `(type, action)` catalogue, twelve seeded rows | `sql/alter2.3.sql:161-186` |
| `0_debtors_master`: 16 columns, **no `email`, no `phone`**; `UNIQUE KEY debtor_ref` | `sql/en_US-new.sql:585-605` (`:604`) |
| `0_cust_branch`: `branch_code` + `debtor_no` composite PK; `KEY branch_ref` | `sql/en_US-new.sql:549-570` (`:569`, `:570`) |
| `0_suppliers`: `contact VARCHAR(60)` is a free-text **name**, not an address | `sql/en_US-new.sql:1691`, `:1698` |
| `0_users`: the "user" hat; **no** link to `0_crm_persons` | `sql/en_US-new.sql:2062` |
| `get_crm_persons()` is an INNER JOIN over `crm_contacts`; silent `general` fallback | `includes/db/crm_contacts_db.inc:87-119` (`:89-91`, `:115-116`) |
| `get_crm_contact()` never exposes `crm_contacts.entity_id` | `includes/db/crm_contacts_db.inc:291-301` |
| `get_branch_contacts()` = branch ∪ customer contacts; **not auto-loaded** | `sales/includes/db/branches_db.inc:181-217` |
| `update_record_status($id,$status,$table,$key)` sets `inactive`; **auto-loaded** | `includes/db/sql_functions.inc:58-63`; loaded at `includes/main.inc:18` |
| FA's customer screen writes two relations for one person | `sales/manage/customers.php:120-126` |
| FA's branch screen writes another person row | `sales/manage/customer_branches.php:101-105` |

## 5. Out of scope and open questions

**Out of scope:** the shared package's own schema and contracts (the platform's
requirement set, `BR-COM-010` / `FR-COM-010-001…014`, proposed in the shared
design document §7.2 and **not yet written**); life-event semantics (they stay
in the CRM); cross-company person identity.

**Open questions (carried in `PARTY-ACCOUNT-MODEL-NOTES.md` §8):**

| # | Question | Recommendation |
|---|---|---|
| **OQ-F** | Global or company-scoped registry? | Decide before the first multi-company deployment. |
| **OQ-G** | Do life events stay in the CRM? | **Yes** — re-point `person_id`; the shared journal is an *account* journal and must not absorb a personal timeline. |
| **OQ-H** | Add the PERSON/CARD sentence to `AGENTS_ARCH.md` §12.2? | **Yes** — without it, module authors keep treating `crm_persons.id` as the person's id and the model does not hold. |

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §1 (vocabulary), §2 (individuals and families), §4 (schema), §6.5 (supersessions).
- **Shared requirements (proposed, not yet written):** `BR-COM-010`,
  `FR-COM-010-001` (vocabulary and invariants), `FR-COM-010-002` (person and card
  schema), `FR-COM-010-003` (party and membership schema),
  `FR-COM-010-004` (account, role and link schema),
  `FR-COM-010-012` (resolver composition and hooks),
  `FR-COM-010-013` (seeding and reconciliation).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md`.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md`.
