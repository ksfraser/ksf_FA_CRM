<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Locks the CRM's table-name convention for the legacy layer.
 *
 * The legacy includes (crm_db.inc, crm_ui.inc, crm_relationships_db.inc), the
 * pages and the reports addressed tables that exist nowhere:
 *
 *   fa_crm_*        the module's own tables are 0_ksf_crm_*; the fa_crm_*
 *                   names were never created
 *   0_crm_*         a bare 0_crm_<name> is not a schema table either
 *   0_custcontacts  a FrontAccounting 2.1 table
 *   0_debtor_master FA core's debtor table is 0_debtors_master (plural)
 *
 * Because db_query() only raises when the caller passes a message, every one
 * of those statements failed silently: the list came back empty and nothing
 * was logged.
 *
 * @BABOK Related: BR-006
 * @BABOK Related: FR-CRM-001
 */
class LegacyTableNameTest extends TestCase
{
    /**
     * Directories whose files must not build an fa_crm_* / 0_crm_* table
     * name.
     */
    private const SCANNED_DIRS = ['includes', 'pages', 'reporting'];

    /**
     * The only place a legacy fa_crm_* name may still appear: the
     * retirement list and the guarded handover of the CRM's email accounts to
     * ksf_FA_EmailManager, both of which deliberately address the OLD table
     * to migrate it away.
     */
    private const ALLOWED_FILES = ['hooks.php'];

    /**
     * @dataProvider scannedFileProvider
     */
    public function testNoFileBuildsANonExistentTableName(string $file): void
    {
        $contents = (string) file_get_contents($file);

        $this->assertDoesNotMatchRegularExpression(
            '/TB_PREF\s*\.\s*"fa_crm_/',
            $contents,
            $file . ' builds an fa_crm_* table name: no schema creates one'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/TB_PREF\s*\.\s*"crm_(?!contact\b|persons\b|categories\b)/',
            $contents,
            $file . ' builds a bare 0_crm_* table name: the CRM tables are 0_ksf_crm_*'
        );

        $this->assertStringNotContainsString(
            'TB_PREF . "custcontacts',
            $contents,
            $file . ' addresses 0_custcontacts: that table belongs to FrontAccounting 2.1'
        );

        $this->assertStringNotContainsString(
            'TB_PREF . "debtor_master',
            $contents,
            $file . " addresses 0_debtor_master: FA core's debtor table is 0_debtors_master"
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function scannedFileProvider(): array
    {
        $moduleDir = dirname(__DIR__, 2);
        $files = [];

        foreach (self::SCANNED_DIRS as $dir) {
            foreach (glob($moduleDir . '/' . $dir . '/*') ?: [] as $path) {
                if (!is_file($path) || !preg_match('/\.(php|inc)$/', $path)) {
                    continue;
                }
                if (in_array(basename($path), self::ALLOWED_FILES, true)) {
                    continue;
                }
                $files[$dir . '/' . basename($path)] = [$path];
            }
        }

        return $files;
    }

    /**
     * Every table the CRM owns must be in the 0_ksf_crm_* family and must be
     * created by a schema file of the same name.
     *
     * @dataProvider ownedTableProvider
     */
    public function testOwnedTableHasASchemaFile(string $table): void
    {
        $moduleDir = dirname(__DIR__, 2);

        $this->assertFileExists(
            $moduleDir . '/sql/' . $table . '.sql',
            'the CRM must ship the schema for ' . $table
        );

        $contents = (string) file_get_contents($moduleDir . '/sql/' . $table . '.sql');

        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `0_' . $table . '`',
            $contents,
            'the schema file must create the table the code addresses'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function ownedTableProvider(): array
    {
        $moduleDir = dirname(__DIR__, 2);

        $tables = array();
        foreach (glob($moduleDir . '/sql/ksf_crm_*.sql') ?: [] as $file) {
            $table = basename($file, '.sql');
            $tables[$table] = [$table];
        }

        return $tables;
    }

    /**
     * The schema files must actually be installed on activation.
     */
    public function testEverySchemaFileIsInstalledOnActivation(): void
    {
        $moduleDir = dirname(__DIR__, 2);
        $hooks = (string) file_get_contents($moduleDir . '/hooks.php');

        foreach (glob($moduleDir . '/sql/ksf_crm_*.sql') ?: [] as $file) {
            $table = basename($file, '.sql');

            $this->assertStringContainsString(
                "'" . $table . ".sql'",
                $hooks,
                'activate_extension() must install ' . $table . '.sql'
            );
        }
    }

    /**
     * The contact layer must go through FA core, not through a table of the
     * module's own.
     */
    public function testContactLayerUsesFaCoreAndTheSharedResolver(): void
    {
        $moduleDir = dirname(__DIR__, 2);
        $contents = (string) file_get_contents($moduleDir . '/includes/crm_db.inc');

        $this->assertStringNotContainsString(
            'fa_crm_contacts',
            $contents,
            'the CRM has no contact table of its own: FA core owns the contact registry'
        );
        $this->assertStringContainsString('add_crm_person(', $contents);
        $this->assertStringContainsString('add_crm_contact(', $contents);
        $this->assertStringContainsString('delete_crm_contact(', $contents);
        $this->assertStringContainsString('crm_contact_resolver()', $contents);
    }

    /**
     * The email-accounts handover is the one place that still addresses the
     * retired table, and it is guarded by a table-exists check.
     */
    public function testTheEmailAccountsHandoverRemainsGuarded(): void
    {
        $moduleDir = dirname(__DIR__, 2);
        $hooks = (string) file_get_contents($moduleDir . '/hooks.php');

        $this->assertStringContainsString(
            "crm_table_exists('fa_crm_email_accounts')",
            $hooks,
            'the handover must only run when the retired table is actually present'
        );
    }
}
