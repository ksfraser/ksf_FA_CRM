<?php
/**
 * DB functions for fa_contacts_banking table
 * Banking details for contacts (ACH/PAD/refunds)
 */

function write_contacts_banking($banking_id, $data) {
    if ($banking_id) {
        $sql = "UPDATE `" . TB_PREF . "fa_contacts_banking` SET ";
        $sets = array();
        foreach (array('person_id', 'bank_name', 'bank_branch', 'account_number', 'routing_number', 'account_type', 'is_primary') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets) . " WHERE `banking_id` = " . db_escape($banking_id);
    } else {
        $sql = "INSERT INTO `" . TB_PREF . "fa_contacts_banking` SET ";
        $sets = array();
        foreach (array('person_id', 'bank_name', 'bank_branch', 'account_number', 'routing_number', 'account_type', 'is_primary') as $field) {
            if (isset($data[$field])) {
                $sets[] = "`$field` = " . db_escape($data[$field]);
            }
        }
        $sql .= implode(', ', $sets);
    }
    
    db_query($sql, "Failed to write contacts banking");
    return $banking_id ? $banking_id : db_insert_id();
}

function get_contacts_banking($banking_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_contacts_banking` WHERE `banking_id` = " . db_escape($banking_id);
    $result = db_query($sql, "Failed to get contacts banking");
    return db_fetch($result);
}

function get_contacts_banking_by_person($person_id, $primary_only = false) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_contacts_banking` WHERE `person_id` = " . db_escape($person_id);
    if ($primary_only) {
        $sql .= " AND `is_primary` = 1";
    }
    $sql .= " ORDER BY `is_primary` DESC";
    return db_query($sql, "Failed to get contacts banking by person");
}

function delete_contacts_banking($banking_id) {
    $sql = "DELETE FROM `" . TB_PREF . "fa_contacts_banking` WHERE `banking_id` = " . db_escape($banking_id);
    db_query($sql, "Failed to delete contacts banking");
}
?>
