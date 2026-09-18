<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM communications (0_fa_crm_communications).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class CommunicationsRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_communications';

    public function findAll(): array
    {
        $sql = "SELECT c.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " c LEFT JOIN " . TB_PREF . "debtors_master d ON c.debtor_no = d.debtor_no"
            . " ORDER BY c.created_at DESC, c.id DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT c.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " c LEFT JOIN " . TB_PREF . "debtors_master d ON c.debtor_no = d.debtor_no"
            . " WHERE c.id = " . $this->intVal($id);
        return $this->dbFetchAssoc($this->dbQuery($sql));
    }

    public function save(array $data): int
    {
        $createdBy = isset($_SESSION['wa_current_user']->user)
            ? (string) $_SESSION['wa_current_user']->user
            : '';
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                debtor_no, communication_type, direction, subject, message,
                status, priority, assigned_to, follow_up_required, follow_up_date,
                notes, created_by)
            VALUES ("
            . $this->escape($data['debtor_no'] ?? '') . ", "
            . $this->escape($data['communication_type'] ?? '') . ", "
            . $this->escape($data['direction'] ?? 'in') . ", "
            . $this->escape($data['subject']) . ", "
            . $this->escape($data['message'] ?? '') . ", "
            . $this->escape($data['status'] ?? 'new') . ", "
            . $this->escape($data['priority'] ?? 'normal') . ", "
            . $this->escape($data['assigned_to'] ?? '') . ", "
            . (isset($data['follow_up_required']) ? (int)$data['follow_up_required'] : 0) . ", "
            . ($data['follow_up_date'] !== '' ? $this->escape($data['follow_up_date']) : 'NULL') . ", "
            . $this->escape($data['notes'] ?? '') . ", "
            . $this->escape($createdBy) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['debtor_no', 'communication_type', 'direction', 'subject',
                  'message', 'status', 'priority', 'assigned_to', 'notes'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        if (array_key_exists('follow_up_required', $data)) {
            $sets[] = "follow_up_required = " . (int)$data['follow_up_required'];
        }
        if (array_key_exists('follow_up_date', $data)) {
            $sets[] = "follow_up_date = " . ($data['follow_up_date'] !== ''
                ? $this->escape($data['follow_up_date'])
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
}