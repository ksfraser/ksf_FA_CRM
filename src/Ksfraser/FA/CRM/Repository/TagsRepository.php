<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM tags backed by the FA core 0_tags table.
 *
 * Tags reuse FA's core tagging facility (admin/db/tags_db.inc); CRM type
 * constants occupy the type smallint space above FA's own types.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class TagsRepository
{
    use FaRepositoryTrait;

    private string $table = 'tags';

    public function findAll(): array
    {
        $sql = "SELECT t.*, "
            . "(SELECT COUNT(*) FROM " . TB_PREF . "tag_associations a WHERE a.tag_id = t.id) AS assigned_count"
            . " FROM " . TB_PREF . $this->table . " t ORDER BY t.type, t.name";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT t.*, "
            . "(SELECT COUNT(*) FROM " . TB_PREF . "tag_associations a WHERE a.tag_id = t.id) AS assigned_count"
            . " FROM " . TB_PREF . $this->table . " t WHERE t.id = " . $this->intVal($id);
        return $this->dbFetchAssoc($this->dbQuery($sql));
    }

    public function assignedCount(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM " . TB_PREF . "tag_associations"
            . " WHERE tag_id = " . $this->intVal($id);
        $row = $this->dbFetchAssoc($this->dbQuery($sql));
        return $row ? (int) array_values($row)[0] : 0;
    }

    public function save(array $data): int
    {
        $type = (int) ($data['type'] ?? 0);
        $sql = "INSERT INTO " . TB_PREF . $this->table
            . " (type, name, description) VALUES ("
            . $this->intVal($type) . ", "
            . $this->escape($data['name']) . ", "
            . $this->escape($data['description'] ?? '') . ")";
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
        if (isset($data['type'])) {
            $sets[] = "type = " . $this->intVal($data['type']);
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
        if ($this->assignedCount($id) > 0) {
            return;
        }
        $sql = "DELETE FROM " . TB_PREF . $this->table
            . " WHERE id = " . $this->intVal($id);
        $this->dbQuery($sql);
    }
}