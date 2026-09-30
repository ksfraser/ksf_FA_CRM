# FR-CRM-007-004 — Opt-in consent registry and marketing-eligibility resolver

@BABOK Related: BR-CRM-09; BR-CRM-04 §Target lists (suppression set).
Status: Approved — product decisions recorded in BR-CRM-09.
Module: ksf_FA_CRM (owner); ksf_FA_EmailManager is the primary consumer.

## Need (BABOK What-not-How)

May we email this person or account? Native FA has no consent concept at all.
Prior drafts modelled an `opt_out` honour flag, which is the wrong polarity: it
makes the default state "emailable", so every record created without a decision,
and every record that fails to match the registry, is marketable. That inverts
the risk.

Consent here is **opt-in**. Absent record ⇒ not marketable.

## Requirement

1. **One registry, two subject kinds.** A single table serves both debtors and
   contacts. A subject is a `('debtor', debtor_no)` or `('contact', person_id)`
   pair. The table and code path are identical for both — this is the whole point
   of the polymorphic key.
2. **Status is opt-in.** Values: `opted_in`, `opted_out`, `pending`. Only
   `opted_in` is marketable. `pending` is the state of a registered subject that
   has never decided, and is **not** marketable. There is no row that means
   "allowed by default".
3. **Purpose is part of the key.** `marketing` consent gates campaigns and
   newsletters. `service` covers invoices, statements and transactional mail and
   is satisfied by the debtor relationship itself. A person with no marketing
   consent is still reachable for service mail. The two must never be conflated
   in a single boolean.
4. **Capture is auditable.** `captured_at`, `method`, `source`, `capture_ip`,
   `notes`, and the acting user. Consent without provenance is not consent.
5. **One current row per subject+purpose**, enforced by a unique key. Changing a
   decision updates the row; the registry answers "what is the status now", not
   "what happened". (A future BR may add an append-only event log; that is
   explicitly not this FR.)
6. **Eligible-for-marketing resolution** implements, in order:
   1. If the contact's own record is `opted_out` → **not** eligible. Absolute;
      inheritance can never re-enable them.
   2. If the contact's own record is `opted_in` → eligible.
   3. Otherwise, the contact's debtor may supply inherited eligibility **only**
      when the debtor is classified `B` **and** the address being used is the
      debtor's business address. Marketing to a named individual at a personal
      address never inherits.
   4. Anything else → **not** eligible.
7. `F` debtors never supply inherited eligibility, and neither do `I` ones (the
   debtor *is* the person, so their own record is the relevant one).
8. Every non-eligible result must name the reason (which rule fired, which
   record was missing) so an operator can act on it. Silent `false` is a defect.

## Acceptance

- **No-record fails closed:** a contact with no consent row is not eligible.
- **Opt-in wins:** `opted_in` on the contact → eligible.
- **Opt-out is absolute:** `opted_out` on the contact + `opted_in` on the debtor
  → not eligible.
- **Business inheritance applies:** contact with no record, debtor `B`, mail sent
  to the debtor's business address → eligible, reason = inherited from `B`.
- **Family does not inherit:** contact with no record, debtor `F` → not eligible.
- **Individual does not inherit:** contact with no record, debtor `I` → not
  eligible.
- **Service unaffected:** same non-marketing contact, `service` purpose → allowed.
- **Auditable:** a captured consent row stores when, how, from where, and by whom.
- **One row per pair:** capturing twice for the same subject+purpose updates
  rather than duplicating, and the earlier value is not resurrected.

## Out of scope

Minor/guardian consent flows; re-permissioning campaigns; honour-list UI; proof
artefact retention. See BR-CRM-09 §Scope.
