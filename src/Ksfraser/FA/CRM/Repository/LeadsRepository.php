<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM leads (0_fa_crm_leads).
 *
 * Rows are returned as associative arrays (no entity layer — leads are
 * transactional records, not DDL-cached reference data).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class LeadsRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_leads';

    public function findAll(): array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " ORDER BY created_at DESC, id DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " WHERE id = " . $this->intVal($id);
        return $this->dbFetchAssoc($this->dbQuery($sql));
    }

    public function save(array $data): int
    {
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                debtor_no, lead_source, lead_status, rating, annual_revenue,
                employee_count, industry, website, phone, email, address,
                assigned_to, campaign_id, notes)
            VALUES ("
            . $this->escape($data['debtor_no']) . ", "
            . $this->escape($data['lead_source'] ?? '') . ", "
            . $this->escape($data['lead_status'] ?? 'new') . ", "
            . $this->escape($data['rating'] ?? '') . ", "
            . $this->escape($data['annual_revenue'] ?? 0) . ", "
            . $this->intVal($data['employee_count'] ?? 0) . ", "
            . $this->escape($data['industry'] ?? '') . ", "
            . $this->escape($data['website'] ?? '') . ", "
            . $this->escape($data['phone'] ?? '') . ", "
            . $this->escape($data['email'] ?? '') . ", "
            . $this->escape($data['address'] ?? '') . ", "
            . $this->escape($data['assigned_to'] ?? '') . ", "
            . $this->intVal($data['campaign_id'] ?? 0) . ", "
            . $this->escape($data['notes'] ?? '') . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['debtor_no', 'lead_source', 'lead_status', 'rating', 'annual_revenue',
                  'employee_count', 'industry', 'website', 'phone', 'email', 'address',
                  'assigned_to', 'notes'] as $col) {
            if (array_key_exists($col, $data)) {
                $val = ($col === 'employee_count') ? $this->intVal($data[$col])
                    : $this->escape($data[$col]);
                $sets[] = "$col = $val";
            }
        }
        if (array_key_exists('campaign_id', $data)) {
            $sets[] = "campaign_id = " . $this->intVal($data['campaign_id']);
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
}