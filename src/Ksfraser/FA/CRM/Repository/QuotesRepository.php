<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM quotes (0_fa_crm_quotes) header rows.
 *
 * Note: quote line items are not managed by the tab controller; this repo
 * covers the single-row quote header only (see APP_TAB_ARCHITECTURE.md §11
 * legacy pageFile constraint).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class QuotesRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_quotes';

    public function findAll(): array
    {
        $sql = "SELECT q.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " q LEFT JOIN " . TB_PREF . "debtors_master d ON q.debtor_no = d.debtor_no"
            . " ORDER BY q.quote_date DESC, q.id DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT q.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " q LEFT JOIN " . TB_PREF . "debtors_master d ON q.debtor_no = d.debtor_no"
            . " WHERE q.id = " . $this->intVal($id);
        return $this->dbFetchAssoc($this->dbQuery($sql));
    }

    public function nextQuoteNo(): string
    {
        $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(quote_no, '-', -1) AS UNSIGNED)), 0) + 1"
            . " FROM " . TB_PREF . $this->table;
        $next = $this->dbFetchRow($this->dbQuery($sql));
        $prefix = 'QT';
        return $prefix . '-' . str_pad((string) (int) ($next[0] ?? 1), 6, '0', STR_PAD_LEFT);
    }

    public function save(array $data): int
    {
        $createdBy = isset($_SESSION['wa_current_user']->user)
            ? (string) $_SESSION['wa_current_user']->user
            : '';
        $quoteNo = $data['quote_no'] !== '' ? $data['quote_no'] : $this->nextQuoteNo();
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $taxRate  = (float) ($data['tax_rate'] ?? 0);
        $taxAmount = (float) ($data['tax_amount'] ?? 0);
        $total = (float) ($data['total'] ?? 0);
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                quote_no, opportunity_id, debtor_no, contact_id, quote_date,
                valid_until, status, subtotal, tax_rate, tax_amount, total,
                notes, terms, created_by)
            VALUES ("
            . $this->escape($quoteNo) . ", "
            . ($data['opportunity_id'] !== '' ? $this->intVal((int) $data['opportunity_id']) : 'NULL') . ", "
            . $this->escape($data['debtor_no'] ?? '') . ", "
            . ($data['contact_id'] !== '' ? $this->intVal((int) $data['contact_id']) : 'NULL') . ", "
            . $this->escape($data['quote_date']) . ", "
            . $this->escape($data['valid_until']) . ", "
            . $this->escape($data['status'] ?? 'draft') . ", "
            . $this->numVal($subtotal) . ", "
            . $this->numVal($taxRate) . ", "
            . $this->numVal($taxAmount) . ", "
            . $this->numVal($total) . ", "
            . $this->escape($data['notes'] ?? '') . ", "
            . $this->escape($data['terms'] ?? '') . ", "
            . $this->escape($createdBy) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['quote_no', 'debtor_no', 'status', 'notes', 'terms'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        foreach (['quote_date', 'valid_until'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        foreach (['opportunity_id', 'contact_id'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . ($data[$col] !== ''
                    ? $this->intVal((int) $data[$col])
                    : 'NULL');
            }
        }
        foreach (['subtotal', 'tax_rate', 'tax_amount', 'total'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->numVal((float) $data[$col]);
            }
        }
        if (array_key_exists('inactive', $data)) {
            $sets[] = "inactive = " . (int) $data['inactive'];
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