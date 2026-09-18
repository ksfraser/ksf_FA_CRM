<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Service\CommunicationsService;

/**
 * CommunicationsTabController — controller SRP for the CRM Communications tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class CommunicationsTabController extends AbstractTabController
{
    /** @var CommunicationsService */
    private $service;

    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new CommunicationsService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return CommunicationsService::getFieldMetadata();
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
        $fields = $this->getFieldMetadata();
        $options = [
            'debtor_no' => $this->service->customerOptions(),
        ];
        foreach ($fields['fields'] as $field => $meta) {
            if (isset($meta['type']) && $meta['type'] === 'select') {
                $map = [
                    'communication_type' => [$this->service, 'typeOptions'],
                    'direction'          => [$this->service, 'directionOptions'],
                    'status'             => [$this->service, 'statusOptions'],
                    'priority'           => [$this->service, 'priorityOptions'],
                ];
                if (isset($map[$field])) {
                    $options[$field] = call_user_func($map[$field]);
                }
            }
        }
        return $options;
    }
}