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

function get_customer_list($salesman = null, $area = null, $show_inactive = false)
{
    global $db_connections;

    $current_user = &$_SESSION["wa_current_user"];
    $user_id = $current_user->user;

    $can_view_all = false;

    $auth_data = array(
        'user_id' => $user_id,
        'action' => 'view',
        'module' => 'customer',
        'resource_type' => 'customer',
        'resource_id' => null
    );
    $auth_result = hook_invoke_first('ksf_FA_RBAC', 'authorize', $auth_data);

    if ($auth_result === true || $auth_result === null) {
        $can_view_all = true;
    }

    $salesman_code = $current_user->salesman;

    $sql = "SELECT d.debtor_no, d.name, d.address, d.phone, d.email,
            d.fax, d.gst_no, d.tax_ref, d.credit_status, d.credit_limit,
            d.balance, d.discount, d.pymt_discount, d.inv_addr_branch,
            c.branch_code, c.branch_name, c.salesman, c.area,
            c.tax_group_id, c.default_location, c.discount,
            s.salesman_name, a.description as area_name,
            ca.name as credit_status_name,
            IF(d.balance > d.credit_limit, 'OVER_LIMIT',
                IF(d.credit_status = 1, 'HOLD', 'OK')) as status
            FROM " . TB_PREF . "debtor_master d
            LEFT JOIN " . TB_PREF . "cust_branch c ON d.debtor_no = c.debtor_no
            LEFT JOIN " . TB_PREF . "salesman s ON c.salesman = s.salesman_code
            LEFT JOIN " . TB_PREF . "areas a ON c.area = a.area_code
            LEFT JOIN " . TB_PREF . "credit_status ca ON d.credit_status = ca.id
            WHERE 1=1";

    if (!$show_inactive) {
        $sql .= " AND d.inactive = 0";
    }

    if ($salesman) {
        $sql .= " AND c.salesman = " . db_escape($salesman);
    }

    if ($area) {
        $sql .= " AND c.area = " . db_escape($area);
    }

    if (!$can_view_all && isset($salesman_code) && $salesman_code != '') {
        $sql .= " AND c.salesman = " . db_escape($salesman_code);
    }

    $sql .= " ORDER BY d.name, c.branch_name";

    return db_query($sql, "No customer list returned");
}

function print_customer_list()
{
    global $path_to_root;

    $salesman = $_POST['PARAM_0'];
    $area = $_POST['PARAM_1'];
    $show_inactive = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
    $destination = $_POST['PARAM_4'];
    $orientation = $_POST['PARAM_5'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $rep = new FrontReport(_('Customer List'), "CustomerList", user_pagesize(), 9, $orientation);

    $cols = array(0, 100, 180, 260, 340, 420, 500, 560);
    $headers = array(
        _('Code'), _('Customer Name'), _('Contact'), _('Phone'),
        _('Salesman'), _('Area'), _('Balance'), _('Status')
    );
    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'right', 'center');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Salesman'), 'from' => $salesman ?: _('All'), 'to' => ''),
        2 => array('text' => _('Area'), 'from' => $area ?: _('All'), 'to' => ''),
        3 => array('text' => _('Show Inactive'), 'from' => $show_inactive ? _('Yes') : _('No'), 'to' => '')
    );

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $res = get_customer_list($salesman, $area, $show_inactive);
    $customer_no = '';
    while ($row = db_fetch($res))
    {
        if ($customer_no != $row['debtor_no'])
        {
            if ($customer_no != '')
            {
                $rep->Line($rep->row - 4);
                $rep->NewLine();
            }
            $customer_no = $row['debtor_no'];
        }

        $rep->NewLine();
        $rep->TextCol(0, 1, $row['debtor_no']);
        $rep->TextCol(1, 2, $row['name']);
        $rep->TextCol(2, 3, $row['phone']);
        $rep->TextCol(3, 4, $row['salesman_name'] ?? '');
        $rep->TextCol(4, 5, $row['area_name'] ?? '');
        $rep->AmountCol(5, 6, $row['balance'], 2);
        $rep->TextCol(6, 7, $row['status']);
    }

    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
}

print_customer_list();
