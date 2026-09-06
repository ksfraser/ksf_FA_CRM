# Business Requirements - ksf_FA_CRM Reporting Suite

## BR-CRM-001: Customer Reporting and CRM Reports

### Purpose
Provide comprehensive customer reporting with RBAC-based access control, plus supporting reports for sales commission calculation, product labels, and work order printing.

### Scope
- Customer List with RBAC filtering
- Customer Transaction Listing with type filtering
- Customer Statement generation
- Low GP Sales identification
- Product and address label printing
- Product specification sheets
- Sales Commission calculation and reporting
- Work Order printing with BOM

### Stakeholders
- Sales Team
- Sales Managers
- CRM Administrators
- Warehouse Staff (labels)
- Production (Work Orders)
- Finance (Commissions)

### Dependencies
- `ksf_FA_RBAC` for access control
- `ksf_FA_ProductAttributes` for spec sheets
- `ksf_FA_HRM` for salesman commission hooks

### Requirements
- FR-CRM-001-001: Customer List Report
- FR-CRM-001-002: Customer Transaction Listing Report
- FR-CRM-001-003: Customer Statement Report
- FR-CRM-001-004: Low GP Sales Report
- FR-CRM-001-005: Product Labels Report
- FR-CRM-001-006: Product Spec Sheet Report
- FR-CRM-001-007: Sales Commission Report
- FR-CRM-001-008: Work Order Print Report

### Status
Draft - Design Complete
