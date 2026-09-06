# Functional Requirements - ksf_FA_CRM

## FR-CRM-001-001: Customer List Report
**BABOK Related**: BR-CRM-001
**UML**: Sequence diagram for RBAC-filtered customer access

### Overview
Generate customer list with RBAC-based visibility restrictions.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Salesman | DROPDOWN | Filter by salesman (optional) |
| Area | DROPDOWN | Filter by sales area (optional) |
| Show Inactive | YES_NO | Include inactive customers |
| Comments | TEXTBOX | Report comments |

### Access Control Logic
```
IF user has 'SA_CRM_ADMIN' or 'SA_SALES_MANAGER':
    RETURN all customers (no filter)
ELSE IF user has 'SA_SALESMAN' role:
    RETURN only customers where debtor_master.salesman = user.salesman_code
ELSE:
    RETURN all customers (public list)
```

### Output
PDF/Excel with columns:
- Customer Code, Name, Phone, Email
- Salesman, Area
- Balance, Credit Limit
- Status (active/inactive)

### Tables Queried
- `debtor_master`
- `cust_branch`
- `salesman`
- `areas`

---

## FR-CRM-001-002: Customer Transaction Listing Report
**BABOK Related**: BR-CRM-001

### Overview
List all transactions for selected customer with type filtering.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Customer | DROPDOWN | Select customer (required) |
| Date From | DATEBEGIN | Start date |
| Date To | DATEEND | End date |
| Show Orders | YES_NO | Include sales orders |
| Show Deliveries | YES_NO | Include deliveries |
| Show Invoices | YES_NO | Include invoices |
| Show Credits | YES_NO | Include credit notes |
| Show Payments | YES_NO | Include payments |
| Show Allocated | YES_NO | Show allocation details |
| Comments | TEXTBOX | Report comments |
| Destination | DESTINATION | PDF/Excel |

### Output
Grouped by transaction type, sorted by date:
- Type, Reference, Date, Due Date, Quantity, Amount, Alloc

### Tables Queried
- `debtor_trans`
- `sales_orders`
- `sales_order_details`
- `cust_deliveries`
- `bank_trans`

---

## FR-CRM-001-003: Customer Statement Report
**BABOK Related**: BR-CRM-001

### Overview
Generate customer statement summarizing invoices vs payments.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Customer | DROPDOWN | Select customer |
| Date | DATE | Statement date |
| Currency Filter | CURRENCY | Filter by currency |
| Show Also Allocated | YES_NO | Include allocated amounts |
| Email Customers | YES_NO | Email statement |
| Comments | TEXTBOX | Report comments |

### Output
Statement format:
- Customer header info
- Opening balance (brought forward)
- Invoice detail with running balance
- Payment/credit detail
- Closing balance

### Tables Queried
- `debtor_trans`
- `debtor_master`
- `cust_branch`

---

## FR-CRM-001-004: Low GP Sales Report
**BABOK Related**: BR-CRM-001

### Overview
Identify sales transactions where gross profit falls below threshold.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Date From | DATEBEGIN | Start date |
| Date To | DATEEND | End date |
| Category | CATEGORIES | Filter by stock category |
| Location | LOCATIONS | Filter by location |
| GP% Threshold | TEXT | Minimum acceptable GP% (default 10%) |
| Comments | TEXTBOX | Report comments |

### Output
| Column | Description |
|--------|-------------|
| Date | Transaction date |
| Invoice # | Reference |
| Customer | Customer name |
| Item | Stock item |
| Qty | Quantity sold |
| Unit Price | Selling price |
| Cost | Unit cost |
| GP% | Gross profit percentage |
| Amount | Total amount |

### Tables Queried
- `debtor_trans`
- `debtor_trans_details`
- `stock_master`
- `stock_moves`

### Calculation
```
GP% = ((selling_price - unit_cost) / selling_price) * 100
```

---

## FR-CRM-001-005: Product Labels Report
**BABOK Related**: BR-CRM-001

### Overview
Print labels for products (barcodes, shelf labels, address labels).

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Label Type | SELECT | Product, Shelf, Address, Shipping |
| Items | TEXT | Stock items to print (comma/semicolon separated) |
| Category | CATEGORIES | Print all items in category |
| Location | LOCATIONS | Print items from location |
| Label Size | SELECT | 40x20mm, 50x25mm, 100x50mm, Custom |
| Copies | TEXT | Number of copies per item (default 1) |
| Include Price | YES_NO | Show price on label |
| Include Barcode | YES_NO | Show barcode (Code128) |

### Output
PDF with labels arranged for thermal/desk label printer.

### Label Formats
**Product Label**: Item code, description, price, barcode
**Shelf Label**: Item code, description, location, bin
**Address Label**: Customer name, address, contact
**Shipping Label**: Full shipping address, order ref, barcode

---

## FR-CRM-001-006: Product Spec Sheet Report
**BABOK Related**: BR-CRM-001

### Overview
Print specification sheet for products using ProductAttributes data.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Items | TEXT | Stock items (comma/semicolon separated) |
| Category | CATEGORIES | Print specs for category |
| Include Image | YES_NO | Include product image |
| Include Pricing | YES_NO | Include pricing info |
| Language | SELECT | Report language |
| Comments | TEXTBOX | Comments |

### Output
Multi-page PDF spec sheet:
- Header: Product name, SKU, image
- Specifications table from `product_attribute_types` + `product_attributes`
- Pricing tiers from `prices`
- Related products / alternatives
- Notes section

### Tables Queried
- `stock_master`
- `product_attribute_types`
- `product_attribute_assignments`
- `prices`
- `product_categories`

---

## FR-CRM-001-007: Sales Commission Report
**BABOK Related**: BR-CRM-001

### Overview
Calculate and report sales commissions based on invoiced sales.

### Trigger
Hook on `ST_SALESINVOICE` transaction write.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Date From | DATEBEGIN | Commission period start |
| Date To | DATEEND | Commission period end |
| Salesman | DROPDOWN | Filter by salesman |
| Commission % | TEXT | Override default commission % |
| Summary Only | YES_NO | Show only totals |
| Comments | TEXTBOX | Comments |

### Output
| Salesman | Sales Amount | Commission Rate | Commission Amount |
|----------|--------------|----------------|------------------|
| John D | $50,000 | 5% | $2,500 |
| Jane S | $35,000 | 5% | $1,750 |

### Calculation
```
Commission = SUM(invoice_total * commission_rate)
WHERE invoice_date BETWEEN @from AND @to
AND salesman_code = @salesman (if specified)
```

### Tables Queried
- `debtor_trans`
- `sales_orders`
- `salesman`
- `www_users`

### RBAC
- Salesmen see only their own commission
- Sales Managers see their team's commissions
- Admins see all

---

## FR-CRM-001-008: Work Order Print Report
**BABOK Related**: BR-CRM-001

### Overview
Print work order with BOM requirements and production details.

### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| Work Order # | TEXT | WO number (supports range) |
| Location | LOCATIONS | Production location |
| Include Materials | YES_NO | Show BOM materials |
| Include Operations | YES_NO | Show work operations |
| Comments | TEXTBOX | Comments |

### Output
Multi-page work order:
- WO Header: Number, date, priority, due date
- Product info: Item, quantity, BOM revision
- Materials list: Component, qty required, qty on hand
- Operations: Work center, setup time, run time, labor
- Quality checkpoints from ProductAttributes

### Tables Queried
- `workorders`
- `woitems`
- `bom`
- `stock_master`
- `locations`
- `workcentres`
