<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM opportunities (0_fa_crm_opportunities).
 *
 * Rows are returned as associative arrays (transactional records, no entity
 * layer). Columns follow the install.sql schema.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class OpportunitiesRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_opportunities';

    public function findAll(): array
    {
        $sql = "SELECT o.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " o LEFT JOIN " . TB_PREF . "debtors_master d ON o.debtor_no = d.debtor_no"
            . " ORDER BY o.created_at DESC, o.id DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT o.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " o LEFT JOIN " . TB_PREF . "debtors_master d ON o.debtor_no = d.debtor_no"
            . " WHERE o.id = " . $this->intVal($id);
        return $this->dbFetchAssoc($this->dbQuery($sql));
    }

    public function save(array $data): int
    {
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                opportunity_name, debtor_no, sales_person, opportunity_type,
                realm, status, stage, source, estimated_value, probability,
                expected_close_date, notes, assigned_to, inactive)
            VALUES ("
            . $this->escape($data['opportunity_name']) . ", "
            . $this->escape($data['debtor_no'] ?? '') . ", "
            . $this->escape($data['sales_person'] ?? '') . ", "
            . $this->escape($data['opportunity_type'] ?? '') . ", "
            . $this->escape($data['realm'] ?? '') . ", "
            . $this->escape($data['status'] ?? 'open') . ", "
            . $this->escape($data['stage'] ?? '') . ", "
            . $this->escape($data['source'] ?? '') . ", "
            . $this->escape($data['estimated_value'] ?? 0) . ", "
            . $this->escape($data['probability'] ?? 0) . ", "
            . ($data['expected_close_date'] !== ''
                ? $this->escape($data['expected_close_date'])
                : 'NULL') . ", "
            . $this->escape($data['notes'] ?? '') . ", "
            . $this->escape($data['assigned_to'] ?? '') . ", "
            . (isset($data['inactive']) ? (int)$data['inactive'] : 0) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        $plain = ['opportunity_name', 'debtor_no', 'sales_person', 'opportunity_type',
                  'realm', 'status', 'stage', 'source', 'notes', 'assigned_to'];
        foreach ($plain as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        foreach (['estimated_value', 'probability'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        if (array_key_exists('inactive', $data)) {
            $sets[] = "inactive = " . (int)$data['inactive'];
        }
        if (array_key_exists('expected_close_date', $data)) {
            $sets[] = "expected_close_date = " . ($data['expected_close_date'] !== ''
                ? $this->escape($data['expected_close_date'])
                : 'NULL');
        }
        if (empty($sets)) {
            return;
        }
        $sql = "UPDATE " . TB_PREF . $this->table
            . " SET " . implode(', ', $sets)
            . " WHERE id = " . $this->intVal($id);
        $this->dbQuery($sql);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM " . TB_PREF . $this->table
            . " WHERE id = " . $this->intVal($id);
        $this->dbQuery($sql);
    }

    public function customerOptions(): array
    {
        $sql = "SELECT debtor_no, name FROM " . TB_PREF . "debtors_master"
            . " WHERE !inactive ORDER BY name";
        $rows = $this->dbFetchAll($this->dbQuery($sql));
        $out = [];
        foreach ($rows as $row) {
            $out[$row['debtor_no']] = $row['name'];
        }
        return $out;
    }

    /** @return array<string,string> salesman_code => salesman_name (FA sales people). */
    public function salesmanOptions(): array
    {
        $sql = "SELECT salesman_code, salesman_name FROM " . TB_PREF . "salesman"
            . " WHERE !inactive ORDER BY salesman_name";
        $rows = $this->dbFetchAll($this->dbQuery($sql));
        $out = [];
        foreach ($rows as $row) {
            $out[$row['salesman_code']] = $row['salesman_name'];
        }
        return $out;
    }

    /** @return array<string,string> user_id => real_name (FA users as assignees). */
    public function userOptions(): array
    {
        $sql = "SELECT user_id, real_name FROM " . TB_PREF . "users"
            . " WHERE !inactive ORDER BY real_name";
        $rows = $this->dbFetchAll($this->dbQuery($sql));
        $out = [];
        foreach ($rows as $row) {
            $out[$row['user_id']] = $row['real_name'] !== '' ? $row['real_name'] : $row['user_id'];
        }
        return $out;
    }

    /** @return array<string,string> option_value => option_label for a CRM option list. */
    public function optionListOptions(string $listKey): array
    {
        $sql = "SELECT option_value, option_label FROM " . TB_PREF . "fa_crm_option_lists"
            . " WHERE list_key = " . $this->escape($listKey)
            . " AND inactive = 0 ORDER BY sort_order ASC, option_label ASC";
        $rows = $this->dbFetchAll($this->dbQuery($sql));
        $out = [];
        foreach ($rows as $row) {
            $out[$row['option_value']] = $row['option_label'];
        }
        return $out;
    }

    /** @return float|null Probability configured for a stage value ('' if none). */
    public function stageProbability(string $value): ?float
    {
        if ($value === '') {
            return null;
        }
        $sql = "SELECT probability FROM " . TB_PREF . "fa_crm_option_lists"
            . " WHERE list_key = 'opportunity_stage'"
            . " AND option_value = " . $this->escape($value) . " LIMIT 1";
        $row = $this->dbFetchAssoc($this->dbQuery($sql));
        if ($row === null || $row['probability'] === '' || $row['probability'] === null) {
            return null;
        }
        return (float) $row['probability'];
    }
}