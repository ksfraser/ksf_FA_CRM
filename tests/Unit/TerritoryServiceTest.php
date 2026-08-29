<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\CRM\Service\TerritoryService;
use Ksfraser\FA\CRM\Repository\TerritoryRepository;
use Ksfraser\HTML\Elements\HtmlOption;

/**
 * Unit tests for TerritoryService.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-006-001
 * @BABOK Related: FR-006-005
 */
class TerritoryServiceTest extends TestCase
{
    private TerritoryService $service;

    protected function setUp(): void
    {
        TerritoryService::invalidateCache();

        $GLOBALS['__fa_select_queue'] = [
            [
                [
                    'id' => 1,
                    'name' => 'North',
                    'description' => 'Northern region',
                    'region' => 'Region A',
                    'inactive' => 0,
                    'sort_order' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'South',
                    'description' => 'Southern region',
                    'region' => 'Region B',
                    'inactive' => 0,
                    'sort_order' => 2,
                ],
            ],
        ];

        $this->service = new TerritoryService();
    }

    protected function tearDown(): void
    {
        TerritoryService::invalidateCache();
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
        $this->assertSame($this->service->getEntities(), $this->service->getEntities());
    }

    public function testGetEntityFieldsIncludeRegion(): void
    {
        $entities = $this->service->getEntities();
        $this->assertSame('Region A', $entities[0]->getRegion());
        $this->assertSame('Region B', $entities[1]->getRegion());
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

    public function testGetHtmlOptionsClonesWhenSelected(): void
    {
        $options2 = $this->service->getHtmlOptions(true, '', '{name}', 1);
        $selected = array_filter($options2, fn($o) => $o->isSelected());
        $this->assertCount(1, $selected);
        $this->assertSame('1', reset($selected)->getValue());
    }

    public function testGetHtmlOptionsFormatStringIncludesRegion(): void
    {
        $options = $this->service->getHtmlOptions(true, '', '{name} ({region})');
        $this->assertSame('North (Region A)', $options[0]->getLabel());
        $this->assertSame('South (Region B)', $options[1]->getLabel());
    }

    // ─── Pre-rendered HTML DDL ──────────────────────────────────────

    public function testGetDdlReturnsStrings(): void
    {
        $rendered = $this->service->getDdl();
        $this->assertCount(2, $rendered);
        foreach ($rendered as $html) {
            $this->assertStringContainsString('<option', $html);
        }
    }

    public function testGetDdlWithBlankLabel(): void
    {
        $rendered = $this->service->getDdl(true, '-- Choose --');
        $this->assertCount(3, $rendered);
        $this->assertStringContainsString('value=""', $rendered[0]);
    }

    public function testGetDdlWithSelectedId(): void
    {
        $rendered = $this->service->getDdl(true, '', '{name}', 2);
        $this->assertStringContainsString('selected', $rendered[1]);
        $this->assertStringNotContainsString('selected', $rendered[0]);
    }

    public function testGetDdlCacheDifferentiatesBySelectedId(): void
    {
        $r1 = $this->service->getDdl(true, '', '{name}', 0);
        $r2 = $this->service->getDdl(true, '', '{name}', 1);
        $this->assertNotSame($r1, $r2);
        $this->assertSame($r2, $this->service->getDdl(true, '', '{name}', 1));
    }

    // ─── Serialized Cache ───────────────────────────────────────────

    public function testGetSerializedCacheReturnsString(): void
    {
        $this->service->getHtmlOptions();
        $this->assertIsArray(unserialize($this->service->getSerializedCache()));
    }

    public function testRenderFromSerializedCacheReturnsStrings(): void
    {
        $this->service->getHtmlOptions();
        $rendered = $this->service->renderFromSerializedCache($this->service->getSerializedCache());
        $this->assertCount(2, $rendered);
    }

    public function testRenderFromSerializedCacheWithSelectedId(): void
    {
        $this->service->getHtmlOptions();
        $rendered = $this->service->renderFromSerializedCache($this->service->getSerializedCache(), 1);
        $this->assertStringContainsString('selected', $rendered[0]);
    }

    public function testRenderFromSerializedCacheInvalidStringReturnsEmpty(): void
    {
        $this->assertSame([], $this->service->renderFromSerializedCache('garbage'));
    }

    // ─── Cache Invalidation ─────────────────────────────────────────

    public function testCreateInvalidatesCache(): void
    {
        $this->service->getEntities();
        $this->service->getHtmlOptions();
        $GLOBALS['__fa_next_id'] = 10;
        $this->service->create(['name' => 'East', 'region' => 'Region C']);
        $this->assertNull(TerritoryService::getOptionCacheState());
    }

    public function testUpdateInvalidatesCache(): void
    {
        $this->service->getHtmlOptions();
        $this->service->update(1, ['name' => 'North Updated']);
        $this->assertNull(TerritoryService::getOptionCacheState());
    }

    public function testDeleteInvalidatesCache(): void
    {
        $this->service->getHtmlOptions();
        $this->service->delete(1);
        $this->assertNull(TerritoryService::getOptionCacheState());
    }

    // ─── Hook Response Methods ──────────────────────────────────────

    public function testHookGetTerritoriesReturnsArrays(): void
    {
        $data = ['active_only' => true];
        $result = $this->service->hookGetTerritories($data);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('region', $result[0]);
    }

    public function testHookGetTerritoryDdlReturnsStrings(): void
    {
        $data = ['blank_label' => '-- Pick --'];
        $result = $this->service->hookGetTerritoryDDL($data);
        $this->assertCount(3, $result);
        $this->assertStringContainsString('-- Pick --', $result[0]);
    }

    public function testHookGetTerritoryHtmlOptionsReturnsOptionObjects(): void
    {
        $data = ['active_only' => true];
        $result = $this->service->hookGetTerritoryHtmlOptions($data);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(HtmlOption::class, $result[0]);
    }

    // ─── Blank Option & Mandatory Validation (FR-006-006) ──────────

    public function testBlankOptionHasEmptyValue(): void
    {
        $rendered = $this->service->getDdl(true, '-- Select Territory --');
        $this->assertStringContainsString('value=""', $rendered[0]);
    }

    public function testBlankOptionNotIncludedWhenNoBlankLabel(): void
    {
        $rendered = $this->service->getDdl(true, '');
        $this->assertStringNotContainsString('value=""', $rendered[0]);
    }

    // ─── Field Metadata (FR-006-007) ─────────────────────────────

    public function testGetFieldMetadataReturnsEntityIdentity(): void
    {
        $md = TerritoryService::getFieldMetadata();
        $this->assertSame('territory', $md['entity']);
        $this->assertSame('0_fa_crm_territories', $md['table']);
        $this->assertSame('Territories', $md['labelPlural']);
        $this->assertSame('id', $md['pk']);
    }

    public function testGetFieldMetadataIncludesRegionField(): void
    {
        $md = TerritoryService::getFieldMetadata();
        $this->assertArrayHasKey('region', $md['fields']);
        $this->assertTrue($md['fields']['region']['showInTable']);
    }

    public function testGetFieldMetadataExposesDdlHooks(): void
    {
        $md = TerritoryService::getFieldMetadata();
        $this->assertSame('hookGetTerritoryDDL', $md['ddlHooks']['getTerritoryDDL']);
    }
}
