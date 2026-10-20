# UC-CRM-011-006 — Associate a person with an organisation

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-007, FR-CRM-011-008
@UML: FR-CRM-011-007 §2

## Use Case

Dr Alvarez is a customer of the shop. She is also a director of the Riverside
Trust and of the County Food Bank. The estate must record all three hats against
**one** person, and — because the trust's correspondence should not go to her
personal address — the directorship may carry its own contact card.

## Actors

- **CRM operator:** the operator recording the associations.
- **Dr Alvarez:** the person.
- **Riverside Trust / County Food Bank:** the organisations.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::associatePerson()`.

## Preconditions

1. Dr Alvarez's person record exists.
2. The Riverside Trust's party record exists.
3. The (trust, person, director) triple is not already present.

## Flow

1. The operator opens Dr Alvarez's person record and chooses **associate with an
   organisation**.
2. The operator selects the Riverside Trust and the role **director**, with a
   start date.
3. The operator asks for a **role-scoped card** and supplies the trust's contact
   email and phone, in place of her personal ones.
4. The operator confirms.
5. The membership row is written with the role and the start date.
6. The association is expressed natively: FA's `add_crm_contact()` creates a
   relation of a dedicated category type — `('party_member','director')` — and
   the matching `0_crm_categories` row is seeded through FA's own
   `add_crm_category()` if it does not yet exist.
7. The role-scoped card is created as a **clone-with-link** of her primary card,
   carrying the trust's contact details, and is marked as a **shadow** of the
   same person with the scope role `party_member:director`.
8. One event record is written.

## Acceptance

1. Dr Alvarez is still **one person**. No duplicate person record was created.
2. She now holds a `director` membership in the Riverside Trust with the stated
   start date, and the membership is retrievable from both the person and the
   organisation.
3. The association is visible in FA's own contact screens for the trust.
4. The trust's contact details are on the **shadow card**, and her personal
   details are still on her primary card. Neither was overwritten.
5. The shadow card names the person it is a shadow of and the role it carries.
6. A second `director` membership of the same trust was refused.
7. The family axis and the organisational axis are distinguishable — a spouse
   membership of a household is not recorded as a directorship.
8. Exactly one event record was written.
