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
include_once($path_to_root . "/gl/includes/gl_db.inc");

function get_customer_transactions($debtor_no, $from_date, $to_date,
    $show_orders, $show_deliveries, $show_invoices, $show_credits, $show_payments)
{
    global $db_connections;

    $current_user = &$_SESSION["wa_current_user"];
    $user_id = $current_user->user;

    $allowed_customers = null;

    $rbac_data = array('user_id' => $user_id);
    $rbac_result = hook_invoke_all('ksf_FA_RBAC', 'getUserCustomerRestrictions', $rbac_data);

    foreach ($rbac_result as $result) {
        if (is_array($result) && !empty($result)) {
            $allowed_customers = $result;
            break;
        }
    }

    if ($allowed_customers === null) {
        $salesman_code = $current_user->salesman;
        if (isset($salesman_code) && $salesman_code != '') {
            $sql = "SELECT DISTINCT debtor_no FROM " . TB_PREF . "cust_branch WHERE salesman = " . db_escape($salesman_code);
            $result = db_query($sql, "Could not get salesman customers");
            $allowed_customers = array();
            while ($row = db_fetch($result)) {
                $allowed_customers[] = $row['debtor_no'];
            }
        }
    }

    if ($allowed_customers !== null && !in_array($debtor_no, $allowed_customers)) {
        return null;
    }

    $types = array();
    if ($show_orders) $types[] = ST_SALESORDER;
    if ($show_deliveries) $types[] = ST_CUSTDELIVERY;
    if ($show_invoices) $types[] = ST_SALESINVOICE;
    if ($show_credits) $types[] = ST_CUSTCREDIT;
    if ($show_payments) $types[] = ST_CUSTPAYMENT;

    if (empty($types)) {
        return null;
    }

    $type_list = implode(',', $types);

    $sql = "SELECT trans.trans_no, trans.type, trans.reference,
            trans.tran_date, trans.due_date, trans.ov_amount, trans.ov_gst,
            trans.ov_freight, trans.ov_discount, trans.alloc,
            (trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_discount) as total,
            t.name as type_name
            FROM " . TB_PREF . "debtor_trans trans
            INNER JOIN " . TB_PREF . "systypes t ON trans.type = t.type_id
            WHERE trans.debtor_no = " . db_escape($debtor_no) . "
            AND trans.type IN ($type_list)
            AND trans.tran_date >= " . db_escape($from_date) . "
            AND trans.tran_date <= " . db_escape($to_date) . "
            ORDER BY trans.tran_date, trans.type, trans.trans_no";

    return db_query($sql, "No transactions returned");
}

function print_customer_trans_listing()
{
    global $path_to_root;

    $customer = $_POST['PARAM_0'];
    $from_date = $_POST['PARAM_1'];
    $to_date = $_POST['PARAM_2'];
    $show_orders = $_POST['PARAM_3'];
    $show_deliveries = $_POST['PARAM_4'];
    $show_invoices = $_POST['PARAM_5'];
    $show_credits = $_POST['PARAM_6'];
    $show_payments = $_POST['PARAM_7'];
    $comments = $_POST['PARAM_8'];
    $destination = $_POST['PARAM_9'];
    $orientation = $_POST['PARAM_10'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $customer_name = get_customer_name($customer);

    $cols = array(0, 50, 100, 160, 220, 280, 340, 400);
    $headers = array(
        _('Type'), _('Reference'), _('Date'), _('Due Date'),
        _('Amount'), _('Tax'), _('Total'), _('Allocated')
    );
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Customer'), 'from' => $customer_name, 'to' => ''),
        2 => array('text' => _('Date From'), 'from' => $from_date, 'to' => ''),
        3 => array('text' => _('Date To'), 'from' => $to_date, 'to' => '')
    );

    $rep = new FrontReport(_('Customer Transaction Listing'), "CustTransListing", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $res = get_customer_transactions($customer, date2sql($from_date), date2sql($to_date),
        $show_orders, $show_deliveries, $show_invoices, $show_credits, $show_payments);

    if (!$res) {
        $rep->NewLine();
        $rep->TextCol(0, 8, _("No transactions found for selected filters"));
        $rep->Line($rep->row - 4);
        $rep->End();
        return;
    }

    $total_amount = 0;
    $total_alloc = 0;

    while ($row = db_fetch($res))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['type_name']);
        $rep->TextCol(1, 2, $row['reference']);
        $rep->TextCol(2, 3, $row['tran_date']);
        $rep->TextCol(3, 4, $row['due_date'] ?: '-');
        $rep->AmountCol(4, 5, $row['ov_amount'], 2);
        $rep->AmountCol(5, 6, $row['ov_gst'], 2);
        $rep->AmountCol(6, 7, $row['total'], 2);
        $rep->AmountCol(7, 8, $row['alloc'], 2);

        $total_amount += $row['total'];
        $total_alloc += $row['alloc'];
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine(2);

    $rep->TextCol(0, 6, _("Totals:"));
    $rep->AmountCol(6, 7, $total_amount, 2);
    $rep->AmountCol(7, 8, $total_alloc, 2);

    $rep->NewLine();
    $rep->TextCol(0, 6, _("Outstanding:"));
    $rep->AmountCol(6, 7, $total_amount - $total_alloc, 2);

    $rep->End();
}

print_customer_trans_listing();
