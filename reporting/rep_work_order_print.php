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
include_once($path_to_root . "/manufacturing/includes/manufacturing_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

function get_work_orders($wo_ref, $location, $include_materials, $include_operations)
{
    $sql = "SELECT wo.id, wo.wo_ref, wo.item_code, wo.qty_reqd, wo.qty_issued,
            wo.date_issued, wo.required_date, wo.start_date,
            sm.description as item_description, sm.units as unit,
            loc.location_name, loc.loc_code
            FROM " . TB_PREF . "workorders wo
            INNER JOIN " . TB_PREF . "stock_master sm ON wo.item_code = sm.stock_id
            LEFT JOIN " . TB_PREF . "locations loc ON wo.loc_code = loc.loc_code
            WHERE 1=1";

    if ($wo_ref) {
        $sql .= " AND wo.wo_ref LIKE " . db_escape('%' . $wo_ref . '%');
    }
    if ($location) {
        $sql .= " AND wo.loc_code = " . db_escape($location);
    }

    $sql .= " ORDER BY wo.date_issued DESC, wo.wo_ref";

    return db_query($sql, "No work orders returned");
}

function get_work_order_bom($item_code)
{
    $sql = "SELECT bom.component, sm.description as component_desc,
            bom.quantity as qty_reqd,
            COALESCE(mss.quantity, 0) as qty_on_hand,
            bom.quantity - COALESCE(mss.quantity, 0) as qty_short
            FROM " . TB_PREF . "bom bom
            INNER JOIN " . TB_PREF . "stock_master sm ON bom.component = sm.stock_id
            LEFT JOIN " . TB_PREF . "locstock mss ON bom.component = mss.stock_id
            WHERE bom.parent = " . db_escape($item_code) . "
            AND bom.type = 'W'
            ORDER BY bom.component";

    return db_query($sql, "No BOM items returned");
}

function print_work_order()
{
    global $path_to_root;

    $wo_ref = $_POST['PARAM_0'];
    $location = $_POST['PARAM_1'];
    $include_materials = $_POST['PARAM_2'];
    $include_operations = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
    $destination = $_POST['PARAM_5'];
    $orientation = $_POST['PARAM_6'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $cols = array(0, 80, 160, 240, 320, 400, 480);
    $headers = array(
        _('WO Ref'), _('Item'), _('Required'),
        _('Issued'), _('On Hand'), _('Short')
    );
    $aligns = array('left', 'left', 'right', 'right', 'right', 'right');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Location'), 'from' => $location ?: _('All'), 'to' => ''),
        2 => array('text' => _('Materials'), 'from' => $include_materials ? _('Yes') : _('No'), 'to' => ''),
        3 => array('text' => _('Operations'), 'from' => $include_operations ? _('Yes') : _('No'), 'to' => '')
    );

    $rep = new FrontReport(_('Work Order Print'), "WorkOrderPrint", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $res = get_work_orders($wo_ref, $location, $include_materials, $include_operations);

    while ($row = db_fetch($res))
    {
        $rep->NewPage();

        $rep->TextCol(0, 4, _('Work Order: ') . $row['wo_ref']);
        $rep->NewLine();
        $rep->TextCol(0, 4, _('Product: ') . $row['item_code'] . ' - ' . $row['item_description']);
        $rep->NewLine();
        $rep->TextCol(0, 4, _('Quantity Required: ') . $row['qty_reqd'] . ' ' . $row['unit']);
        $rep->NewLine();
        $rep->TextCol(0, 4, _('Quantity Issued: ') . $row['qty_issued']);
        $rep->NewLine();
        $rep->TextCol(0, 4, _('Date Issued: ') . $row['date_issued']);
        $rep->NewLine();
        $rep->TextCol(0, 4, _('Required By: ') . $row['required_date']);
        $rep->NewLine();
        $rep->TextCol(0, 4, _('Location: ') . $row['location_name']);
        $rep->NewLine(2);

        if ($include_materials) {
            $rep->Line($rep->row - 4);
            $rep->NewLine();
            $rep->TextCol(0, 6, _('BILL OF MATERIALS'));
            $rep->NewLine();
            $rep->Line($rep->row - 4);
            $rep->NewLine();

            $bom = get_work_order_bom($row['item_code']);
            while ($bom_row = db_fetch($bom)) {
                $rep->TextCol(0, 1, $bom_row['component']);
                $rep->TextCol(1, 2, $bom_row['component_desc']);
                $rep->AmountCol(2, 3, $bom_row['qty_reqd'], 2);
                $rep->AmountCol(3, 4, $bom_row['qty_on_hand'], 2);
                $rep->AmountCol(4, 5, $bom_row['qty_short'], 2);
                $rep->NewLine();
            }
        }

        $rep->Line($rep->row - 4);
        $rep->NewLine();
        $rep->TextCol(0, 6, _('Signature: _____________________ Date: _____________'));
        $rep->NewLine();
        $rep->TextCol(0, 6, _('Approved By: __________________ Date: _____________'));
    }

    $rep->End();
}

print_work_order();
