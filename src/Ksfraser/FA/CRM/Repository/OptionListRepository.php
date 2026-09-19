<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM option lists (0_fa_crm_option_lists).
 *
 * Single generic reference table backing the editable opportunity DDL sets
 * (source / type / realm / stage). Stage rows carry a `probability` used to
 * derive an opportunity's probability from its sales stage (SuiteCRM-style).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class OptionListRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_option_lists';

    public function findByList(string $listKey): array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " WHERE list_key = " . $this->escape($listKey)
            . " ORDER BY sort_order ASC, option_label ASC, id ASC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findByListAndValue(string $listKey, string $value): ?array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " WHERE list_key = " . $this->escape($listKey)
            . " AND option_value = " . $this->escape($value)
            . " LIMIT 1";
        return $this->dbFetchAssoc($this->dbQuery($sql));
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
                list_key, option_value, option_label, probability, sort_order, inactive)
            VALUES ("
            . $this->escape($data['list_key']) . ", "
            . $this->escape($data['option_value']) . ", "
            . $this->escape($data['option_label']) . ", "
            . $this->escape(($data['probability'] ?? '') !== '' ? $data['probability'] : 0) . ", "
            . $this->intVal($data['sort_order'] ?? 0) . ", "
            . (isset($data['inactive']) ? (int)$data['inactive'] : 0) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['option_value', 'option_label', 'list_key'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        if (array_key_exists('probability', $data)) {
            $sets[] = "probability = "
                . $this->escape(($data['probability'] ?? '') !== '' ? $data['probability'] : 0);
        }
        if (isset($data['sort_order'])) {
            $sets[] = "sort_order = " . $this->intVal($data['sort_order']);
        }
        if (isset($data['inactive'])) {
            $sets[] = "inactive = " . (int)$data['inactive'];
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