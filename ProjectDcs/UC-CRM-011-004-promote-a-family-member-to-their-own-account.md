# UC-CRM-011-004 — Promote a family member to their own account

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-005, FR-CRM-011-008,
               BR-CRM-09
@UML: FR-CRM-011-005 §2

## Use Case

The Smith household account carries Jane (spouse), Rob (spouse) and Tom, their
sixteen-year-old. Tom is now eighteen and wants to trade in his own right — a
phone contract, a repair booking, his own credit. He still lives at home.

## Actors

- **Account handler / clerk:** the operator performing the promotion.
- **Tom Smith:** the family member being promoted.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::promoteMember()`.

## Preconditions

1. The household account exists and is active.
2. Tom is a member of the household party and holds a `family_member` role on
   the household account.
3. The new account reference is unique (or a new one is supplied).
4. Tom does not already hold a `customer_contact` role on an account of his own.

## Flow

1. The operator opens the promotion action from the household account's
   member list.
2. The operator supplies the new account name and reference.
3. The screen shows Tom's card and names the **default** strategy: **move** when
   his card carries his own email and phone; **clone** when it carries the
   family's. The operator may override it.
4. The screen states that the household **membership** stays by default and that
   **no consent record changes**.
5. The operator confirms.
6. The new account is created through FA's native `add_customer()`, with its
   default branch.
7. A new party is created — a household of one — with Tom as its single member.
8. Tom's `family_member` role on the household account is **ended with a date**;
   the row is retained.
9. A `customer_contact` role is created on the new account.
10. Tom's card is moved or cloned as chosen. A **cloned** card leaves the family's
    card in place and gives Tom a second card, marked as a shadow of the same
    person, on the new account.
11. The household party's membership of Tom is **unchanged** — he still lives
    there — unless the operator ended it.
12. One event record is written.

## Acceptance

1. Tom has his **own** account, with a party that is a household of one.
2. His `family_member` role on the household account is **ended with a date**,
   and the row is still there.
3. The household **party membership is unchanged** — the family record still
   shows him, because he still lives at home.
4. A `customer_contact` role exists on the new account for Tom.
5. With the default rules, a card carrying Tom's own email was **moved**, and a
   card carrying the family email was **cloned**; the strategy used is recorded.
6. Where the card was cloned, the household account kept its card and its
   history, and Tom's new card is marked as a shadow of the same person.
7. **No consent record was created, changed or removed.** Tom's marketability
   is exactly what it was before the promotion.
8. Tom is still **one person** — no second person record was created and the
   original was not merged or dissolved.
9. The household account is still active, still has its branches, and still has
   Jane's and Rob's roles.
10. Exactly one event record was written.
