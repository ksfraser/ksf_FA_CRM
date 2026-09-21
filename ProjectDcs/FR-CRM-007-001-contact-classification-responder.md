# FR-CRM-007-001 — Contact classification responder (known non-employee = external)

@BABOK Related: BR-007; FR-CAL-007-003 (responder role).
@UML : CRM/responder -> `ksf_event_classify_attendees`
Status: Approved — BABOK; implementation parks next stage.
Module: ksf_FA_CRM (answers contact identity for classification; read-only).

## Need (BABOK What-not-How)
CRM owns `0_crm_persons`. When an attendee is a known CRM CONTACT but not an
active employee, that is authoritative signal the person is EXTERNAL
(customer/vendor/other-org) — so the close workflow marks them supporting-role
(no auto-time) without Timesheets reading CRM tables.

## Requirement
1. On `ksf_event_classify_attendees`, for each `attendee_emails[]` that
   resolves via `0_crm_persons.email` to a person WITHOUT active employment
   (HRM responder decides employment), append the email to
   `$opts['classification']['external']`.
2. CRM never classifies a person it does not know; it NEVER marks `member`
   (employment-membership is HRM/PM's call).
3. Read-only; native `db_*`; fault-tolerant (errors contained).
4. A CRM contact that DOES have active employment is left for HRM/PM responders
   (CRM does not stamp member/external on employees).

## Acceptance
- ARI: attendee = known contact, no employment -> classified external.
- AZZ: unknown email -> unclassified (no opinion).
- BON: attendee = contact WITH employment -> CRM stays silent (HRM decides).