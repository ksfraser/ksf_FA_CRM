# UC-CRM-007-001 — CRM Contact Classification Responder

Status: Approved — BABOK; implementation parks next stage
Scope : ksf_FA_CRM subscriber (read-only responder)
Author: KSF (BABOK-v2 modeled)
Last  : 2026-09
@BABOK Related: BR-007 (cross-module event-close workflow), FR-CRM-007-001
@UML: ARCH-007-event-close-workflow-design.md §Aggregation

## Use Case
The CRM module responds to `ksf_event_classify_attendees` whenever any
subscriber (Timesheets, or any future caller) asks for membership
classification of an event's attendee emails. CRM's role: recognize its own
known external contacts (from `0_crm_persons`) and mark them `external` so
they do NOT receive auto-timed timesheet rows against project tasks they are
not assigned to.

## Actors
- **Caller:** Any subscriber on the `ksf_event_closed` flow (typically
  ksf_FA_Timesheets per FR-TIME-007-002/003).
- **Responder:** ksf_FA_CRM (`hooks_ksf_FA_CRM::ksf_event_classify_attendees()`).

## Preconditions
1. An admin or PM has closed an attendance/work event; Calendar has committed
   the close and emitted `ksf_event_closed`.
2. A subscriber (e.g., Timesheets) is preparing to offer time entry and has
   called `hook_invoke_all('ksf_event_classify_attendees', $dto, $opts)`.
3. `$opts['classification']` is `['member' => [], 'external' => []]` and will
   be mutated in place.

## Flow
1. CRM's responder receives the DTO and `$opts`.
2. For each email in `$dto['attendee_emails']`, CRM queries:
   `SELECT email FROM 0_crm_persons WHERE email = :email LIMIT 1`.
3. If the email is found → append it to `$opts['classification']['external']`.
4. If not found → CRM is indifferent (does nothing; leaves email unclassified).
5. Return `$opts` to caller without modification beyond the classification.

## Acceptance
1. Every email present in `0_crm_persons.email` that appears in
   `attendee_emails[]` is classified `external` by the CRM responder.
2. CRM never classifies anyone `member` (only PM and HRM may do that).
3. CRM never writes to any timesheet or expense table.
4. If no CRM contacts match, the responder returns `$opts` unchanged.
5. CRM never reads or writes to another module's tables.
