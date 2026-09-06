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
include_once($path_to_root . "/sales/includes/sales_Db.inc");

function get_sales_commission($from_date, $to_date, $salesman, $summary_only)
{
    $from = date2sql($from_date);
    $to = date2sql($to_date);

    $sql = "SELECT
        s.salesman_code,
        s.salesman_name,
        SUM(dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_discount) as total_sales,
        s.commission_rate,
        SUM((dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_discount) * s.commission_rate / 100) as commission
        FROM " . TB_PREF . "debtor_trans dt
        INNER JOIN " . TB_PREF . "cust_branch cb ON dt.debtor_no = cb.debtor_no
        INNER JOIN " . TB_PREF . "salesman s ON cb.salesman = s.salesman_code
        WHERE dt.type = " . ST_SALESINVOICE . "
        AND dt.tran_date >= '$from'
        AND dt.tran_date <= '$to'";

    if ($salesman) {
        $sql .= " AND s.salesman_code = " . db_escape($salesman);
    }

    $sql .= " GROUP BY s.salesman_code, s.salesman_name, s.commission_rate
              ORDER BY s.salesman_name";

    return db_query($sql, "No commission data returned");
}

function get_commission_details($salesman_code, $from_date, $to_date)
{
    $from = date2sql($from_date);
    $to = date2sql($to_date);

    $sql = "SELECT
        dt.trans_no,
        dt.reference,
        dt.tran_date,
        dm.name as customer_name,
        dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_discount as total,
        s.commission_rate,
        (dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_discount) * s.commission_rate / 100 as commission
        FROM " . TB_PREF . "debtor_trans dt
        INNER JOIN " . TB_PREF . "debtor_master dm ON dt.debtor_no = dm.debtor_no
        INNER JOIN " . TB_PREF . "cust_branch cb ON dt.debtor_no = cb.debtor_no
        INNER JOIN " . TB_PREF . "salesman s ON cb.salesman = s.salesman_code
        WHERE dt.type = " . ST_SALESINVOICE . "
        AND dt.tran_date >= '$from'
        AND dt.tran_date <= '$to'
        AND s.salesman_code = " . db_escape($salesman_code) . "
        ORDER BY dt.tran_date DESC";

    return db_query($sql, "No details returned");
}

function print_sales_commission()
{
    global $path_to_root;

    $from_date = $_POST['PARAM_0'];
    $to_date = $_POST['PARAM_1'];
    $salesman = $_POST['PARAM_2'];
    $summary_only = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
    $destination = $_POST['PARAM_5'];
    $orientation = $_POST['PARAM_6'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $cols = array(0, 100, 200, 280, 360, 440);
    $headers = array(
        _('Salesman'), _('Total Sales'), _('Commission Rate'),
        _('Commission'), _('Reference'), _('Date')
    );
    $aligns = array('left', 'right', 'right', 'right', 'left', 'left');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Period'), 'from' => $from_date, 'to' => $to_date),
        2 => array('text' => _('Salesman'), 'from' => $salesman ?: _('All'), 'to' => '')
    );

    $rep = new FrontReport(_('Sales Commission Report'), "SalesCommission", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $res = get_sales_commission($from_date, $to_date, $salesman, $summary_only);
    $grand_total = 0;
    $grand_commission = 0;

    while ($row = db_fetch($res))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['salesman_name']);
        $rep->AmountCol(1, 2, $row['total_sales'], 2);
        $rep->AmountCol(2, 3, $row['commission_rate'], 2);
        $rep->AmountCol(3, 4, $row['commission'], 2);

        $grand_total += $row['total_sales'];
        $grand_commission += $row['commission'];

        if (!$summary_only) {
            $rep->NewLine();
            $details = get_commission_details($row['salesman_code'], $from_date, $to_date);
            while ($detail = db_fetch($details)) {
                $rep->TextCol(4, 5, $detail['reference']);
                $rep->TextCol(5, 6, $detail['tran_date']);
                $rep->NewLine();
            }
        }
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->TextCol(0, 1, _('Grand Total:'));
    $rep->AmountCol(1, 2, $grand_total, 2);
    $rep->AmountCol(3, 4, $grand_commission, 2);

    $rep->End();
}

print_sales_commission();
