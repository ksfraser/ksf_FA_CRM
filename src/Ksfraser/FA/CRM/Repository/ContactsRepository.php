<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM contacts (0_fa_crm_contacts).
 *
 * Contact persons attached to a debtor (FA customer). Rows are associative
 * arrays (transactional records, no entity layer).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class ContactsRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_contacts';

    public function findAll(?string $debtorNo = null): array
    {
        $where = $debtorNo !== null && $debtorNo !== ''
            ? " WHERE c.debtor_no = " . $this->escape($debtorNo)
            : '';
        $sql = "SELECT c.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " c LEFT JOIN " . TB_PREF . "debtors_master d ON c.debtor_no = d.debtor_no"
            . $where
            . " ORDER BY c.last_name ASC, c.first_name ASC, c.id DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function countAll(?string $debtorNo = null): int
    {
        $where = $debtorNo !== null && $debtorNo !== ''
            ? " WHERE debtor_no = " . $this->escape($debtorNo)
            : '';
        $sql = "SELECT COUNT(*) AS cnt FROM " . TB_PREF . $this->table . $where;
        $row = $this->dbFetchAssoc($this->dbQuery($sql));
        return (int) ($row['cnt'] ?? 0);
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
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                debtor_no, first_name, last_name, title, department, phone,
                mobile, email, address, notes, is_primary, inactive)
            VALUES ("
            . $this->escape($data['debtor_no']) . ", "
            . $this->escape($data['first_name']) . ", "
            . $this->escape($data['last_name'] ?? '') . ", "
            . $this->escape($data['title'] ?? '') . ", "
            . $this->escape($data['department'] ?? '') . ", "
            . $this->escape($data['phone'] ?? '') . ", "
            . $this->escape($data['mobile'] ?? '') . ", "
            . $this->escape($data['email'] ?? '') . ", "
            . $this->escape($data['address'] ?? '') . ", "
            . $this->escape($data['notes'] ?? '') . ", "
            . (isset($data['is_primary']) ? (int)$data['is_primary'] : 0) . ", "
            . (isset($data['inactive']) ? (int)$data['inactive'] : 0) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['debtor_no', 'first_name', 'last_name', 'title', 'department',
                  'phone', 'mobile', 'email', 'address', 'notes'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        foreach (['is_primary', 'inactive'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . (int)$data[$col];
            }
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