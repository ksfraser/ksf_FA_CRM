<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards the module's PHP namespace against regression.
 *
 * The module guideline is ksfraser\FrontAccounting\<ModuleName>\ for FA
 * platform modules. This module previously used Ksfraser\FA\CRM, which is one of
 * the variations the guideline warns against (alongside ksfraser\FA<Module>).
 *
 * @package ksf_FA_CRM
 */
class NamespaceConventionTest extends TestCase
{
    private const EXPECTED_PREFIX = 'ksfraser\\FrontAccounting\\CRM\\';
    private const BANNED = ['Ksfraser\\FA\\CRM', 'Ksfraser\\FACRM', 'ksfraser\\FA\\CRM', 'ksfraser\\FACRM'];

    /**
     * @return array<int, array{0: string}>
     */
    public function phpFileProvider(): array
    {
        $files = array_merge(
            glob(dirname(__DIR__, 2) . '/src/*/*/*/*.php') ?: array(),
            glob(dirname(__DIR__, 2) . '/src/*/*/*/*/*.php') ?: array(),
            glob(dirname(__DIR__, 2) . '/src/*/*/*.php') ?: array(),
            array(dirname(__DIR__, 2) . '/hooks.php')
        );

        $out = array();
        foreach ($files as $file) {
            $out[] = array($file);
        }
        return $out;
    }

    /**
     * @dataProvider phpFileProvider
     */
    public function testFileDeclaresTheExpectedNamespace(string $file): void
    {
        $contents = file_get_contents($file);

        foreach (self::BANNED as $banned) {
            $this->assertStringNotContainsString(
                $banned,
                $contents,
                basename($file) . " still uses the non-conforming namespace {$banned}"
            );
        }

        if (preg_match('/^namespace\s+([^;]+);/m', $contents, $m)) {
            $declared = trim($m[1]);
            $this->assertTrue(
                strpos($declared, self::EXPECTED_PREFIX) === 0
                    || $declared === rtrim(self::EXPECTED_PREFIX, '\\'),
                basename($file) . " declares {$declared}, expected the "
                    . rtrim(self::EXPECTED_PREFIX, '\\') . ' prefix'
            );
        }
    }

    /**
     * The autoload mapping must agree with the declared namespace, or the
     * classes are unreachable at runtime despite parsing.
     */
    public function testComposerPsr4MappingMatchesTheNamespace(): void
    {
        $composer = json_decode(
            (string)file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true
        );

        $this->assertArrayHasKey(self::EXPECTED_PREFIX, $composer['autoload']['psr-4']);
        $this->assertSame(
            'src/FrontAccounting/CRM/',
            $composer['autoload']['psr-4'][self::EXPECTED_PREFIX]
        );
    }
}
