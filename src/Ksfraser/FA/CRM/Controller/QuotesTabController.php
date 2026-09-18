<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Service\QuotesService;

/**
 * QuotesTabController — controller SRP for the CRM Quotes tab.
 *
 * Manages the single-row quote header (0_fa_crm_quotes). No line-item
 * subeditor (see APP_TAB_ARCHITECTURE.md §11).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class QuotesTabController extends AbstractTabController
{
    /** @var QuotesService */
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new QuotesService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return QuotesService::getFieldMetadata();
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        return array_slice($this->service->listAll(), $offset, $perPage);
    }

    /** {@inheritDoc} */
    protected function countRows(): int
    {
        return count($this->service->listAll());
    }

    /** {@inheritDoc} */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById((int) $pk);
    }

    /** {@inheritDoc} */
    protected function createRecord(array $data)
    {
        if (empty($data['quote_date'])) {
            $data['quote_date'] = date('Y-m-d');
        }
        if (empty($data['quote_no'])) {
            $data['quote_no'] = '';
        }
        return $this->service->create($data);
    }

    /** {@inheritDoc} */
    protected function updateRecord(string $pk, array $data): void
    {
        unset($data['quote_no']);
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
            'status'    => QuotesService::statusOptions(),
        ];
    }
}