# FR-CRM-011-005 — Family-member promotion to an account of their own

@BABOK Related: BR-CRM-011; FR-CRM-011-001; FR-CRM-011-008; UC-CRM-011-004;
          PARTY-ACCOUNT-MODEL-NOTES §1.5, §1.9, §3; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "Families could shed children as they become adults — they should then have
> their own accounts."

**The situation.** The Smith household account carries three contacts: Jane
(spouse), Rob (spouse) and Tom, their sixteen-year-old. Tom is now eighteen and
wants to trade in his own right — a phone contract, a repair booking, his own
credit.

**Why this is an operation and not an edit.** Two *different* facts have to end,
and they end **separately**:

1. Tom's **account role** on the household account (`family_member`) ends.
2. Tom's **membership of the household party** may **continue** — he still lives
   at home.

"Lives at home" and "trades with us as a separate customer" are different facts
with different end dates. If the model collapsed "family member" into one flag,
promoting a child would either evict him from the household record — destroying
the family's picture of itself — or leave him trading on the family account,
which is the thing the promotion was meant to stop.

**Why this is not SPLIT.** A split divides a household into two households. A
promotion takes one member *out* of the household account and gives them an
account of their own; the household continues, undivided.

## 2. Requirement

### 2.1 The operation

`AccountLifecycleInterface::promoteMember(PromoteRequest): PromoteOutcome`.

**Inputs:** household account, person, new account name, new account reference,
the card strategy (`move` | `clone`), whether the household membership is kept
(default **yes**), reason, actor.

### 2.2 Preconditions

1. The person is a **member of the household party** and holds a `family_member`
   role on the household account.
2. The new reference is unique — FA's `0_debtors_master.debtor_ref` is `UNIQUE`
   (`sql/en_US-new.sql:604`).
3. The person does not already hold a `customer_contact` role on an account of
   their own.

### 2.3 Effects

| Step | What happens | FA primitive / build |
|---|---|---|
| 1 | A new **individual** account is created, with its default branch | `add_customer()` + `db_insert_id()`; `add_branch()` |
| 2 | A new party is created — a **household of one** — with the promoted person as its single member | — **[BUILD]** |
| 3 | The `family_member` role on the household account is **ended** (`status = ended`, with an end date) — the row is **kept**, not deleted, so the history survives | — **[BUILD]** |
| 4 | A `customer_contact` role is created on the new account | — **[BUILD]** |
| 5 | The person's card is **moved** or **cloned** per the strategy | `add_crm_contact()` / `delete_crm_contact()`; clone per FR-CRM-011-008 |
| 6 | If the household membership is kept, the party membership row is **unchanged**; if not, it is ended with the same date | — **[BUILD]** |
| 7 | Optionally, a `split_from` link from the new account to the household account is written | — **[BUILD]** |
| 8 | One event record is written | — **[BUILD]** |

**Step 3 and step 6 are the whole point of the requirement.** They are different
facts and they are ended independently.

### 2.4 The card strategy — move or clone

The same question as the split (FR-CRM-011-003 §2.4), because it is the same
question:

> **Default: MOVE the card when the child has their own email and phone; CLONE
> it when the card carries the family address and the family email.**

Both cases are **detectable from the card's own fields**, so the default is a
rule, not an operator question. The operator may override it; the override is
recorded.

- **Move** — the child's details are their own. The family account loses the
  relation; the new account gains it.
- **Clone** — the card carries the *family's* details. The family account keeps
  its card (and the family's correspondence history); the child gets a second
  card, marked as a shadow of the same person, on the new account. This is
  FR-CRM-011-008's operation, and it is the reason the clone must exist at all.

**Getting this wrong is not cosmetic.** Moving a family-address card breaks the
family's correspondence; cloning a child's own card duplicates the child's mail
to the household. That is why the rule is written down rather than left to the
operator's judgement.

### 2.5 Consent is unaffected by a promotion

Per `BR-CRM-09`, marketing consent is **opt-in** and its inheritance is
asymmetric by account type: an `F` (family) account **never** inherits consent
to its members, because every adult member is a separate natural person with
their own rights and children cannot consent at all.

**Therefore a promotion must not change a single consent record.** The child's
absence of consent is unchanged when they become a customer of their own. The
promotion changes **who we trade with**; it does not change **what we may send
them**. A screen that offers "promote this member" shall not imply that
promotion unlocks marketing.

### 2.6 What a promotion does **not** do

- It does not end the person's **person** record, and it does not create a new
  person — the same human being, one more account.
- It does not touch the household's **party**, its other members, or its
  branches.
- It does not touch transactions, and it does not move any balance.

### 2.7 The CRM's place in the promotion

- The CRM initiates the promotion from its account screens and records it; the
  shared package performs it.
- The CRM's `0_ksf_crm_person_account_roles` table is the natural place an
  operator would expect to trigger this from — and it is the table the shared
  model supersedes. The CRM's screen must therefore be repointed to the shared
  role index **before** the promotion action is offered, so that the role it
  reads is the role it acts on.
- The CRM's `0_ksf_crm_life_events` may record the event (a personal timeline,
  in the CRM — **OQ-G**), keyed by the **person id** after FR-CRM-011-001's
  re-pointing.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | Promoting a family member creates a new account and a new party (a household of one) with that person as its single member. |
| **AC-2** | The `family_member` role on the household account is **ended with a date** and the row is **retained**; it is not deleted and not left active. |
| **AC-3** | The household **party membership is unchanged** by default, and is ended with the same date only when the operator asks for it. |
| **AC-4** | The person's card is moved or cloned per the strategy, and the strategy used is recorded. With the default rules: a card carrying the child's own email and phone is **moved**; a card carrying the family's email is **cloned**. |
| **AC-5** | When the card is cloned, the household account keeps its card and its history, and the child gets a shadow card marked as the same person in a different role. |
| **AC-6** | A `customer_contact` role exists on the new account for the promoted person. |
| **AC-7** | **No consent record is created, changed or removed** by a promotion. The person's marketability is exactly what it was before. |
| **AC-8** | The promoted person remains **one person** — no new person record is created, and the original is not merged or dissolved. |
| **AC-9** | The household account remains `active`, keeps its branches, and keeps its other members' roles. |
| **AC-10** | A promotion whose new reference would collide with an existing `debtor_ref` is refused before anything is written. |
| **AC-11** | The CRM offers the promotion action only after its role screen has been repointed to the shared role index, so the role it reads is the role it acts on. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `add_customer()` — 14 arguments, **returns nothing** | `sales/includes/db/customers_db.inc:13-28` |
| `db_insert_id()` | `includes/db/connect_db_mysqli.inc:173` |
| `add_branch()` | `sales/includes/db/branches_db.inc:12-39` |
| `add_crm_contact()` / `delete_crm_contact()` | `includes/db/crm_contacts_db.inc:251-258` / `:263-270` |
| `delete_entity_contacts()` — removes every relation of an entity and cleans up persons left with no contacts (the trap when a card is moved and the person has no other relation) | `includes/db/crm_contacts_db.inc:176-186` |
| `0_debtors_master.debtor_ref` is `UNIQUE` | `sql/en_US-new.sql:604` |
| A branch is the mandatory child record of an account; FA auto-creates one when `auto_create_branch` is set | `sales/manage/customer_branches.php`; `sales/manage/customers.php:111-119` |
| FA's customer screen writes two relations for one person | `sales/manage/customers.php:120-126` |
| **No** data-level clone of a debtor, contact or person | whole-tree search — see FR-CRM-011-002 §4 |

## 5. Out of scope and open questions

**Out of scope:** guardian consent capture and its legal sign-off (per
`BR-CRM-09`, the no-inheritance rule for `F` accounts is what keeps children
unmarketable in the meantime); authorisation — which family member may transact
against the account is an accounting/permissions question, not a CRM one; moving
balances.

| # | Question | Recommendation | Blocks |
|---|---|---|---|
| **OQ-E** | Does the child's card **move** or **clone** by default? | **Move** when the child has their own email/phone; **clone** when the card carries the family address. Both cases are detectable from the card's fields, so the default can be a rule rather than a question. | nothing — the requirement implements the recommendation |

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §2.3 (the family shedding a child who becomes an adult), §3.5 (PROMOTE),
  §6.3.
- **Shared requirements (proposed, not yet written):** `FR-COM-010-008` (promote
  operation), `FR-COM-010-010` (clone-with-link),
  `FR-COM-010-004` (account, role and link schema).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.5.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md`.
- **Related CRM requirement:** `BR-CRM-09` (consent — the promotion does not
  change it).
- **Use case:** `UC-CRM-011-004-promote-a-family-member-to-their-own-account.md`.
