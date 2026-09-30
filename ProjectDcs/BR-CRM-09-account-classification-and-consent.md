# BR-CRM-09 — Account Classification and Consent (opt-in)

**Modules:** ksf_FA_CRM (owner), ksf_FA_EmailManager (consumer of consent),
ksf_FA_HRM (employee identity), native FA `crm_persons`/`crm_contacts`/`debtors_master`
**Status:** PENDING (requirements ratified in this BR; design in FR-CRM-007-002 §3.2)
**Issue:** #14 (dual store), #15 (entry UX), Cluster D
**Related:** FR-CRM-007-001 (external classification), FR-CRM-007-002 (shared layer),
FR-CRM-007-003, FR-CRM-007-004, UC-CRM-007-002, UC-CRM-007-003

## Business Need / Current State

CRM today keeps a parallel `0_fa_crm_contacts` store next to the native FA
`crm_persons`/`crm_contacts` pair, so the same person exists twice (#14), and the
contact entry form re-asks for the customer that the surrounding FA page has
already narrowed to a branch (#15).

Alongside that, two compliance-ish attributes have no home at all:

1. **What kind of account is this?** Native FA has no notion of an individual vs a
   family vs a business debtor.
2. **May we email them?** Native FA has no consent concept whatsoever (see
   `NATIVE-FA-CONTACTS-REFERENCE.md:32`).

Both were previously sketched as columns on a *person* side table. That is the
wrong axis, and getting it wrong is a compliance problem rather than a tidiness
problem.

## The correction: I/F/B describes the DEBTOR, not the contact

A customer is an account. An account is:

- **I — Individual.** One person. The debtor *is* the person.
- **F — Family.** One account, one bill, several real people on it. A video rental
  shop may let the kids rent against the family account; a mobile carrier may put
  a line in each family member's name but issue **one** bill to the "family"
  account. Mom, Dad and each child are each a *contact*; none of them is the
  account.
- **B — Business.** An organisation. Staff are contacts; the organisation is the
  debtor.

So `I/F/B` is a property of `debtors_master` rows, **not** of `crm_persons` rows.
Putting it on the person silently collapses "family account" into "individual",
which then makes every downstream consent and reporting decision wrong.

A family account is also the case that proves the point: three children who can
each transact against one account are three contacts with three *different*
authorisations, sharing one debtor. Authorisation is not modelled by the I/F/B
flag and is explicitly out of scope here — it is a permissions question for the
accounting/debtor side, not a CRM attribute.

## The correction: consent is opt-in, and partly inherited

Consent is **absent by default**. A person or account with no consent record, or
with `status != 'opted_in'`, is **not marketable**. There is no "opted out by
default" row and no permissive default — the failure mode of a missing row must be
"do not email", never "email".

Inheritance is asymmetric by account type, which is the whole point of having I/F/B:

- **B — Business.** You may email a business *at its business address* on far
  looser terms than you may email any individual. A contact's business-address
  marketing consent may therefore **inherit** the debtor's consent.
- **F — Family.** The account's consent does **not** extend to its members as
  individuals. Every adult member is a separate natural person with their own
  rights, and members who are children cannot consent at all. Marketing
  inheritance is therefore **off** for family accounts.
- **I — Individual.** The debtor is the person, so one record covers it.

And in every case an **explicit person-level opt-out is an absolute override**:
someone who opted out of marketing cannot be mailed again just because their
employer or household account consented. Inheritance may loosen a default; it may
never overrule a stated refusal.

## Business Requirement

The business requires:

1. **One store of record for people and contacts** — `crm_persons` and
   `crm_contacts` are the system of record; `0_fa_crm_contacts` is retired and its
   rows migrated (#14). No CRM code may keep a second person table.
2. **Debtor classification** — every debtor carries exactly one of I / F / B, held
   in a CRM-owned side table keyed by `debtor_no` (no ALTER of the core
   `debtors_master`), defaulting to `I`, with the reason for any non-default value
   captured as an operator decision rather than derived silently.
3. **A single opt-in consent registry, reused for both debtors and contacts** —
   one polymorphic table keyed by `(subject_type, subject_id, purpose)`, holding
   the auditable capture (timestamp, method, source, IP). Same table, same
   semantics, whether the subject is an account or a person.
4. **Type-dependent, refusal-proof marketing eligibility** — an
   `is_marketable()`-shaped resolver implementing the inheritance table above:
   business-address consent may inherit for `B` only; it must never overrule an
   explicit per-person opt-out; and an absent or non-`opted_in` record is
   always ineligible.
5. **Service vs marketing are distinct purposes** — a person with no marketing
   consent is still perfectly receivable for invoices, statements and
   transactional mail. Consent gates *marketing* only.
6. **Branch-scoped contact entry** (#15) — the entry form belongs to the already
   selected customer branch (no second customer selector), is unavailable while
   the customer filter is "ALL", and attaches one person to one or more of that
   debtor's branches via a branch selector.

## Scope

- In scope: debtor I/F/B classification, opt-in consent registry + resolver,
  retirement of `0_fa_crm_contacts`, branch-scoped contact entry, migration.
- Out of scope: transactional/per-person **authorisation** (which child may rent
  which title, which family member may open which line). That belongs to the
  debtor/accounting permissions model, not the CRM. Consent is about *whether we
  may email*, never about *what a person may do*.
- Out of scope: minor/guardian consent capture and its legal sign-off. The
  no-inheritance rule for `F` is what keeps children unmarketable in the
  meantime; formal guardian flows are a later BR.
- Out of scope: honour-list management, re-permissioning workflows, and proof
  retention beyond the capture fields (the registry records *that* consent was
  captured, not the artefacts).

## Acceptance Criteria (UAT)

1. A debtor can be classified I, F or B, and the classification is stored without
   altering `debtors_master`.
2. A new person with no consent row is **not** marketable; the resolver says so
   and names the missing record rather than defaulting to allow.
3. A person at a `B` debtor, emailed at the business address, is marketable when
   the debtor consented and the person has not opted out.
4. The same person who **has** opted out is not marketable regardless of the
   debtor's consent.
5. A person at an `F` debtor is **not** marketable by inheritance, even with a
   consenting family account.
6. Service/transactional mail to a non-marketing person is still permitted.
7. Consent is captured for a debtor and for a contact through the same table and
   the same code path, and the capture is auditable (when/how/who).
8. Every legacy `0_fa_crm_contacts` row appears exactly once in the native
   model after migration, with no duplicate person created, and re-running the
   migration is a no-op.

## Non-Functional

- PHP 7.3; PSR-4 under the CRM namespace; FA `db_*` only; `0_` literal SQL
  prefixes in `sql/`.
- No ALTER of core FA tables — side tables keyed by native ids only.
- Consent reads must be cheap: they sit on the send path, so index on status.

## Related

- FR-CRM-007-001 (read-only external classification of known contacts),
  FR-CRM-007-002 §3.2 (the design this BR constrains), FR-CRM-007-003
  (classification), FR-CRM-007-004 (consent), UC-CRM-007-002, UC-CRM-007-003.
- #22 (opportunity enrichment) and #28 (quote field types) are independent of
  this BR and are not blocked by it.
