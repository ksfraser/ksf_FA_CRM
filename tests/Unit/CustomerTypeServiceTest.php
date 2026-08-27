<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\CRM\Service\CustomerTypeService;
use Ksfraser\FA\CRM\Repository\CustomerTypeRepository;
use Ksfraser\HTML\Elements\HtmlOption;

/**
 * Unit tests for CustomerTypeService.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-006-001
 * @BABOK Related: FR-006-005
 */
class CustomerTypeServiceTest extends TestCase
{
    private CustomerTypeService $service;

    protected function setUp(): void
    {
        CustomerTypeService::invalidateCache();

        $GLOBALS['__fa_select_queue'] = [
            [
                [
                    'id' => 1,
                    'name' => 'Retail',
                    'description' => 'Retail customers',
                    'inactive' => 0,
                    'sort_order' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'Wholesale',
                    'description' => 'Wholesale customers',
                    'inactive' => 0,
                    'sort_order' => 2,
                ],
            ],
        ];

        $this->service = new CustomerTypeService();
    }

    protected function tearDown(): void
    {
        CustomerTypeService::invalidateCache();
        unset($GLOBALS['__fa_select_queue']);
        unset($GLOBALS['__fa_current_result']);
    }

    // ─── Entity Access ──────────────────────────────────────────────

    public function testGetEntitiesReturnsActiveOnlyByDefault(): void
    {
        $entities = $this->service->getEntities();
        $this->assertCount(2, $entities);
        foreach ($entities as $e) {
            $this->assertTrue($e->isActive());
        }
    }

    public function testGetEntitiesCachesResult(): void
    {
        $first = $this->service->getEntities();
        $second = $this->service->getEntities();
        $this->assertSame($first, $second);
    }

    // ─── Option Cache ───────────────────────────────────────────────

    public function testGetHtmlOptionsReturnsHtmlOptionArray(): void
    {
        $options = $this->service->getHtmlOptions();
        $this->assertCount(2, $options);
        foreach ($options as $opt) {
            $this->assertInstanceOf(HtmlOption::class, $opt);
        }
    }

    public function testGetHtmlOptionsIncludesBlankLabel(): void
    {
        $options = $this->service->getHtmlOptions(true, '-- Select --');
        $this->assertCount(3, $options);
        $this->assertSame('', $options[0]->getValue());
        $this->assertSame('-- Select --', $options[0]->getLabel());
    }

    public function testGetHtmlOptionsCacheExcludesSelectedId(): void
    {
        $this->service->getHtmlOptions(true, '', '{name}', 0);
        $this->service->getHtmlOptions(true, '', '{name}', 1);

        $cacheState = CustomerTypeService::getOptionCacheState();
        $this->assertCount(1, $cacheState);
    }

    public function testGetHtmlOptionsClonesWhenSelected(): void
    {
        $options1 = $this->service->getHtmlOptions(true, '', '{name}', 0);
        $options2 = $this->service->getHtmlOptions(true, '', '{name}', 1);

        foreach ($options1 as $i => $opt) {
            $this->assertNotSame($opt, $options2[$i]);
        }

        $selected = array_filter($options2, fn($o) => $o->isSelected());
        $this->assertCount(1, $selected);
        $this->assertSame('1', reset($selected)->getValue());
    }

    public function testGetHtmlOptionsFormatStringCustom(): void
    {
        $options = $this->service->getHtmlOptions(true, '', '{id} - {name}');
        $this->assertSame('1 - Retail', $options[0]->getLabel());
        $this->assertSame('2 - Wholesale', $options[1]->getLabel());
    }

    // ─── Pre-rendered HTML DDL ──────────────────────────────────────

    public function testGetDdlReturnsStrings(): void
    {
        $rendered = $this->service->getDdl();
        $this->assertCount(2, $rendered);
        foreach ($rendered as $html) {
            $this->assertIsString($html);
            $this->assertStringContainsString('<option', $html);
            $this->assertStringContainsString('</option>', $html);
        }
    }

    public function testGetDdlWithBlankLabel(): void
    {
        $rendered = $this->service->getDdl(true, '-- Choose --');
        $this->assertCount(3, $rendered);
        $this->assertStringContainsString('-- Choose --', $rendered[0]);
        $this->assertStringContainsString('value=""', $rendered[0]);
    }

    public function testGetDdlWithSelectedId(): void
    {
        $rendered = $this->service->getDdl(true, '', '{name}', 1);
        $this->assertStringContainsString('selected', $rendered[0]);
        $this->assertStringNotContainsString('selected', $rendered[1]);
    }

    public function testGetDdlCacheDifferentiatesBySelectedId(): void
    {
        $r1 = $this->service->getDdl(true, '', '{name}', 0);
        $r2 = $this->service->getDdl(true, '', '{name}', 1);
        $r3 = $this->service->getDdl(true, '', '{name}', 2);

        $this->assertNotSame($r1, $r2);
        $this->assertNotSame($r2, $r3);
        $this->assertSame($r2, $this->service->getDdl(true, '', '{name}', 1));
    }

    // ─── Serialized Cache ───────────────────────────────────────────

    public function testGetSerializedCacheReturnsString(): void
    {
        $this->service->getHtmlOptions();
        $serialized = $this->service->getSerializedCache();
        $this->assertIsString($serialized);
        $this->assertIsArray(unserialize($serialized));
    }

    public function testRenderFromSerializedCacheReturnsStrings(): void
    {
        $this->service->getHtmlOptions();
        $serialized = $this->service->getSerializedCache();
        $rendered = $this->service->renderFromSerializedCache($serialized);
        $this->assertCount(2, $rendered);
        $this->assertStringContainsString('<option', $rendered[0]);
    }

    public function testRenderFromSerializedCacheWithSelectedId(): void
    {
        $this->service->getHtmlOptions();
        $serialized = $this->service->getSerializedCache();
        $rendered = $this->service->renderFromSerializedCache($serialized, 2);
        $this->assertStringContainsString('selected', $rendered[1]);
        $this->assertStringNotContainsString('selected', $rendered[0]);
    }

    public function testRenderFromSerializedCacheInvalidStringReturnsEmpty(): void
    {
        $this->assertSame([], $this->service->renderFromSerializedCache('garbage'));
    }

    // ─── Cache Invalidation ─────────────────────────────────────────

    public function testInvalidateCacheClearsAllLayers(): void
    {
        $this->service->getEntities();
        $this->service->getHtmlOptions();
        CustomerTypeService::invalidateCache();
        $this->assertNull(CustomerTypeService::getOptionCacheState());
    }

    public function testCreateInvalidatesCache(): void
    {
        $this->service->getEntities();
        $this->service->getHtmlOptions();
        $GLOBALS['__fa_next_id'] = 10;
        $this->service->create(['name' => 'Enterprise']);
        $this->assertNull(CustomerTypeService::getOptionCacheState());
    }

    public function testUpdateInvalidatesCache(): void
    {
        $this->service->getEntities();
        $this->service->getHtmlOptions();
        $this->service->update(1, ['name' => 'Retail Updated']);
        $this->assertNull(CustomerTypeService::getOptionCacheState());
    }

    public function testDeleteInvalidatesCache(): void
    {
        $this->service->getEntities();
        $this->service->getHtmlOptions();
        $this->service->delete(1);
        $this->assertNull(CustomerTypeService::getOptionCacheState());
    }

    // ─── Hook Response Methods ──────────────────────────────────────

    public function testHookGetCustomerTypesReturnsArrays(): void
    {
        $data = ['active_only' => true];
        $result = $this->service->hookGetCustomerTypes($data);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
    }

    public function testHookGetCustomerTypeDdlReturnsStrings(): void
    {
        $data = ['active_only' => true, 'blank_label' => '-- Pick --'];
        $result = $this->service->hookGetCustomerTypeDDL($data);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertStringContainsString('-- Pick --', $result[0]);
    }

    public function testHookGetCustomerTypeDdlWithSelectedId(): void
    {
        $data = ['selected_id' => 2];
        $result = $this->service->hookGetCustomerTypeDDL($data);
        $this->assertStringContainsString('selected', $result[1]);
    }

    public function testHookGetCustomerTypeHtmlOptionsReturnsOptionObjects(): void
    {
        $data = ['active_only' => true];
        $result = $this->service->hookGetCustomerTypeHtmlOptions($data);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(HtmlOption::class, $result[0]);
    }

    // ─── Blank Option & Mandatory Validation (FR-006-006) ──────────

    public function testBlankOptionHasEmptyValue(): void
    {
        $rendered = $this->service->getDdl(true, '-- Select Type --');
        $this->assertStringContainsString('value=""', $rendered[0]);
        $this->assertStringContainsString('-- Select Type --', $rendered[0]);
    }

    public function testBlankOptionNotIncludedWhenNoBlankLabel(): void
    {
        $rendered = $this->service->getDdl(true, '');
        $this->assertStringNotContainsString('value=""', $rendered[0]);
    }
}
