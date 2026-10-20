# FR-CRM-011-007 — Person-to-organisation association with a role

@BABOK Related: BR-CRM-011; FR-CRM-011-001; FR-CRM-011-008; UC-CRM-011-006;
          PARTY-ACCOUNT-MODEL-NOTES §1.6, §1.9, §3; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "People should be able to be associated to multiple organizations anyway — a
> person who is a customer, but who is also on the board of directors of a charity
> or two — so permit the person to be associated rather than requiring a duplicate
> record."

**The situation.** Dr Alvarez is a customer of the shop. She is also a director
of the Riverside Trust and of the County Food Bank. Today the estate would need
a separate record for each hat, and nothing would link them.

**What the owner is saying.** A person-to-organisation relationship is:

1. **many-to-many** — one person, many organisations; one organisation, many
   people;
2. **role-bearing** — *director* is not *shareholder* is not *employee* is not
   *trustee*;
3. **dated** — a directorship starts and ends;
4. and it is **not a duplicate record** — the human being is one.

Two boards plus a customer role means three hats and **one** human being.

### 1.1 Why "not a duplicate record" is the load-bearing part

The estate's existing failure mode is visible in the employees: after seven
modules each created its own local employee directory
(`0_ksf_leave_employees`, `0_ksf_performance_employees`,
`0_ksf_roster_employees`, `0_ksf_teams_employees`, `0_ksf_timesheets_employees`,
`0_ksf_training_employees`, `0_ksf_travelexpense_employees`,
`0_ksf_projectmanagement_employees`), *one person who travels, is rostered, is
on a team and is in training is now seven employees* — with no shared key, and
each directory row carrying its own copy of the name and email
(`Contact_Ambiguity_Review_2026-10.md` §6).

The person's hats are the same problem wearing different hats. The fix is a
**membership with a role**, not another copy of the person.

## 2. Requirement

### 2.1 The operations

`AccountLifecycleInterface::associatePerson(AssociateRequest): PartyMember` and
`dissociatePerson($membershipId, $reason, $actor): void`.

**Inputs for ASSOCIATE:** person, party, member role, start date (optional), end
date (optional), whether the role gets its own contact card (default **no**),
and the contact overrides for that card (optional).

### 2.2 The role catalogue

| Role | Typical relationship |
|---|---|
| `director` | a board member |
| `officer` | an officer of an organisation |
| `shareholder` | an owner — carries an ownership percentage |
| `employee` | employed by the organisation |
| `trustee` | a trustee of a trust |
| `member` | a member of a household or a club |
| `spouse`, `child`, `parent` | the **family** axis — relationship to the household head, distinct from the organisational axis |

The **family axis and the organisational axis are separate.** A spouse is a
member of a household; a director is a member of a company. Both are memberships,
but the household's membership is a *personal* fact and the company's is a
*commercial* one, and they must be distinguishable.

### 2.3 Preconditions

1. The person exists; the party exists.
2. The (party, person, role) triple is not already present.
3. If a role-scoped card is requested, the person has a primary card to clone
   from.

### 2.4 Effects

| Step | What happens | FA primitive / build |
|---|---|---|
| 1 | The membership row is written, with the role and the dates | — **[BUILD]** |
| 2 | The association is expressed **natively** so it is visible in FA's own contact screens: a `0_crm_contacts` row of a dedicated category type, e.g. `add_crm_contact('party_member', 'director', $party_id, $person_id)` | `add_crm_contact()` (`includes/db/crm_contacts_db.inc:251-258`) |
| 3 | The `(type, action)` category row is seeded for the role | `add_crm_category()` (`includes/db/crm_contacts_db.inc:191-199`) — **[BUILD]** for the catalogue |
| 4 | If a role-scoped card was requested, a **CLONE-WITH-LINK** is performed with `scope_role = 'party_member:' . $memberRole` | FR-CRM-011-008 |
| 5 | One event record is written | — **[BUILD]** |

**Step 2 is what makes this FA-native.** FA's category catalogue is
`UNIQUE KEY(type, action)` (`sql/alter2.3.sql:170`), so each `(type, action)` pair
is one row, and `0_crm_contacts.type` / `.action` are foreign keys to it
(`sql/alter2.3.sql:214-215`). A board role is therefore a **new category pair** —
`('party_member','director')` — not a new table. This is the ambiguity-review's
ambiguity-9 ruling ("a contact role becomes an FA `crm_categories` row") applied
to organisational roles, and it means the association appears in FA's own
contact screens without the CRM owning a second relation table.

**And it composes with the shared resolver.** `findByEntity('party_member',
$partyId)` returns the people in that role — through the seam
`AGENTS_ARCH.md` §12.2 makes mandatory.

### 2.5 Role-scoped contact details

The owner's sentence continues: *"wearing the different hats they could have
different emails and phone numbers"*. A board role at a charity needs the
charity's address; a customer role needs the personal one.

So a membership may carry its **own card**: a clone of the person's card, with
the role's contact details, marked as a **shadow** of the same person. This is
FR-CRM-011-008, and it is what keeps "one human, several hats, several
addresses" expressible without a duplicate person record.

### 2.6 Dissociation ends the membership; it does not delete the person

`dissociatePerson()` sets the membership's end date and status. It does **not**
delete the person, and it does not delete the cards — the history of the
association is the point. A person who leaves a board still *was* on it.

### 2.7 The CRM's place in the association

- The CRM initiates the association from its person and organisation screens and
  records it; the shared package performs it.
- The CRM's `0_ksf_crm_person_account_roles` table already models
  "a person holds a role on an account" (`person_id`, `debtor_no`, `role`,
  `start_date`, `end_date`, `is_primary`). That is the **account-role** shape
  and it is superseded by the shared role index — but its `role` column's
  documented values (`director, beneficiary, trustee, employee, owner`,
  `sql/ksf_crm_person_account_roles.sql:16`) are the CRM's own seed of the role
  catalogue and should carry over into it.
- The CRM's `pages/person_account_roles.php` becomes the surface for
  account-level roles; organisational roles belong to the party view.
- **The `is_primary` column is dropped** (ambiguity 8) when the table is
  retired.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | A person can hold roles in **two or more** organisations, and in an account, simultaneously, without a duplicate person record being created. |
| **AC-2** | Each membership records its role, and optionally a start and end date. |
| **AC-3** | A second membership with the same (party, person, role) triple is refused. |
| **AC-4** | The association is written through FA's own `add_crm_contact()` with a dedicated category pair, and the category row is seeded through FA's own `add_crm_category()`. No CRM-owned relation table is created for it. |
| **AC-5** | The association is retrievable through the shared resolver's `findByEntity()` for the new category type. |
| **AC-6** | A membership may carry its own contact card, created as a clone-with-link of the person's primary card, with the role's email and phone. The card is marked as a shadow of the same person. |
| **AC-7** | Dissociating a membership ends it with a date; the row is retained, the person is **not** deleted, and the cards are **not** deleted. |
| **AC-8** | The **family axis** and the **organisational axis** are distinguishable: a spouse membership of a household is not conflated with a directorship of a company, and a household's `member_role` records the relationship to the household head separately from the organisational role. |
| **AC-9** | An ownership percentage may be recorded on a `shareholder` membership. |
| **AC-10** | The CRM's role catalogue's documented values carry into the shared role index. |
| **AC-11** | The CRM writes the membership and the FA category row through the shared package's contract; no CRM code writes the shared tables directly. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `add_crm_contact($type, $action, $entity_id, $person_id)` — the native relation write | `includes/db/crm_contacts_db.inc:251-258` |
| `add_crm_category($type, $action, $name, $description)` / `update_crm_category()` / `get_crm_categories()` / `get_crm_category_name()` | `includes/db/crm_contacts_db.inc:191-199` / `:202-213` / `:222-228` / `:238-249` |
| `0_crm_categories` — `UNIQUE KEY(type, action)`, `UNIQUE KEY(type, name)` | `sql/alter2.3.sql:170-171` (table at `:161-172`) |
| the twelve seeded categories: `cust_branch` / `customer` / `supplier` × `general` / `order` / `delivery` / `invoice` | `sql/alter2.3.sql:175-186` |
| `0_crm_contacts.type` and `.action` are foreign keys to `crm_categories` | `sql/alter2.3.sql:214-215` |
| HRM already seeds an `employee/general` category — the precedent for a module-owned role category | `ksf_FA_HRM/sql/crm_categories.sql:6-8` |
| `get_crm_persons($type, $action, $entity, …)` — how FA resolves "the people of an entity in a role" | `includes/db/crm_contacts_db.inc:87-119` |
| FA has **no** party, household, family or ownership concept | a whole-tree grep of the FA schema returns exactly `crm_categories`, `crm_contacts`, `crm_persons` for `crm_*`, and no `party` / `household` / `family` / `ownership` table |
| `0_users` has no link to `0_crm_persons` | `sql/en_US-new.sql:2062` |

## 5. Out of scope and open questions

**Out of scope:** authorisation (what a board member may do); payroll or
remuneration; per-role **default** contact details (dropped per ambiguity 9 —
the defaults were never read: six references in the estate, all writes, zero
reads); verifying a person's identity against a company register.

**Open questions:** none specific to this requirement.

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §1.4a (how the owner's hats map onto the model), §3.8 (ASSOCIATE),
  §4.3 (`party_members` schema).
- **Shared requirements (proposed, not yet written):** `FR-COM-010-009`
  (associate/dissociate operation), `FR-COM-010-003` (party and membership
  schema), `FR-COM-010-010` (clone-with-link).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.6.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` (**I3**,
  **I6**).
- **Use case:** `UC-CRM-011-006-associate-a-person-with-an-organisation.md`.
