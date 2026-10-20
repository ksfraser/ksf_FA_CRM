# BR-CRM-011 — Party, Person, Account and Role Separation, and the Account Lifecycle

**Modules:** ksf_FA_CRM (owner of the CRM-facing view), ksf_FA_Common (owner of the
shared party/account package and its contracts), native FA `debtors_master` /
`cust_branch` / `crm_persons` / `crm_contacts` / `crm_categories`
**Status:** DRAFT — requirements ratified in this BR; implementation pending
**Date:** 2026-10-20
**Source:** `PARTY-ACCOUNT-MODEL-NOTES.md` (the owner's reasoning, worked through)
**Shared view:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
· `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`
**Related:** BR-CRM-09 (account classification & consent), FR-CRM-007-002 (shared
persons/contacts layer), FR-CRM-007-003, FR-CRM-007-004, UC-CRM-007-001,
UC-CRM-007-002, UC-CRM-007-003, AGENTS_ARCH.md §12.2

---

## Business Need / Current State

The estate has **one record type** doing the work of four.

> "A physical person can be a customer, a supplier, a user and an employee. So we
> overloaded the PERSON definition." — the estate owner, 2026-10

> "A customer (debtor) can be either a company, a family or an individual."

> "An individual is a family case with 1 family member (contact)."

Read together, those say that *one record* must answer four different questions:
**who is this human being**, **what is this legal or social entity**, **who do we
trade with**, and **in what capacity does this person or entity appear here**.
FA's own screens already produce more than one `0_crm_persons` row for one human
being — `sales/manage/customers.php:120-126` writes **two** relations for **one**
person row, and `sales/manage/customer_branches.php:101-105` writes **another**
person row for the branch contact. Nothing links them, so the estate cannot
answer "are these the same person?".

The consequences are not theoretical; they are the reason five CRM screens are
broken today:

| CRM site | Line | What it asks FA for | What FA has |
|---|---|---|---|
| `includes/crm_relationships_db.inc` | `:60` | `crm_persons.first_name`, `.last_name` | `name`, `name2` — **no such columns** (`sql/alter2.3.sql:190-207`) |
| `includes/crm_relationships_db.inc` | `:123-125` | same | same |
| `pages/contact_relationships.php` | `:121-122` | same | same |
| `pages/life_events.php` | `:104` | same | same |
| `pages/person_account_roles.php` | `:126` | same | same |

All five pass an error message to `db_query()`, so they fail **loudly** — the page
dies with a DB error rather than rendering an empty list.

And the owner's reasoning names the *operations* the estate is missing. Each of
these is a thing a real customer does, and none of them can be expressed today:

| The owner's sentence | The operation it implies |
|---|---|
| "A single person getting married would be similar to a corporate merger or buyout — the company name might change but the contact details shouldn't, except maybe for position/title?" | **MERGE** |
| "One case we haven't considered/discussed is divorce — splitting an account into 2." | **SPLIT** |
| "Companies can split off subsidiaries and then possibly sell them." | **SPIN-OFF** (+ a later sale) |
| "Families could shed children as they become adults — they should then have their own accounts." | **PROMOTE** |
| "So we should have a way to soft link debtors." | **SOFT LINK** |
| "…permit the person to be associated rather than requiring a duplicate record." | **ASSOCIATE** |
| "…maybe clone the person but have a link pointing out that it is the same person in a different role." | **CLONE-WITH-LINK** |

## The correction: four concepts, not one overloaded record

| Concept | Question it answers | Where it lives |
|---|---|---|
| **PERSON** | *Who is this human being?* | the shared package's person registry |
| **PARTY** | *What legal or social entity is this?* | the shared package's party registry (company, household, sole trader, trust) |
| **ACCOUNT** | *Who do we trade with?* | **FA's own `0_debtors_master`** — not replaced, not copied |
| **ACCOUNT-PARTY ROLE** | *In what capacity?* | the shared package's role index |
| *(CARD)* | *Which role-scoped contact record carries this human's details?* | **FA's own `0_crm_persons`** — one human may have several |

Two of the estate's already-ratified decisions sit underneath this and are **not**
reopened:

- **BR-CRM-09** — I/F/B is a property of the **debtor**, not of the person. This
  BR agrees and is consistent with it.
- **AGENTS_ARCH.md §12.2** — the contact registry is FA's. The CRM's own
  `0_ksf_crm_contacts` is deprecated. This BR composes with FA's registry; it
  does not replace it.

**A refinement of the vocabulary, and it is an addition, not a redefinition.**
`Contact_Ambiguity_Review_2026-10.md` ambiguity 1 ruled that *"contact" means a
person, and only a person*. This BR keeps that ruling and adds one distinction
**above** it: one human being may be recorded by more than one contact record.
The human being is the **PERSON**; the role-scoped record is a **CARD**. This
needs one added sentence in `AGENTS_ARCH.md` §12.2 so that a module author does
not read "a contact" as "the person" (open question **OQ-H** in the notes).

## Business Requirement

The business requires:

1. **The four concepts are separated, and the CRM may not blur them.** A PERSON
   carries identity only — no email, no phone, no address, no credit terms. A
   PARTY is an entity that can hold roles. An ACCOUNT is FA's debtor. A role
   says who holds which hat where. An individual **is** a household of one
   member; that is an invariant, not a separate type.
2. **Seven lifecycle operations exist, each with its own semantics**, and each is
   one operation serving every case the owner named: MERGE (marriage, corporate
   merger, buyout, rename), SPLIT (divorce), SPIN-OFF (subsidiary separation and
   its later sale), PROMOTE (a family member leaving the household role for an
   account of their own), SOFT LINK (typed, dated, reversible relationships
   between accounts), ASSOCIATE (a person in many organisations with a role),
   and CLONE-WITH-LINK (a deliberate, marked shadow record for a second hat).
3. **A merge changes identity, not contact details.** The account's name may
   change; a title may change; the email, phone and address of the people
   involved **do not**. A contact-detail edit that happens around a marriage is
   an ordinary edit, done separately and audited as such — it is **not** merge
   semantics.
4. **The two people in a marriage do not merge.** The *party structure* and the
   *account* change; the *persons* remain two human beings. Without this, the
   divorce of §"SPLIT" would be inexpressible.
5. **Every operation is auditable and reversible in the identity dimension.** The
   absorbed account is **deactivated, never deleted**; an ended role keeps its
   history; every operation writes an event record.
6. **A deliberate shadow is distinguishable from a data-entry duplicate.** The
   model records that two contact records are the same human in different roles,
   and it reports the pairs that are probably mistakes.
7. **The CRM reads people and accounts through the shared seams**, never through
   its own SQL against FA core tables.
8. **The CRM's superseded surfaces are repointed, then retired** — in that order,
   never the other way round.

## Scope

- **In scope:** the CRM-facing specification of the four concepts and the seven
  operations; the invariants; the FA primitives each operation is composed from;
  the CRM surfaces that must be repointed; the supersession of
  `0_ksf_crm_person_account_roles` and `0_ksf_crm_account_relationships`; the
  incomplete-handling rules.
- **Out of scope — transaction re-pointing.** No operation moves **transactions**.
  FA has no ledger-level merge or split, and the commercial treatment of a
  merger, a divorce or a sale is a product decision (**OQ-B**, **OQ-C**).
- **Out of scope — deletion of an absorbed account.** Always deactivate
  (**OQ-A**).
- **Out of scope — a designated primary contact.** Per ambiguity 8 the estate has
  no such concept; no `is_primary` column is introduced.
- **Out of scope — cross-company person identity.** The registry is
  company-scoped (**OQ-F**).
- **Out of scope — life events.** They stay in the CRM (`0_ksf_crm_life_events`);
  only the id they key on is re-pointed (**OQ-G**).
- **Out of scope — the shared package's own schema and contracts.** Those are the
  platform's requirement set (`BR-COM-010`, `FR-COM-010-001…014`), proposed in
  the shared design document §7.2 and **not yet written** — see
  `PARTY-ACCOUNT-MODEL-NOTES.md` §6.

## Acceptance Criteria (UAT)

| ID | Criterion |
|---|---|
| **ARI-1** | The CRM distinguishes a **person** (identity), an **entity/party**, an **account** and a **role** in its data model and in its screens; no surface renders an account's name in a contact-name column, and no surface renders a person's name as an account's. |
| **ARI-2** | An individual account is representable as a household of exactly **one** member, and the model states the invariant rather than treating "individual" as a different kind of thing. |
| **ARI-3** | A **merge** of two accounts deactivates the absorbed account (never deletes it), moves the selected contacts, records the successor/predecessor links, writes an event record, and leaves **the absorbed account's transactions where they are** — with a visible warning that this is the case. |
| **ARI-4** | A **merge** changes the account's name and may change a card's title; it changes **no** contact detail. A contact-detail change made in the same session is recorded as a separate, separately-auditable edit. |
| **ARI-5** | A **marriage** merge produces one account and one household party holding **two** members, and the two persons remain **two** persons. |
| **ARI-6** | A **split** of a household account produces two independent accounts, a `split_from` link between them, and a per-contact record of whether the contact moved or was cloned. The source account keeps the balance unless the operator says otherwise. |
| **ARI-7** | A **spin-off** creates a separate company account and branch with its **own** GL assignment supplied as a required input (never a default), records the ownership, and writes a `spin_off` link; a later sale writes a `sold` link to the buyer and the sold entity **remains** in FA with its own ledger. |
| **ARI-8** | A **promotion** ends the family member's role on the household account (with an end date, the row retained) while leaving the household membership intact by default, creates the new account, and records whether the card moved or was cloned. |
| **ARI-9** | A **soft link** between two accounts is typed, dated and reversible, and does not move any data between the accounts. A link that already exists for the same pair and type is refused. |
| **ARI-10** | A person can be associated with **multiple** organisations, each with its own role and its own start/end dates, without creating a duplicate person record. The association is visible in FA's own contact screens through a category row. |
| **ARI-11** | A **shadow** contact record is created only with a declared scope role, and the model distinguishes it from a probable duplicate (same email **and** phone **and** fax, and no distinct role). Probable duplicates are **reported**, not silently accepted. |
| **ARI-12** | No CRM surface shows an empty role list as "this person holds no roles" where the estate is unseeded; it states that the estate is unseeded. |
| **ARI-13** | The five CRM sites that query `0_crm_persons` for `first_name` / `last_name` no longer do so, and the affected pages render instead of dying with a DB error. |
| **ARI-14** | The CRM writes no shared-model table directly; every write goes through the shared package's contract. |
| **ARI-15** | `0_ksf_crm_person_account_roles` and `0_ksf_crm_account_relationships` are retired **only after** the CRM pages that read them are repointed, and the retirement is recorded in this BR's traceability. |

## Non-Functional

- **PHP 7.3** floor (`AGENTS_ARCH.md` §1). No PHP 8+ syntax.
- **Native FA functions only** for FA core data (`AGENTS_ARCH.md` §12.2). No raw
  SQL against `0_crm_persons`, `0_crm_contacts`, `0_crm_categories`,
  `0_debtors_master`, `0_cust_branch`, `0_suppliers`, `0_users`.
- **No ALTER of FA core tables.** Every new fact lives in a table the owning
  module owns.
- **No `0_fa_*` table names.** A parallel sweep is renaming every `0_fa_*` table
  to `0_ksf_<module>_*`; no requirement in this set may depend on a `0_fa_*`
  name, and none does.
- **No dependency on an unratified table name.** These requirements are written
  against the shared package's **contracts**, not its proposed tables — see
  `PARTY-ACCOUNT-MODEL-NOTES.md` §7.3.
- **Every operation is idempotent-safe to retry** at the identity level, and
  every operation writes an event row before it returns.

## Traceability

- FR-CRM-011-001 — party, person, account and role separation
- FR-CRM-011-002 — account merge
- FR-CRM-011-003 — account split
- FR-CRM-011-004 — subsidiary spin-off and sale
- FR-CRM-011-005 — family-member promotion
- FR-CRM-011-006 — soft account links
- FR-CRM-011-007 — person-to-organisation association
- FR-CRM-011-008 — shadow and linked person records
- UC-CRM-011-001 … UC-CRM-011-007 — one use case per operation
- `PARTY-ACCOUNT-MODEL-NOTES.md` — the narrative this BR is derived from
- `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` — the degradation rules
- `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`
  — the decision record

## Related

- BR-CRM-09 — account classification (I/F/B) and opt-in consent. **Consistent**;
  this BR does not reopen either.
- FR-CRM-007-002 — shared persons/contacts layer. **Partly superseded** by
  `AGENTS_ARCH.md` §12.2 (2026-10) — see the open question in
  `PARTY-ACCOUNT-MODEL-NOTES.md` §8 and the note in
  `~/Documents/ksf_Architecture_Docs/Contact_Ambiguity_Review_2026-10.md` §3.
- The shared platform view of this work:
  `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`, with
  requirement ids `BR-COM-010` / `FR-COM-010-001…014` / `UC-COM-010-001…007`
  proposed in its §7.2 and **not yet written**.
