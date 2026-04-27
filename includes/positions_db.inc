<?php
/**
 * DB functions for fa_positions table
 * Shared by HRM + Recruitment
 */

function write_position($position_id, $data) {
    if ($position_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_positions` SET ";
        $sets = array();
        foreach (array('position_name', 'position_code', 'job_class_id', 'basic_amount', 'description', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `position_id` = " . db_escape($position_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_positions` SET ";
        $sets = array();
        foreach (array('position_name', 'position_code', 'job_class_id', 'basic_amount', 'description', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write position");
    return $position_id ? $position_id : db_insert_id();
}

function get_position($position_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_positions` WHERE `position_id` = " . db_escape($position_id);
    $result = db_query($sql, "Failed to get position");
    return db_fetch($result);
}

function get_all_positions($active_only = true) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_positions`";
    if ($active_only) {
        $sql .= " WHERE `is_active` = 1";
    }
    $sql .= " ORDER BY `position_name`";
    return db_query($sql, "Failed to get positions");
}

function get_positions_by_job_class($job_class_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_positions` 
        WHERE `job_class_id` = " . db_escape($job_class_id) . "
        AND `is_active` = 1
        ORDER BY `position_name`";
    return db_query($sql, "Failed to get positions by job class");
}

function delete_position($position_id) {
    $sql = "UPDATE `" . TB_PREF . "fa_positions` SET `is_active` = 0 WHERE `position_id` = " . db_escape($position_id);
    db_query($sql, "Failed to delete position");
}
?>
