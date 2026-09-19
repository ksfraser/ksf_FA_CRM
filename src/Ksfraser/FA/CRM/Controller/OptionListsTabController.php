<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\Service\OptionListsService;

/**
 * OptionListsTabController — controller SRP for the CRM opportunity DDL admin
 * views (Sources / Types / Realms / Stages).
 *
 * One controller serves all four views; the active option list key is derived
 * from the current view key, so the same page flow (summary + entry form)
 * manages whichever list is selected. Stage rows carry a probability % that is
 * used to derive an opportunity's probability from its stage.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §2/§10/§11 (controller SRP + UI SRPs)
 * @BABOK Related: FR-CRM-001
 */
class OptionListsTabController extends AbstractTabController
{
    /** @var OptionListsService */
    private $service;

    /** @var string */
    private $listKey;

    /**
     * @param \Ksfraser\Frontaccounting\HTML\TabContext|null $context DI request state
     * @param array<string, mixed>                            $options
     *
     * @since 1.0.0
     */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $view = $_GET['view'] ?? '';
        $this->listKey = OptionListsService::listKeyForView((string) $view);
        $this->service = new OptionListsService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return OptionListsService::getFieldMetadata($this->listKey);
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        return array_slice($this->service->listAll($this->listKey), $offset, $perPage);
    }

    /** {@inheritDoc} */
    protected function countRows(): int
    {
        return count($this->service->listAll($this->listKey));
    }

    /** {@inheritDoc} */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById((int) $pk);
    }

    /** {@inheritDoc} */
    protected function createRecord(array $data)
    {
        return $this->service->create($this->listKey, $data);
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