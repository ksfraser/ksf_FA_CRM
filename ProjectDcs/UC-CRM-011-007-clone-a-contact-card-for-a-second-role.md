# UC-CRM-011-007 — Clone a contact card for a second role

Status: Draft
Scope : ksf_FA_CRM (initiates) / ksf_FA_Common shared package (performs)
Author: KSF
Last  : 2026-10
@BABOK Related: BR-CRM-011, FR-CRM-011-001, FR-CRM-011-008
@UML: FR-CRM-011-008 §2

## Use Case

Dr Alvarez's customer card carries her personal address
`alvarez@example.org`. Her Riverside Trust directorship needs
`dr.alvarez@riversidetrust.org`. The estate must give the directorship its own
card **without** creating a second person — and must be able to tell, later,
that a card is a deliberate shadow rather than a mistake.

A second scenario runs alongside: an operator used FA's own **"Clone"** button
on the contact panel and produced two identical cards by accident. The estate
must **report** that pair, not silently accept it and not silently repair it.

## Actors

- **CRM operator:** the operator creating the shadow card, and the reviewer
  clearing the reconciliation report.
- **Dr Alvarez:** the person wearing both hats.
- **Primary actor:** ksf_FA_CRM, via the shared package's `AccountLifecycleInterface::cloneContactCard()`.

## Preconditions

1. Dr Alvarez's person record exists with a **primary** card.
2. The operator can name the **scope role** for the new card.
3. The new card's contact details differ from the source card's in at least one
   field.

## Flow — the deliberate shadow

1. The operator opens Dr Alvarez's customer card and chooses **clone for
   another role**.
2. The operator names the scope role — `party_member:director` — and replaces
   the email with the trust address.
3. The screen warns that the new card is a **shadow** of the same person, and
   that the person record itself is unchanged.
4. The operator confirms.
5. A new card is created through FA's own `add_crm_person()`, with a **distinct
   reference**, and its new id is taken from the return value.
6. The shadow → root mapping is written, with the card kind `shadow` and the
   scope role.
7. The new card is attached to the Riverside Trust through FA's own
   `add_crm_contact()`.
8. One event record is written.

## Flow — the probable duplicate, found by reconciliation

9. The reconciliation job finds two cards whose email **and** phone **and** fax
   are identical and which declare no distinct role.
10. The job **reports** the pair. It does not repair it and does not delete
    anything.
11. The reviewer decides: either merge the two cards into one person, or
    re-point the second row to a different person. Both are **person-level**
    decisions, distinct from a card clone and from an account merge.

## Acceptance

1. The new card was created through FA's native `add_crm_person()` and its id
   came from the return value.
2. The new card has a **different reference** from the source card.
3. The mapping points from the card to the **root person**, and the card is
   marked `shadow` with the scope role `party_member:director`.
4. Dr Alvarez still has exactly **one** `primary` card, and the person record
   is unchanged.
5. Her personal details are still on the primary card; the trust's details are
   on the shadow card.
6. A clone attempted **without** a scope role was refused.
7. A clone attempted with **identical** details in every field was refused.
8. The accidental pair (same email, phone and fax, no declared role) was
   **reported** by the reconciliation job and **not** reported as a shadow.
9. A pair differing in exactly **one** field was **not** reported.
10. The card merge, the person merge and the account merge are three distinct
    operations in the UI; the reviewer in step 11 could not have triggered an
    account merge by mistake.
