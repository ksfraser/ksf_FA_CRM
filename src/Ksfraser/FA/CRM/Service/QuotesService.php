<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\QuotesRepository;

/**
 * QuotesService — DAO-backed service for the CRM Quotes (header) tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class QuotesService
{
    private QuotesRepository $repo;

    public function __construct(?QuotesRepository $repo = null)
    {
        $this->repo = $repo ?? new QuotesRepository();
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
     * Field metadata for the Quotes tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'quote',
            'table'        => '0_fa_crm_quotes',
            'label'        => 'Quote',
            'labelPlural'  => 'Quotes',
            'hookPrefix'   => 'Quote',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'quote_no' => [
                    'label' => 'Quote #', 'type' => 'text', 'max' => 30,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'customer_name' => [
                    'label' => 'Customer', 'type' => 'text',
                    'showInTable' => true, 'showInForm' => false,
                ],
                'debtor_no' => [
                    'label' => 'Customer', 'type' => 'select', 'required' => true,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'opportunity_id' => [
                    'label' => 'Opportunity ID', 'type' => 'number',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'contact_id' => [
                    'label' => 'Contact ID', 'type' => 'number',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'quote_date' => [
                    'label' => 'Quote Date', 'type' => 'date', 'required' => true,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'valid_until' => [
                    'label' => 'Valid Until', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'draft',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'subtotal' => [
                    'label' => 'Subtotal', 'type' => 'number', 'step' => '0.01',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'tax_rate' => [
                    'label' => 'Tax Rate %', 'type' => 'number', 'step' => '0.01',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'tax_amount' => [
                    'label' => 'Tax Amount', 'type' => 'number', 'step' => '0.01',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'total' => [
                    'label' => 'Total', 'type' => 'number', 'step' => '0.01',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'notes' => [
                    'label' => 'Notes', 'type' => 'textarea',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'terms' => [
                    'label' => 'Terms', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'inactive' => [
                    'label' => 'Inactive', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'quote_date DESC, id DESC'],
        ];
    }

    /** @return array<string,string> quote status options */
    public static function statusOptions(): array
    {
        return [
            'draft'     => 'Draft',
            'sent'      => 'Sent',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'accepted'  => 'Accepted',
            'expired'   => 'Expired',
        ];
    }
}