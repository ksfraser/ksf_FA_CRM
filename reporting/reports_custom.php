<?php
/**
 * ksf_FA_CRM - Reports Customization
 *
 * Registers all CRM-related reports:
 * - Customer List
 * - Customer Transaction Listing
 * - Customer Statement
 * - Low GP Sales
 * - Product Labels
 * - Product Spec Sheets
 * - Sales Commission
 * - Work Order Print
 */

global $reports, $dim;

define('RC_CRM_REPORTS', 10);

function crm_customers($name, $type)
{
    if ($type == 'CRM_CUSTOMERS')
        return customer_list($name, null, _('All Customers'), false, true);
}

function crm_salesman($name, $type)
{
    if ($type == 'CRM_SALESMAN')
        return salesman_list($name, null, _('All Salesmen'), false, true);
}

function crm_areas($name, $type)
{
    if ($type == 'CRM_AREAS')
        return area_list($name, null, _('All Areas'), false, true);
}

function crm_locations($name, $type)
{
    if ($type == 'CRM_LOCATIONS')
        return location_list($name, null, _('All Locations'), false, true);
}

function crm_stock_items($name, $type)
{
    if ($type == 'CRM_STOCK')
        return stock_item_list($name, null, _('All Items'), false, true);
}

function crm_categories($name, $type)
{
    if ($type == 'CRM_CATEGORIES')
        return category_list($name, null, _('All Categories'), false, true);
}

function crm_label_type($name, $type)
{
    if ($type == 'CRM_LABEL_TYPE') {
        return "<select name='" . $name . "'>
            <option value='PRODUCT'>" . _('Product Labels') . "</option>
            <option value='SHELF'>" . _('Shelf Labels') . "</option>
            <option value='ADDRESS'>" . _('Address Labels') . "</option>
            <option value='SHIPPING'>" . _('Shipping Labels') . "</option>
        </select>";
    }
}

function crm_label_size($name, $type)
{
    if ($type == 'CRM_LABEL_SIZE') {
        return "<select name='" . $name . "'>
            <option value='40x20'>40x20mm</option>
            <option value='50x25'>50x25mm</option>
            <option value='100x50'>100x50mm</option>
            <option value='A4'>A4 Sheet</option>
        </select>";
    }
}

$reports->register_controls('crm_customers');
$reports->register_controls('crm_salesman');
$reports->register_controls('crm_areas');
$reports->register_controls('crm_locations');
$reports->register_controls('crm_stock_items');
$reports->register_controls('crm_categories');
$reports->register_controls('crm_label_type');
$reports->register_controls('crm_label_size');

$reports->addReportClass(_('CRM Reports'), RC_CRM_REPORTS);

$reports->addReport(RC_CRM_REPORTS, '_customer_list', _('Customer List'),
    array(
        _('Salesman') => 'CRM_SALESMAN',
        _('Area') => 'CRM_AREAS',
        _('Show Inactive') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_customer_trans_listing', _('Customer Transaction Listing'),
    array(
        _('Customer') => 'CRM_CUSTOMERS',
        _('Date From') => 'DATEBEGIN',
        _('Date To') => 'DATEENDM',
        _('Show Orders') => 'YES_NO',
        _('Show Deliveries') => 'YES_NO',
        _('Show Invoices') => 'YES_NO',
        _('Show Credits') => 'YES_NO',
        _('Show Payments') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_customer_statement', _('Customer Statement'),
    array(
        _('Customer') => 'CRM_CUSTOMERS',
        _('Date') => 'DATE',
        _('Currency Filter') => 'CURRENCY',
        _('Show Also Allocated') => 'YES_NO',
        _('Email Customers') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_low_gp_sales', _('Low GP Sales'),
    array(
        _('Date From') => 'DATEBEGIN',
        _('Date To') => 'DATEEND',
        _('Category') => 'CATEGORIES',
        _('Location') => 'LOCATIONS',
        _('GP% Threshold') => 'TEXT',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_product_labels', _('Product Labels'),
    array(
        _('Label Type') => 'CRM_LABEL_TYPE',
        _('Items') => 'TEXT',
        _('Category') => 'CATEGORIES',
        _('Location') => 'LOCATIONS',
        _('Label Size') => 'CRM_LABEL_SIZE',
        _('Copies') => 'TEXT',
        _('Include Price') => 'YES_NO',
        _('Include Barcode') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_product_specs', _('Product Specification Sheets'),
    array(
        _('Items') => 'TEXT',
        _('Category') => 'CATEGORIES',
        _('Include Image') => 'YES_NO',
        _('Include Pricing') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_sales_commission', _('Sales Commission Report'),
    array(
        _('Date From') => 'DATEBEGIN',
        _('Date To') => 'DATEEND',
        _('Salesman') => 'CRM_SALESMAN',
        _('Summary Only') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CRM_REPORTS, '_work_order_print', _('Work Order Print'),
    array(
        _('Work Order #') => 'TEXT',
        _('Location') => 'LOCATIONS',
        _('Include Materials') => 'YES_NO',
        _('Include Operations') => 'YES_NO',
        _('Comments') => 'TEXTBOX',
        _('Orientation') => 'ORIENTATION',
        _('Destination') => 'DESTINATION'));
