<?php
declare(strict_types=1);

namespace Ksfraser\FA\CRM\Entity;

/**
 * CustomerDTO — creation request payload for the CREATE_CUSTOMER responder.
 *
 * Deliberately classic property style: the cross-module compatibility floor is
 * PHP 7.3 and the FA container runs 7.4, so constructor property promotion
 * (PHP 8.0) and `readonly` (PHP 8.1) would be parse errors here.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 *
 * @BABOK Related: FR-CRM-008 (CREATE_CUSTOMER responder)
 */
class CustomerDTO
{
    /** @var string */
    private $name;
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var string */
    private $email;
    /** @var string */
    private $phone;
    /** @var string */
    private $address;
    /** @var string */
    private $taxId;

    /**
     * @param string $name
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $phone
     * @param string $address
     * @param string $taxId
     */
    public function __construct(
        $name = '',
        $firstName = '',
        $lastName = '',
        $email = '',
        $phone = '',
        $address = '',
        $taxId = ''
    ) {
        $this->name = (string)$name;
        $this->firstName = (string)$firstName;
        $this->lastName = (string)$lastName;
        $this->email = (string)$email;
        $this->phone = (string)$phone;
        $this->address = (string)$address;
        $this->taxId = (string)$taxId;
    }

    /**
     * Build from the loosely-keyed array callers send over the hook boundary.
     *
     * Callers legitimately spell these keys differently (ISU sends first_name /
     * last_name / address; a future migration module may send firstName), so
     * both spellings are accepted rather than silently dropping the name.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $name = isset($data['name']) ? (string)$data['name'] : '';
        $first = isset($data['first_name']) ? (string)$data['first_name']
            : (isset($data['firstName']) ? (string)$data['firstName'] : '');
        $last = isset($data['last_name']) ? (string)$data['last_name']
            : (isset($data['lastName']) ? (string)$data['lastName'] : '');

        return new self(
            $name,
            $first,
            $last,
            isset($data['email']) ? (string)$data['email'] : '',
            isset($data['phone']) ? (string)$data['phone'] : '',
            isset($data['address']) ? (string)$data['address'] : '',
            isset($data['tax_id']) ? (string)$data['tax_id'] : ''
        );
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return string */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /** @return string */
    public function getLastName()
    {
        return $this->lastName;
    }

    /** @return string */
    public function getEmail()
    {
        return $this->email;
    }

    /** @return string */
    public function getPhone()
    {
        return $this->phone;
    }

    /** @return string */
    public function getAddress()
    {
        return $this->address;
    }

    /** @return string */
    public function getTaxId()
    {
        return $this->taxId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_id' => $this->taxId,
        ];
    }
}