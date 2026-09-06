<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
    Released under the terms of the GNU General Public License, GPL,
    as published by the Free Software Foundation, either version 3
    of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_CRM_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

function get_low_gp_sales($from_date, $to_date, $category, $location, $threshold)
{
    $from = date2sql($from_date);
    $to = date2sql($to_date);
    $threshold = $threshold ?: 10;

    $sql = "SELECT
        dt.tran_date,
        dt.reference,
        dm.name as customer_name,
        sm.stock_id,
        sm.description,
        ssd.quantity,
        ssd.unit_price,
        ssd.unit_cost,
        ((ssd.unit_price - ssd.unit_cost) / NULLIF(ssd.unit_price, 0)) * 100 as gp_percent,
        ssd.quantity * ssd.unit_price as total_amount
        FROM " . TB_PREF . "debtor_trans_details ssd
        INNER JOIN " . TB_PREF . "debtor_trans dt ON ssd.debtor_trans_type = dt.type AND ssd.debtor_trans_no = dt.trans_no
        INNER JOIN " . TB_PREF . "debtor_master dm ON dt.debtor_no = dm.debtor_no
        INNER JOIN " . TB_PREF . "stock_master sm ON ssd.stock_id = sm.stock_id
        WHERE dt.type = " . ST_SALESINVOICE . "
        AND dt.tran_date >= '$from'
        AND dt.tran_date <= '$to'
        AND ((ssd.unit_price - ssd.unit_cost) / NULLIF(ssd.unit_price, 0)) * 100 < $threshold";

    if ($category) {
        $sql .= " AND sm.category_id = " . db_escape($category);
    }
    if ($location) {
        $sql .= " AND ssd.loc_code = " . db_escape($location);
    }

    $sql .= " ORDER BY gp_percent ASC, dt.tran_date DESC";

    return db_query($sql, "No low GP sales found");
}

function print_low_gp_sales()
{
    global $path_to_root;

    $from_date = $_POST['PARAM_0'];
    $to_date = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
    $location = $_POST['PARAM_3'];
    $threshold = $_POST['PARAM_4'];
    $comments = $_POST['PARAM_5'];
    $destination = $_POST['PARAM_6'];
    $orientation = $_POST['PARAM_7'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $cols = array(0, 50, 100, 170, 240, 310, 370, 430);
    $headers = array(
        _('Date'), _('Invoice'), _('Customer'), _('Item'),
        _('Unit Price'), _('Unit Cost'), _('GP%'), _('Amount')
    );
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Date From'), 'from' => $from_date, 'to' => ''),
        2 => array('text' => _('Date To'), 'from' => $to_date, 'to' => ''),
        3 => array('text' => _('GP% Threshold'), 'from' => ($threshold ?: 10) . '%', 'to' => '')
    );

    $rep = new FrontReport(_('Low GP Sales Report'), "LowGPSales", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $res = get_low_gp_sales($from_date, $to_date, $category, $location, $threshold);

    while ($row = db_fetch($res))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['tran_date']);
        $rep->TextCol(1, 2, $row['reference']);
        $rep->TextCol(2, 3, $row['customer_name']);
        $rep->TextCol(3, 4, $row['stock_id'] . ' - ' . $row['description']);
        $rep->AmountCol(4, 5, $row['unit_price'], 2);
        $rep->AmountCol(5, 6, $row['unit_cost'], 2);
        $rep->AmountCol(6, 7, $row['gp_percent'], 2);
        $rep->AmountCol(7, 8, $row['total_amount'], 2);
    }

    $rep->Line($rep->row - 4);
    $rep->End();
}

print_low_gp_sales();
