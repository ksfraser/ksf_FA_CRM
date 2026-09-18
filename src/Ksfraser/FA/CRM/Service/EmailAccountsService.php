<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\EmailAccountsRepository;

/**
 * EmailAccountsService — DAO-backed service for the CRM Email Accounts tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001, FR-006-007
 */
class EmailAccountsService
{
    private EmailAccountsRepository $repo;

    public function __construct(?EmailAccountsRepository $repo = null)
    {
        $this->repo = $repo ?? new EmailAccountsRepository();
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
     * Field metadata for the Email Accounts tab.
     *
     * @return array
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'       => 'email_account',
            'table'        => '0_fa_crm_email_accounts',
            'label'        => 'Email Account',
            'labelPlural'  => 'Email Accounts',
            'hookPrefix'   => 'EmailAccount',
            'pk'           => 'id',
            'fields'       => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'account_name' => [
                    'label' => 'Account Name', 'type' => 'text', 'required' => true, 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'email_address' => [
                    'label' => 'Email Address', 'type' => 'text', 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'server_host' => [
                    'label' => 'Server Host', 'type' => 'text', 'required' => true, 'max' => 200,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'server_port' => [
                    'label' => 'Port', 'type' => 'number', 'default' => 993,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'encryption' => [
                    'label' => 'Encryption', 'type' => 'select', 'default' => '',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'username' => [
                    'label' => 'Username', 'type' => 'text', 'max' => 100,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'password' => [
                    'label' => 'Password', 'type' => 'text', 'max' => 255,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'auto_import' => [
                    'label' => 'Auto Import', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'import_frequency' => [
                    'label' => 'Import Frequency (min)', 'type' => 'number', 'default' => 60,
                    'showInTable' => false, 'showInForm' => true,
                ],
                'last_import' => [
                    'label' => 'Last Import', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'inactive' => [
                    'label' => 'Inactive', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'account_name ASC, id DESC'],
        ];
    }

    /** @return array<string,string> encryption options */
    public static function encryptionOptions(): array
    {
        return [
            ''      => 'None',
            'ssl'   => 'SSL',
            'tls'   => 'TLS',
        ];
    }
}