<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

use Ksfraser\FA\CRM\Entity\Realm;

/**
 * Repository for CRM realms (0_fa_crm_realms).
 *
 * @see BR-006 (Cross-Module DDL Caching)
 *
 * @since 1.0.0
 */
class RealmRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_realms';

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

    public function findById(int $id): ?Realm
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " WHERE id = " . $this->intVal($id);
        $row = $this->dbFetchAssoc($this->dbQuery($sql));
        return $row ? new Realm($row) : null;
    }

    public function save(array $data): int
    {
        $sql = "INSERT INTO " . TB_PREF . $this->table
            . " (name, description, requires_quote, requires_project,"
            . " default_stage, stages_json, inactive, sort_order) VALUES ("
            . $this->escape($data['name']) . ", "
            . $this->escape($data['description'] ?? '') . ", "
            . (int)($data['requires_quote'] ?? 0) . ", "
            . (int)($data['requires_project'] ?? 0) . ", "
            . $this->escape($data['default_stage'] ?? 'qualification') . ", "
            . $this->escape($data['stages_json'] ?? '') . ", "
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
        if (isset($data['requires_quote'])) {
            $sets[] = "requires_quote = " . (int)$data['requires_quote'];
        }
        if (isset($data['requires_project'])) {
            $sets[] = "requires_project = " . (int)$data['requires_project'];
        }
        if (array_key_exists('default_stage', $data)) {
            $sets[] = "default_stage = " . $this->escape($data['default_stage']);
        }
        if (array_key_exists('stages_json', $data)) {
            $sets[] = "stages_json = " . $this->escape($data['stages_json']);
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
