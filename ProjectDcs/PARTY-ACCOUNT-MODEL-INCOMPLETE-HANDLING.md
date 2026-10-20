# PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING — the CRM module's note

**Date:** 2026-10-20
**Status:** **Note.** No production code changed, no test suite run.
**Module:** `ksf_FA_CRM`
**Companion:** `PARTY-ACCOUNT-MODEL-NOTES.md` (the narrative), `BR-CRM-011`,
`FR-CRM-011-001…008`
**Shared source:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
§6.4 (the nine incompleteness items I1–I9) and §6.5 (where the note must live)
**Decision record:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`

---

## 1. Why this note exists

The party/account model is **deliberately incomplete in nine places**. It ships
as a set of contracts and operations that are correct for the data that exists
and explicitly *unanswered* for the data that does not yet exist.

The rule this note exists to enforce:

> **A screen that shows something *wrong* is worse than one that shows something
> incomplete.** Every CRM surface that depends on the model must therefore state
> what it knows and what it does not, and must never let "I have no answer" render
> as "the answer is no".

The CRM is where this note lives because the CRM is the module that owns the
overlapping — and currently broken — surfaces, so it is where the supersession
has to be written down.

---

## 2. The nine incompleteness items and their degradation rules

Restated from the shared design document §6.4, in the CRM's own terms. **I1–I9
are the canonical list**; this file adds the CRM surface each one lands on.

| # | What is incomplete | Degradation rule | CRM surfaces affected |
|---|---|---|---|
| **I1** | **Person → account for pre-existing data.** Before the estate's data is seeded into the shared model, the account-role index is empty, so "which accounts is this person on?" returns nothing for legacy records. | Callers must state **"unseeded"**, never "none". The API returns an empty result **plus a `seeded` flag**; an empty array that means "no links" is forbidden. | `pages/person_account_roles.php`, `pages/contact_relationships.php`, `pages/life_events.php`, `pages/org_chart.php` |
| **I2** | **No primary contact.** Per ambiguity 8, FA has no such concept and the owner decision forbids adding one to FA core. | The designated recipient is FA's own branch/action preference (`get_branch_contacts()`, `sales/includes/db/branches_db.inc:181-217`). Screens **list** the account's contacts; they do **not** mark one. | `pages/person_account_roles.php` (the `is_primary` column read at `pages/person_account_roles.php:129`), the contact panels |
| **I3** | **No FA representation of party or family structure at all.** | Party and family screens are the CRM's alone. If the shared model's tables are absent (package not deployed), the screen shows the account and its contacts — it does **not** show a half-family. | `pages/account_relationships.php`, `pages/org_chart.php` |
| **I4** | **No ledger-level merge or split.** FA has no mechanism for re-pointing an account's transactions. | The merge/split operations move **identity** (cards, roles, links) and never **transactions**. A screen offering "merge these accounts" must **warn that balances stay where they are**. | the merge and split screens (FR-CRM-011-002, FR-CRM-011-003) |
| **I5** | **`0_users` is not linked by FA.** FA has no FK from `0_users` to `0_crm_persons` (`sql/en_US-new.sql:2062`). | The link from a person to a system user is **the model's**, not FA's. Nothing in FA keeps it in step; the reconciliation job reports drift. | the person detail screen |
| **I6** | **The shared contact resolver still cannot see relation-less people.** `get_crm_persons()` is an INNER JOIN over `crm_contacts` (`includes/db/crm_contacts_db.inc:89-91`), so a person with **no** `0_crm_contacts` row — an employee created by an inbound-email match-or-create, for instance — is invisible to `findByEmail()` and `search()`. | The person's own index **does** know them. Any CRM screen that must see unlinked people reads the **person directory**, not the contact resolver. The model does **not** fix the resolver gap; it provides an alternative path. | the contact picker, `get_contact_persons_selector()` (`includes/crm_relationships_db.inc:123-125`) |
| **I7** | **The model's role rows can drift from FA's relations.** FA has no foreign keys into our tables and fires no hooks when a contact is deleted. | A scheduled reconciliation compares the model's role rows against FA's `crm_contacts` set and **reports orphans**. FA's `delete_crm_contact()`, `delete_customer()` and `delete_branch()` all remove relations behind our back. | `includes/crm_relationships_db.inc` |
| **I8** | **Cloned accounts do not stay in step.** A clone copies currency, tax id, credit terms and GL assignments **at the moment of cloning**. Later edits to the source do not propagate — deliberately. | The clone outcome must state **which fields were copied and which were left to the operator**. | the split and spin-off screens (FR-CRM-011-003, FR-CRM-011-004) |
| **I9** | **The registry is company-scoped.** FA's tables are company-prefixed, so the person/party registry is too: the same human who is a customer in Company A and a supplier in Company B is **two** persons under the model. | Every CRM screen that shows a person **states the company**. No cross-company person matching is offered, and the UI must not imply one. | every person screen |

---

## 3. What each CRM surface renders today, what it will render after, and the rule it follows

### 3.1 `pages/person_account_roles.php` + `includes/crm_relationships_db.inc:184-235`

- **Today:** reads `0_ksf_crm_person_account_roles`
  (`pages/person_account_roles.php:128`, `includes/crm_relationships_db.inc:185-235`),
  a **CRM-owned** table whose `person_id` is FA's `0_crm_persons.id` and which
  carries an `is_primary` column (`sql/ksf_crm_person_account_roles.sql:19`). The
  page also selects `p.first_name` / `p.last_name` (`pages/person_account_roles.php:126`)
  — **columns FA's person table does not have** (`sql/alter2.3.sql:190-207` has
  `name` and `name2`), and it fails **loudly** (`db_query($sql, "…")` with a
  message).
- **After:** the roles come from the shared **account-role** index, read through
  `AccountDirectoryInterface::listRolesForSubject()`; the person names come from
  the **person directory**, never from the CRM's own SQL; there is no
  `is_primary`.
- **Rule while incomplete:** **I1** (state "unseeded" rather than "no roles"),
  **I2** (list the roles; do not mark one), **I6** (a person with no FA relation
  is still listed, because the person directory knows them).
- **Do not implement until:** the shared model's account-role contract exists and
  the CRM's `0_ksf_crm_person_account_roles` is retired.

### 3.2 `pages/account_relationships.php` + `includes/crm_relationships_db.inc:138-182`

- **Today:** reads `0_ksf_crm_account_relationships`
  (`pages/account_relationships.php:126`, `includes/crm_relationships_db.inc:139-182`),
  a CRM-owned table with `parent_debtor_no` / `child_debtor_no` / `relation_type`
  / `ownership_pct` (`sql/ksf_crm_account_relationships.sql`).
- **After:** the structural links come from the shared **account-link** index;
  the ownership percentage is a **party membership** fact, not a link fact, and
  moves there.
- **Rule while incomplete:** **I3** (the relationship screen is the CRM's alone;
  if the shared model's tables are absent, show nothing rather than half a
  hierarchy), **I7** (report orphans).
- **Do not implement until:** the shared link contract exists.

### 3.3 `pages/life_events.php` + `includes/crm_relationships_db.inc:240-280`

- **Today:** reads `0_ksf_crm_life_events`, whose `person_id` is FA's
  `0_crm_persons.id`; the page selects `p.first_name` / `p.last_name`
  (`pages/life_events.php:104`) and fails **loudly**.
- **After:** `person_id` becomes the **root person id**. The life-event timeline
  **stays in the CRM** — it is a personal timeline, not an account operation, and
  the shared model's journal is an *account* journal.
- **Rule while incomplete:** **I1** (a life event whose person cannot yet be
  resolved must still be listed, with the person shown as unresolved), **I9**
  (state the company).
- **Do not implement until:** the root person id exists for every person the
  table references.

### 3.4 `pages/contact_relationships.php` + `includes/crm_relationships_db.inc:77-117`

- **Today:** reads `0_ksf_crm_contact_relationships`; the page selects
  `pa.first_name` / `pa.last_name` / `pb.first_name` / `pb.last_name`
  (`pages/contact_relationships.php:121-122`) and fails **loudly**.
- **After:** both sides become **root person ids**.
- **Rule while incomplete:** **I1**, **I6**.
- **Do not implement until:** the root person id exists on both sides.

### 3.5 `pages/org_chart.php`

- **Today:** the **one** CRM site that already uses FA's real column names
  (`pages/org_chart.php:126-129`, `p_a.name` / `p_b.name`) — so it works. It
  reads `0_ksf_crm_contact_relationships` for the person-to-person edges.
- **After:** a **party-membership** view — the org chart is a party graph, and
  party membership is what the model adds.
- **Rule while incomplete:** **I3**.
- **Do not implement until:** the party directory exists.

### 3.6 `includes/crm_relationships_db.inc` — the two person-name lookups

- **Today:** `get_person_name()` (`includes/crm_relationships_db.inc:59-61`) and
  `get_contact_persons_selector()` (`includes/crm_relationships_db.inc:123-125`)
  both select `first_name` / `last_name` from `0_crm_persons` — columns that do
  not exist — and both fail **loudly**.
- **After:** both read the person directory / the shared contact resolver, and
  return FA's real `name` / `name2`.
- **Rule while incomplete:** **I6** — the picker must be able to list a person
  with **no** FA contact relation, which is precisely the set the resolver cannot
  see. The picker therefore reads the **person directory**, not the resolver.
- **Do not implement until:** the person directory exists.

---

## 4. Two standing rules for every CRM surface

### 4.1 The CRM reads people and accounts through the shared seams, never through its own SQL

- **People** → `PersonDirectoryInterface` (the shared model's person registry),
  and `ContactResolverInterface` for FA's person/contact reads.
- **Accounts** → `AccountResolverInterface` / `AccountDirectoryInterface`.

This is the CRM's expression of `AGENTS_ARCH.md` §12.2's rule that a module must
go through the shared seam. It is also the only way to satisfy **I6**: the
resolver cannot see relation-less people, so a surface that needs them must use
the person directory.

**A live defect this rule will fix.** Five CRM sites still query
`0_crm_persons` for columns FA does not have, and all five fail loudly:

| Site | Line |
|---|---|
| `includes/crm_relationships_db.inc` | `:60` (`get_person_name()`) |
| `includes/crm_relationships_db.inc` | `:123-125` (`get_contact_persons_selector()`) |
| `pages/contact_relationships.php` | `:121-122` |
| `pages/life_events.php` | `:104` |
| `pages/person_account_roles.php` | `:126` |

Verified 2026-10-20 against `ksf_FA_CRM` `main` at commit `3353165`.

### 4.2 The CRM must not write the shared model's tables directly

The shared model's tables are written **only** through the shared package's
contracts. This is the same rule §12.2 states for the contact tables, extended
to the new ones. Direct writes are how the estate got its 46 `WRONG_FA_TABLE`
sites.

---

## 5. What the CRM must NOT do while the model is incomplete

1. **Do not invent a party, family or household table inside the CRM.** The
   shared model owns parties; a CRM-local copy would be the second registry the
   whole exercise exists to end.
2. **Do not re-point a `person_id` column at the root person id until the root
   exists for every person that column references.** A half-re-pointed table is
   worse than a consistently wrong one, because the two id spaces then overlap
   silently.
3. **Do not offer "merge" or "split" without the balances warning** (**I4**).
4. **Do not render an empty role list as "this person holds no roles"** (**I1**).
5. **Do not mark a primary contact** (**I2**).
6. **Do not drop `0_ksf_crm_person_account_roles` or
   `0_ksf_crm_account_relationships` before their CRM pages are repointed.**
   Both are live tables with a live schema; retiring them is the *last* step of
   the repointing, not the first.

---

## 6. Traceability

| Item | Where |
|---|---|
| The canonical I1–I9 list | `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md` §6.4 |
| Where the note must live (three places) | shared design document §6.5 — this file is place **1**; place 2 is `ksf_Architecture_Docs/Requirements/OPEN-QUESTIONS.md`; place 3 is `ksf_FA_Common/AGENTS_APPENDIX.md` |
| The CRM requirement set that must obey this note | `BR-CRM-011`, `FR-CRM-011-001…008` |
| The narrative that explains the model | `PARTY-ACCOUNT-MODEL-NOTES.md` |
| The decision | `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md` |

---

*Note produced 2026-10-20. **No production code was modified, no test suite was
run, and no FA table was written to.***
