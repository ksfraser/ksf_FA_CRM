# UC-CRM-011-001 — Merge two accounts on marriage

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-002, FR-CRM-011-006
@UML: FR-CRM-011-002 §2

## Use Case

Jane Smith and Rob Jones each have their own customer account. They marry. The
business wants them to trade against **one** account — one bill, one account
number — while Jane and Rob remain **two separate people** with their own email
addresses and their own consent records.

## Actors

- **Account handler / clerk:** the operator performing the merge.
- **Jane Smith and Rob Jones:** the two customers.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::merge()`.

## Preconditions

1. Both accounts exist and are active.
2. Jane and Rob each have a person record and at least one card.
3. The surviving account's reference is unique (or a new one is supplied).
4. The operator is on an account screen with a specific account selected.

## Flow

1. The operator opens the merge action on the surviving account and selects the
   account to absorb.
2. The operator chooses the variant **marriage** and, optionally, a new account
   name and reference.
3. The screen lists every contact relation on the absorbing account and lets the
   operator choose which to move. A contact holding the same role on both
   accounts is flagged as a conflict and must be resolved explicitly.
4. The screen states, in words: **the account name may change; contact details
   will not change; the balance stays on the absorbed account, which is retired,
   not deleted.**
5. The operator confirms.
6. The account is renamed if a new name was given.
7. The selected contact relations are created on the surviving account and
   removed from the absorbed one.
8. The absorbed account is **deactivated** (FA's `inactive` flag); its row, its
   ledger and its transactions are untouched.
9. A **household party** is created with two members — one `spouse`, one `member`
   — and Jane and Rob are attached to it.
10. `successor` and `predecessor` links are written between the two accounts.
11. One event record is written, carrying the variant, the reason, the actor and
    the effective date.

## Acceptance

1. There is **one** account afterwards, and it is the surviving one.
2. There is **one** household party with **two** members.
3. Jane and Rob are **still two persons** — the merge did not merge them.
4. The absorbed account still exists as a row and is deactivated; its invoices,
   allocations, orders and credit notes are unchanged.
5. No email, phone, fax or address changed. A contact-detail change made in the
   same session is a separate, separately-audited edit.
6. Both accounts list each other through the successor/predecessor links.
7. Exactly one event record was written, and it names the variant `marriage`.
8. If the operator supplied a reference that collides with an existing
   `debtor_ref`, the merge was refused and nothing was written.
9. A contact that held the same role on both accounts was resolved explicitly:
   the absorbed role row was **ended**, not duplicated and not deleted.
