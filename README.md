# ksf_FA_CRM

CRM module for FrontAccounting

**Version:** `2.4.3-0` &nbsp;|&nbsp; **FA:** 2.4 &nbsp;|&nbsp; **PHP:** 7.4

---

## What this module does

- **BR-006** - Cross-Module DDL Caching Architecture
  - Multiple FA modules render dropdown lists (DDLs) for shared reference data: departments, teams, roles, grades, positions, employment statuses, customer types, territories, project types, and similar lookup entities.
- **BR-007** - Cross-module Event-Close → Timesheet + Expense Workflow
  - Status: Approved (BR-007, series after BR-006 cross-module DDL caching) Scope : Cross-module — Calendar (anchor), Timesheets, TravelExpense, ProjectManagement, HRM, EmployeePay, Warehouse...
- **BR-COM-01-workflow-step-table-engine** - BR-COM-01 — Workflow Step-Table Engine (SuiteCRM-Inspired Lifecycle Hooks)
  - module switching on record lifecycle...
- **BR-COM-02-approval-state-machine** - BR-COM-02 — Approval / State-Machine Engine (SuiteCRM Advanced Workflow)
  - any module with records that flow through human/automated...
- **BR-COM-03-notification-activity-feed** - BR-COM-03 — Notification + Activity-Feed Subsystem
  - (HRM, CRM, Calendar, ProjectManagement, Timesheets), UI host (see...
- **BR-COM-04-scheduled-automation-jobs** - BR-COM-04 — Scheduled Automation Jobs (cron-friendly CLI runner)
  - (HRM payroll/leave accrual, CRM quote expiry/campaign sends, BR-COM-02 time-wait edges, BR-COM-03...
- **BR-CRM-001** - Business Requirements - ksf_FA_CRM Reporting Suite
  - Provide comprehensive customer reporting with RBAC-based access control, plus supporting reports for sales commission calculation, product labels, and work order printing.
- **BR-CRM-01-pipeline-funnel-forecast** - BR-CRM-01 — Pipeline Funnel & Forecast (C1)
  - (`get_dto` schema) / BR-COM-03 (stage-change...
- **BR-CRM-010-front-controller-adoption-and-vendor-health** - BR-CRM-010 — CRM Front Controller Adoption & Vendor Health
  - CRM's whole user-facing surface — sixteen views — hangs off a...
- **BR-CRM-02-email-integration-imap-smtp** - BR-CRM-02 — Email Integration (IMAP/SMTP) (C3)
  - BR-COM-03 (notify on important mail) / BR-COM-01 (`get_dto`...
- **BR-CRM-03-quote-to-order-conversion** - BR-CRM-03 — Quote → Order Conversion (C4)
  - (quote state), BR-COM-03 (notify), BR-COM-01...
- **BR-CRM-04-marketing-campaigns** - BR-CRM-04 — Marketing Campaigns (C2)
  - BR-COM-04 (scheduled sends), BR-COM-01...
- **BR-CRM-05-helpdesk-tickets** - BR-CRM-05 — Helpdesk / Cases / Tickets (C5)
  - (notify) / BR-CRM-02 (mail routing + reply...
- **BR-CRM-06-contracts-renewal-lifecycle** - BR-CRM-06 — Contracts & Renewal Lifecycle (C6)
  - (renewal reminders) / BR-COM-03...
- **BR-CRM-07-crm-dashboards-kpis** - BR-CRM-07 — CRM Dashboards & KPIs (C7)
  - BR-CRM-01 (forecast resolver), BR-CRM-06 (contracts...
- **BR-CRM-08-activity-automation-chaining** - BR-CRM-08 — Activity Automation & Chaining (C8)
  - BR-COM-02 (state machines) / BR-COM-03...
- **BR-CRM-09-account-classification-and-consent** - BR-CRM-09 — Account Classification and Consent (opt-in)
  - ksf_FA_HRM (employee identity), native FA...
- **BR-HRM-01-leave-approval-workflow** - BR-HRM-01 — Leave Approval Workflow (H3)
  - from ksf_FA_Common...
- **BR-HRM-02-recruitment-ats** - BR-HRM-02 — Recruitment ATS (H1)
  - from ksf_FA_Common (BR-COM-01/02/03), CRM contact types (applicants...
- **BR-HRM-03-payroll-run-gross-to-net** - BR-HRM-03 — Payroll Run: Gross → Net (H2)
  - ksf_FA_Common (BR-COM-04 batch...
- **BR-HRM-04-timesheet-payroll-integration** - BR-HRM-04 — Timesheet → Payroll Integration (H5)
  - substrate BR-COM-02 (approval states), BR-HRM-03 (payroll...
- **BR-HRM-05-onboarding-offboarding-lifecycle** - BR-HRM-05 — Onboarding / Offboarding Lifecycle (H6)
  - persons (0_crm_persons as the employer record), ksf_FA_CRM/Common...
- **BR-HRM-06-hrm-dashboards-reports** - BR-HRM-06 — HRM Dashboards & Reports (H7)
  - (refresh...
- **BR-HRM-07-training-certification-catalog** - BR-HRM-07 — Training & Certification Catalog (H8)
  - worked-window), substrate BR-COM-02 (enrollment flows) / BR-COM-04...
- **BR-HRM-08-employee-self-service-portal** - BR-HRM-08 — Employee Self-Service Portal (S5 + H9)
  - FA security areas; substrate BR-COM-01 (get_dto), BR-COM-03...

## Installing

This is a FrontAccounting extension; it installs into the FA `modules/` tree.

```bash
rsync -a --exclude .git --exclude vendor \
    ~/Documents/ksf_FA_CRM/ ~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_CRM/
cd ~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_CRM \
    && composer install --no-dev
```

Then in the FA UI: **Setup -> Extensions**, tick the module and press
**Refresh/Update**. FA reads `_init/config` for the package name and
version, so that file must stay in sync with this README.

Notes:

- Do **not** deploy `vendor/` from the dev checkout - it carries dev
  dependencies (PHPUnit needs PHP >= 8.1) that fatal on the 7.4 runtime.
- Never edit anything under `fa_modules/` by hand. It is build output;
  development happens in `~/Documents/ksf_FA_CRM`.

## Where to find it in the UI

Page entry points (each enforces its own `$page_security` area):

| Page | Security area |
|------|----------------|
| `account_relationships.php` | `SA_CRM_ACCOUNT_RELATIONSHIPS` |
| `calendar.php` | `SA_CUSTOMER` |
| `communications.php` | `SA_CUSTOMER` |
| `contact_relationships.php` | `SA_CRM_CONTACT_RELATIONSHIPS` |
| `convert_lead.php` | `SA_CRM` |
| `crm_tags.php` | `SA_CRM_TAGS` |
| `customer_types.php` | `SA_CUSTOMER` |
| `dashboard.php` | `SA_CRM_DASHBOARD` |
| `gedcom_export.php` | `SA_CRM_GEDCOM` |
| `gedcom_import.php` | `SA_CRM_GEDCOM` |
| `index.php` | - |
| `leads.php` | `SA_CRM` |
| `life_events.php` | `SA_CRM_LIFE_EVENTS` |
| `meeting_rooms.php` | `SA_CUSTOMER` |
| `meetings.php` | `SA_CUSTOMER` |
| `opportunities.php` | `SA_CRM` |
| `org_chart.php` | `SA_CRM_ORG_CHART` |
| `person_account_roles.php` | `SA_CRM_PERSON_ACCOUNT_ROLES` |
| `quotes.php` | `SA_CRM` |
| `realms.php` | `SA_CRMADMIN` |
| `territories.php` | `SA_CUSTOMER` |

- Hooks class: `hooks_ksf_FA_CRM`
- Security sections defined: `SS_CRM`
- **Extensions -> Setup** decides whether the module may run at all;
  grant the areas above under *Access* before the pages appear.

Menu placement is added by the page code (see `pages/` above); if a tab is
missing, check that the module is activated for your company and that your
user holds the listed security areas.

## Repository layout

```
ksf_FA_CRM/
├── hooks.php            # FA extension entry point, security areas
├── _init/config         # FA package manifest (plain Key: Value)
├── composer.json        # PSR-4 autoload, ksfraser/* dependencies
├── pages/               # FA pages, one $page_security each
├── src/                 # PSR-4 classes
├── includes/            # procedural FA helpers
├── sql/                 # schema (literal 0_ table prefix)
├── tests/               # PHPUnit suite
├── ProjectDcs/          # BABOK documents
└── vendor/              # not deployed; composer install --no-dev in fa_modules
```

## Contributing

1. Work in `~/Documents/ksf_FA_CRM` - never in `fa_modules/`.
2. Branch: `feat/`, `fix/`, `refactor/`, `docs/`.
3. Tests first: `cd ~/Documents/ksf_FA_CRM && php vendor/bin/phpunit`.
4. Requirements live in `ProjectDcs/` as individual files
   (`BR-<MOD>-<seq>-<slug>.md`, `FR-...`, `UC-...`, `UT-...`), and code
   carries `@BABOK Related: <ID>` in its docblock so traceability can be
   generated from the source.
5. Commit per logical change, then push. Deploys are rsync from dev;
   they must never carry commits of their own.

Conventions that apply across every module are documented in
`AGENTS_ARCH.md` (shared, hardlinked) and `AGENTS_APPENDIX.md`.
