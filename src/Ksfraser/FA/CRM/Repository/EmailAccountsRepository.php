<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM email accounts (0_fa_crm_email_accounts).
 *
 * Mailbox definitions used by the CRM importer/sync layer.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class EmailAccountsRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_email_accounts';

    public function findAll(): array
    {
        $sql = "SELECT * FROM " . TB_PREF . $this->table
            . " ORDER BY account_name ASC, id DESC";
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
        $serverPort = (string) ($data['server_port'] ?? '993');
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                account_name, email_address, server_host, server_port,
                encryption, username, password, auto_import, import_frequency,
                last_import, inactive)
            VALUES ("
            . $this->escape($data['account_name']) . ", "
            . $this->escape($data['email_address'] ?? '') . ", "
            . $this->escape($data['server_host']) . ", "
            . ($serverPort !== '' ? $this->intVal((int) $serverPort) : 'NULL') . ", "
            . $this->escape($data['encryption'] ?? '') . ", "
            . $this->escape($data['username'] ?? '') . ", "
            . $this->escape($data['password'] ?? '') . ", "
            . (isset($data['auto_import']) ? (int) $data['auto_import'] : 0) . ", "
            . $this->intVal((int) ($data['import_frequency'] ?? 60)) . ", "
            . ($data['last_import'] !== '' ? $this->escape($data['last_import']) : 'NULL') . ", "
            . (isset($data['inactive']) ? (int) $data['inactive'] : 0) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['account_name', 'email_address', 'server_host', 'encryption',
                  'username', 'password'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        if (array_key_exists('server_port', $data)) {
            $sets[] = "server_port = " . ($data['server_port'] !== ''
                ? $this->intVal((int) $data['server_port'])
                : 'NULL');
        }
        if (array_key_exists('import_frequency', $data)) {
            $sets[] = "import_frequency = " . $this->intVal((int) $data['import_frequency']);
        }
        foreach (['auto_import', 'inactive'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . (int) $data[$col];
            }
        }
        if (array_key_exists('last_import', $data)) {
            $sets[] = "last_import = " . ($data['last_import'] !== ''
                ? $this->escape($data['last_import'])
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
}