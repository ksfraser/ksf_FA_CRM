<?php
/**
 * DB functions for fa_contacts_employment table
 * Employment details for employees
 */

function write_contacts_employment($employment_id, $data) {
    if ($employment_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_contacts_employment` SET ";
        $sets = array();
        foreach (array('person_id', 'employee_code', 'department_id', 'position_id', 'grade_id', 'employment_type', 'hire_date', 'probation_end_date', 'confirmation_date', 'termination_date', 'separation_reason_id', 'salary_amount', 'login_id', 'reports_to_person_id', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `employment_id` = " . db_escape($employment_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_contacts_employment` SET ";
        $sets = array();
        foreach (array('person_id', 'employee_code', 'department_id', 'position_id', 'grade_id', 'employment_type', 'hire_date', 'probation_end_date', 'confirmation_date', 'termination_date', 'separation_reason_id', 'salary_amount', 'login_id', 'reports_to_person_id', 'is_active') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write contacts employment");
    return $employment_id ? $employment_id : db_insert_id();
}

function get_contacts_employment($employment_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_contacts_employment` WHERE `employment_id` = " . db_escape($employment_id);
    $result = db_query($sql, "Failed to get contacts employment");
    return db_fetch($result);
}

function get_employment_by_person($person_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_contacts_employment` WHERE `person_id` = " . db_escape($person_id);
    $result = db_query($sql, "Failed to get employment by person");
    return db_fetch($result);
}

function get_employment_by_code($employee_code) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_contacts_employment` WHERE `employee_code` = " . db_escape($employee_code);
    $result = db_query($sql, "Failed to get employment by code");
    return db_fetch($result);
}

function terminate_employee($person_id, $termination_date, $reason_id) {
    $sql = "UPDATE `" . TB_PREF . "fa_contacts_employment` SET 
        `termination_date` = " . db_escape($termination_date) . ",
        `separation_reason_id` = " . db_escape($reason_id) . ",
        `is_active` = 0
        WHERE `person_id` = " . db_escape($person_id);
    db_query($sql, "Failed to terminate employee");
}

function delete_contacts_employment($employment_id) {
    $sql = "DELETE FROM `" . TB_PREF . "fa_contacts_employment` WHERE `employment_id` = " . db_escape($employment_id);
    db_query($sql, "Failed to delete contacts employment");
}
?>
