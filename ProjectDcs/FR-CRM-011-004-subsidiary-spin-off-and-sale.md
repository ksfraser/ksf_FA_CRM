# FR-CRM-011-004 — Subsidiary spin-off and sale

@BABOK Related: BR-CRM-011; FR-CRM-011-001; FR-CRM-011-006; UC-CRM-011-003;
          PARTY-ACCOUNT-MODEL-NOTES §1.4, §1.9, §3; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "Companies can split off subsidiaries and then possibly sell them."

**The situation.** Acme Ltd has a subsidiary, Acme Widgets Ltd. Acme Widgets
becomes a separate trading entity with its own account and its own ledger
accounts. Later, Acme sells Acme Widgets to a buyer.

**Why this is not SPLIT.** The mechanical steps overlap — a new account, a new
branch, contacts moved — but the *semantics* differ, and conflating them loses
facts:

| | SPLIT (FR-CRM-011-003) | SPIN-OFF (this requirement) |
|---|---|---|
| Subject | a **household** divides into two households | a **company** separates from a company |
| Ownership | none | the parent holds (or held) a percentage of the subsidiary |
| Third party | none | possibly a **buyer** |
| Ledger | one account's ledger | the subsidiary's invoices must post to **its own** GL accounts |

The owner wrote "split off subsidiaries **and then possibly sell them**" — two
events, with a date on each. So this is one operation (the separation) plus one
dated link (the sale), not one operation.

**The estate already has the shape of this.** The CRM's own
`0_ksf_crm_account_relationships` table already models
`parent_debtor_no` / `child_debtor_no` / `relation_type` / `ownership_pct`
(`sql/ksf_crm_account_relationships.sql`), with `relation_type` documented as
`owns, subsidiary, trustee_of, beneficiary_of`. That is the right *shape*; it is
superseded as a *store* (FR-CRM-011-006), but the ownership fact it holds is
real and moves with it.

## 2. Requirement

### 2.1 The operation

`AccountLifecycleInterface::spinOff(SpinOffRequest): SpinOffOutcome`.

**Inputs:** source account, new account name, new account reference, new party
name, the branches that move, the contacts that move or clone, the ownership
percentage (optional), the buyer party (optional), the sale date (optional),
reason, actor.

### 2.2 Preconditions

1. All of SPLIT's preconditions (FR-CRM-011-003 §2.2) — same mechanical checks.
2. The source account's party is a **company**.
3. The new party is created as a **company**.
4. **The GL account assignment for the new branch is a required input**, never a
   default (**OQ-D**).

### 2.3 Effects

| Step | What happens | FA primitive / build |
|---|---|---|
| 1 | The new company account and its branch are created | `add_customer()` + `db_insert_id()`; `add_branch()` — the branch carries its own GL assignments (`sales/includes/db/branches_db.inc:12-39`) |
| 2 | The new party and its membership are created | — **[BUILD]** |
| 3 | **Ownership** is recorded — the parent party is a member of the new party with the role `shareholder` and the ownership percentage | — **[BUILD]** |
| 4 | A `spin_off` link is written from the new account to the source account, dated at the separation date | — **[BUILD]** |
| 5 | If the subsidiary is later **sold**, a `sold` link is written from the new account to the **buyer's** account, dated at the sale date | — **[BUILD]** |
| 6 | One event record per operation (spin-off; sale) | — **[BUILD]** |

### 2.4 The sale

- The sold entity **stays in FA** with its own account. FA has no "sold" state,
  and deleting it would destroy its ledger.
- The sale is a **separate dated operation** from the separation. Both are
  recorded; both can be queried independently.
- After the sale, the ownership membership of the parent party is **ended**
  (with an end date = the sale date), not deleted — the same "end, don't delete"
  rule the role table follows everywhere.

### 2.5 GL separation is a required input, never a default

`add_branch()` takes the GL account assignments as arguments
(`sales/includes/db/branches_db.inc:12-39`), so a branch **can** post to its own
`sales_account`, `receivables_account`, `sales_discount_account` and
`payment_discount_account`. **Which** accounts a spun-off company posts to is an
accounting decision the estate must make, not one the model may guess:

> Wrong defaults post a subsidiary's invoices to the parent's receivables
> account. That is a silent mis-posting across a whole ledger.

So the spin-off **refuses** to proceed without an explicit GL assignment, and the
CRM's spin-off screen must present the parent's current assignments as the
*starting point* for the operator, clearly labelled as a copy, not as a default.

### 2.6 What a spin-off does **not** do

- It does not re-point the subsidiary's transactions. FA has no mechanism for
  moving a ledger between accounts, and the subsidiary's ledger must be correct
  **before** the separation, not moved by it.
- It does not delete the parent's contact cards. The parent's contacts on the
  subsidiary are moved or cloned per the same rule as SPLIT (§2.4 of
  FR-CRM-011-003).
- It does not merge the two companies' parties into one — the absorbed party
  keeps its identity with an end-dated membership.

### 2.7 The CRM's place in the spin-off

- The CRM initiates the spin-off from its account screens and records it; the
  shared package performs it.
- The CRM's `0_ksf_crm_account_relationships.ownership_pct` is the fact that
  moves into the **party membership** (the parent is a shareholder of the
  subsidiary); the *link* between the two accounts is a separate, typed, dated
  row. **Two facts, two stores.** Folding them together is how the CRM's current
  table confuses "owns" with "was spun off from".
- The CRM's account screen for the parent must show the subsidiary's account as
  a **spin-off**, not as a generic child, so that the operator can tell a
  separation from an ownership relationship.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | A spin-off creates a second, **company** party and a second account with its own branch, and the parent party is recorded as a `shareholder` member of the new party with the stated ownership percentage. |
| **AC-2** | A `spin_off` link from the new account to the source account is written and dated at the separation date, and is visible from both accounts. |
| **AC-3** | A spin-off **without** an explicit GL assignment for the new branch is **refused**, and the refusal names the missing input. No default GL account is ever used. |
| **AC-4** | The new branch's GL assignment is read back after creation and matches what was supplied. |
| **AC-5** | A later sale writes a `sold` link from the subsidiary account to the **buyer's** account, dated at the sale date, and ends the parent's ownership membership with that date. The subsidiary account is **not** deleted and remains readable. |
| **AC-6** | The spin-off and the sale are **separate** event records with separate dates; the sale is not implied by the spin-off. |
| **AC-7** | No transaction row of either company is modified by a spin-off or a sale. |
| **AC-8** | The CRM's account screen distinguishes a `spin_off` link from an ownership relationship, and the two facts are stored separately. |
| **AC-9** | A spin-off whose new reference would collide with an existing `debtor_ref` is refused before anything is written. |
| **AC-10** | The CRM writes the spin-off's links, memberships and events through the shared package's contract; no CRM code writes the shared tables directly. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `add_customer()` — 14 arguments, **returns nothing** | `sales/includes/db/customers_db.inc:13-28`; `db_insert_id()` at `includes/db/connect_db_mysqli.inc:173` |
| `add_branch()` takes the GL assignments as arguments (`sales_account`, `sales_discount_account`, `receivables_account`, `payment_discount_account`) | `sales/includes/db/branches_db.inc:12-39` |
| `0_cust_branch` carries those four GL columns | `sql/en_US-new.sql:559-562` |
| `add_crm_contact()` / `delete_crm_contact()` | `includes/db/crm_contacts_db.inc:251-258` / `:263-270` |
| `delete_branch()` | `sales/includes/db/branches_db.inc:65-71` |
| `0_debtors_master.debtor_ref` is `UNIQUE` | `sql/en_US-new.sql:604` |
| `0_debtors_master` has no `email` and no `phone` column | `sql/en_US-new.sql:585-605` |
| **No** data-level clone of a debtor or branch; **no** FA "sold" state; **no** FA ownership concept | whole-tree search — see FR-CRM-011-002 §4; a whole-tree grep for FA table names returns exactly `crm_categories`, `crm_contacts`, `crm_persons` for `crm_*`, and no `party` / `household` / `family` / `ownership` table exists |

## 5. Out of scope and open questions

**Out of scope:** transaction re-pointing; the accounting decision of *which* GL
accounts a subsidiary posts to (the requirement makes it an input, not an
answer); automatic sale processing; consolidation reporting.

| # | Question | Recommendation | Blocks |
|---|---|---|---|
| **OQ-D** | Which GL accounts does the new company post to? | **Accounting decision, not ours.** Implement so the GL assignment is a **required input**. | nothing — the requirement implements the recommendation |
| **OQ-B** | (inherited) transaction re-pointing | Not in scope for any variant. | nothing at identity level |

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §3.4 (SPIN-OFF), §6.3.
- **Shared requirements (proposed, not yet written):** `FR-COM-010-007` (spin-off
  operation), `FR-COM-010-004` (account, role and link schema),
  `FR-COM-010-011` (soft link).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.4.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` (**I8**).
- **Use case:** `UC-CRM-011-003-spin-off-and-sell-a-subsidiary.md`.
