<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Locks the CRM's table-name convention: `TB_PREF . 'ksf_crm_*'`.
 *
 * The repositories previously named `fa_crm_*`, which produced `0_fa_crm_*`
 * — tables that do not exist. Because the reads swallowed the db error and
 * returned an empty row set, every list tab rendered "no records" and the
 * defect was invisible in the UI and to the other tests. These assertions
 * read the repository's declared table name so a future rename cannot pass
 * unnoticed again.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-CRM-001
 */
class RepositoryTableNameTest extends TestCase
{
    /**
     * Repository short name => expected unprefixed table name.
     *
     * @var array<string, string>
     */
    private const EXPECTED = [
        'CustomerTypeRepository' => 'ksf_crm_customer_types',
        'TerritoryRepository' => 'ksf_crm_territories',
        'RealmRepository' => 'ksf_crm_realms',
        'LeadsRepository' => 'ksf_crm_leads',
        'OpportunitiesRepository' => 'ksf_crm_opportunities',
        'ContactsRepository' => 'ksf_crm_contacts',
        'CommunicationsRepository' => 'ksf_crm_communications',
        'QuotesRepository' => 'ksf_crm_quotes',
        'MeetingsRepository' => 'ksf_crm_meetings',
        'OptionListRepository' => 'ksf_crm_option_lists',
    ];

    private const REPOSITORY_DIR = __DIR__ . '/../../src/FrontAccounting/CRM/Repository';

    /**
     * @dataProvider expectedTableProvider
     */
    public function testRepositoryDeclaresTheExpectedTable(string $repository, string $expected): void
    {
        $this->assertSame(
            $expected,
            $this->declaredTable($repository),
            "{$repository} must query " . TB_PREF . $expected
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function expectedTableProvider(): array
    {
        $cases = [];
        foreach (self::EXPECTED as $repository => $expected) {
            $cases[$repository] = [$repository, $expected];
        }
        return $cases;
    }

    /**
     * Guards the naming convention itself, independent of any one repository.
     *
     * Any `fa_crm_` literal anywhere in the source tree yields
     * `0_fa_crm_*`, which is not a schema table.
     */
    public function testNoRepositoryDeclaresTheNonExistentFaCrmPrefix(): void
    {
        $offenders = [];
        foreach (glob(self::REPOSITORY_DIR . '/*.php') ?: [] as $file) {
            $contents = (string) file_get_contents($file);
            if (strpos($contents, 'fa_crm_') !== false) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'these files still build 0_fa_crm_* table names: ' . implode(', ', $offenders)
        );
    }

    /**
     * TagsRepository targets FA core's `0_tags`, not a CRM table; asserting
     * it here documents that the `ksf_crm_` rename was scoped deliberately.
     */
    public function testTagsRepositoryKeepsTheFaCoreTagsTable(): void
    {
        $this->assertSame('tags', $this->declaredTable('TagsRepository'));
    }

    /**
     * Reads the private `$table` property via reflection, since it is an
     * implementation detail with no public accessor.
     */
    private function declaredTable(string $repository): string
    {
        $class = 'ksfraser\\FrontAccounting\\CRM\\Repository\\' . $repository;
        $this->assertTrue(
            class_exists($class),
            "{$repository} must exist so the convention can be verified"
        );

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        $instance = $constructor === null || $constructor->getNumberOfRequiredParameters() === 0
            ? $reflection->newInstance()
            : null;
        if ($instance !== null) {
            return (string) $reflection->getProperty('table')->getValue($instance);
        }

        // No zero-arg construction possible: fall back to the declared default.
        $defaults = $reflection->getProperty('table')->getDefaultValue();
        return is_string($defaults) ? $defaults : '';
    }
}