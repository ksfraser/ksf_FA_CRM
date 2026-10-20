# UC-CRM-011-005 — Soft-link two related accounts

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-006
@UML: FR-CRM-011-006 §2

## Use Case

Acme Ltd and the Fenwick Group have separate customer accounts and no business
relationship the estate models. But the Fenwick Group owns the building Acme Ltd
trades from, and the two accounts must be **findable** and **labelled** from
either side — without being merged and without any data moving between them.

## Actors

- **Account handler / clerk:** the operator declaring the relationship.
- **Acme Ltd / Fenwick Group:** the two account holders.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::linkAccounts()`.

## Preconditions

1. Both accounts exist and are active.
2. The pair is not already linked with the chosen type.

## Flow

1. The operator opens the link action on one of the accounts and selects the
   other.
2. The operator chooses a link type from the catalogue, or enters an
   operator-defined label.
3. The operator may supply an effective date, and may write a short note.
4. The screen states that **no data moves** between the two accounts.
5. The operator confirms.
6. The link row is written.
7. Optionally, a breadcrumb line is appended to the surviving account's FA
   `notes` — the current row is read, concatenated and written back, so the
   account's other fields are untouched.
8. One event record is written.

## Acceptance

1. The link is visible from **both** accounts, with its type and its dates.
2. **Nothing moved** between the accounts: no contact, no role, no branch, no
   transaction changed.
3. A second link of the same type between the same pair was refused.
4. The operator-defined type was recorded with the actor and the date.
5. After the operation, the account's name, reference, address, currency, credit
   status, payment terms, discounts, credit limit and sales type are **byte for
   byte what they were** — the breadcrumb did not damage the row.
6. The "accounts this person touches" question is answered by a **derived**
   query, and no stored row duplicates it.
7. Exactly one event record was written.
