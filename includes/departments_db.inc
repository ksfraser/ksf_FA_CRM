<?php
/**
 * DB functions for fa_departments table
 */

function write_department($department_id, $data) {
    if ($department_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_departments` SET ";
        $sets = array();
        foreach (array('department_code', 'department_name', 'manager_person_id', 'parent_department_id', 'cost_center_id', 'description', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `department_id` = " . db_escape($department_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_departments` SET ";
        $sets = array();
        foreach (array('department_code', 'department_name', 'manager_person_id', 'parent_department_id', 'cost_center_id', 'description', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write department");
    return $department_id ? $department_id : db_insert_id();
}

function get_department($department_id) {
    $sql = "SELECT d.*, p.name as manager_name 
        FROM `" . TB_PREF . "fa_departments` d
        LEFT JOIN `" . TB_PREF . "crm_persons` p ON d.manager_person_id = p.id
        WHERE d.`department_id` = " . db_escape($department_id);
    $result = db_query($sql, "Failed to get department");
    return db_fetch($result);
}

function get_all_departments($active_only = true) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_departments`";
    if ($active_only) {
        $sql .= " WHERE `is_active` = 1";
    }
    $sql .= " ORDER BY `department_name`";
    return db_query($sql, "Failed to get departments");
}

function get_department_employees($department_id) {
    $sql = "SELECT ce.*, p.name, p.email 
        FROM `" . TB_PREF . "fa_contacts_employment` ce
        LEFT JOIN `" . TB_PREF . "crm_persons` p ON ce.person_id = p.id
        WHERE ce.`department_id` = " . db_escape($department_id) . "
        AND ce.`is_active` = 1";
    return db_query($sql, "Failed to get department employees");
}

function delete_department($department_id) {
    $sql = "UPDATE `" . TB_PREF . "fa_departments` SET `is_active` = 0 WHERE `department_id` = " . db_escape($department_id);
    db_query($sql, "Failed to delete department");
}
?>
