<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\ContactsRepository;

/**
 * ContactsService — DAO-backed service for the CRM Contacts tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class ContactsService
{
    private ContactsRepository $repo;

    public function __construct(?ContactsRepository $repo = null)
    {
        $this->repo = $repo ?? new ContactsRepository();
    }

    public function listAll(?string $debtorNo = null): array
    {
        return $this->repo->findAll($debtorNo);
    }

    public function countAll(?string $debtorNo = null): int
    {
        return $this->repo->countAll($debtorNo);
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
     * Field metadata for the Contacts tab (FR-006-007 schema).
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'contact',
            'table'        => '0_fa_crm_contacts',
            'label'        => 'Contact',
            'labelPlural'  => 'Contacts',
            'hookPrefix'   => 'Contact',
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
                    'label' => 'Customer', 'type' => 'select', 'required' => true,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'first_name' => [
                    'label' => 'First Name', 'type' => 'text', 'required' => true, 'max' => 50,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'last_name' => [
                    'label' => 'Last Name', 'type' => 'text', 'max' => 50,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'title' => [
                    'label' => 'Title', 'type' => 'text', 'max' => 50,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'department' => [
                    'label' => 'Department', 'type' => 'text', 'max' => 50,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'phone' => [
                    'label' => 'Phone', 'type' => 'text', 'max' => 20,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'mobile' => [
                    'label' => 'Mobile', 'type' => 'text', 'max' => 20,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'email' => [
                    'label' => 'Email', 'type' => 'text', 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'address' => [
                    'label' => 'Address', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'notes' => [
                    'label' => 'Notes', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'is_primary' => [
                    'label' => 'Primary', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'inactive' => [
                    'label' => 'Inactive', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'last_name ASC, first_name ASC, id DESC'],
        ];
    }
}