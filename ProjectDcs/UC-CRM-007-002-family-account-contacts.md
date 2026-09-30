# UC-CRM-007-002 — Register contacts on a family account

Status: Approved
Scope : ksf_FA_CRM (native `crm_persons`/`crm_contacts` writer)
Author: KSF
Last  : 2026-09
@BABOK Related: BR-CRM-09, FR-CRM-007-002, FR-CRM-007-003
@UML: FR-CRM-007-002 §3.1

## Use Case

A family runs one account with a video rental shop. The account holder's children
are old enough to rent, and the family wants all of them recorded against the one
account so the shop can see who may transact. None of the children is the account
holder, and the shop must not be able to market to the children on the strength
of the account's consent.

## Actors

- **Account holder / clerk:** the operator entering the contacts.
- **Customer:** the family, as one debtor.
- **Primary actor:** ksf_FA_CRM (`ContactsService`, native model).

## Preconditions

1. The debtor exists in native `debtors_master` and is classified `F`
   (FR-CRM-007-003).
2. The operator is on a customer page with a specific branch selected — not the
   "ALL" filter (FR-CRM-007-002 §3.1).
3. The debtor's branch list is known.

## Flow

1. The operator opens contact entry for the selected branch.
2. The form inherits the customer from the surrounding page; **no second customer
   selector is shown** (#15).
3. The operator enters one person's details and submits.
4. CRM creates one `crm_persons` row for that person (a new person, because a
   child of an existing account is not an existing person).
5. CRM creates one `crm_contacts` xref row for that person against the selected
   branch (`type='cust_branch'`, `entity_id` = branch id).
6. Steps 4–5 repeat per family member, or in one pass via the branch selector
   when a person is authorised on several branches.
7. The account is **not** re-entered, and the children are **not** created as
   debtors.

## Acceptance

1. N family members ⇒ N `crm_persons` rows and N `crm_contacts` xref rows.
2. Exactly one `debtors_master` row is involved: the family account.
3. Each child contact is a contact on the family account, not a customer.
4. No contact row carries an I/F/B value; classification is a debtor attribute
   (FR-CRM-007-003) and is `F` on the single account.
5. A second pass for the same person+branch does not create a duplicate person or
   a duplicate xref.
6. None of the children becomes marketing-eligible via inheritance, because the
   debtor is `F` (FR-CRM-007-004 §7).
7. With the customer filter on "ALL", the entry form is unavailable rather than
   allowing a contact to be attached to no one.
