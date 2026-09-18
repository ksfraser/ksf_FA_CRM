<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Service\TerritoryService;

/**
 * TerritoriesTabController — controller SRP for the CRM Territories tab.
 *
 * Mirrors CustomerTypesTabController: summary table + always-visible entry
 * form backed by the DAO TerritoryService (reference data, DDL cached).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class TerritoriesTabController extends AbstractTabController
{
    /** @var TerritoryService */
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
        $this->service = new TerritoryService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return TerritoryService::getFieldMetadata();
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
}