<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\TagsRepository;

/**
 * TagsService — DAO-backed service for the CRM Tags tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class TagsService
{
    private TagsRepository $repo;

    public function __construct(?TagsRepository $repo = null)
    {
        $this->repo = $repo ?? new TagsRepository();
    }

    public function listAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): int
    {
        return $this->repo->save($data);
    }

    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /**
     * Field metadata for the Tags tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'tag',
            'table'        => '0_tags',
            'label'        => 'Tag',
            'labelPlural'  => 'Tags',
            'hookPrefix'   => 'Tag',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'type' => [
                    'label' => 'Type', 'type' => 'select', 'default' => 3,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'name' => [
                    'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 30,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'description' => [
                    'label' => 'Description', 'type' => 'text', 'max' => 60,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'assigned_count' => [
                    'label' => 'Assigned', 'type' => 'text',
                    'showInTable' => true, 'showInForm' => false,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'type, name'],
        ];
    }

    /** @return array<int,string> CRM tag type options value => label */
    public static function typeOptions(): array
    {
        return [
            3 => 'Customer',
            4 => 'Contact',
            5 => 'Opportunity',
            6 => 'Lead',
            7 => 'Communication',
        ];
    }
}