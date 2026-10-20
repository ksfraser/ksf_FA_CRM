# FR-CRM-011-008 — Shadow cards, linked persons, and the probable-duplicate marker

@BABOK Related: BR-CRM-011; FR-CRM-011-001; FR-CRM-011-007; UC-CRM-011-007;
          PARTY-ACCOUNT-MODEL-NOTES §1.7, §1.9, §3; AGENTS_ARCH.md §12.2
Status: Draft — requirements ratified in BR-CRM-011; implementation pending
Module: ksf_FA_CRM
Date : 2026-10-20

---

## 1. Need (BABOK What-not-How)

> "Of course, wearing the different hats they could have different emails and
> phone numbers, so maybe clone the person but have a link pointing out that it is
> the same person in a different role."

This is the most delicate sentence in the whole set, because it proposes a
**duplicate** and pairs it with a **link**. Three things follow, and they have
to be held apart:

1. **The estate must be able to say "these are the same human being".**
2. **The estate must be able to say "this is a deliberate shadow, in a different
   role"** — and that is a *different* statement from (1).
3. **The estate must be able to say "this is probably a data-entry mistake"** —
   a second, different human being's record that somebody created by accident.

If (2) and (3) are not distinguished, the model has two identical-looking
problems and no way to route either.

### 1.1 FA makes this worse, today, from a stock screen

FA's contact panel ships a **"Clone"** button — *"Edit new record with current
data"* (`includes/ui/simple_crud_class.inc:227`) — whose action `_cloning()`
(`includes/ui/simple_crud_class.inc:188-193`) re-opens the current record's
fields as a new record. It is reached through the contact CRUD view
(`includes/ui/contacts_view.inc:12,18`) which FA instantiates on the **customer**
(`sales/manage/customers.php:363`), **branch** (`sales/manage/customer_branches.php:321`)
and **supplier** (`purchasing/manage/suppliers.php:323`) screens.

That button produces **exactly** the unlinked duplicate the owner is complaining
about, and FA has **no** mechanism to record that the two rows are one human.

**This makes the "probable duplicate" rule operational, not theoretical.** An
operator can create such a pair from a stock FA screen today, with no link, no
marker and no record that it was intentional. The reconciliation job must find
those pairs.

**What FA does *not* have** — and this is the sharp boundary of the whole
requirement — is any **data-level clone function**. A whole-tree search for
clone/copy functions returns only `copy_file` (`includes/main.inc:335`),
`copy_files` (`includes/main.inc:318`), `url_copy` (`includes/remote_url.inc:79`),
the sales/purchasing cart and credit-note copies, the admin security-role clone
(`admin/security_roles.php:141-162`) and the generic CRUD action above. **Every
clone in this model is therefore [BUILD].**

## 2. Requirement

### 2.1 The operation

`AccountLifecycleInterface::cloneContactCard(CloneCardRequest): CloneCardOutcome`.

**Inputs:** the root person, the source card (default: the person's primary
card), the scope role (the new hat), the contact overrides (email, phone, phone2,
fax, address, and optionally a name), the target account (optional), the target
party (optional).

### 2.2 Preconditions

1. The root person exists.
2. **The scope role is supplied and non-empty.** A shadow without a role is a
   mistake, not a clone.
3. The overrides **differ from the source card in at least one contact field.**
   A clone that changes nothing is a duplicate, and duplicates are the opposite
   of what this operation is for.

### 2.3 Effects

| Step | What happens | FA primitive / build |
|---|---|---|
| 1 | The new card is created | `add_crm_person($ref, $name, $name2, $address, $phone, $phone2, $fax, $email, $lang, $notes)` — **returns the new id** (`includes/db/crm_contacts_db.inc:13-41`) |
| 2 | The shadow → root mapping is written, with the card kind and the scope role | — **[BUILD]** |
| 3 | If a target account was given, the new card is attached to it | `add_crm_contact('customer', 'general', $debtor_no, $new_id)` (`:251-257`) |
| 4 | If a target party was given, the new card is attached to it | `add_crm_contact('party_member', $role, $party_id, $new_id)` — FR-CRM-011-007 |
| 5 | One event record is written | — **[BUILD]** |

**The reference must be unique per card.** FA's `0_crm_persons.ref` has only a
non-unique `KEY` (`sql/alter2.3.sql:206`) — it is **not** unique at the database
level — but FA's own screens use it as a handle, so the clone must mint a
distinct one (e.g. `P-000123-S`) rather than reuse the source's.

### 2.4 The marker: card kind × scope role

Two facts together:

| Field | Values | Meaning |
|---|---|---|
| **card kind** | `primary` | the person's canonical card |
| | `shadow` | a card that exists because the person wears another hat |
| **scope role** | `customer`, `supplier`, `employee`, `party_member:director`, … | **which** hat this card carries |

A shadow with `scope_role = 'supplier'` is the same human being, wearing the
supplier hat.

**The link direction is shadow → root, always.** The mapping row points from the
card to the **person**, and the person is the row whose card kind is `primary`.
The structure is a **tree, not a graph**: exactly one root per human being, and
every shadow points at it.

### 2.5 Legitimate shadow versus probable duplicate

| | Legitimate shadow | Probable duplicate |
|---|---|---|
| Same human being? | yes, provably | no — a second, different person |
| Different contact details? | **yes** — that is the whole point | no — identical email **and** phone **and** fax |
| Distinct role declared? | yes (`scope_role` non-empty) | no |
| Contact history | the shadow carries the **new** hat's correspondence | the shadow duplicates the original's history |
| Resolution | **keep** | **merge the cards into one**, or **re-point the second row to a different person** |

**The rule.** A shadow whose email **and** phone **and** fax all equal the root's,
and which declares no distinct role, is a **probable duplicate** and must be
**reported** by the reconciliation job — not silently accepted.

A shadow that differs in exactly one field — say the email — is the legitimate
"same human, work address vs home address" case, and must **not** be reported.

**Why "and fax" matters.** Two fields can coincide by accident — a shared
household phone, a generic `info@` address. Three matching fields plus no
declared role is the point at which the pair is almost certainly one human
recorded twice.

### 2.6 The person-level merge is a different operation

When the reconciliation job finds a probable duplicate, the remedy is **not** the
clone-with-link operation. It is a **person-level merge**: fold one person
record into another, carrying the cards with it, and record the lineage on the
absorbed person.

- A **card** merge and a **person** merge are different facts.
- An **account** merge (FR-CRM-011-002) is a third, different fact.
- The three must never be conflated, and the CRM's UI must not offer one under
  another's name.

### 2.7 The CRM's place in the shadow record

- The CRM initiates the clone from its contact screens; the shared package
  performs it and owns the mapping.
- The CRM's contact screens must **show** the shadow relationship: a card that is
  a shadow says so, and says which role it carries. An operator looking at a
  customer contact must be able to see "this is the same person as Dr Alvarez,
  in her director capacity".
- The CRM's **reconciliation report** surfaces probable duplicates for review.
  It must **report**, not repair automatically: a probable duplicate is a
  judgement call, and the estate has an ambiguity-review precedent for exactly
  this — *"a screen that shows something wrong is worse than one that shows
  something incomplete"*.
- **The CRM must not create a shadow without a scope role.** If the operator
  cannot say which hat the new card is for, the operation is refused.

## 3. Acceptance

| ID | Criterion |
|---|---|
| **AC-1** | A shadow card is created through FA's own `add_crm_person()` and its new id is taken from the return value — no CRM-side INSERT into `0_crm_persons`. |
| **AC-2** | A clone **without** a scope role is refused, and the refusal names the missing input. |
| **AC-3** | A clone whose overrides are identical to the source card in every contact field is refused. |
| **AC-4** | The clone is minted with a **distinct** `ref` from the source card. |
| **AC-5** | The shadow → root mapping is written, with the card kind (`primary` \| `shadow`) and the scope role, and the mapping points from the card to the **root person**. |
| **AC-6** | A person has exactly **one** `primary` card; every other card is a `shadow` pointing at it. |
| **AC-7** | A shadow card is attached to the target account or party through FA's own `add_crm_contact()`, and is visible in FA's own contact screens. |
| **AC-8** | Two cards whose email **and** phone **and** fax are identical and which declare no distinct role are **reported** as probable duplicates by the reconciliation job. |
| **AC-9** | Two cards that differ in **exactly one** field are **not** reported as probable duplicates. |
| **AC-10** | The CRM's contact screens **display** the shadow relationship and the scope role; a card that is a shadow says which person it is a shadow of. |
| **AC-11** | A card merge, a person merge and an account merge are three distinct operations with three distinct names in the CRM's UI; no one of them can be triggered by another. |
| **AC-12** | The CRM writes the shadow mapping through the shared package's contract; no CRM code writes the shared tables directly. |

## 4. Verification basis — FA 2.4.3

| Claim | Location |
|---|---|
| `add_crm_person(...)` — **returns the new id** | `includes/db/crm_contacts_db.inc:13-41` |
| `update_crm_person(...)` | `includes/db/crm_contacts_db.inc:43-68` |
| `add_crm_contact($type, $action, $entity_id, $person_id)` | `includes/db/crm_contacts_db.inc:251-258` |
| `delete_crm_contact($id)` / `delete_crm_contacts(...)` | `includes/db/crm_contacts_db.inc:263-270` / `:272-289` |
| `delete_entity_contacts($class, $entity)` — removes every relation of an entity and cleans up persons left with no contacts | `includes/db/crm_contacts_db.inc:176-186` |
| `0_crm_persons` — `ref varchar(30)` with only `KEY(ref)`: **not unique**; `name`, `name2` (no `first_name`/`last_name`); `email` with **no index** | `sql/alter2.3.sql:190-207` (`:206` is the `KEY(ref)` line) |
| the generic CRUD "Clone" submit button, *"Edit new record with current data"* | `includes/ui/simple_crud_class.inc:227` |
| the clone action `_cloning()` — re-opens the current record's fields as a new record | `includes/ui/simple_crud_class.inc:188-193` |
| the CRUD clone is reachable: `contacts_view.inc` includes `simple_crud_class.inc` and declares `class contacts extends simple_crud` | `includes/ui/contacts_view.inc:12`, `:18` |
| the contact CRUD view is instantiated on the customer, branch and supplier screens | `sales/manage/customers.php:363`, `sales/manage/customer_branches.php:321`, `purchasing/manage/suppliers.php:323` |
| **No** data-level clone of a person, contact, debtor or branch | whole-tree search: `includes/main.inc:318,335`, `includes/remote_url.inc:79`, `admin/security_roles.php:141-162`, `includes/ui/class.crud_view.inc:303-304`, `includes/ui/simple_crud_class.inc:201-227` — and no debtor/branch clone of any kind |
| FA writes **two** relations for one person on customer creation | `sales/manage/customers.php:120-126` |
| FA writes **another** person row for a branch contact | `sales/manage/customer_branches.php:101-105` |

## 5. Out of scope and open questions

**Out of scope:** the **person-level merge** itself (the remedy for a probable
duplicate is specified here; the operation is not part of this requirement set);
automatic de-duplication; de-duplicating the estate's historical data (the
reconciliation job **reports**; it does not repair).

**Open questions:** none specific to this requirement. Whether the person-level
merge should be added to the CRM's requirement set is a separate decision, and
this requirement's `card merge` / `person merge` / `account merge` distinction
(AC-11) exists so that whichever way it is decided, the three stay apart.

## 6. Cross-references

- **Shared design:** `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md`
  §1.4 (why the person must not be overloaded), §3.7 (CLONE-WITH-LINK),
  §4.2 (the card ↔ root mapping), §6.1 (`cloneContactCard`).
- **Shared requirements (proposed, not yet written):** `FR-COM-010-010`
  (clone-with-link operation), `FR-COM-010-002` (person and card schema),
  `FR-COM-010-013` (seeding and reconciliation).
- **Decision:** `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md`.
- **CRM narrative:** `PARTY-ACCOUNT-MODEL-NOTES.md` §1.7.
- **CRM incomplete handling:** `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` (**I6**,
  **I7**).
- **Use case:** `UC-CRM-011-007-clone-a-contact-card-for-a-second-role.md`.
