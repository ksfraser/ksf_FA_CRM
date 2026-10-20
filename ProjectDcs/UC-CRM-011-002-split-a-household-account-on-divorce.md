# UC-CRM-011-002 — Split a household account on divorce

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-003, FR-CRM-011-006,
               FR-CRM-011-008
@UML: FR-CRM-011-003 §2

## Use Case

The Smith household account has to become two accounts again after a divorce.
Jane keeps the house; Rob takes the flat. The business wants a `split_from`
relationship so the estate can still answer "these two accounts were one until
March". The family's **shared mailbox** must remain on the household account
*and* be available on Rob's new one.

## Actors

- **Account handler / clerk:** the operator performing the split.
- **Jane Smith and Rob Jones:** the two former co-holders.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::split()`.

## Preconditions

1. The household account exists and is active.
2. It has at least one branch, and at least one branch will stay with it.
3. The new account reference is unique (or a new one is supplied).
4. A household party exists with both people as members.

## Flow

1. The operator opens the split action on the household account.
2. The operator supplies the new account name and reference, and selects the
   branches that move to the new account. The branches that stay are the
   remainder.
3. The screen lists every contact relation on the household account and shows,
   for each one, the **default** strategy: **clone** where the email is shared
   with someone staying on the source account, **move** otherwise. The operator
   may override each one.
4. The screen states the balance treatment: **the source account keeps the
   balance, the credit limit and the payment terms; the new account starts with
   the same terms and an empty ledger.**
5. The operator confirms.
6. The new account is created through FA's native `add_customer()`, and its id
   is read back.
7. The moving branches are created on the new account; the moving branches are
   removed from the source account.
8. Each contact is moved or cloned as chosen. A **cloned** contact leaves the
   household's card in place and creates a second card for the new account,
   marked as a shadow of the same person.
9. A new party is created — a household of one — with Rob as its single member;
   the household party's membership of Rob is ended or removed.
10. A `split_from` link is written from the new account to the source account.
11. One event record is written, naming the moved/cloned decisions.

## Acceptance

1. There are **two active accounts** afterwards.
2. The source account keeps at least one branch and its balance, its credit limit
   and its payment terms. The new account starts with the same terms and an
   empty ledger.
3. **No transaction row was modified.**
4. The shared family mailbox is reachable from **both** accounts: the household
   still holds its card and its correspondence history; the new account holds a
   shadow card marked as the same person.
5. A contact whose details belonged to Rob alone was **moved**: it is no longer
   listed on the household account.
6. Every contact's move-or-clone decision is recorded.
7. A `split_from` link exists from the new account to the source account and is
   visible from both.
8. Jane and Rob are still **two persons**; neither was merged, dissolved or
   deleted.
9. The household party still exists and still holds Jane as a member.
10. Exactly one event record was written.
