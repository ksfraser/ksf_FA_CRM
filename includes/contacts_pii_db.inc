<?php
/**
 * DB functions for fa_contacts_pii table
 * PII separated for GDPR compliance
 */

function write_contacts_pii($person_id, $data) {
    $sql = "REPLACE INTO `" . TB_PREF . "fa_contacts_pii` SET 
        `person_id` = " . db_escape($person_id);
    
    foreach (array('gender', 'birth_date', 'nationality', 'national_id', 'passport', 'passport_expiry', 'marital_status', 'dependents_no') as $field) {
        if (isset($data[$field])) {
            $sql .= ", `$field` = " . db_escape($data[$field]);
        }
    }
    
    db_query($sql, "Failed to write contacts PII");
}

function get_contacts_pii($person_id) {
    $sql = "SELECT * FROM `" . TB_PREF . "fa_contacts_pii` WHERE `person_id` = " . db_escape($person_id);
    $result = db_query($sql, "Failed to get contacts PII");
    return db_fetch($result);
}

function delete_contacts_pii($person_id) {
    $sql = "DELETE FROM `" . TB_PREF . "fa_contacts_pii` WHERE `person_id` = " . db_escape($person_id);
    db_query($sql, "Failed to delete contacts PII");
}
?>
