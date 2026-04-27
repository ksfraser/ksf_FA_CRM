<?php
/**
 * DB functions for fa_salary_structure table
 * Position × Grade × Element
 */

function write_salary_structure($structure_id, $data) {
    if ($structure_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_salary_structure` SET ";
        $sets = array();
        foreach (array('position_id', 'grade_id', 'element_id', 'pay_amount', 'formula', 'effective_from', 'effective_to', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `structure_id` = " . db_escape($structure_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_salary_structure` SET ";
        $sets = array();
        foreach (array('position_id', 'grade_id', 'element_id', 'pay_amount', 'formula', 'effective_from', 'effective_to', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write salary structure");
    return $structure_id ? $structure_id : db_insert_id();
}

function get_salary_structure($structure_id) {
    $sql = "SELECT ss.*, p.position_name, g.grade_name, e.element_name, e.element_code, e.is_deduction
        FROM `" . TB_PREF . "fa_salary_structure` ss
        LEFT JOIN `" . TB_PREF . "fa_positions` p ON ss.position_id = p.position_id
        LEFT JOIN `" . TB_PREF . "fa_grades` g ON ss.grade_id = g.grade_id
        LEFT JOIN `" . TB_PREF . "fa_pay_elements` e ON ss.element_id = e.element_id
        WHERE ss.`structure_id` = " . db_escape($structure_id);
    $result = db_query($sql, "Failed to get salary structure");
    return db_fetch($result);
}

function get_salary_by_position_grade($position_id, $grade_id, $effective_date = null) {
    $sql = "SELECT ss.*, e.element_name, e.element_code, e.is_deduction, e.is_taxable, e.affects_gross, e.account_code
        FROM `" . TB_PREF . "fa_salary_structure` ss
        LEFT JOIN `" . TB_PREF . "fa_pay_elements` e ON ss.element_id = e.element_id
        WHERE ss.`position_id` = " . db_escape($position_id) . "
        AND ss.`grade_id` = " . db_escape($grade_id) . "
        AND ss.`is_active` = 1";
    
    if ($effective_date) {
        $sql .= " AND (ss.`effective_from` IS NULL OR ss.`effective_from` <= " . db_escape($effective_date) . ")
                  AND (ss.`effective_to` IS NULL OR ss.`effective_to` >= " . db_escape($effective_date) . ")";
    }
    
    $sql .= " ORDER BY e.`display_order`";
    return db_query($sql, "Failed to get salary by position/grade");
}

function delete_salary_structure($structure_id) {
    $sql = "UPDATE `" . TB_PREF . "fa_salary_structure` SET `is_active` = 0 WHERE `structure_id` = " . db_escape($structure_id);
    db_query($sql, "Failed to delete salary structure");
}
?>
