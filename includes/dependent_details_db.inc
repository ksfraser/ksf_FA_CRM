<?php
/**
 * DB functions for fa_dependent_details table
 * Additional info for dependents (for benefits)
 */

function write_dependent_details($dependent_id, $data) {
    if ($dependent_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_dependent_details` SET ";
        $sets = array();
        foreach (array('person_id', 'employee_person_id', 'relationship', 'date_of_birth', 'gender', 'eligible_for_benefits', 'benefit_start_date', 'benefit_end_date') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `dependent_id` = " . db_escape($dependent_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_dependent_details` SET ";
        $sets = array();
        foreach (array('person_id', 'employee_person_id', 'relationship', 'date_of_birth', 'gender', 'eligible_for_benefits', 'benefit_start_date', 'benefit_end_date') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write dependent details");
    return $dependent_id ? $dependent_id : db_insert_id();
}

function get_dependent_details($dependent_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_dependent_details` WHERE `dependent_id` = " . db_escape($dependent_id);
    $result = db_query($sql, "Failed to get dependent details");
    return db_fetch($result);
}

function get_dependents_by_employee($employee_person_id) {
    $sql = "SELECT dd.*, p.name, p.email, p.phone 
        FROM `" . TB_PREF . "fa_dependent_details` dd
        LEFT JOIN `" . TB_PREF . "crm_persons` p ON dd.person_id = p.id
        WHERE dd.`employee_person_id` = " . db_escape($employee_person_id) . "
        ORDER BY dd.relationship";
    return db_query($sql, "Failed to get dependents by employee");
}

function delete_dependent_details($dependent_id) {
    $sql = "DELETE FROM `" . TB_PREF . "fa_dependent_details` WHERE `dependent_id` = " . db_escape($dependent_id);
    db_query($sql, "Failed to delete dependent details");
}
?>
