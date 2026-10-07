<?php
declare(strict_types=1);

namespace Ksfraser\FA\CRM\Tests\Unit;

use Ksfraser\FA\CRM\Entity\CustomerDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomerDTO normalization.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 *
 * @BABOK Related: FR-CRM-008, UT-CRM-008-001
 */
class CustomerDTOTest extends TestCase
{
    /**
     * The ISU payload shape must map cleanly onto the DTO.
     *
     * @test
     */
    public function normalisesIsuPayload(): void
    {
        $dto = CustomerDTO::fromArray([
            'name' => 'Ada Lovelace Inc',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '555-1234',
            'address' => '1 Analytical Way',
            'source_customer_id' => 'cus_123',
        ]);

        $this->assertSame('Ada Lovelace Inc', $dto->getName());
        $this->assertSame('Ada', $dto->getFirstName());
        $this->assertSame('Lovelace', $dto->getLastName());
        $this->assertSame('ada@example.com', $dto->getEmail());
        $this->assertSame('555-1234', $dto->getPhone());
        $this->assertSame('1 Analytical Way', $dto->getAddress());
        $this->assertSame('', $dto->getTaxId());
    }

    /**
     * A future migration module may use camelCase keys; they must not be
     * silently dropped, which would produce a nameless debtor.
     *
     * @test
     */
    public function acceptsCamelCaseKeys(): void
    {
        $dto = CustomerDTO::fromArray([
            'name' => 'Grace Hopper',
            'firstName' => 'Grace',
            'lastName' => 'Hopper',
        ]);

        $this->assertSame('Grace', $dto->getFirstName());
        $this->assertSame('Hopper', $dto->getLastName());
    }

    /**
     * @test
     */
    public function missingKeysBecomeEmptyStrings(): void
    {
        $dto = CustomerDTO::fromArray([]);

        $this->assertSame('', $dto->getName());
        $this->assertSame('', $dto->getEmail());
        $this->assertSame([], array_filter($dto->toArray(), function ($v) {
            return $v !== '';
        }));
    }

    /**
     * @test
     */
    public function nonStringValuesAreCoerced(): void
    {
        $dto = CustomerDTO::fromArray([
            'name' => 12345,
            'email' => null,
            'tax_id' => 999,
        ]);

        $this->assertSame('12345', $dto->getName());
        $this->assertSame('', $dto->getEmail());
        $this->assertSame('999', $dto->getTaxId());
    }
}