<?php
/**
 * DB functions for fa_pay_elements table
 * Salary components (earnings/deductions)
 */

function write_pay_element($element_id, $data) {
    if ($element_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_pay_elements` SET ";
        $sets = array();
        foreach (array('element_name', 'element_code', 'is_deduction', 'is_taxable', 'affects_gross', 'account_code', 'element_category', 'display_order', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `element_id` = " . db_escape($element_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_pay_elements` SET ";
        $sets = array();
        foreach (array('element_name', 'element_code', 'is_deduction', 'is_taxable', 'affects_gross', 'account_code', 'element_category', 'display_order', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write pay element");
    return $element_id ? $element_id : db_insert_id();
}

function get_pay_element($element_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_pay_elements` WHERE `element_id` = " . db_escape($element_id);
    $result = db_query($sql, "Failed to get pay element");
    return db_fetch($result);
}

function get_all_pay_elements($active_only = true) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_pay_elements`";
    if ($active_only) {
        $sql .= " WHERE `is_active` = 1";
    }
    $sql .= " ORDER BY `display_order`, `element_name`";
    return db_query($sql, "Failed to get pay elements");
}

function get_pay_elements_by_type($is_deduction = 0) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_pay_elements` 
        WHERE `is_deduction` = " . db_escape($is_deduction) . "
        AND `is_active` = 1
        ORDER BY `display_order`";
    return db_query($sql, "Failed to get pay elements by type");
}

function delete_pay_element($element_id) {
    $sql = "UPDATE `" . TB_PREF . "fa_pay_elements` SET `is_active` = 0 WHERE `element_id` = " . db_escape($element_id);
    db_query($sql, "Failed to delete pay element");
}
?>
