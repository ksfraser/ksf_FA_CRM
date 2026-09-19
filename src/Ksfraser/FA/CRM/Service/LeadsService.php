<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\LeadsRepository;

/**
 * LeadsService — DAO-backed service for the CRM Leads tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class LeadsService
{
    private LeadsRepository $repo;

    public function __construct(?LeadsRepository $repo = null)
    {
        $this->repo = $repo ?? new LeadsRepository();
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

    public function customerOptions(): array
    {
        return $this->repo->customerOptions();
    }

    /**
     * Field metadata for the Leads tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'lead',
            'table'        => '0_fa_crm_leads',
            'label'        => 'Lead',
            'labelPlural'  => 'Leads',
            'hookPrefix'   => 'Lead',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'lead_source' => [
                    'label' => 'Source', 'type' => 'text', 'max' => 50,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'rating' => [
                    'label' => 'Rating', 'type' => 'select', 'default' => '',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'lead_status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'new',
                    'showInTable' => true, 'showInForm' => false,
                ],
                'annual_revenue' => [
                    'label' => 'Annual Revenue', 'type' => 'number',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'employee_count' => [
                    'label' => 'Employees', 'type' => 'number',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'industry' => [
                    'label' => 'Industry', 'type' => 'text', 'max' => 50,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'website' => [
                    'label' => 'Website', 'type' => 'text', 'max' => 255,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'phone' => [
                    'label' => 'Phone', 'type' => 'text', 'max' => 20,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'email' => [
                    'label' => 'Email', 'type' => 'text', 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'address' => [
                    'label' => 'Address', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'assigned_to' => [
                    'label' => 'Assigned To', 'type' => 'text', 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'notes' => [
                    'label' => 'Notes', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'created_at DESC, id DESC'],
        ];
    }

    /** @return array<string,string> rating options value => label */
    public static function ratingOptions(): array
    {
        return [
            'hot'  => 'Hot',
            'warm' => 'Warm',
            'cold' => 'Cold',
        ];
    }

    /** @return array<string,string> lead status options value => label */
    public static function statusOptions(): array
    {
        return [
            'new'         => 'New',
            'assigned'    => 'Assigned',
            'in_progress' => 'In Progress',
            'dead'        => 'Dead',
        ];
    }
}