<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\CRM\Service\DebtorOptionsService;
use Ksfraser\FA\CRM\Service\ContactOptionsService;

/**
 * Unit tests for the read-only option services that Notes (and any other
 * module) consult instead of reaching into debtors_master / crm_persons.
 *
 * @BABOK Related: FR-NT-001-002
 */
class OptionsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = array();
        $GLOBALS['__fa_current_result'] = array();
    }

    private function seedSelect(array $rows): void
    {
        $GLOBALS['__fa_select_queue'][] = $rows;
    }

    // --- DebtorOptionsService -------------------------------------------

    public function testDebtorMapReturnsValueToLabel(): void
    {
        $this->seedSelect(array(
            array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD'),
            array('debtor_no' => '1002', 'name' => 'Globex', 'curr_code' => 'USD'),
        ));

        $map = (new DebtorOptionsService())->getOptionMap();

        $this->assertSame(array('1001' => 'Acme', '1002' => 'Globex'), $map);
    }

    public function testDebtorMapQueriesTheNativeTableAndFiltersActive(): void
    {
        $this->seedSelect(array(array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD')));

        (new DebtorOptionsService())->getOptionMap(true);

        $this->assertStringContainsString('FROM ' . TB_PREF . 'debtors_master', $GLOBALS['__fa_last_sql']);
        $this->assertStringContainsString('WHERE inactive = 0', $GLOBALS['__fa_last_sql']);
    }

    public function testDebtorOmitsTheActiveFilterWhenAllAreRequested(): void
    {
        $this->seedSelect(array(array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD')));

        (new DebtorOptionsService())->getOptionMap(false);

        $this->assertStringNotContainsString('inactive', $GLOBALS['__fa_last_sql']);
    }

    public function testDebtorCountIsTheNumberOfRowsOffered(): void
    {
        $this->seedSelect(array(
            array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD'),
            array('debtor_no' => '1002', 'name' => 'Globex', 'curr_code' => 'USD'),
        ));

        $this->assertSame(2, (new DebtorOptionsService())->count());
    }

    public function testDebtorHtmlOptionsSupportBlankLabelFormatAndSelection(): void
    {
        $this->seedSelect(array(
            array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD'),
            array('debtor_no' => '1002', 'name' => 'Globex', 'curr_code' => 'USD'),
        ));

        $options = (new DebtorOptionsService())->getHtmlOptions(true, '— pick —', '{id} / {name}', 1002);

        $this->assertSame(array('value' => '', 'label' => '— pick —', 'selected' => false), $options[0]);
        $this->assertSame('1001 / Acme', $options[1]['label']);
        $this->assertFalse($options[1]['selected']);
        $this->assertSame('1002 / Globex', $options[2]['label']);
        $this->assertTrue($options[2]['selected']);
    }

    public function testDebtorRespondersPopulateDataAndReturnTheSameShape(): void
    {
        $this->seedSelect(array(array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD')));
        $service = new DebtorOptionsService();
        $data = array();

        $this->assertSame($service->hookGetDebtorOptions($data), $data['options']);
        $this->assertSame(array('1001' => 'Acme'), $data['options']);

        $this->seedSelect(array(array('debtor_no' => '1001', 'name' => 'Acme', 'curr_code' => 'USD')));
        $html = array();
        $this->assertSame($service->hookGetDebtorOptionsHtmlOptions($html), $html['options']);
        $this->assertSame('Acme', $html['options'][0]['label']);
    }

    public function testDebtorOptionsAreEmptyWhenTheTableIsAbsent(): void
    {
        // No rows seeded; the fake returns an empty result set.
        $this->assertSame(array(), (new DebtorOptionsService())->getOptionMap());
    }

    // --- ContactOptionsService ------------------------------------------

    public function testContactMapReturnsValueToLabel(): void
    {
        $this->seedSelect(array(
            array('id' => 11, 'name' => 'Ada', 'email' => 'ada@example.com'),
            array('id' => 12, 'name' => 'Linus', 'email' => 'linus@example.com'),
        ));

        $map = (new ContactOptionsService())->getOptionMap();

        $this->assertSame(array('11' => 'Ada', '12' => 'Linus'), $map);
    }

    public function testContactMapQueriesTheNativePersonsTable(): void
    {
        $this->seedSelect(array(array('id' => 11, 'name' => 'Ada', 'email' => 'ada@example.com')));

        (new ContactOptionsService())->getOptionMap();

        $this->assertStringContainsString('FROM ' . TB_PREF . 'crm_persons', $GLOBALS['__fa_last_sql']);
    }

    public function testContactHtmlOptionsCanFormatWithEmailAndMarkSelection(): void
    {
        $this->seedSelect(array(
            array('id' => 11, 'name' => 'Ada', 'email' => 'ada@example.com'),
        ));

        $options = (new ContactOptionsService())->getHtmlOptions(true, '', '{name} <{email}>', 11);

        $this->assertSame('Ada <ada@example.com>', $options[0]['label']);
        $this->assertTrue($options[0]['selected']);
    }

    public function testContactRespondersPopulateData(): void
    {
        $this->seedSelect(array(array('id' => 11, 'name' => 'Ada', 'email' => 'ada@example.com')));
        $service = new ContactOptionsService();
        $data = array();

        $this->assertSame($service->hookGetContactOptions($data), $data['options']);
        $this->assertSame(array('11' => 'Ada'), $data['options']);
    }
}