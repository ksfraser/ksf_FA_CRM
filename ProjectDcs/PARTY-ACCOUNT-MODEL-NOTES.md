# PARTY-ACCOUNT-MODEL-NOTES — the owner's reasoning, worked through

**Date:** 2026-10-20
**Status:** **Narrative / design record. No production code changed, no test suite run.**
**Module:** `ksf_FA_CRM` (this file is the CRM module's view of the work)
**Owner input:** the estate owner's verbal reasoning on people, families, accounts
and their lifecycle (reproduced in §1).
**Governing decisions:** `AGENTS_ARCH.md` §12.2 (native FA data access; the shared
contact resolver) · `Contact_Ambiguity_Review_2026-10.md` (11 ambiguities and their
recommended rulings — the vocabulary used throughout) · `Contact_Access_Inventory_2026-10.md`
(144 contact-access sites across 46 repositories)
**Companion documents:**
- `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` — the incomplete-handling note the
  owner asked for (same directory).
- `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md` — the **shared
  design document** (the platform view).
- `~/Documents/ksf_Architecture_Docs/Requirements/` — the estate's cross-module
  requirement home.
- `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md` —
  the decision record.

---

## 0. How to read this document

Three kinds of statement appear here, marked throughout:

| Marker | Meaning |
|---|---|
| **[FA]** | FrontAccounting 2.4.3 already does this. Verified against the source at `ksf_Infrastructure/FA/2.4.3/`, cited `file:line`. |
| **[BUILD]** | We must write it. FA 2.4.3 has no equivalent. |
| **[OPEN]** | Not decided. A product decision or a data migration is required before it can be implemented. |

Every claim about FA is checked against a file. Where FA's behaviour is unclear, or
where this document **disagrees** with the shared design document, that is stated
explicitly rather than assumed — see §7.

**FA source tree used for verification:** `/home/kevin/Documents/ksf_Infrastructure/FA/2.4.3/`.
All line numbers below refer to that tree.

**Vocabulary.** This document uses the vocabulary of `Contact_Ambiguity_Review_2026-10.md`
§12 (the eleven recommended rulings) and adds the **PERSON / PARTY / ACCOUNT /
ACCOUNT-PARTY ROLE** distinction from the shared design document. Where the two
overlap, the review's ruling stands; the addition is layered on top, never in
contradiction of it.

---

## 1. The owner's reasoning, laid out as a story

### 1.1 The complaint: "we overloaded the PERSON definition"

> "A physical person can be a customer, a supplier, a user and an employee. So we
> overloaded the PERSON definition."

> "A customer (debtor) can be either a company, a family or an individual."

> "An individual is a family case with 1 family member (contact)."

Read those three together and the defect is precise. The estate has **one record
type** answering **four different questions**:

| Question | The concept that answers it |
|---|---|
| *Who is this human being?* | **PERSON** |
| *What is this legal or social entity?* | **PARTY** |
| *Who do we trade with?* | **ACCOUNT** |
| *In what capacity does this person or entity appear here?* | **ACCOUNT-PARTY ROLE** |

FA's own screens already make more than one `0_crm_persons` row for one human
being, which is the overload in its purest form:

- `sales/manage/customers.php:120-126` — creating a customer writes **two**
  relations for **one** person row: `add_crm_contact('cust_branch','general',$branch,…)`
  and `add_crm_contact('customer','general',$debtor_no,…)`.
- `sales/manage/customer_branches.php:101-105` — creating a branch contact writes
  **another** person row with `add_crm_person(…)`, again with no link back.

So a customer invoiced at two branches is two `0_crm_persons` rows today, and
nothing in FA says whether they are the same human. That is the defect the owner
is describing, and every operation below is a repair for it.

**Why "an individual is a family case with one member" matters.** It makes the
household the *only* family concept, and an individual a household with exactly
one member. The rule is enforced as an invariant on the party, not as a separate
"individual" entity type bolted on the side (see the shared design document
§2.1; CRM view: FR-CRM-011-001 §3.4).

### 1.2 Worked example A — the marriage (→ MERGE)

> "A single person getting married would be similar to a corporate merger or
> buyout — the company name might change but the contact details shouldn't,
> except maybe for position/title?"

> "Of course it could also involve address/phone/email updates but that can happen
> outside of a 'merger' (marriage)."

**The situation.** Jane Smith and Rob Jones each have their own customer account —
two `0_debtors_master` rows, two contact records, two sets of contact details. They
marry. The business wants them to trade against one account: one bill, one account
number, one postal address for the household.

**What the owner is saying, precisely:**

1. A marriage is **the same operation** as a corporate merger or a buyout. One
   commercial identity absorbs another. That is why there is **one** MERGE
   operation with variants, not "a marriage feature" and "a merger feature".
2. The merge **may** change the account's name ("the company name might change")
   and **may** change a title/position ("except maybe for position/title").
3. The merge **must not** change contact details — the email, the phone and the
   address are unchanged facts about two people who are now one account.
4. The contact-detail edits that *do* happen around a marriage (they move house,
   they change email) are **not merge semantics**. They are ordinary edits and they
   happen *outside* the merge.

**The operation it implies:** `MERGE`, variant `marriage`. The contract is
`AccountLifecycleInterface::merge(MergeRequest): MergeOutcome`
(shared design document §3.2, §5.2). CRM view: **FR-CRM-011-002**, **UC-CRM-011-001**.

**The consequence for the data model, which is the sharpest part of the example:**
**the two persons are not merged.** Jane and Rob remain two human beings — two
PERSONs. What merges is the *party structure* (two individuals become one
household) and the *account* (two debtor rows become one). If the merge merged the
persons, the divorce (§1.3) would be impossible to express, because there would be
only one person left to give an account to. This is the single clearest proof that
PERSON and PARTY must be separate concepts.

### 1.3 Worked example B — the divorce (→ SPLIT)

> "One case we haven't considered/discussed is divorce — splitting an account into 2."

**The situation.** The household account of §1.2 now has to become two accounts
again. Jane keeps the house; Rob takes the flat. The business wants a `split_from`
relationship so the estate can still answer "these two accounts were one until
March".

**What the owner is saying:** SPLIT is the inverse of MERGE and it is a first-class
operation, not a special case of "merge with negative direction". A household
account divides into two independent commercial identities.

**The operation it implies:** `SPLIT`. CRM view: **FR-CRM-011-003**, **UC-CRM-011-002**.

**The distinction the model must draw — and this is the one that is easy to get
wrong.** The family's **shared mailbox** is one `0_crm_persons` row. After the
split, does that row stay on the household account, move to Rob's, or exist on
both? The answer depends on whether the details belong to the departing person or
to the household:

- The details belong to **Rob alone** → **MOVE** the relation (FA's own
  `add_crm_contact()` + `delete_crm_contact()` are enough).
- The details are the **household's** (a shared mailbox, a shared landline) →
  **CLONE-WITH-LINK**: keep the original card on the household account, create a
  second card for the new account, and record that the two cards are the same
  record family. Deleting the original would destroy the household's correspondence
  history; moving it would take the household's mail with Rob.

**The operation it implies:** SPLIT, with a per-contact `move` | `clone`
decision. CRM view: **FR-CRM-011-003 §3.5**, **FR-CRM-011-008**.

### 1.4 Worked example C — the subsidiary spin-off, and its sale (→ SPIN-OFF)

> "Companies can split off subsidiaries and then possibly sell them."

**The situation.** Acme Ltd has a subsidiary, Acme Widgets Ltd. Acme Widgets
becomes a separate trading entity with its own account and its own ledger
accounts. Later, Acme sells Acme Widgets to a buyer.

**What the owner is saying:** this is **not** the same operation as a divorce.
A split divides one household into two households with no ownership. A spin-off
separates a company from a company and involves **ownership**, and it may involve
a **third party** (the buyer). "Split off subsidiaries and then possibly sell
them" is two events: a separation, and later a sale.

**The operation it implies:** `SPIN-OFF`, with an optional `sold` link to the
buyer. Distinct link types, distinct operation, distinct requirements — the
mechanical steps overlap with SPLIT but the semantics differ, so conflating them
would lose the ownership fact. CRM view: **FR-CRM-011-004**, **UC-CRM-011-003**.

**What FA can and cannot do here [FA]/[BUILD]:**

- `add_customer()` takes 14 arguments and **returns nothing**
  (`sales/includes/db/customers_db.inc:13-28`) — the caller must read
  `db_insert_id()` (`includes/db/connect_db_mysqli.inc:173`) immediately after,
  exactly as FA's own customer screen does (`sales/manage/customers.php:109`).
- `add_branch()` takes the GL account assignments as arguments
  (`sales/includes/db/branches_db.inc:12-39`), so a branch *can* post to its own
  receivables account — but **which** accounts a spun-off company posts to is an
  accounting decision, not ours. **[OPEN]**
- FA has **no** "sold" state and no ledger-level separation. The sold entity
  **stays in FA** with its own account; deleting it would destroy its ledger.
  **[BUILD]** for the `spin_off` / `sold` links.

### 1.5 Worked example D — the child who becomes an adult (→ PROMOTE)

> "Families could shed children as they become adults — they should then have
> their own accounts."

**The situation.** The Smith household account carries three contacts: Jane
(spouse), Rob (spouse), and Tom, their 16-year-old. Tom is now 18 and wants to
trade in his own right — a phone contract, a repair booking, his own credit.

**What the owner is saying:** a family member leaving the household role and
acquiring their own account is an operation with its own semantics. It is *not*
a divorce (the household does not divide), and it is *not* a merge (nothing is
absorbed).

**The operation it implies:** `PROMOTE`. CRM view: **FR-CRM-011-005**, **UC-CRM-011-004**.

**The subtlety that makes it an operation rather than an edit.** Two facts have to
end, and they end *separately*:

1. Tom's **account role** on the household account (`family_member`) ends.
2. Tom's **membership of the household party** may *continue* — he still lives at
   home. "Lives at home" and "trades with us as a separate customer" are
   different facts with different end dates.

If the model collapsed "family member" into one flag, promoting a child would
either evict him from the household record or leave him trading on the family
account. The split between party membership and account role is what makes the
operation possible without destroying the family record.

**And the card decision again.** Tom's contact card may carry the *family's*
address and the *family's* email, or his own. Same move-vs-clone rule as §1.3.

### 1.6 Worked example E — the person on two boards (→ ASSOCIATE)

> "People should be able to be associated to multiple organizations anyway — a
> person who is a customer, but who is also on the board of directors of a charity
> or two — so permit the person to be associated rather than requiring a duplicate
> record."

**The situation.** Dr Alvarez is a customer of the shop. She is also a director of
the Riverside Trust and of the County Food Bank. Today the estate would need a
separate record for each hat, and nothing would link them.

**What the owner is saying:** a person-to-organisation relationship is
**many-to-many**, with a **role**, and it is a first-class thing — not a duplicate
record. Two boards means two memberships; a customer role plus two board roles
means three hats and **one** human being.

**The operation it implies:** `ASSOCIATE` / `DISSOCIATE`. CRM view:
**FR-CRM-011-007**, **UC-CRM-011-006**.

**How it is expressed so it is visible in FA's own screens.** FA's native way to
attach a person to an entity in a role is `add_crm_contact($type, $action, $entity_id,
$person_id)` (`includes/db/crm_contacts_db.inc:251-258`), where `(type, action)` is a
row of the `0_crm_categories` catalogue (`sql/alter2.3.sql:161-186`, which seeds
twelve `cust_branch` / `customer` / `supplier` rows across `general` / `order` /
`delivery` / `invoice`). A board role is therefore a **new category pair** — e.g.
`('party_member','director')` — seeded through FA's own `add_crm_category()`
(`includes/db/crm_contacts_db.inc:191-199`). This is the ambiguity-review's
ambiguity-9 ruling ("a contact role becomes an FA `crm_categories` row") applied
to organisational roles, and it is why the association is visible in FA without
the CRM owning a second relation table.

### 1.7 Worked example F — two emails, one human (→ CLONE-WITH-LINK)

> "Of course, wearing the different hats they could have different emails and
> phone numbers, so maybe clone the person but have a link pointing out that it is
> the same person in a different role."

**The situation.** Dr Alvarez has a personal address `alvarez@example.org` and a
work address `dr.alvarez@riversidetrust.org`. Her charity role needs the work
address; her customer role needs the personal one.

**What the owner is saying — and this is the most delicate sentence in the whole
set.** He is *proposing a duplicate*, and he is pairing it with a **link** that
says what the duplicate is. So the estate needs to be able to say:

- "these are **the same human being**", and
- "this is a **deliberate shadow**, in a **different role**", and
- separately, "this is probably **a data-entry mistake** — two records for one
  human that nobody meant to create".

**The operation it implies:** `CLONE-WITH-LINK`. CRM view: **FR-CRM-011-008**,
**UC-CRM-011-007**.

**The marker that distinguishes the legitimate shadow from the mistake.** Two
columns together: the card's *kind* (`primary` | `shadow`) and the *scope role*
(the hat). The rule the owner implies, and the shared design document makes
mechanical:

- A shadow whose email **and** phone **and** fax all equal the root's, and which
  declares no distinct role, is a **probable duplicate** and must be reported for
  review, not silently accepted.
- A shadow that differs in even one field — the work-email case above — is the
  legitimate "same human, different hat" case.

Note that FA itself makes this worse: FA's contact panel offers a **"Clone"**
button (`includes/ui/simple_crud_class.inc:227`, reached through
`includes/ui/contacts_view.inc:12,18`, used at `sales/manage/customers.php:363`,
`sales/manage/customer_branches.php:321`, `purchasing/manage/suppliers.php:323`)
that re-opens the current record's fields as a new record
(`includes/ui/simple_crud_class.inc:188-193`). That affordance produces exactly
the unlinked duplicate the owner is complaining about, and FA has no way to record
that the two rows are one human. **[FA]** for the affordance; **[BUILD]** for the
link.

### 1.8 The soft link

> "So we should have a way to soft link debtors."

**The situation.** Two accounts are related — a parent and a subsidiary, a
business and the household that owns it, two branches of the same family that
happen to have separate accounts. The business wants them *findable* and
*labelled*, without merging them and without moving any data.

**What the owner is saying:** "related" is not "the same" and not "one of the
other". A **typed, dated, reversible** relationship between two accounts.

**The operation it implies:** `SOFT LINK`. CRM view: **FR-CRM-011-006**,
**UC-CRM-011-005**.

**The three kinds of link, which are not the same thing** (shared design
document §3.6):

| Link | Between | Stored? |
|---|---|---|
| **Structural** — successor / predecessor / spin-off / sold / split-from | account ↔ account | **yes** — it is a dated historical fact |
| **Shared subject** — two accounts touched by the same person or party | account ↔ account | **derived** — it is a query over the role index; storing it would create a third thing to keep in step |
| **Card link** — two FA person rows that are the same human | card ↔ card | **yes** — the root ↔ shadow mapping (§1.7) |

Storing the derived one is how a registry rots. Deriving the structural one is
how the estate loses history. The distinction matters.

### 1.9 The operations the story implies

| # | Operation | The owner's sentence it comes from | CRM requirement |
|---|---|---|---|
| 1 | **MERGE** (variants `marriage`, `corporate`, `buyout`, `rename`) | "a single person getting married would be similar to a corporate merger or buyout" | FR-CRM-011-002 |
| 2 | **SPLIT** | "divorce — splitting an account into 2" | FR-CRM-011-003 |
| 3 | **SPIN-OFF** (+ `sold`) | "Companies can split off subsidiaries and then possibly sell them" | FR-CRM-011-004 |
| 4 | **PROMOTE** | "Families could shed children as they become adults" | FR-CRM-011-005 |
| 5 | **SOFT LINK** | "we should have a way to soft link debtors" | FR-CRM-011-006 |
| 6 | **ASSOCIATE** / **DISSOCIATE** | "permit the person to be associated rather than requiring a duplicate record" | FR-CRM-011-007 |
| 7 | **CLONE-WITH-LINK** | "clone the person but have a link pointing out that it is the same person in a different role" | FR-CRM-011-008 |

Plus the **separation** that all seven sit on top of: party / person / account /
role (FR-CRM-011-001).

---

## 2. The vocabulary this document uses

Taken from `Contact_Ambiguity_Review_2026-10.md` §12 (the recommended rulings) and
the shared design document §1.2, layered in this order:

| Term | Means | Ruling it comes from |
|---|---|---|
| **contact** | a person — and only a person. A relation row is a *relation*; a category is a *category*; an actor label is an *actor type*. | ambiguity 1 |
| **customer** | the **account** (`0_debtors_master`). The person behind it is a contact. Direction: account → contacts, never the reverse. | ambiguity 2 |
| **PERSON** | the identity of a human being. No contact details, no commercial terms, no roles. | shared design §1.2 (an *addition* to the vocabulary — see §7.2) |
| **CARD** | one FA `0_crm_persons` row — the role-scoped contact record. One human being may have several. | shared design §1.2/§4.2 |
| **PARTY** | a legal or social entity that can hold roles: company, household, sole trader, trust. | shared design §1.2 |
| **ACCOUNT** | the commercial identity we trade with. In FA this **is** `0_debtors_master` — not replaced, not copied. | ambiguity 2 + shared design §1.2 |
| **ACCOUNT-PARTY ROLE** | the link that says: this person (or party) holds this role on this account. | shared design §1.2 |
| **lead** | a prospective **account** with attached contacts. Not a person. | ambiguity 7 |
| **primary contact** | the estate has **no** such concept. | ambiguity 8 |
| **inactive** | excluded by default on every list. Distinct from an identity lifecycle state. | ambiguity 11 |

**Two words this document deliberately does not use.** *"Individual" and "family" are
not two kinds of account* — they are one household concept with one or more members
(§1.1). And *"contact detail"* is never a synonym for *"person"*: a person has
identity; a card has details.

---

## 3. The operations, traced from example to requirement

Each row below names the example in §1 that produces it, the FA primitives it is
composed from **[FA]**, and what must be built **[BUILD]**.

| Operation | Example | FA primitives used | Must be built | Requirement |
|---|---|---|---|---|
| MERGE | §1.2 marriage | `update_customer()` (`customers_db.inc:30-51`), `update_crm_person()` (`crm_contacts_db.inc:43-68`), `add_crm_contact()` (`:251`), `delete_crm_contact()` (`:263`), `update_record_status()` (`sql_functions.inc:58`) | the merge decision, the contact selection, the retired-account marker, the soft links, the event journal | FR-CRM-011-002 |
| SPLIT | §1.3 divorce | `add_customer()` (`:13`), `db_insert_id()` (`connect_db_mysqli.inc:173`), `add_branch()` (`branches_db.inc:12`), `delete_branch()` (`:65`), `add_crm_contact()` / `delete_crm_contact()` | new party + membership, the move-vs-clone rule, the `split_from` link | FR-CRM-011-003 |
| SPIN-OFF | §1.4 subsidiary | as SPLIT, plus the branch GL assignment arguments (`branches_db.inc:12-39`) | ownership record, `spin_off` / `sold` links, the GL assignment as a *required* input | FR-CRM-011-004 |
| PROMOTE | §1.5 child | `add_customer()`, `add_branch()`, `add_crm_contact()` / `delete_crm_contact()` | end the account role without ending the membership; the card move-vs-clone rule | FR-CRM-011-005 |
| SOFT LINK | §1.8 | — (no FA change; the optional `notes` breadcrumb uses `update_customer()`) | the link row, the catalogue, the three queries | FR-CRM-011-006 |
| ASSOCIATE | §1.6 boards | `add_crm_contact()` (`:251`), `add_crm_category()` (`:191`) | the per-role category seeding, the membership record | FR-CRM-011-007 |
| CLONE-WITH-LINK | §1.7 two emails | `add_crm_person()` (`:13`, **returns the new id**) | the card↔root mapping, the `primary`/`shadow` marker, the scope role, the probable-duplicate report | FR-CRM-011-008 |

### 3.1 What FA 2.4.3 can and cannot do — the verified facts

Everything in this section is verified against
`/home/kevin/Documents/ksf_Infrastructure/FA/2.4.3/`.

**[FA] — there is no data-level clone of a debtor, a branch, a contact or a person.**
A whole-tree search for clone/copy functions returns only `copy_file`
(`includes/main.inc:335`), `copy_files` (`includes/main.inc:318`), `url_copy`
(`includes/remote_url.inc:79`), the sales/purchasing cart and credit-note copies
(`copy_to_cart` / `copy_from_cart` / `copy_to_cn` / `copy_from_cn`), the
**security-role** clone in the admin UI (`admin/security_roles.php:141-162`) and the
**generic CRUD "Clone"** action (`includes/ui/class.crud_view.inc:303-304`,
`includes/ui/simple_crud_class.inc:201-227`), which re-opens the current record's
form fields as a new record.

Two things follow, and the second corrects the shared design document (§7.1):

1. There is no **server-side** clone for a debtor or a branch. The customer screen
   (`sales/manage/customers.php`) and the branch screen
   (`sales/manage/customer_branches.php`) are hand-rolled pages and wire **no**
   clone affordance for the debtor or the branch themselves.
2. The generic CRUD clone **is** reachable, on the **contact** panel only:
   `includes/ui/contacts_view.inc:12` includes `simple_crud_class.inc`,
   `includes/ui/contacts_view.inc:18` declares `class contacts extends simple_crud`,
   and it is instantiated at `sales/manage/customers.php:363`,
   `sales/manage/customer_branches.php:321` and `purchasing/manage/suppliers.php:323`.
   So FA ships a **"Clone this contact"** button
   (`includes/ui/simple_crud_class.inc:227`) whose action
   (`_cloning()`, `includes/ui/simple_crud_class.inc:188-193`) re-opens the record's
   fields for a new person — **with no link to the original**. That is the
   unlinked duplicate of §1.7, and it is reachable today.

**Every clone in this model is therefore [BUILD].**

**[FA] — the primitives a clone must be composed from:**

| Native function | Location | Note that matters here |
|---|---|---|
| `add_customer(14 args)` | `sales/includes/db/customers_db.inc:13-28` | **returns nothing** — the caller must read `db_insert_id()` (as FA's own screen does at `sales/manage/customers.php:109`) |
| `update_customer(15 args)` | `sales/includes/db/customers_db.inc:30-51` | **does not carry `inactive`** |
| `delete_customer($id)` | `sales/includes/db/customers_db.inc:53-61` | deletes the debtor **and** its `customer`-type contacts, in a transaction |
| `add_branch()` / `update_branch()` / `delete_branch()` | `sales/includes/db/branches_db.inc:12` / `:39` / `:65` | `delete_branch` also removes the branch's `cust_branch` contacts |
| `add_crm_person(...)` | `includes/db/crm_contacts_db.inc:13-41` | **returns the new id** |
| `update_crm_person(...)` | `includes/db/crm_contacts_db.inc:43-68` | the only contact field a merge may legitimately change is the title, and it is changed on the *card* |
| `add_crm_contact($type,$action,$entity_id,$person_id)` | `includes/db/crm_contacts_db.inc:251-258` | the native relation write |
| `delete_crm_contact($id)` / `delete_crm_contacts(...)` | `includes/db/crm_contacts_db.inc:263-270` / `:272-289` | the native relation delete |
| `delete_entity_contacts($class,$entity)` | `includes/db/crm_contacts_db.inc:176-186` | removes every relation of an entity, and cleans up persons left with no contacts |
| `get_crm_persons($type,$action,$entity,$person,$unique)` | `includes/db/crm_contacts_db.inc:87-119` | an **INNER JOIN** over `crm_contacts` (`:89-91`), so a person with no relation is invisible; and a silent fallback to `action='general'` (`:115-116`) |
| `get_crm_contact($id)` | `includes/db/crm_contacts_db.inc:291-301` | **never exposes `crm_contacts.entity_id`** — so FA can walk account → contacts but never person → accounts |
| `get_branch_contacts($branch,$action,$customer,$default)` | `sales/includes/db/branches_db.inc:181-217` | the **union** of branch and account contacts; not auto-loaded by `includes/main.inc` |
| `update_record_status($id,$status,$table,$key)` | `includes/db/sql_functions.inc:58-63` | sets a record's `inactive` flag; **auto-loaded** (`includes/main.inc:18`) — this is how FA's own customer screen deactivates a customer |
| `db_insert_id()` | `includes/db/connect_db_mysqli.inc:173` | |

**[FA] — the debtor model, for reference:**

- `0_debtors_master` — `debtor_no` PK; 16 columns
  `debtor_no, name, debtor_ref, address, tax_id, curr_code, sales_type,
  dimension_id, dimension2_id, credit_status, payment_terms, discount,
  pymt_discount, credit_limit, notes, inactive`
  (`sql/en_US-new.sql:585-605`); `UNIQUE KEY debtor_ref` at `:604`. **No `email`
  column and no `phone` column** — a customer's email is reachable only through a
  contact.
- `0_cust_branch` — `branch_code` + `debtor_no` composite PK (`:569`), `KEY
  branch_ref` (`:570`), and its own GL assignments `sales_account`,
  `sales_discount_account`, `receivables_account`, `payment_discount_account`.
- `0_crm_persons` — `id, ref, name, name2, address, phone, phone2, fax, email,
  lang, notes, tmp_id, tmp_class, inactive` (`sql/alter2.3.sql:190-207`); `KEY(ref)`
  only — **no index on `email`**.
- `0_crm_contacts` — `id, person_id, type, action, entity_id`
  (`sql/alter2.3.sql:211-219`).
- `0_crm_categories` — the `(type, action)` catalogue
  (`sql/alter2.3.sql:161-172`), twelve seeded rows (`:175-186`).
- `0_suppliers` — the supplier-side account; `contact VARCHAR(60)` at
  `sql/en_US-new.sql:1698` is a free-text contact **name**, not an address.
- `0_users` (`sql/en_US-new.sql:2062`) — the "user" hat; FA has **no** link from
  `0_users` to `0_crm_persons`.

### 3.2 What is explicitly NOT in this model

- **No ledger-level merge or split.** FA has no notion of re-pointing an account's
  invoices, allocations, credit notes or orders. The operations move **identity**
  (cards, roles, links) and never **transactions**. A screen offering "merge these
  accounts" must warn that balances stay where they are. **[OPEN]** — the
  commercial treatment is a product decision.
- **No deletion of an absorbed account.** It is deactivated (FA-native
  `update_record_status()`), never deleted, because `delete_customer()` also
  deletes its contacts and destroys the ledger context the soft link exists to
  preserve.
- **No primary contact.** Per ambiguity 8 the estate has no such concept, so no
  `is_primary` column is introduced by this model.
- **No cross-company person identity.** The registry is company-scoped because FA's
  tables are company-prefixed. **[OPEN]**

---

## 4. Where the CRM fits

The CRM is the module that already owns the overlapping — and currently broken —
surfaces. It is where these operations will be driven from and where the
supersession has to be written down.

**The CRM's live surfaces that this model repoints:**

| Surface | Today | After the model |
|---|---|---|
| `pages/person_account_roles.php`, `includes/crm_relationships_db.inc:184-235` | `0_ksf_crm_person_account_roles` — a **CRM-owned** table keyed by `0_crm_persons.id` with an `is_primary` column (`sql/ksf_crm_person_account_roles.sql:19`) | superseded by the shared **account-role** index; the CRM reads it through `AccountDirectoryInterface` |
| `pages/account_relationships.php`, `includes/crm_relationships_db.inc:138-182` | `0_ksf_crm_account_relationships` — `parent_debtor_no` / `child_debtor_no` / `relation_type` / `ownership_pct` (`sql/ksf_crm_account_relationships.sql`) | superseded by the shared **account-link** index; the CRM's seven lifecycle operations write it |
| `pages/life_events.php`, `includes/crm_relationships_db.inc:240-280` | `0_ksf_crm_life_events.person_id` → `0_crm_persons.id` | re-pointed to the **root person id**; the life-event timeline stays in the CRM (it is a personal timeline, not an account operation) |
| `pages/contact_relationships.php`, `includes/crm_relationships_db.inc:77-117` | `0_ksf_crm_contact_relationships` — person ↔ person, keyed by `0_crm_persons.id` | re-pointed to root person ids on both sides |
| `pages/org_chart.php` | `p_a.name` / `p_b.name` — the one site that already uses FA's real column names (`pages/org_chart.php:126-129`) | becomes a party-membership view |

**A live defect this model makes urgent.** Five CRM sites still query FA's
`0_crm_persons` for `first_name` / `last_name`, **columns FA's person table does
not have** (it has `name` and `name2`, `sql/alter2.3.sql:190-207`):

- `includes/crm_relationships_db.inc:60` (`get_person_name()`)
- `includes/crm_relationships_db.inc:123-125` (`get_contact_persons_selector()`)
- `pages/contact_relationships.php:121-122`
- `pages/life_events.php:104`
- `pages/person_account_roles.php:126`

All five use `db_query($sql, "…")` **with a message**, so they fail **loudly** —
the page dies with a DB error rather than rendering an empty list. This was
recorded in `Contact_Ambiguity_Review_2026-10.md` §0.5 item 8 and is **still true
today** (verified 2026-10-20 against `ksf_FA_CRM` `main` at `3353165`). The
repointing required by FR-CRM-011-001 is the fix.

---

## 5. The requirement set this notes document produces

| Id | File | Title |
|---|---|---|
| BR-CRM-011 | `BR-CRM-011-party-account-lifecycle.md` | Party, person, account and role separation, and the account lifecycle operations |
| FR-CRM-011-001 | `FR-CRM-011-001-party-person-account-role-separation.md` | The four concepts and their invariants |
| FR-CRM-011-002 | `FR-CRM-011-002-account-merge.md` | Account merge (marriage, corporate, buyout, rename) |
| FR-CRM-011-003 | `FR-CRM-011-003-account-split.md` | Account split (the divorce case) |
| FR-CRM-011-004 | `FR-CRM-011-004-subsidiary-spin-off-and-sale.md` | Subsidiary spin-off and subsequent sale |
| FR-CRM-011-005 | `FR-CRM-011-005-family-member-promotion.md` | Promotion of a family member to an account of their own |
| FR-CRM-011-006 | `FR-CRM-011-006-soft-account-links.md` | Typed, dated, reversible links between accounts |
| FR-CRM-011-007 | `FR-CRM-011-007-person-organisation-association.md` | Many-to-many person-to-organisation association with a role |
| FR-CRM-011-008 | `FR-CRM-011-008-shadow-and-linked-person-records.md` | Shadow cards, linked persons, and the probable-duplicate marker |
| UC-CRM-011-001 … 007 | `UC-CRM-011-00X-*.md` | One use case per operation |

Each requirement carries acceptance criteria in the house style and a `@BABOK
Related:` line naming the section of this notes document it comes from.

---

## 6. Cross-references — the two views

This document is the **CRM-module view** of work whose **shared view** is the
platform contract. The two must not drift.

| | Shared view | CRM view (this repository) |
|---|---|---|
| Design | `~/Documents/ksf_Architecture_Docs/Party_Account_Model_2026-10.md` | this file |
| Requirements | `BR-COM-010`, `FR-COM-010-001…014`, `UC-COM-010-001…007` — the ids **named** in the shared design document §7.2 | `BR-CRM-011`, `FR-CRM-011-001…008`, `UC-CRM-011-001…007` — this repository |
| Decision | `~/Documents/ksf_Architecture_Docs/ADR/ADR-004-party-account-lifecycle-model.md` | referenced by BR-CRM-011 and by every FR |
| Incomplete handling | shared design document §6.4 (the nine items I1–I9) | `PARTY-ACCOUNT-MODEL-INCOMPLETE-HANDLING.md` — the CRM-facing note |

**A gap that must be stated rather than papered over.** The shared design document
§7.1 says the requirement documents "live in `ksf_Architecture_Docs/Requirements/`"
and §7.2 names the ids `BR-COM-010`, `FR-COM-010-001…014` and
`UC-COM-010-001…007`. **Those files do not exist yet.** Verified 2026-10-20:
`~/Documents/ksf_Architecture_Docs/Requirements/` contains `BR-ACL-001`,
`BR-ACL-010`, `BR-CAL-010`, `BR-EM-001`, the `FR-ACL-*` / `FR-CAL-*` / `FR-EM-*`
sets and `UC-EM-001` — and no `COM-010` file of any kind. The shared design
document's own header says the requirement set it is traceable to "are **not yet
written**" (line 11). The CRM set written here is therefore the **first** written
expression of this model, and it is written to be *consistent with* the COM-010 ids
rather than to depend on them.

**How the two views are kept from drifting.** The rule is that **the CRM's
requirements are written against the shared package's *contracts*, not its
*tables***:

- `PersonDirectoryInterface` — the person (PERSON) and its cards
- `PartyDirectoryInterface` — parties and memberships
- `AccountResolverInterface` / `AccountDirectoryInterface` — accounts, roles, links
- `AccountLifecycleInterface` — the seven operations
- `ContactResolverInterface` (already shipped, `ksf_FA_Common/src/ContactResolver/Contract/ContactResolverInterface.php`)
  — people and their relations, and the seam AGENTS_ARCH.md §12.2 makes mandatory

Two reasons. First, a parallel sweep is renaming every `0_fa_*` table to
`0_ksf_<module>_*`; **no requirement written here depends on a `0_fa_*` name**,
and none should. Second, the shared model's `0_ksf_common_*` table names are a
**proposal** in a design document, not a ratified schema — if they move, the CRM
requirements do not move with them.

---

## 7. Where this document diverges from the shared design document

Three places. Two are corrections; one is a scoping decision.

### 7.1 The shared design document says `simple_crud_class` has no consumer — it does

**Shared design document §0** states: *"that `simple_crud_class` has **no consumer
anywhere in the FA tree**"*, and uses the claim to conclude that FA's contact
screen wires no clone affordance.

**Verified 2026-10-20 — the claim is wrong:**

- `includes/ui/contacts_view.inc:12` — `include_once(… '/includes/ui/simple_crud_class.inc')`
- `includes/ui/contacts_view.inc:18` — `class contacts extends simple_crud`
- `sales/manage/customers.php:363` — `new contacts('contacts', $selected_id, 'customer')`
- `sales/manage/customer_branches.php:321` — `new contacts('contacts', $selected_id, 'cust_branch')`
- `purchasing/manage/suppliers.php:323` — `new contacts('contacts', $supplier_id, 'supplier')`
- `includes/ui/simple_crud_class.inc:227` — the **"Clone"** submit button
  (`"Edit new record with current data"`), whose action
  `_cloning()` (`includes/ui/simple_crud_class.inc:188-193`) re-opens the current
  record's fields as a new record.

**The conclusion the shared design document draws still holds**, and this document
restates it more precisely: there is **no server-side clone function** for a
debtor, a branch, a contact or a person, so **every clone in this model is
[build]**. What changes is the framing: FA does ship a **UI-level clone
affordance for a contact record**, on the customer, branch and supplier contact
panels — and it produces an **unlinked** duplicate, which is exactly the case
FR-CRM-011-008 has to give a marker to. That makes the "probable duplicate" rule
an *operational* rule, not a theoretical one: the operator can create such a pair
from a stock FA screen today.

### 7.2 The shared design document says the CRM's role and relationship tables are never created — they are

**Shared design document §6.5** states that the CRM's
`0_ksf_crm_person_account_roles` "table **is** created on activation; every CRM
read site addresses `0_fa_crm_person_account_roles`, which was never created —
`crm_relationships_db.inc:185-229`, `pages/person_account_roles.php:128`".

**Verified 2026-10-20 — the second half is stale.** `ksf_FA_CRM` commit
`3353165` ("fix(crm): address the CRM's own tables and FA core's contact
registry", 2026-10-19 22:14) renamed every `0_fa_crm_*` reference to
`0_ksf_crm_*`. Today:

- `includes/crm_relationships_db.inc:185,197,203,214,220,229` → `ksf_crm_person_account_roles`
- `includes/crm_relationships_db.inc:139,151,157,168,173` → `ksf_crm_account_relationships`
- `pages/person_account_roles.php:128` → `ksf_crm_person_account_roles`
- `pages/account_relationships.php:126` → `ksf_crm_account_relationships`

Both tables have schema (`sql/ksf_crm_person_account_roles.sql`,
`sql/ksf_crm_account_relationships.sql`) and are created on activation. **The
supersession plan still stands** — those two tables are exactly what the shared
model's account-role and account-link tables replace, and the CRM's `is_primary`
column (`sql/ksf_crm_person_account_roles.sql:19`) is exactly what ambiguity 8
rules out. But the framing changes from *"these read a table that never existed
and die loudly"* to *"these are live CRM-owned tables that the shared model will
supersede"*. That is a **migration with real rows**, not a bug fix.

### 7.3 The CRM requirement set is written against contracts, not tables

The shared design document §4.1 fixes a concrete table layout
(`0_ksf_common_persons`, `0_ksf_common_person_cards`, `0_ksf_common_parties`,
`0_ksf_common_party_members`, `0_ksf_common_accounts`,
`0_ksf_common_account_roles`, `0_ksf_common_account_links`,
`0_ksf_common_account_events`) in the shared package.

**The CRM's requirements do not name those tables.** They name the *contracts*.
Reason: the table layout is a proposal in a design document that has not been
ratified into requirements, and a parallel sweep is renaming table names across
the estate right now. A requirement that names an unratified table will be wrong
the moment the schema lands; a requirement that names a *contract* is wrong only
if the contract changes — and the contract is the thing the shared requirement set
will ratify. Where a table name is unavoidable (the CRM's own tables, which
exist), it is written down and cited.

**This is a divergence in emphasis, not in substance**, and it is recorded here so
that the two views are visibly reconciled rather than silently different.

---

## 8. Open questions carried forward

These are the decisions that must be answered before the corresponding
requirements can be completed. They mirror the shared design document's OQ series;
the CRM's copy is here so that the CRM module's own blockers are visible in one
place.

| # | Question | CRM impact | Blocks |
|---|---|---|---|
| **OQ-A** | May a merge **delete** the absorbed account, or must it always deactivate it? | Recommendation: **always deactivate** — `delete_customer()` also deletes its contacts and destroys the ledger context the soft link preserves. | FR-CRM-011-002 |
| **OQ-B** | When two accounts merge, what happens to the absorbed account's **transactions**? | Recommendation: **do not re-point them.** The absorbed account is retired, not deleted; the `successor` link records the history. A full re-pointing is a separate, later project. | FR-CRM-011-002 |
| **OQ-C** | On a divorce split, who keeps the **balance, the credit limit and the payment terms**? | Recommendation: **the source account keeps them**; the new account starts with the same terms and an empty ledger. | FR-CRM-011-003 |
| **OQ-D** | On a spin-off, which **GL accounts** does the new company post to? | Accounting decision, not ours. Implement so the GL assignment is a **required input**, not a default. | FR-CRM-011-004 |
| **OQ-E** | On a promotion, does the child's card **move** or **clone** by default? | Recommendation: **move** when the child has their own email/phone; **clone** when the card carries the family address. Both cases are detectable from the card's fields, so the default can be a rule rather than a question. | FR-CRM-011-005 |
| **OQ-F** | Should the person/party registry be **global** (cross-company) rather than company-prefixed? | Decide before the first multi-company deployment. Retro-fitting after two companies have each seeded their own roots is a painful merge of identities. | all of them |
| **OQ-G** | Is `0_ksf_crm_life_events` the estate's home for **life events**, or does the party model own them? | Recommendation: **keep them in the CRM** — a personal timeline is not an account operation — but re-point `person_id` to the root person id. The shared model's journal is an *account* journal and must not absorb them. | FR-CRM-011-001 |
| **OQ-H** | Does the estate want the **PERSON / CARD** distinction added to `AGENTS_ARCH.md` §12.2 (one sentence), so that "a contact" is not read as "the person"? | Recommendation: **yes.** Without it, module authors keep treating `crm_persons.id` as the person's id and the model does not hold. | FR-CRM-011-001 |
| **OQ-I** | May a merge **ever** change contact details? | The owner said no: *"the contact details shouldn't"* and *"that can happen outside of a 'merger'"*. The requirement implements the owner's answer, but it is recorded as a question because a merge variant that changes details would be a different operation. | FR-CRM-011-002 |

---

*Narrative produced 2026-10-20 from the estate owner's party/contact/account
reasoning, verified against FA 2.4.3 at `ksf_Infrastructure/FA/2.4.3/` and against
`ksf_FA_CRM` `main` at `3353165`. Consistent with `AGENTS_ARCH.md` §12.2,
`Contact_Access_Inventory_2026-10.md` and `Contact_Ambiguity_Review_2026-10.md`.
**No production code was modified, no test suite was run, and no FA table was
written to.***
