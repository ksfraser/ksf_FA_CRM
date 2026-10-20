# FR-CRM-011-006 — Typed, dated, reversible links between accounts

@BABOK Related: BR-CRM-011; FR-CRM-011-001; FR-CRM-011-002; FR-CRM-011-004;
          UC-CRM-011-005; PARTY-ACCOUNT-MODEL-NOTES §1.8, §1.9, §3;
          AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "So we should have a way to soft link debtors."

**The situation.** Two accounts are related — a parent and a subsidiary, a
business and the household that owns it, two branches of the same family that
happen to have separate accounts. The business wants them **findable** and
**labelled**, without merging them and without moving any data between them.

**What the owner is saying.** "Related" is not "the same" and not "one of the
other". It is a **typed, dated, reversible** relationship — a fact, with a type
and a date range, that can be ended.

### 1.1 The three kinds of link, which are not the same thing

| Link | Between | Stored? | Why |
|---|---|---|---|
| **Structural** — successor, predecessor, spin-off, sold, split-from | account ↔ account | **yes** | a dated historical fact FA cannot hold |
| **Shared subject** — two accounts touched by the same person or party | account ↔ account (via a shared person or party) | **derived, not stored** | it is a *query* over the role index; storing it creates a third thing to keep in step |
| **Card link** — two FA person rows that are the same human | card ↔ card | **yes** | the root ↔ shadow mapping (FR-CRM-011-008) |

**Why the derived link must not be stored.** It is a query over
"accounts this person holds a role on". Storing it means a second copy of a
fact that already has a home, and the two copies drift. This is the same
principle that keeps the CRM out of FA's contact tables.

**Why the structural link must be stored.** "These two accounts were one until
2026-03-14" and "this account was spun off from that one on 2026-02-01" are
facts about the *past*. They cannot be derived from the present state, because
the present state no longer contains them. Deriving them would mean deleting the
history every time an account is retired — which is exactly what the model
refuses to do.

## 2. Requirement

### 2.1 The operation

`AccountLifecycleInterface::linkAccounts(AccountLinkRequest): AccountLink`.

**Inputs:** from account, to account, link type, effective date (optional), end
date (optional), notes, actor.

### 2.2 The link-type catalogue

| Type | Direction | Written by |
|---|---|---|
| `successor` | absorbed → surviving | FR-CRM-011-002 |
| `predecessor` | surviving → absorbed | FR-CRM-011-002 |
| `spin_off` | new subsidiary → former parent | FR-CRM-011-004 |
| `sold` | sold entity → buyer | FR-CRM-011-004 |
| `split_from` | new account → source account | FR-CRM-011-003, FR-CRM-011-005 |
| `duplicate_of` | suspect account → the account it duplicates | the reconciliation job |
| *operator-defined* | any pair | an operator, with a free-text label |

**The catalogue is open.** The owner said "a way to soft link debtors" — not "a
way to link a subsidiary to its parent". So the operation must accept a type the
business invents, while the types the model writes itself are fixed and
enumerated.

### 2.3 Preconditions and effects

**Preconditions:** both accounts exist; the pair and type are not already linked
(uniqueness); the type is in the catalogue or is an accepted operator label.

**Effects:**

1. The link row is written.
2. Optionally, a **breadcrumb in FA**: append a line to the surviving account's
   `notes` via `update_customer()`. Note that `update_customer()` rewrites the
   **whole** row (`sales/includes/db/customers_db.inc:30-51`), so the
   implementation must read the current row (`get_customer()`), concatenate and
   write back — otherwise the breadcrumb silently destroys the account's other
   fields. This is cosmetic but useful: FA's own customer screen then shows the
   link without our module being involved.
3. One event record.

### 2.4 The three queries the model must answer

| Query | Question it answers | Source |
|---|---|---|
| **direct** | "what is linked to this account?" | the link rows for the account |
| **transitive** | "is B the successor of A, possibly through C?" | a recursive walk over `successor` links |
| **by shared subject** | "which accounts does this person/party touch?" | the role index, filtered by subject — **derived**, never stored |

The transitive walk needs a **visited set** and a **depth bound**; a cycle in
the data must terminate rather than spin.

### 2.5 Links are ended, never deleted

A link carries `effective_date` and `end_date`. Ending a link sets its end date;
it does not remove the row. **The history of a link is the point.**

### 2.6 The CRM's place in the soft link

- The CRM's `0_ksf_crm_account_relationships` table is the CRM's own
  implementation of a soft link, and it is **superseded** by the shared account
  link store. Its shape — `parent_debtor_no` / `child_debtor_no` /
  `relation_type` / `ownership_pct` / `start_date` / `end_date` — is close to
  what is needed, but it conflates three different facts:
  1. a **structural** link (`spin_off`, `split_from`, `sold`);
  2. an **ownership** relationship (the parent owns 60% of the subsidiary);
  3. an **operator-declared** relationship (`trustee_of`, `beneficiary_of`).

  Only the first is a link; the second is a **party membership**
  (FR-CRM-011-007); the third is an operator-defined link type.
- The repointing order is fixed: **repoint the CRM's pages first, then retire the
  table.** Retiring a live table first loses the rows.
- The CRM's `pages/account_relationships.php` becomes the surface that writes
  operator-defined links and shows the structural ones, with the two visually
  distinguished so that a spin-off is never mistaken for an ownership fact.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | Two accounts can be linked with a type, and the link is retrievable from **both** accounts. |
| **AC-2** | A link carries an effective date and may carry an end date; ending a link sets the end date and **keeps** the row. |
| **AC-3** | A second link of the same type between the same pair is refused. |
| **AC-4** | Linking two accounts moves **no** data between them: no contact, no role, no branch, no transaction changes. |
| **AC-5** | The three link types the model writes itself (`successor`, `predecessor`, `spin_off`, `sold`, `split_from`) are enumerated and cannot be renamed by an operator. |
| **AC-6** | An operator may create a link with a type outside the catalogue, and the link records who created it and when. |
| **AC-7** | The transitive query answers "is B the successor of A" through an intermediate account, and terminates on a cyclic set of links rather than looping. |
| **AC-8** | The "accounts this person touches" query is **derived** from the role index, and no stored row duplicates it. |
| **AC-9** | When the optional FA `notes` breadcrumb is used, the account's **other** fields are unchanged — the read-concatenate-write round trip is verified against the row before and after. |
| **AC-10** | The CRM's account-relationships page distinguishes a structural link from an ownership relationship, and the ownership fact is stored as a party membership, not as a link. |
| **AC-11** | The CRM writes links through the shared package's contract; no CRM code writes the shared tables directly. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `update_customer()` rewrites the **whole** row (name, ref, address, tax id, currency, dimensions, credit status, terms, discounts, credit limit, sales type, notes) — a read-concatenate-write is required to append to `notes` safely | `sales/includes/db/customers_db.inc:30-51` |
| `get_customer()` — read of the current row | `sales/includes/db/customers_db.inc:118-125` |
| `get_customer_name()` | `sales/includes/db/customers_db.inc:127-133` |
| `0_debtors_master` — `notes tinytext NOT NULL`, `inactive tinyint(1) NOT NULL default '0'` | `sql/en_US-new.sql:585-605` |
| FA has **no** table for account-to-account relationships | a whole-tree grep for FA table names returns exactly `crm_categories`, `crm_contacts`, `crm_persons` for `crm_*`; no `party`, `household`, `family` or `ownership` table exists in the FA schema |

## 5. Out of scope and open questions

**Out of scope:** derived links (they are queries, not rows); moving data along
a link; automatic link inference; graph analytics over the link set.

**Open questions:** none specific to this requirement. The transitive walk's
depth bound is an implementation choice, not a product decision; it is recorded
here so that it is not left to the implementer by accident.

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §3.6 (SOFT LINK — the three kinds of link), §4.3 (`account_links` schema).
- **Shared requirements (proposed, not yet written):** `FR-COM-010-011` (soft
  link operation), `FR-COM-010-004` (account, role and link schema).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.8.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` (**I3**).
- **Use case:** `UC-CRM-011-005-soft-link-two-related-accounts.md`.
