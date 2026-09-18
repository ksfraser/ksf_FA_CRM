<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\CommunicationsRepository;

/**
 * CommunicationsService — DAO-backed service for the CRM Communications tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class CommunicationsService
{
    private CommunicationsRepository $repo;

    public function __construct(?CommunicationsRepository $repo = null)
    {
        $this->repo = $repo ?? new CommunicationsRepository();
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
     * Field metadata for the Communications tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'communication',
            'table'        => '0_fa_crm_communications',
            'label'        => 'Communication',
            'labelPlural'  => 'Communications',
            'hookPrefix'   => 'Communication',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'customer_name' => [
                    'label' => 'Customer', 'type' => 'text',
                    'showInTable' => true, 'showInForm' => false,
                ],
                'debtor_no' => [
                    'label' => 'Customer', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'communication_type' => [
                    'label' => 'Type', 'type' => 'select', 'default' => 'email',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'direction' => [
                    'label' => 'Direction', 'type' => 'select', 'default' => 'in',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'subject' => [
                    'label' => 'Subject', 'type' => 'text', 'required' => true, 'max' => 255,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'message' => [
                    'label' => 'Message', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'new',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'priority' => [
                    'label' => 'Priority', 'type' => 'select', 'default' => 'normal',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'assigned_to' => [
                    'label' => 'Assigned To', 'type' => 'text', 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'follow_up_required' => [
                    'label' => 'Follow-up', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'follow_up_date' => [
                    'label' => 'Follow-up Date', 'type' => 'date',
                    'showInTable' => false, 'showInForm' => true,
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

    /** @return array<string,string> communication type options */
    public static function typeOptions(): array
    {
        return [
            'email'   => 'Email',
            'phone'   => 'Phone',
            'meeting' => 'Meeting',
            'note'    => 'Note',
            'sms'     => 'SMS',
        ];
    }

    /** @return array<string,string> direction options */
    public static function directionOptions(): array
    {
        return ['in' => 'Inbound', 'out' => 'Outbound'];
    }

    /** @return array<string,string> status options */
    public static function statusOptions(): array
    {
        return [
            'new'       => 'New',
            'planned'   => 'Planned',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string,string> priority options */
    public static function priorityOptions(): array
    {
        return [
            'low'    => 'Low',
            'normal' => 'Normal',
            'high'   => 'High',
            'urgent' => 'Urgent',
        ];
    }
}