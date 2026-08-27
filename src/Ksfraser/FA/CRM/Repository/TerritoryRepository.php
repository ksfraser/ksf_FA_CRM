<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

use Ksfraser\FA\CRM\Entity\Territory;

/**
 * Repository for CRM territories (0_fa_crm_territories).
 *
 * @see BR-006 (Cross-Module DDL Caching)
 *
 * @since 1.0.0
 */
class TerritoryRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_territories';

    public function findActive(): array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " WHERE inactive = 0 ORDER BY sort_order, name";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " ORDER BY sort_order, name";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?Territory
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " WHERE id = " . $this->intVal($id);
        $row = $this->dbFetchAssoc($this->dbQuery($sql));
        return $row ? new Territory($row) : null;
    }

    public function save(array $data): int
    {
        $sql = "INSERT INTO " . TB_PREF . $this->table
            . " (name, description, region, inactive, sort_order) VALUES ("
            . $this->escape($data['name']) . ", "
            . $this->escape($data['description'] ?? '') . ", "
            . $this->escape($data['region'] ?? '') . ", "
            . (isset($data['inactive']) ? (int)$data['inactive'] : 0) . ", "
            . $this->intVal($data['sort_order'] ?? 0) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        if (isset($data['name'])) {
            $sets[] = "name = " . $this->escape($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $sets[] = "description = " . $this->escape($data['description']);
        }
        if (array_key_exists('region', $data)) {
            $sets[] = "region = " . $this->escape($data['region']);
        }
        if (isset($data['inactive'])) {
            $sets[] = "inactive = " . (int)$data['inactive'];
        }
        if (isset($data['sort_order'])) {
            $sets[] = "sort_order = " . $this->intVal($data['sort_order']);
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
}
