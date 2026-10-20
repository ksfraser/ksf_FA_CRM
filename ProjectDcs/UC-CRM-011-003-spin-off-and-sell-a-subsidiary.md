# UC-CRM-011-003 — Spin off and sell a subsidiary

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-004, FR-CRM-011-006,
               FR-CRM-011-007
@UML: FR-CRM-011-004 §2

## Use Case

Acme Ltd has a subsidiary, Acme Widgets Ltd. Acme Widgets becomes a separate
trading entity with its own account and its own ledger accounts. Eighteen months
later Acme sells Acme Widgets to the Fenwick Group. The estate must be able to
answer, for both events, *when did this happen* and *who was involved*.

## Actors

- **Finance operator:** the operator performing the separation and, later, the
  sale.
- **Acme Ltd / Acme Widgets Ltd / Fenwick Group:** the parties.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::spinOff()`.

## Preconditions

1. Acme Ltd's account exists, is active, and its party is a **company**.
2. The new account reference is unique (or a new one is supplied).
3. The operator can supply the **GL account assignment** for the new branch —
   this is an accounting decision the operator must make, not a default.

## Flow — the separation

1. The operator opens the spin-off action on Acme Ltd's account.
2. The operator supplies the new account name and reference, the new party name,
   the branches that move, the contacts that move or clone, and the ownership
   percentage Acme will hold.
3. The screen presents Acme's **current** GL assignments as a starting point,
   clearly labelled as a copy. The operator edits them or confirms them.
4. The operator confirms.
5. The new company account and its branch are created; the branch carries the
   supplied GL assignment, which is read back and verified.
6. A new **company** party is created, and Acme's party is recorded as a
   `shareholder` member of it with the stated ownership percentage.
7. A `spin_off` link is written from the new account to Acme's account, dated at
   the separation date.
8. One event record is written.

## Flow — the sale, eighteen months later

9. The operator opens the sale action on Acme Widgets Ltd's account and selects
   the **buyer** (Fenwick Group).
10. The operator supplies the sale date and confirms.
11. A `sold` link is written from the subsidiary's account to the buyer's
    account, dated at the sale date.
12. Acme's `shareholder` membership of the subsidiary is **ended** with that
    date; the row is retained.
13. A second event record is written.

## Acceptance

1. Acme Widgets Ltd has its **own** account, its own branch and its own party of
   type *company*.
2. The new branch's GL assignment matches what was supplied, and the spin-off
   was **refused** if none was.
3. Acme's ownership of the subsidiary is recorded as a party membership with the
   stated percentage — not as a link row.
4. The `spin_off` link is dated at the separation date and is visible from both
   accounts.
5. The `sold` link is dated at the sale date, points at the **buyer's** account,
   and is a **separate** record from the spin-off.
6. The subsidiary's account still exists and its ledger is intact; it was not
   deleted.
7. Acme's ownership membership was ended with the sale date and retained.
8. **No transaction row of either company was modified** by either operation.
9. Two event records were written — one for the separation, one for the sale —
   and each names its actor and its date.
10. The CRM's account screen shows the subsidiary as a **spin-off**, visibly
    distinct from an ownership relationship.
