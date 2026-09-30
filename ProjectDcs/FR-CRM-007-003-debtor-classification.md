# FR-CRM-007-003 — Debtor classification (I / F / B)

@BABOK Related: BR-CRM-09; BR-007 (person–account roles).
@UML: FR-CRM-007-002 §3.2.
Status: Approved — product decisions recorded in BR-CRM-09.
Module: ksf_FA_CRM (writes); any module may read the classification.

## Need (BABOK What-not-How)

The **account** is what is individual, family or business — not the person. A
family account has one bill and several real people on it (a video rental shop
letting the kids rent against the family account; a carrier issuing one bill for
a line in each member's name). A business account has staff contacts. A single
person is the account only in the `I` case.

Native `debtors_master` has no such column, and must not be ALTERed.

## Requirement

1. Every debtor carries exactly one classification: `I` (individual), `F` (family)
   or `B` (business).
2. Stored in a CRM-owned side table keyed by `debtor_no`. No ALTER of
   `debtors_master`.
3. Absent row ⇒ `I`. Defaulting to `I` is the conservative reading: it is the
   case with the strictest consent rules (see FR-CRM-007-004 §4), so an
   unclassified debtor cannot accidentally be treated as a business and inherit
   looser marketing rules.
4. Classification is an **operator decision**, captured with who set it and when.
   It is NOT derived from contact structure: inferring `B` from "has several
   contacts" would misclassify every family account, and inferring from
   employment (FR-CRM-007-001's external signal) would drag the HRM responder's
   answer into a debtor attribute.
5. Read via a single accessor; no module reaches for the table directly.

## Acceptance

- CLI: a debtor with no profile row reads as `I`.
- A debtor can be set to `F`; a second read returns `F`; the row records actor and
  timestamp.
- Setting `F` never writes to `debtors_master`.
- Changing `F` → `B` does **not** alter any contact's consent; the two are
  independent (BR-CRM-09: I/F/B is separate from CASL).
- An unknown/invalid value is rejected, not coerced.

## Out of scope

Per-person authorisation on a shared account (which child may rent which title)
— an accounting/permissions question, not a CRM attribute.
