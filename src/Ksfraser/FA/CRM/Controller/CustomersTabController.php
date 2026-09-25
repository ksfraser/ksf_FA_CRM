<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Repository\CustomersRepository;

/**
 * CustomersTabController — read-only CRM tab over native FA customers.
 *
 * Customers are owned by native FA (sales/manage/customers.php); the CRM does
 * not duplicate or edit them. This tab lists native debtors (debtors_master)
 * in a paged summary table and deep-links each row's Edit action into the
 * native customer editor (sales/manage/customer_branches.php) in a new window.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001 (issue #17 dashboard Customers tab)
 */
class CustomersTabController extends AbstractTabController
{
    /** @var CustomersRepository */
    private $repo;

    /**
     * @param mixed                            $context
     * @param array<string, mixed>             $options
     *
     * @since 1.0.0
     */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->repo = new CustomersRepository();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'debtor_no';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return [
            'labelPlural' => 'Customers',
        ];
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        return $this->repo->listPage($page, $perPage, $this->searchTerm());
    }

    /** {@inheritDoc} */
    protected function countRows(): int
    {
        return $this->repo->countAll($this->searchTerm());
    }

    /**
     * Native FA owns customer records; there is nothing to find here.
     *
     * {@inheritDoc}
     */
    protected function findRecord(string $pk): ?array
    {
        return null;
    }

    /**
     * Read-only tab — creation is owned by native FA.
     *
     * {@inheritDoc}
     */
    protected function createRecord(array $data)
    {
        throw new \RuntimeException('Customers are managed in native FA.');
    }

    /**
     * Read-only tab — updates are owned by native FA.
     *
     * {@inheritDoc}
     */
    protected function updateRecord(string $pk, array $data): void
    {
        throw new \RuntimeException('Customers are managed in native FA.');
    }

    /**
     * Read-only tab — deletion is owned by native FA.
     *
     * {@inheritDoc}
     */
    protected function deleteRecord(string $pk): void
    {
        throw new \RuntimeException('Customers are managed in native FA.');
    }

    /**
     * No editable entry form: every edit happens in the native FA window.
     *
     * {@inheritDoc}
     */
    protected function renderEntryForm(): string
    {
        return '';
    }

    /**
     * Read-only paged summary of native customers, each row deep-linking into
     * the native editor in a new window.
     *
     * {@inheritDoc}
     */
    protected function renderSummaryTable(): void
    {
        if (!function_exists('start_table')) {
            return;
        }

        global $path_to_root;

        $page = max(1, $this->page());
        $perPage = $this->perPage;
        $rows = $this->listRows($page, $perPage);
        $total = $this->countRows();
        $pageCount = max(1, (int) ceil($total / $perPage));

        start_table(TABLESTYLE2, "width='95%'");
        $th = function (string $label): void {
            echo '<th>' . htmlspecialchars($this->localise($label), ENT_QUOTES) . '</th>';
        };
        echo '<tr>';
        $th('Customer #');
        $th('Name');
        $th('Currency');
        $th('Actions');
        echo '</tr>';

        if (empty($rows)) {
            echo '<tr><td colspan="4"><em>'
                . htmlspecialchars($this->localise('No customers yet.'), ENT_QUOTES)
                . '</em></td></tr>';
        }

        foreach ($rows as $i => $row) {
            $debtorNo = isset($row['debtor_no']) ? (string) $row['debtor_no'] : '';
            $name = isset($row['debtor_name']) ? (string) $row['debtor_name'] : '';
            $curr = isset($row['curr_code']) ? (string) $row['curr_code'] : '';
            $editUrl = $path_to_root . '/sales/manage/customer_branches.php'
                . '?debtor_no=' . rawurlencode($debtorNo);

            echo '<tr class="' . ($i % 2 ? 'evenrow' : 'oddrow') . '">';
            echo '<td>' . htmlspecialchars($debtorNo, ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($name, ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($curr, ENT_QUOTES) . '</td>';
            echo '<td><a href="' . htmlspecialchars($editUrl, ENT_QUOTES) . '" target="_blank">'
                . htmlspecialchars($this->localise('Edit'), ENT_QUOTES) . '</a></td>';
            echo '</tr>';
        }

        // Pager (First / Prev / Next / Last), preserving the active view.
        echo '<tr class="navibar"><td colspan="4"><div style="float:right;">';
        echo $this->pagerLink(1, 'First', $page > 1, $page);
        echo $this->pagerLink($page - 1, 'Prev', $page > 1, $page);
        echo $this->pagerLink($page + 1, 'Next', $page < $pageCount, $page);
        echo $this->pagerLink($pageCount, 'Last', $page < $pageCount, $page);
        echo '</div></td></tr>';

        end_table(0);
    }

    /**
     * Build a pager anchor preserving the current query string.
     *
     * @param int    $target target page
     * @param string $label  link label
     * @param bool   $active whether the link is enabled
     * @param int    $current current page (for the disabled state)
     * @return string
     */
    private function pagerLink(int $target, string $label, bool $active, int $current): string
    {
        $label = htmlspecialchars($this->localise($label), ENT_QUOTES);
        if (!$active) {
            return '<span style="color:#999;">' . $label . '</span> ';
        }
        $base = $this->formAction();
        $sep = (strpos($base, '?') !== false) ? '&' : '?';
        // Replace an existing page= param, otherwise append.
        if (preg_match('/([?&])page=\d+/', $base)) {
            $url = preg_replace('/([?&])page=\d+/', '${1}page=' . (int) $target, $base);
        } else {
            $url = $base . $sep . 'page=' . (int) $target;
        }
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . $label . '</a> ';
    }

    /**
     * Current optional customer search term.
     *
     * @return string
     */
    private function searchTerm(): string
    {
        $search = $_REQUEST['search'] ?? '';
        return is_string($search) ? trim($search) : '';
    }
}
