# UC-CRM-007-003 — Decide whether a contact may be marketed to

Status: Approved
Scope : ksf_FA_CRM (consent resolver), ksf_FA_EmailManager (consumer)
Author: KSF
Last  : 2026-09
@BABOK Related: BR-CRM-09, FR-CRM-007-004
@UML: FR-CRM-007-002 §3.2

## Use Case

A campaign is about to send. For each target the sender must decide whether that
person may receive marketing mail, and be able to say why not. The answer depends
on the person's own consent, and — only for a business contact mailed at the
business address — on the account's consent.

## Actors

- **Primary actor:** ksf_FA_CRM (`ConsentService::isMarketingEligible()`).
- **Secondary:** ksf_FA_EmailManager campaign dispatch (FR-CRM-004-002
  suppression).

## Preconditions

1. A campaign is being expanded to targets (FR-CRM-004-002).
2. Each target resolves to a contact (`crm_persons`) and, where relevant, to a
   debtor (`debtors_master`).

## Flow

1. The dispatch asks whether contact P may be marketed to, with the address A.
2. CRM reads P's own consent row for `marketing`.
3. If P is `opted_out` → return **not eligible**, reason "contact opted out".
   Stop.
4. If P is `opted_in` → return **eligible**, reason "contact opted in". Stop.
5. If P has no row or is `pending`:
   a. If P's debtor is classified `B` **and** A is the debtor's business address
      → return **eligible**, reason "inherited from business account".
   b. Otherwise → return **not eligible**, naming the missing record.
6. Service-purpose mail takes a separate path that does not consult the marketing
   decision.

## Acceptance

1. A contact with no consent record is never eligible; the reason names the
   missing row.
2. `opted_out` is absolute: a consenting business debtor does not re-enable a
   contact who declined.
3. Business-address mail to an employee contact inherits the business account's
   consent.
4. The same employee contacted at a personal address does **not** inherit.
5. A family account's consent never makes a family member eligible.
6. Every negative result is accompanied by a human-readable reason.
7. A `service` send to a non-marketing contact is still permitted.
