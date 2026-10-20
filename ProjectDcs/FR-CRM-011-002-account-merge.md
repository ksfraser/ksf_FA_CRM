# FR-CRM-011-002 — Account merge

@BABOK Related: BR-CRM-011; FR-CRM-011-001; UC-CRM-011-001;
          PARTY-ACCOUNT-MODEL-NOTES §1.2, §1.9, §3; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "A single person getting married would be similar to a corporate merger or
> buyout — the company name might change but the contact details shouldn't,
> except maybe for position/title?"

> "Of course it could also involve address/phone/email updates but that can happen
> outside of a 'merger' (marriage)."

Three separate assertions are packed into those two sentences, and they pull in
different directions:

1. **One operation, many cases.** A marriage, a corporate merger, a buyout and a
   brand rename are the *same* operation with different variants. The estate must
   not grow a "marriage feature" and a "merger feature".
2. **What a merge may change.** The account's **name** may change. A card's
   **title / position** may change. **Contact details must not.**
3. **What a merge must not absorb.** The contact-detail edits that happen around
   a marriage (they move house, they change email) are **ordinary edits**, done
   separately and audited separately. They are not merge semantics and must not
   be modelled as part of the merge.

**The operation is not in FA.** FA 2.4.3 has no data-level clone, no
`merge_customer`, no `copy_customer` and no `duplicate_branch`; a whole-tree
search for clone/copy functions returns only `copy_file`
(`includes/main.inc:335`), `copy_files` (`includes/main.inc:318`), `url_copy`
(`includes/remote_url.inc:79`), the sales/purchasing cart and credit-note copies,
the admin security-role clone (`admin/security_roles.php:141-162`) and the generic
CRUD "Clone" action (`includes/ui/class.crud_view.inc:303-304`,
`includes/ui/simple_crud_class.inc:201-227`). **Every merge is therefore [BUILD],
composed from FA's native primitives.**

## 2. Requirement

### 2.1 The operation

`AccountLifecycleInterface::merge(MergeRequest): MergeOutcome`, with a
`variant` of `marriage` | `corporate` | `buyout` | `rename`.

**Inputs:** surviving account, absorbed account, variant, new name (optional),
new reference (optional), the list of contact relations to move, the title
changes (optional), reason, actor, effective date.

### 2.2 Preconditions

1. Both accounts exist and are distinct.
2. The surviving account is not already `merged`, `split` or `closed`.
3. The absorbed account has no operation in flight (no pending split or
   spin-off).
4. Every relation selected for moving belongs to the **absorbed** account.
5. If a contact holds the **same role** on both accounts, the conflict is
   resolved explicitly: the surviving account's role wins and the absorbed row is
   **ended, not duplicated**.
6. A new reference, if given, is unique — FA's `0_debtors_master.debtor_ref` is
   `UNIQUE` (`sql/en_US-new.sql:604`).

### 2.3 Effects — and specifically what a merge does **not** touch

| Aspect | What the merge does | FA primitive |
|---|---|---|
| Account name / reference | **may** change | `update_customer()` (`sales/includes/db/customers_db.inc:30-51`) — note it carries **no `inactive`** column |
| Card title / position | **may** change, and only on the **card**, never on the person | `update_crm_person()` (`includes/db/crm_contacts_db.inc:43-68`) |
| Card email / phone / fax / address | **unchanged** — the owner's words: *"the contact details shouldn't"* | — |
| Selected contacts | moved: create the relation on the surviving account, delete it on the absorbed one | `add_crm_contact()` (`:251-257`) + `delete_crm_contact()` (`:263-270`) |
| The absorbed account | **deactivated, never deleted** | `update_record_status($debtor_no, 1, 'debtors_master', 'debtor_no')` (`includes/db/sql_functions.inc:58-63`) |
| The absorbed account's **transactions** | **not touched** — see §2.5 | — |
| Soft links | `successor` and `predecessor` links written both ways | — |
| Audit | one event row per merge | — |

**Why the absorbed account is deactivated and not deleted.** FA's
`delete_customer()` (`sales/includes/db/customers_db.inc:53-61`) deletes the
debtor **and** its `customer`-type contacts inside a transaction, and then deletes
the debtor row — which would destroy the ledger context the soft link exists to
preserve. Deactivation is FA-native, reversible, and leaves the history legible.

### 2.4 Marriage specifically

A marriage merge produces:

1. **one account** (the surviving one, optionally renamed);
2. **one household party** with **two** members — one `spouse`, one `member`;
3. **two persons, still two.** The merge merges the *party structure* and the
   *account*. It does **not** merge the human beings. If it did, the divorce
   (FR-CRM-011-003) would be inexpressible, because there would be only one
   person left to give an account to.

This is the sharpest test of the model, and it is the reason PERSON and PARTY
must be separate concepts.

### 2.5 What a merge must **not** do — the warning the screen must carry

- **No transaction re-pointing.** FA has no ledger-level merge. Moving an
  account's invoices, orders, allocations or credit notes means rewriting
  `debtor_trans`, `cust_allocations`, sales orders and credit notes and
  re-checking every report. That is a separate project, and until it exists the
  estate has **two account histories**.
- **Therefore a merge screen must warn**: *"balances stay on the absorbed
  account; it is retired, not deleted."* This is incomplete-handling item **I4**.
- **No contact-detail edit.** A contact-detail change made in the same session
  is a **separate** operation with its own audit row. The merge record must not
  contain it.

### 2.6 Variants and their safe scope

| Variant | Case | Safe scope today |
|---|---|---|
| `rename` | brand rename; same account, new name | **Implementable and shippable now.** Only `update_customer()` is called; one row, name and reference only. |
| `marriage` | two individual accounts → one household | Identity only; no transaction re-pointing; the two persons remain distinct. |
| `corporate` | one company absorbs another | As `marriage`, plus the absorbed company's party marked `merged` and its directors/owners carried over as memberships with end dates on the old rows. |
| `buyout` | acquisition | As `corporate`. |

**The merge variants that need a product decision are not blocked from being
*specified*; they are blocked from being *shipped whole*.** The rename variant
is the safe subset the estate can ship while **OQ-B** (what happens to the
absorbed account's transactions) is open.

### 2.7 The CRM's place in the merge

- The CRM **initiates** merges from its account screens and **records** them; the
  shared package **performs** them. The CRM writes no shared-model table
  directly.
- The CRM's existing `0_ksf_crm_account_relationships` table
  (`parent_debtor_no` / `child_debtor_no` / `relation_type` / `ownership_pct`)
  is **not** the merge's successor/predecessor store. The successor/predecessor
  links are a distinct, typed fact with dates and must not be folded into a
  generic "parent/child" row.
- The CRM's `0_ksf_crm_life_events` may record the marriage itself (it is a
  personal timeline and stays in the CRM — **OQ-G**), keyed by the **person id**
  of each spouse after FR-CRM-011-001's re-pointing.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | Merging two accounts deactivates the absorbed account through FA's own `update_record_status()` and **never** calls `delete_customer()`. The absorbed account's row still exists afterwards. |
| **AC-2** | The surviving account's name and reference can be changed as part of the merge; a card's title can be changed. |
| **AC-3** | No email, phone, fax or address is changed by a merge. A contact-detail change made in the same session is recorded as a **separate** operation with its own audit row, and the merge record does not contain it. |
| **AC-4** | A marriage merge leaves **two** persons and **one** household party with two members and **one** account. |
| **AC-5** | The absorbed account's transactions, allocations, orders and credit notes are **unchanged**, and the merge screen states so before the operator confirms. |
| **AC-6** | A `successor` and a `predecessor` link are written, both visible from either account, both dated. |
| **AC-7** | A merge whose new reference would collide with an existing `debtor_ref` is refused before anything is written. |
| **AC-8** | A merge where a contact holds the same role on both accounts is refused unless the operator resolves the conflict explicitly; the absorbed role row is **ended**, not deleted and not duplicated. |
| **AC-9** | Every merge writes exactly one event record carrying the variant, the reason, the actor and the effective date, and the record survives the operation. |
| **AC-10** | The `rename` variant changes exactly one FA row's name and reference and touches nothing else — verifiable by row count before and after. |
| **AC-11** | The CRM writes the merge's links and event through the shared package's contract; no CRM code writes the shared tables directly. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `add_customer()` — 14 arguments, **returns nothing**; the caller must read `db_insert_id()` | `sales/includes/db/customers_db.inc:13-28`; `db_insert_id()` at `includes/db/connect_db_mysqli.inc:173`; FA's own use at `sales/manage/customers.php:109` |
| `update_customer()` — 15 arguments, **does not carry `inactive`** | `sales/includes/db/customers_db.inc:30-51` |
| `delete_customer()` — deletes the debtor **and** its `customer`-type contacts, in a transaction | `sales/includes/db/customers_db.inc:53-61` |
| `update_record_status($id,$status,$table,$key)` — sets `inactive`; auto-loaded | `includes/db/sql_functions.inc:58-63`; `includes/main.inc:18` |
| FA's customer screen deactivates a customer exactly this way | `sales/manage/customers.php:94-95` |
| `add_crm_contact()` / `delete_crm_contact()` | `includes/db/crm_contacts_db.inc:251-258` / `:263-270` |
| `update_crm_person()` | `includes/db/crm_contacts_db.inc:43-68` |
| `delete_entity_contacts()` — removes every relation of an entity and cleans up orphaned persons | `includes/db/crm_contacts_db.inc:176-186` |
| `0_debtors_master.debtor_ref` is `UNIQUE` | `sql/en_US-new.sql:604` |
| **No** data-level clone of a debtor / branch / contact / person | whole-tree search: `includes/main.inc:318,335`, `includes/remote_url.inc:79`, `admin/security_roles.php:141-162`, `includes/ui/class.crud_view.inc:303-304`, `includes/ui/simple_crud_class.inc:201-227` — and no debtor/branch clone of any kind |
| `0_debtors_master` has no `email` and no `phone` column | `sql/en_US-new.sql:585-605` |

## 5. Out of scope and open questions

**Out of scope:** transaction re-pointing (a separate project); deletion of the
absorbed account; any contact-detail change; per-role default contact details
(dropped per ambiguity 9 — the defaults were never read).

| # | Question | Recommendation | Blocks |
|---|---|---|---|
| **OQ-A** | May a merge ever **delete** the absorbed account? | **Always deactivate.** Deleting loses the history the successor/predecessor link records. | nothing — the requirement implements the recommendation |
| **OQ-B** | What happens to the absorbed account's **transactions**? | **Do not re-point them.** The absorbed account is retired, not deleted, and the link records the history. | the full merge, not the rename variant |
| **OQ-I** | May a merge change contact details? | **No** — the owner's answer is explicit. Recorded as a question only because a variant that did would be a different operation. | nothing — the requirement implements the recommendation |

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §3.2 (MERGE), §6.1 (what must be built because FA has no clone),
  §6.3 (what needs a decision first).
- **Shared requirements (proposed, not yet written):** `FR-COM-010-005` (merge
  operation), `FR-COM-010-012` (resolver composition and hooks),
  `FR-COM-010-014` (incomplete handling).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.2.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` (**I4**).
- **Use case:** `UC-CRM-011-001-merge-two-accounts-on-marriage.md`.
