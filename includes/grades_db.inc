<?php
/**
 * DB functions for fa_grades table
 * Salary grades with hierarchy
 */

function write_grade($grade_id, $data) {
    if ($grade_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_grades` SET ";
        $sets = array();
        foreach (array('grade_name', 'position_id', 'grade_level', 'min_salary', 'mid_salary', 'max_salary', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `grade_id` = " . db_escape($grade_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_grades` SET ";
        $sets = array();
        foreach (array('grade_name', 'position_id', 'grade_level', 'min_salary', 'mid_salary', 'max_salary', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write grade");
    return $grade_id ? $grade_id : db_insert_id();
}

function get_grade($grade_id) {
    $sql = "SELECT g.*, p.position_name 
        FROM `" . TB_PREF . "fa_grades` g
        LEFT JOIN `" . TB_PREF . "fa_positions` p ON g.position_id = p.position_id
        WHERE g.`grade_id` = " . db_escape($grade_id);
    $result = db_query($sql, "Failed to get grade");
    return db_fetch($result);
}

function get_all_grades($active_only = true) {
    $sql = "SELECT g.*, p.position_name 
        FROM `" . TB_PREF . "fa_grades` g
        LEFT JOIN `" . TB_PREF . "fa_positions` p ON g.position_id = p.position_id";
    if ($active_only) {
        $sql .= " WHERE g.`is_active` = 1";
    }
    $sql .= " ORDER BY g.`position_id`, g.`grade_level`";
    return db_query($sql, "Failed to get grades");
}

function get_grades_by_position($position_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_grades` 
        WHERE `position_id` = " . db_escape($position_id) . "
        AND `is_active` = 1
        ORDER BY `grade_level`";
    return db_query($sql, "Failed to get grades by position");
}

function delete_grade($grade_id) {
    $sql = "UPDATE `" . TB_PREF . "fa_grades` SET `is_active` = 0 WHERE `grade_id` = " . db_escape($grade_id);
    db_query($sql, "Failed to delete grade");
}
?>
