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

function get_product_specs($items_text, $category, $include_pricing)
{
    $items = array_filter(array_map('trim', explode(',', str_replace(';', ',', $items_text))));

    $sql = "SELECT sm.stock_id, sm.description, sm.long_description,
            sm.units, sm.mb_flag, sm.category_id,
            sm.sales_price, sm.cost, sm.material_cost, sm.labour_cost, sm.overhead_cost,
            sc.category_description,
            pat.type_name, pa.attribute_value
            FROM " . TB_PREF . "stock_master sm
            LEFT JOIN " . TB_PREF . "stock_category sc ON sm.category_id = sc.category_id
            LEFT JOIN " . TB_PREF . "product_attribute_types pat ON sm.stock_id = pat.stock_id
            LEFT JOIN " . TB_PREF . "product_attributes pa ON pat.type_id = pa.attribute_type_id
            WHERE 1=1";

    if (!empty($items)) {
        $item_list = implode(',', array_map('db_escape', $items));
        $sql .= " AND sm.stock_id IN ($item_list)";
    } elseif ($category) {
        $sql .= " AND sm.category_id = " . db_escape($category);
    }

    $sql .= " ORDER BY sm.stock_id, pat.type_name";

    return db_query($sql, "No product specs returned");
}

function print_product_specs()
{
    global $path_to_root;

    $items_text = $_POST['PARAM_0'];
    $category = $_POST['PARAM_1'];
    $include_image = $_POST['PARAM_2'];
    $include_pricing = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
    $destination = $_POST['PARAM_5'];
    $orientation = $_POST['PARAM_6'];

    if ($destination)
        include_once($path_to_root . "/reporting/includes/excel_report.inc");
    else
        include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $cols = array(0, 150, 350, 500);
    $headers = array(_('Item'), _('Description'), _('Value'));
    $aligns = array('left', 'left', 'left');

    $params = array(
        0 => $comments,
        1 => array('text' => _('Items'), 'from' => $items_text ?: _('All'), 'to' => ''),
        2 => array('text' => _('Category'), 'from' => $category ?: _('All'), 'to' => '')
    );

    $rep = new FrontReport(_('Product Specification Sheets'), "ProductSpecs", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);

    $res = get_product_specs($items_text, $category, $include_pricing);
    $current_item = '';

    while ($row = db_fetch($res))
    {
        if ($current_item != $row['stock_id'])
        {
            if ($current_item != '')
            {
                $rep->Line($rep->row - 4);
                $rep->NewLine(2);
            }

            $rep->NewPage();
            $current_item = $row['stock_id'];

            $rep->TextCol(0, 4, $row['stock_id'] . ' - ' . $row['description']);
            $rep->NewLine();
            $rep->TextCol(0, 4, _('Category: ') . $row['category_description']);
            $rep->NewLine();
            if ($include_pricing) {
                $rep->TextCol(0, 4, _('Sales Price: ') . $row['sales_price'] . ' | ' .
                    _('Cost: ') . $row['cost']);
                $rep->NewLine();
            }
            $rep->Line($rep->row - 4);
            $rep->NewLine();
            $rep->TextCol(0, 4, _('Specification'));
            $rep->NewLine();
            $rep->Line($rep->row - 4);
            $rep->NewLine();
        }

        $rep->TextCol(0, 1, $row['type_name'] ?: _('General'));
        $rep->TextCol(1, 2, $row['attribute_value'] ?: $row['long_description'] ?: '-');
        $rep->NewLine();
    }

    $rep->Line($rep->row - 4);
    $rep->End();
}

print_product_specs();
