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
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

function get_items_for_labels($items_text, $category, $location, $include_price, $include_barcode)
{
    $items = array_filter(array_map('trim', explode(',', str_replace(';', ',', $items_text))));

    $sql = "SELECT sm.stock_id, sm.description, sm.units, sm.mb_flag,
            sm.sales_price, sm.cost,
            loc.location_name, loc.bin,
            CASE WHEN LENGTH(sm.stock_id) > 4 THEN SUBSTR(sm.stock_id, LENGTH(sm.stock_id) - 3, 4) ELSE sm.stock_id END as barcode
            FROM " . TB_PREF . "stock_master sm
            LEFT JOIN " . TB_PREF . "locations loc ON sm.default_location = loc.loc_code
            WHERE sm.mb_flag = 'B' OR sm.mb_flag = 'M'";

    if (!empty($items)) {
        $item_list = implode(',', array_map('db_escape', $items));
        $sql .= " AND sm.stock_id IN ($item_list)";
    } elseif ($category) {
        $sql .= " AND sm.category_id = " . db_escape($category);
    }

    $sql .= " ORDER BY sm.stock_id";

    return db_query($sql, "No items returned for labels");
}

function print_product_labels()
{
    global $path_to_root;

    $label_type = $_POST['PARAM_0'];
    $items_text = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
    $location = $_POST['PARAM_3'];
    $label_size = $_POST['PARAM_4'];
    $copies = $_POST['PARAM_5'] ?: 1;
    $include_price = $_POST['PARAM_6'];
    $include_barcode = $_POST['PARAM_7'];
    $comments = $_POST['PARAM_8'];
    $destination = $_POST['PARAM_9'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $label_width = 40;
    $label_height = 20;
    switch ($label_size) {
        case '50x25':
            $label_width = 50;
            $label_height = 25;
            break;
        case '100x50':
            $label_width = 100;
            $label_height = 50;
            break;
        case 'A4':
            $label_width = 70;
            $label_height = 37;
            break;
    }

    $cols = array(0, $label_width * 10, $label_width * 20, $label_width * 30, $label_width * 40, $label_width * 50);
    $headers = array('Item Code', 'Description', 'Price', 'Barcode');
    $aligns = array('left', 'left', 'right', 'center');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Label Type'), 'from' => $label_type, 'to' => ''),
        2 => array('text' => _('Size'), 'from' => $label_size, 'to' => '')
    );

    $rep = new FrontReport(_('Product Labels'), "ProductLabels", "A4", 9);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $res = get_items_for_labels($items_text, $category, $location, $include_price, $include_barcode);

    $col_count = 0;
    $items_per_row = 3;

    while ($row = db_fetch($res))
    {
        if ($col_count == 0) {
            $rep->NewLine();
        }

        $label_content = $row['stock_id'] . "\n" . $row['description'];
        if ($include_price) {
            $label_content .= "\n" . _("Price: ") . $row['sales_price'];
        }
        if ($include_barcode) {
            $label_content .= "\n*" . $row['barcode'] . "*";
        }

        $rep->TextWrap($col_count * ($label_width * 10 / $items_per_row),
            $rep->row - $label_height,
            $label_width * 10 / $items_per_row - 5,
            $label_content);

        $col_count = ($col_count + 1) % $items_per_row;
    }

    $rep->End();
}

print_product_labels();
