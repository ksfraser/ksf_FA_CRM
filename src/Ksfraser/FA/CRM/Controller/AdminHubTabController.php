<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use Ksfraser\FA\CRM\App\CrmAppShell;

/**
 * AdminHubTabController — landing page for the CRM Admin group.
 *
 * Mirrors the Product Attributes admin-page pattern: the Admin parent shows a
 * hub listing every admin-grouped configuration sub-tab (customer types,
 * territories, opportunity option lists, email accounts) as links. Each link
 * resolves through the app-shell router to the corresponding controller tab.
 *
 * Read-only hub — it owns no records. CRUD operations are owned by the
 * individual admin sub-tabs it links to.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-CRM-001 (Admin sub-navigation)
 */
class AdminHubTabController extends AbstractTabController
{
    /** @var CrmAppShell|null Injected shell, used to enumerate admin tabs. */
    private $shell = null;

    /**
     * @param mixed                $context DI request state
     * @param array<string, mixed> $options
     *
     * @since 1.0.0
     */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
    }

    /**
     * Inject the owning shell so the hub can list the admin sub-tabs.
     *
     * @param CrmAppShell $shell
     * @return void
     */
    public function setShell(CrmAppShell $shell): void
    {
        $this->shell = $shell;
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return ['labelPlural' => 'Admin'];
    }

    /**
     * The hub has no rows of its own.
     *
     * {@inheritDoc}
     */
    protected function listRows(int $page, int $perPage): array
    {
        return [];
    }

    /**
     * The hub has no rows of its own.
     *
     * {@inheritDoc}
     */
    protected function countRows(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    protected function findRecord(string $pk): ?array
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function createRecord(array $data)
    {
        throw new \RuntimeException('Admin hub is read-only.');
    }

    /**
     * {@inheritDoc}
     */
    protected function updateRecord(string $pk, array $data): void
    {
        throw new \RuntimeException('Admin hub is read-only.');
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteRecord(string $pk): void
    {
        throw new \RuntimeException('Admin hub is read-only.');
    }

    /**
     * No editable entry form on the hub.
     *
     * {@inheritDoc}
     */
    protected function renderEntryForm(): string
    {
        return '';
    }

    /**
     * Render the admin hub: a list of links to every admin-grouped sub-tab.
     *
     * {@inheritDoc}
     */
    protected function renderSummaryTable(): void
    {
        if (!function_exists('start_table')) {
            return;
        }

        $tabs = $this->shell !== null ? $this->shell->getAdminTabs() : [];

        start_table(TABLESTYLE2, "width='95%'");
        echo '<tr><th>'
            . htmlspecialchars($this->localise('Configuration'), ENT_QUOTES)
            . '</th></tr>';

        if (empty($tabs)) {
            echo '<tr><td><em>'
                . htmlspecialchars($this->localise('No admin sections available.'), ENT_QUOTES)
                . '</em></td></tr>';
        }

        foreach ($tabs as $tab) {
            $label = $this->localise($tab->getLabel());
            $url = 'index.php?view=' . rawurlencode($tab->getKey());
            echo '<tr class="oddrow"><td><a href="'
                . htmlspecialchars($url, ENT_QUOTES) . '">'
                . htmlspecialchars($label, ENT_QUOTES) . '</a></td></tr>';
        }

        end_table(0);
    }
}
