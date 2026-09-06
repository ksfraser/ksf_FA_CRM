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

function get_open_balance($debtor_no, $to)
{
    global $db_connections;

    $current_user = &$_SESSION["wa_current_user"];
    $user_id = $current_user->user;

    $can_view = false;

    $auth_data = array(
        'user_id' => $user_id,
        'action' => 'view',
        'module' => 'customer',
        'resource_type' => 'customer',
        'resource_id' => $debtor_no
    );
    $auth_result = hook_invoke_first('ksf_FA_RBAC', 'authorize', $auth_data);

    if ($auth_result === true || $auth_result === null) {
        $can_view = true;
    }

    if (!$can_view) {
        $salesman_code = $current_user->salesman;
        if (isset($salesman_code) && $salesman_code != '') {
            $sql = "SELECT 1 FROM " . TB_PREF . "cust_branch
                    WHERE debtor_no = " . db_escape($debtor_no) . "
                    AND salesman = " . db_escape($salesman_code) . " LIMIT 1";
            $result = db_query($sql, "Could not check customer access");
            $can_view = db_num_rows($result) > 0;
        }
    }

    if (!$can_view) {
        return null;
    }

    $to = date2sql($to);

    $sql = "SELECT SUM(IF(t.type = " . ST_SALESINVOICE . " OR t.type = " . ST_BANKPAYMENT . ",
        (t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 0)) AS charges,
        SUM(IF(t.type <> " . ST_SALESINVOICE . " AND t.type <> " . ST_BANKPAYMENT . ",
            (t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1, 0)) AS credits,
        SUM(t.alloc) AS Allocated,
        SUM(IF(t.type = " . ST_SALESINVOICE . " OR t.type = " . ST_BANKPAYMENT . ",
            (t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount - t.alloc),
            ((t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount) * -1 + t.alloc))) AS OutStanding
        FROM " . TB_PREF . "debtor_trans t
        WHERE t.debtor_no = " . db_escape($debtor_no) . "
        AND t.type <> " . ST_CUSTDELIVERY;
    if ($to)
        $sql .= " AND t.tran_date < '$to'";
    $sql .= " GROUP BY debtor_no";

    $result = db_query($sql, "No transactions were returned");
    return db_fetch($result);
}

function get_statement_transactions($debtor_no, $from, $to)
{
    $from = date2sql($from);
    $to = date2sql($to);

    $sql = "SELECT trans.trans_no, trans.type, trans.reference,
            trans.tran_date, trans.due_date,
            trans.ov_amount, trans.ov_gst, trans.ov_freight, trans.ov_discount,
            trans.alloc, trans.inv_date,
            t.name as type_name,
            (trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_discount) as total
            FROM " . TB_PREF . "debtor_trans trans
            INNER JOIN " . TB_PREF . "systypes t ON trans.type = t.type_id
            WHERE trans.debtor_no = " . db_escape($debtor_no) . "
            AND trans.type <> " . ST_CUSTDELIVERY . "
            AND trans.tran_date >= '$from'
            AND trans.tran_date <= '$to'
            ORDER BY trans.tran_date, trans.type, trans.trans_no";

    return db_query($sql, "No transactions were returned");
}

function print_customer_statement()
{
    global $path_to_root;

    $customer = $_POST['PARAM_0'];
    $statement_date = $_POST['PARAM_1'];
    $currency_filter = $_POST['PARAM_2'];
    $show_allocated = $_POST['PARAM_3'];
    $email = $_POST['PARAM_4'];
    $comments = $_POST['PARAM_5'];
    $destination = $_POST['PARAM_6'];
    $orientation = $_POST['PARAM_7'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $customer_record = get_customer($customer);
    $customer_name = $customer_record['name'];
    $customer_address = $customer_record['address'];

    $open_balance = get_open_balance($customer, $statement_date);
    $ob = $open_balance ? $open_balance['OutStanding'] : 0;

    $cols = array(0, 60, 120, 180, 240, 300, 360, 420);
    $headers = array(
        _('Date'), _('Type'), _('Reference'), _('Due Date'),
        _('Charges'), _('Payments'), _('Balance')
    );
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Customer'), 'from' => $customer_name, 'to' => ''),
        2 => array('text' => _('Statement Date'), 'from' => $statement_date, 'to' => ''),
        3 => array('text' => _('Currency'), 'from' => $currency_filter ?: _('All'), 'to' => '')
    );

    $rep = new FrontReport(_('Customer Statement'), "CustStatement", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $rep->TextCol(0, 7, $customer_name);
    $rep->NewLine();
    $rep->TextCol(0, 7, $customer_address);
    $rep->NewLine();
    $rep->TextCol(0, 7, _("Statement Date: ") . $statement_date);
    $rep->NewLine(2);

    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->TextCol(0, 5, _("Opening Balance:"));
    $rep->AmountCol(5, 6, $ob, 2);
    $rep->NewLine(2);

    $res = get_statement_transactions($customer, '2000-01-01', date2sql($statement_date));
    $running_balance = $ob;

    while ($row = db_fetch($res))
    {
        $rep->NewLine();
        $rep->TextCol(0, 1, $row['tran_date']);
        $rep->TextCol(1, 2, $row['type_name']);
        $rep->TextCol(2, 3, $row['reference']);
        $rep->TextCol(3, 4, $row['due_date'] ?: '-');

        if (in_array($row['type'], array(ST_SALESINVOICE, ST_CUSTCREDIT))) {
            $rep->AmountCol(4, 5, $row['total'], 2);
            $rep->AmountCol(5, 6, 0, 2);
            $running_balance += $row['total'];
        } else {
            $rep->AmountCol(4, 5, 0, 2);
            $rep->AmountCol(5, 6, abs($row['total']), 2);
            $running_balance -= abs($row['total']);
        }

        $rep->AmountCol(6, 7, $running_balance, 2);
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine(2);
    $rep->TextCol(0, 5, _("Closing Balance:"));
    $rep->AmountCol(5, 6, $running_balance, 2);

    $rep->End();
}

print_customer_statement();
