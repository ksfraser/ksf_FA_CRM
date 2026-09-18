<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\MeetingsRepository;

/**
 * MeetingsService — DAO-backed service for the CRM Meetings tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class MeetingsService
{
    private MeetingsRepository $repo;

    public function __construct(?MeetingsRepository $repo = null)
    {
        $this->repo = $repo ?? new MeetingsRepository();
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
     * Field metadata for the Meetings tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'meeting',
            'table'        => '0_fa_crm_meetings',
            'label'        => 'Meeting',
            'labelPlural'  => 'Meetings',
            'hookPrefix'   => 'Meeting',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'meeting_name' => [
                    'label' => 'Meeting Name', 'type' => 'text', 'required' => true, 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'meeting_type' => [
                    'label' => 'Type', 'type' => 'select', 'default' => 'meeting',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'customer_name' => [
                    'label' => 'Customer', 'type' => 'text',
                    'showInTable' => true, 'showInForm' => false,
                ],
                'debtor_no' => [
                    'label' => 'Customer', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'opportunity_id' => [
                    'label' => 'Opportunity ID', 'type' => 'number',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'start_date' => [
                    'label' => 'Start Date', 'type' => 'date', 'required' => true,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'end_date' => [
                    'label' => 'End Date', 'type' => 'date',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'duration_minutes' => [
                    'label' => 'Duration (min)', 'type' => 'number', 'default' => 60,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'location_type' => [
                    'label' => 'Location', 'type' => 'select', 'default' => 'physical',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'custom_location' => [
                    'label' => 'Address / Room', 'type' => 'text', 'max' => 200,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'phone_number' => [
                    'label' => 'Phone', 'type' => 'text', 'max' => 20,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'conference_url' => [
                    'label' => 'Conference URL', 'type' => 'text', 'max' => 500,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'planned',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'priority' => [
                    'label' => 'Priority', 'type' => 'select', 'default' => 'normal',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'description' => [
                    'label' => 'Description', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'agenda' => [
                    'label' => 'Agenda', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'notes' => [
                    'label' => 'Notes', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'assigned_to' => [
                    'label' => 'Assigned To', 'type' => 'text', 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'start_date DESC, id DESC'],
        ];
    }

    /** @return array<string,string> meeting type options */
    public static function typeOptions(): array
    {
        return [
            'meeting'  => 'Meeting',
            'call'     => 'Call',
            'video'    => 'Video Call',
            'task'     => 'Task',
            'workshop' => 'Workshop',
        ];
    }

    /** @return array<string,string> meeting status options */
    public static function statusOptions(): array
    {
        return [
            'planned'   => 'Planned',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string,string> meeting priority options */
    public static function priorityOptions(): array
    {
        return [
            'low'    => 'Low',
            'normal' => 'Normal',
            'high'   => 'High',
            'urgent' => 'Urgent',
        ];
    }

    /** @return array<string,string> location type options */
    public static function locationOptions(): array
    {
        return [
            'physical' => 'Physical',
            'phone'    => 'Phone',
            'virtual'  => 'Virtual',
        ];
    }
}