<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Service\ContactsService;

/**
 * ContactsTabController — controller SRP for the CRM Contacts tab.
 *
 * Manages contact persons (0_fa_crm_contacts) attached to customers.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class ContactsTabController extends AbstractTabController
{
    /** @var ContactsService */
    private $service;

    /**
     * @param \Ksfraser\Frontaccounting\HTML\TabContext|null $context DI request state
     * @param array<string, mixed>                            $options
     *
     * @since 1.0.0
     */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new ContactsService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return ContactsService::getFieldMetadata();
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        return array_slice($this->service->listAll($this->filterCustomerId()), $offset, $perPage);
    }

    /** {@inheritDoc} */
    protected function countRows(): int
    {
        return $this->service->countAll($this->filterCustomerId());
    }

    /**
     * Current customer filter value ('' = all customers).
     *
     * @return string
     */
    private function filterCustomerId(): string
    {
        $filter = $_REQUEST['filter_debtor_no'] ?? '';
        return is_string($filter) ? trim($filter) : '';
    }

    /**
     * Emit the FA-native customer filter DDL (submit_on_change => immediate
     * reload of the summary table for the selected customer) above the table.
     *
     * {@inheritDoc}
     */
    protected function renderSummaryTable(): void
    {
        if (function_exists('customer_list_row')) {
            $selected = $this->filterCustomerId();
            start_table(TABLESTYLE2, "width='95%'");
            customer_list_row(_('Customer:'), 'filter_debtor_no',
                $selected !== '' ? $selected : null, true, true);
            end_table(0);
        }
        parent::renderSummaryTable();
    }

    /**
     * Preserve the active customer filter across POST redirects.
     *
     * {@inheritDoc}
     */
    protected function redirectAfterPost(string $pk, string $status): void
    {
        $url = $this->formAction();
        if ($url === '') {
            $url = $this->context->redirectTarget();
        }
        if ($url === '') {
            return;
        }
        $filter = $this->filterCustomerId();
        if ($filter !== '') {
            $sep = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $sep . 'filter_debtor_no=' . rawurlencode($filter);
        }
        header('Location: ' . $url);
        exit;
    }

    /** {@inheritDoc} */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById((int) $pk);
    }

    /** {@inheritDoc} */
    protected function createRecord(array $data)
    {
        return $this->service->create($data);
    }

    /** {@inheritDoc} */
    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->update((int) $pk, $data);
    }

    /** {@inheritDoc} */
    protected function deleteRecord(string $pk): void
    {
        $this->service->delete((int) $pk);
    }

    /** {@inheritDoc} */
    protected function fkOptions(): array
    {
        return [
            'debtor_no' => $this->service->customerOptions(),
        ];
    }
}