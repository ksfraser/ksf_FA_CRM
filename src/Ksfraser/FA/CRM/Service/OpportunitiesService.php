<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\OpportunitiesRepository;

/**
 * OpportunitiesService — DAO-backed service for the CRM Opportunities tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class OpportunitiesService
{
    private OpportunitiesRepository $repo;

    public function __construct(?OpportunitiesRepository $repo = null)
    {
        $this->repo = $repo ?? new OpportunitiesRepository();
    }

    public function listAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function customerOptions(): array
    {
        return $this->repo->customerOptions();
    }

    public function salesmanOptions(): array
    {
        return $this->repo->salesmanOptions();
    }

    public function userOptions(): array
    {
        return $this->repo->userOptions();
    }

    public function optionListOptions(string $listKey): array
    {
        return $this->repo->optionListOptions($listKey);
    }

    /**
     * Probability configured for a stage value (null when the stage has none).
     *
     * @param string $value Stage value
     * @return float|null
     */
    public function stageProbability(string $value): ?float
    {
        return $this->repo->stageProbability($value);
    }

    /**
     * Create an opportunity, defaulting probability from the sales stage when
     * the caller did not supply one (SuiteCRM-style stage-to-probability link).
     *
     * @param array<string, mixed> $data
     * @return int
     */
    public function create(array $data): int
    {
        return $this->repo->save($this->resolveProbability($data));
    }

    /**
     * Update an opportunity, deriving probability from the stage when the
     * caller did not supply one explicitly.
     *
     * @param int                  $id
     * @param array<string, mixed> $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $this->resolveProbability($data));
    }

    /**
     * Fill probability from the chosen stage when absent/blank.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function resolveProbability(array $data): array
    {
        $provided = $data['probability'] ?? null;
        $missing  = $provided === null || $provided === '';
        if ($missing) {
            $stageProbability = $this->stageProbability((string) ($data['stage'] ?? ''));
            if ($stageProbability !== null) {
                $data['probability'] = $stageProbability;
            }
        }
        return $data;
    }

    /**
     * Field metadata for the Opportunities tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'opportunity',
            'table'        => '0_fa_crm_opportunities',
            'label'        => 'Opportunity',
            'labelPlural'  => 'Opportunities',
            'hookPrefix'   => 'Opportunity',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'opportunity_name' => [
                    'label' => 'Opportunity Name', 'type' => 'text',
                    'required' => true, 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'debtor_no' => [
                    'label' => 'Customer', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'customer_name' => [
                    'label' => 'Customer', 'type' => 'text',
                    'showInTable' => true, 'showInForm' => false,
                ],
                'sales_person' => [
                    'label' => 'Sales Person', 'type' => 'select',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'open',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'stage' => [
                    'label' => 'Stage', 'type' => 'select', 'default' => '',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'estimated_value' => [
                    'label' => 'Est. Value', 'type' => 'number',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'probability' => [
                    'label' => 'Probability %', 'type' => 'number',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'expected_close_date' => [
                    'label' => 'Expected Close', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'source' => [
                    'label' => 'Source', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'opportunity_type' => [
                    'label' => 'Type', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'realm' => [
                    'label' => 'Realm', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'assigned_to' => [
                    'label' => 'Assigned To', 'type' => 'select',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'notes' => [
                    'label' => 'Notes', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'inactive' => [
                    'label' => 'Inactive', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'created_at DESC, id DESC'],
        ];
    }

    /** @return array<string,string> opportunity status options */
    public static function statusOptions(): array
    {
        return [
            'open'        => 'Open',
            'won'         => 'Won',
            'lost'        => 'Lost',
            'cancelled'   => 'Cancelled',
            'on_hold'     => 'On Hold',
        ];
    }

    /** @return array<string,string> sales-pipeline stage options */
    public static function stageOptions(): array
    {
        return [
            'prospecting'   => 'Prospecting',
            'qualification' => 'Qualification',
            'proposal'      => 'Proposal',
            'negotiation'   => 'Negotiation',
            'closed'        => 'Closed',
        ];
    }
}