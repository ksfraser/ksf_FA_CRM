<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Service\OpportunitiesService;

/**
 * OpportunitiesTabController — controller SRP for the CRM Opportunities tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class OpportunitiesTabController extends AbstractTabController
{
    /** @var OpportunitiesService */
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
        $this->service = new OpportunitiesService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return OpportunitiesService::getFieldMetadata();
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
            'debtor_no'        => $this->service->customerOptions(),
            'status'           => OpportunitiesService::statusOptions(),
            'sales_person'     => $this->service->salesmanOptions(),
            'assigned_to'      => $this->service->userOptions(),
            'source'           => $this->service->optionListOptions('opportunity_source'),
            'opportunity_type' => $this->service->optionListOptions('opportunity_type'),
            'realm'            => $this->service->optionListOptions('opportunity_realm'),
            'stage'            => $this->service->optionListOptions('opportunity_stage'),
        ];
    }
}