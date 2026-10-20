# FR-CRM-011-003 — Account split

@BABOK Related: BR-CRM-011; FR-CRM-011-001; FR-CRM-011-008; UC-CRM-011-002;
          PARTY-ACCOUNT-MODEL-NOTES §1.3, §1.9, §3; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "One case we haven't considered/discussed is divorce — splitting an account into
> 2."

The owner's framing is the requirement: **SPLIT is the inverse of MERGE**, and
it is a first-class operation — not "merge run backwards", and not an edit.

**The situation.** A household account (FR-CRM-011-002's marriage merge produced
it) has to become two accounts again. One party keeps the house; the other takes
the flat. The business wants the estate to still answer "these two accounts were
one until March".

**Why it is its own operation and not a special merge.** A merge *absorbs*; a
split *divides*. A merge leaves one account and retires the other; a split leaves
**two live** accounts that are independent commercial identities from that moment
on. A merge is one-way in effect (the successor link records the history); a
split is a genuine fork.

**What makes the split hard is not the account — it is the shared contact.**
The family's **shared mailbox** is one `0_crm_persons` row. After the split, does
that row stay with the household, go with the departing person, or exist on both?

- Details that belong to the **departing person alone** → **MOVE** the relation.
- Details that are the **household's** (a shared mailbox, a shared landline) →
  **CLONE-WITH-LINK**: keep the original card on the household account, create a
  second card for the new account, and record that the two cards are one record
  family (FR-CRM-011-008).

Deleting the original would destroy the household's correspondence history.
Moving it would take the household's mail with the departing person. **Neither
is acceptable as a blanket rule**, so the choice is made per contact and
recorded.

## 2. Requirement

### 2.1 The operation

`AccountLifecycleInterface::split(SplitRequest): SplitOutcome`.

**Inputs:** source account, new account name, new account reference, new party's
type (default: a household of one), the branches that stay with the source, the
contacts to move or clone, whether the balance stays with the source (default
**yes**), reason, actor, effective date.

### 2.2 Preconditions

1. The source account exists and is `active`.
2. The new reference is unique in `0_debtors_master`
   (`sql/en_US-new.sql:604`).
3. Every branch named as staying belongs to the source account.
4. The source account keeps **at least one branch** — FA invoices against a
   branch, and a branch is the mandatory child record of an account
   (`sales/manage/customer_branches.php`).
5. Every contact named for moving or cloning belongs to the source account.

### 2.3 Effects

| Step | What happens | FA primitive / build |
|---|---|---|
| 1 | The new account is created | `add_customer()` (`sales/includes/db/customers_db.inc:13-28`) then `db_insert_id()` (`includes/db/connect_db_mysqli.inc:173`) — **`add_customer()` returns nothing** |
| 2 | The branch (or branches) that move are created on the new account | `add_branch()` (`sales/includes/db/branches_db.inc:12-39`) |
| 3 | The branch that stays is removed from the source account | `delete_branch()` (`:65-72`) — note it also removes the branch's `cust_branch`-type contacts |
| 4 | A new party is created — a household of one — with the moving person as its single member; the source household's membership of that person is ended or the member removed | — **[BUILD]** |
| 5 | Each selected contact is **moved** or **cloned** | `add_crm_contact()` / `delete_crm_contact()` (`includes/db/crm_contacts_db.inc:251-258`, `:263-270`); the clone is FR-CRM-011-008 |
| 6 | The source account stays **active** | — |
| 7 | A `split_from` link is written from the new account to the source | — **[BUILD]** |
| 8 | One event record is written | — **[BUILD]** |

### 2.4 The move-vs-clone rule

> **Default: CLONE a contact whose email is shared with a person remaining on the
> source account; MOVE otherwise.**

The rule is stated as a rule, not as an operator question, because it is
detectable from the contact's own fields. The operator may override it per
contact, and the override is recorded in the event.

### 2.5 Balances, credit and terms

**The source account keeps the balance, the credit limit and the payment terms by
default** (`keepBalanceOnSource = true`). The new account starts with the same
terms and an empty ledger. **No transaction rows are touched.**

**The commercial treatment of a divorce is a product decision** (**OQ-C**): who
keeps the credit limit and the debt is not ours to decide, and FA offers no
ledger-level split. Getting it wrong creates two accounts with the same credit
exposure. The requirement therefore:

1. implements the safe default;
2. makes the split screen state the treatment explicitly; and
3. refuses to guess — an operator who wants the balance moved is refused with a
   clear message, not silently given one.

### 2.6 What a split does **not** do

- It does **not** merge or dissolve the household **party**. The household may
  continue to exist for other members.
- It does **not** re-point transactions, allocations, orders or credit notes.
- It does **not** end the departing person's **person** record, and it does not
  merge the two people — a divorce makes two people, it does not make one.

### 2.7 The CRM's place in the split

- The CRM initiates the split from its account screens and records it; the shared
  package performs it.
- The CRM's `0_ksf_crm_account_relationships` table is **not** the split's link
  store: a `split_from` link is a distinct, typed, dated fact and must not be
  folded into a generic parent/child row.
- The CRM's `0_ksf_crm_life_events` may record the divorce (a personal timeline,
  in the CRM — **OQ-G**), keyed by the **person id** of both spouses after
  FR-CRM-011-001's re-pointing.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | Splitting a household account produces **two active accounts**, a `split_from` link between them, and one event record. |
| **AC-2** | The new account is created through FA's own `add_customer()` and its id is read with `db_insert_id()` — no CRM-side INSERT into `0_debtors_master`. |
| **AC-3** | The source account keeps at least one branch; a split that would leave it with none is refused. |
| **AC-4** | Every selected contact is recorded as **moved** or **cloned**, and the record says which. |
| **AC-5** | A contact whose email is shared with someone remaining on the source account is **cloned** by default; a contact whose details belong to the departing person alone is **moved** by default; the operator may override either, and the override is recorded. |
| **AC-6** | When a contact is cloned, the household account **keeps** its card and its history; when a contact is moved, the source no longer lists it. |
| **AC-7** | The source account keeps the balance, the credit limit and the payment terms; the new account starts with the same terms and an empty ledger. **No transaction row is modified.** |
| **AC-8** | The split screen states the balance treatment before the operator confirms. |
| **AC-9** | The two people remain **two** people; neither is merged into the other, and neither is dissolved. |
| **AC-10** | A split whose new reference would collide with an existing `debtor_ref` is refused before anything is written. |
| **AC-11** | The CRM writes the split's link and event through the shared package's contract; no CRM code writes the shared tables directly. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `add_customer()` — 14 arguments, **returns nothing** | `sales/includes/db/customers_db.inc:13-28` |
| `db_insert_id()` | `includes/db/connect_db_mysqli.inc:173` |
| `add_branch()` / `update_branch()` / `delete_branch()` — branch CRUD; `delete_branch` also removes the branch's `cust_branch` contacts | `sales/includes/db/branches_db.inc:12-39` / `:65-72` |
| `get_cust_branch()` / `get_branch()` / `get_sql_for_customer_branches()` | `sales/includes/db/branches_db.inc:96` / `:82` / `:142` |
| `add_crm_contact()` / `delete_crm_contact()` / `delete_crm_contacts()` | `includes/db/crm_contacts_db.inc:251-258` / `:263-270` / `:272-289` |
| `delete_entity_contacts()` | `includes/db/crm_contacts_db.inc:176-186` |
| `0_debtors_master.debtor_ref` is `UNIQUE` | `sql/en_US-new.sql:604` |
| `0_cust_branch` — `branch_code` + `debtor_no` composite PK; `branch_ref` is only a `KEY` | `sql/en_US-new.sql:569`, `:570` |
| A branch is the mandatory child record of an account | `sales/manage/customer_branches.php`; FA auto-creates one when `auto_create_branch` is set (`sales/manage/customers.php:111-119`) |
| **No** data-level clone of a debtor or branch | whole-tree search — see FR-CRM-011-002 §4 |

## 5. Out of scope and open questions

**Out of scope:** transaction re-pointing; moving balances; dissolving the
household party; merging or dissolving the people involved.

| # | Question | Recommendation | Blocks |
|---|---|---|---|
| **OQ-C** | Who keeps the balance, the credit limit and the payment terms? | **The source account keeps them**; the new account starts with the same terms and an empty ledger. | nothing — the requirement implements the recommendation as the safe default; the *commercial* answer remains open |
| **OQ-B** | (inherited from FR-CRM-011-002) transaction re-pointing | Not in scope for any variant. | the full split, not the identity-level split |

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §3.3 (SPLIT), §6.1, §6.3.
- **Shared requirements (proposed, not yet written):** `FR-COM-010-006` (split
  operation), `FR-COM-010-010` (clone-with-link), `FR-COM-010-014`.
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.3.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` (**I4**,
  **I8**).
- **Use case:** `UC-CRM-011-002-split-a-household-account-on-divorce.md`.
