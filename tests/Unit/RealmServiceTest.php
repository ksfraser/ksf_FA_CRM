<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\CRM\Service\RealmService;
use Ksfraser\FA\CRM\Repository\RealmRepository;
use Ksfraser\HTML\Elements\HtmlOption;

/**
 * Unit tests for RealmService.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-006-001
 * @BABOK Related: FR-006-005
 */
class RealmServiceTest extends TestCase
{
    private RealmService $service;

    protected function setUp(): void
    {
        RealmService::invalidateCache();

        $GLOBALS['__fa_select_queue'] = [
            [
                [
                    'id' => 1,
                    'name' => 'Enterprise',
                    'description' => 'Enterprise realm',
                    'requires_quote' => 1,
                    'requires_project' => 0,
                    'default_stage' => 'qualification',
                    'stages_json' => '["qualification","proposal"]',
                    'inactive' => 0,
                    'sort_order' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'SMB',
                    'description' => 'SMB realm',
                    'requires_quote' => 0,
                    'requires_project' => 1,
                    'default_stage' => 'discovery',
                    'stages_json' => '["discovery","negotiation"]',
                    'inactive' => 0,
                    'sort_order' => 2,
                ],
            ],
        ];

        $this->service = new RealmService();
    }

    protected function tearDown(): void
    {
        RealmService::invalidateCache();
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

    public function testGetEntityExposesRealmSpecificFields(): void
    {
        $entities = $this->service->getEntities();
        $this->assertTrue($entities[0]->requiresQuote());
        $this->assertFalse($entities[0]->requiresProject());
        $this->assertSame('qualification', $entities[0]->getDefaultStage());
        $this->assertSame('["qualification","proposal"]', $entities[0]->getStagesJson());
    }

    public function testGetByIdReturnsNullWhenNotFound(): void
    {
        $GLOBALS['__fa_select_queue'] = [[]];
        $this->assertNull($this->service->getById(999));
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

    public function testGetHtmlOptionsFormatStringCustom(): void
    {
        $options = $this->service->getHtmlOptions(true, '', '{name} [{default_stage}]');
        $this->assertSame('Enterprise [qualification]', $options[0]->getLabel());
        $this->assertSame('SMB [discovery]', $options[1]->getLabel());
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
        $rendered = $this->service->renderFromSerializedCache($this->service->getSerializedCache(), 2);
        $this->assertStringContainsString('selected', $rendered[1]);
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
        $this->service->create([
            'name' => 'Startup',
            'default_stage' => 'ideation',
        ]);
        $this->assertNull(RealmService::getOptionCacheState());
    }

    public function testUpdateInvalidatesCache(): void
    {
        $this->service->getHtmlOptions();
        $this->service->update(1, ['default_stage' => 'proposal']);
        $this->assertNull(RealmService::getOptionCacheState());
    }

    public function testDeleteInvalidatesCache(): void
    {
        $this->service->getHtmlOptions();
        $this->service->delete(1);
        $this->assertNull(RealmService::getOptionCacheState());
    }

    // ─── Hook Response Methods ──────────────────────────────────────

    public function testHookGetRealmsReturnsArrays(): void
    {
        $data = ['active_only' => true];
        $result = $this->service->hookGetRealms($data);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('requires_quote', $result[0]);
        $this->assertArrayHasKey('default_stage', $result[0]);
    }

    public function testHookGetRealmDdlReturnsStrings(): void
    {
        $data = ['blank_label' => '-- Pick --'];
        $result = $this->service->hookGetRealmDDL($data);
        $this->assertCount(3, $result);
        $this->assertStringContainsString('-- Pick --', $result[0]);
    }

    public function testHookGetRealmDdlWithSelectedId(): void
    {
        $data = ['selected_id' => 1];
        $result = $this->service->hookGetRealmDDL($data);
        $this->assertStringContainsString('selected', $result[0]);
    }

    public function testHookGetRealmHtmlOptionsReturnsOptionObjects(): void
    {
        $data = ['active_only' => true];
        $result = $this->service->hookGetRealmHtmlOptions($data);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(HtmlOption::class, $result[0]);
    }

    // ─── Blank Option & Mandatory Validation (FR-006-006) ──────────

    public function testBlankOptionHasEmptyValue(): void
    {
        $rendered = $this->service->getDdl(true, '-- Select Realm --');
        $this->assertStringContainsString('value=""', $rendered[0]);
    }

    public function testBlankOptionNotIncludedWhenNoBlankLabel(): void
    {
        $rendered = $this->service->getDdl(true, '');
        $this->assertStringNotContainsString('value=""', $rendered[0]);
    }
}
