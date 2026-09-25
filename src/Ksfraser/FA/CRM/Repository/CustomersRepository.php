<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * CustomersRepository — read-only access to native FA customers (debtors_master).
 *
 * Customers are owned by native FA (sales/manage/customers.php). The CRM does
 * not duplicate or edit them; it lists them and deep-links into the native
 * editor. This repository therefore exposes only SELECT paths, over native
 * db_* calls (no PDO, no raw mysqli).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 *
 * @BABOK Related: FR-CRM-001 (issue #17/#18 native customers view)
 */
class CustomersRepository
{
    use FaRepositoryTrait;

    /**
     * One page of native customers.
     *
     * @param int $page     1-based page
     * @param int $perPage  rows per page
     * @param string $search optional case-insensitive name/no filter
     * @return array<int, array<string, string>>
     */
    public function listPage(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = '';
        if ($search !== '') {
            $esc = $this->escape($search);
            $where = " WHERE debtor_no LIKE '%$esc%' OR debtor_name LIKE '%$esc%'";
        }

        $sql = "SELECT debtor_no, debtor_name, curr_code"
            . " FROM " . TB_PREF . "debtors_master"
            . $where
            . " ORDER BY debtor_name"
            . " LIMIT $perPage OFFSET $offset";

        return $this->dbFetchAll($this->dbQuery($sql));
    }

    /**
     * Total native customers (matching the optional filter).
     *
     * @param string $search
     * @return int
     */
    public function countAll(string $search = ''): int
    {
        $where = '';
        if ($search !== '') {
            $esc = $this->escape($search);
            $where = " WHERE debtor_no LIKE '%$esc%' OR debtor_name LIKE '%$esc%'";
        }
        $sql = "SELECT COUNT(*) AS c FROM " . TB_PREF . "debtors_master" . $where;
        $row = $this->dbFetchAssoc($this->dbQuery($sql));
        return isset($row['c']) ? (int) $row['c'] : 0;
    }
}
