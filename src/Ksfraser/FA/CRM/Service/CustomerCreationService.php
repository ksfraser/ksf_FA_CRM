<?php
declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Entity\CustomerDTO;

/**
 * CustomerCreationService — creates a native FA customer (debtor) with its
 * default branch and default contact person.
 *
 * Delegates to FA's own core routines, mirroring the reference sequence in
 * `sales/manage/customers.php` (the add_customer block). Note two core quirks
 * that are easy to get wrong:
 *
 *  - `add_customer()` and `add_branch()` RETURN NOTHING. The new id is read
 *    with `db_insert_id()` immediately afterwards.
 *  - `add_crm_contact()` takes FOUR arguments: ($type, $action, $entity_id,
 *    $person_id) — 'customer' is the $type and there is no $type/'action' swap.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 *
 * @BABOK Related: FR-CRM-008 (CREATE_CUSTOMER responder)
 */
class CustomerCreationService
{
    /**
     * @param CustomerDTO $dto
     * @return array Response payload
     * @throws \RuntimeException If the core routines are unavailable
     */
    public function createCustomer(CustomerDTO $dto)
    {
        if (!function_exists('add_customer')) {
            throw new \RuntimeException(
                'FA core sales routines unavailable: add_customer() not loaded '
                . '(sales/includes/db/customers_db.inc not included)'
            );
        }

        $name = trim($dto->getName());
        if ($name === '') {
            throw new \RuntimeException('Customer name is required');
        }

        $currCode = $this->companyPref('curr_code', 'CAD');
        $debtorsAct = $this->companyPref('debtors_act', '');
        $discountAct = $this->companyPref('default_sales_discount_act', '');
        $promptAct = $this->companyPref('default_prompt_payment_act', '');

        begin_transaction();

        // debtors_master. Core reads the new id from db_insert_id().
        add_customer(
            $name,
            $name,
            $dto->getAddress(),
            $dto->getTaxId(),
            $currCode,
            0,          // dimension_id
            0,          // dimension2_id
            1,          // credit_status  (1 = Good)
            1,          // payment_terms
            0,          // discount      (fraction)
            0,          // pymt_discount (fraction)
            0,          // credit_limit
            1,          // sales_type
            ''          // notes
        );
        $debtorNo = (int)db_insert_id();

        if ($debtorNo <= 0) {
            commit_transaction();
            throw new \RuntimeException('add_customer() did not yield a debtor_no');
        }

        // cust_branch — one default branch so the debtor is usable in sales.
        add_branch(
            $debtorNo,
            $name,
            $name,
            $dto->getAddress(),
            0,          // salesman
            0,          // area
            0,          // tax_group_id
            $discountAct,
            $discountAct,
            $debtorsAct,
            $promptAct,
            0,          // default_location
            $dto->getAddress(),   // br_post_address
            0,          // group_no
            0,          // default_ship_via
            '',         // notes
            0           // bank_account
        );
        $branchCode = (int)db_insert_id();

        // crm_persons + crm_contacts — the default contact for the customer.
        $personId = 0;
        if (function_exists('add_crm_person')) {
            add_crm_person(
                '',
                $dto->getFirstName() !== '' ? $dto->getFirstName() : $name,
                $dto->getLastName(),
                $dto->getAddress(),
                $dto->getPhone(),
                '',
                '',
                $dto->getEmail(),
                '',
                ''
            );
            $personId = (int)db_insert_id();
        }

        if ($personId > 0 && function_exists('add_crm_contact')) {
            if ($branchCode > 0) {
                add_crm_contact('cust_branch', 'general', $branchCode, $personId);
            }
            add_crm_contact('customer', 'general', $debtorNo, $personId);
        }

        commit_transaction();

        return [
            'success' => true,
            'fa_debtor_no' => $debtorNo,
            'branch_code' => $branchCode,
            'contact_id' => $personId,
            'name' => $name,
        ];
    }

    /**
     * Read a company preference, tolerating an un-bootstrapped FA.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    private function companyPref($key, $default = '')
    {
        if (function_exists('get_company_pref')) {
            $value = get_company_pref($key);
            if ($value !== null && $value !== '' && $value !== 0) {
                return $value;
            }
        }
        return $default;
    }
}