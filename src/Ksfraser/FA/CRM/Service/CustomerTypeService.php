<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\CustomerTypeRepository;
use Ksfraser\FA\CRM\Entity\CustomerType;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Traits\DdlCacheTrait;

/**
 * CustomerTypeService — DDL caching + hooks for customer type reference data.
 *
 * @see BR-006 (Cross-Module DDL Caching)
 *
 * @since 1.0.0
 */
class CustomerTypeService
{
    use DdlCacheTrait;

    private CustomerTypeRepository $repo;

    /** @var array[]|null Entity cache */
    private static ?array $entityCache = null;

    public function __construct(?CustomerTypeRepository $repo = null)
    {
        $this->repo = $repo ?? new CustomerTypeRepository();
    }

    // ─── Entity Access ─────────────────────────────────────────────

    public function getEntities(bool $activeOnly = true): array
    {
        $key = $activeOnly ? 'active' : 'all';
        if (self::$entityCache !== null && isset(self::$entityCache[$key])) {
            return self::$entityCache[$key];
        }
        $rows = $activeOnly ? $this->repo->findActive() : $this->repo->findAll();
        $entities = [];
        foreach ($rows as $row) {
            $entities[] = new CustomerType($row);
        }
        self::$entityCache[$key] = $entities;
        return $entities;
    }

    public function listAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): ?array
    {
        $entity = $this->repo->findById($id);
        return $entity ? $entity->toArray() : null;
    }

    // ─── DDL (via DdlCacheTrait) ──────────────────────────────────

    public function getHtmlOptions(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        int $selectedId = 0
    ): array {
        $dataKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString;

        $options = $this->getOrBuildOptions($dataKey, function () use ($activeOnly, $blankLabel, $formatString) {
            $entities = $this->getEntities($activeOnly);
            $opts = [];
            if ($blankLabel !== '') {
                $opts[] = new HtmlOption('', $blankLabel);
            }
            foreach ($entities as $entity) {
                $text = str_replace(
                    ['{name}', '{id}'],
                    [$entity->getName(), (string)$entity->getId()],
                    $formatString
                );
                $opts[] = new HtmlOption((string)$entity->getId(), $text);
            }
            return $opts;
        });

        if ($selectedId > 0) {
            $cloned = [];
            foreach ($options as $option) {
                $clone = clone $option;
                $clone->setSelected($clone->getValue() === (string)$selectedId);
                $cloned[] = $clone;
            }
            return $cloned;
        }
        return $options;
    }

    public function getDdl(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        int $selectedId = 0
    ): array {
        $htmlKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString . '|' . $selectedId;
        $dataKey = ($activeOnly ? 'active' : 'all') . '|' . $blankLabel . '|' . $formatString;
        $this->getOrBuildOptions($dataKey, function () use ($activeOnly, $blankLabel, $formatString) {
            return $this->getHtmlOptions($activeOnly, $blankLabel, $formatString);
        });
        $options = self::getOptionCacheState()[$dataKey] ?? [];
        return $this->getOrRenderHtml($htmlKey, $options, $selectedId);
    }

    public function getCustomerTypeSelect(
        string $name = 'customer_type_id',
        bool $activeOnly = true,
        string $blankLabel = '',
        string $formatString = '{name}',
        string $cssClass = 'form-control',
        int $selectedId = 0
    ): HtmlSelect {
        $select = new HtmlSelect($name);
        $select->setClass($cssClass);
        foreach ($this->getHtmlOptions($activeOnly, $blankLabel, $formatString, $selectedId) as $option) {
            $select->addOption($option);
        }
        return $select;
    }

    // ─── CRUD (invalidates cache) ─────────────────────────────────

    public function create(array $data): int
    {
        $id = $this->repo->save($data);
        self::invalidateAllCaches();
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $data);
        self::invalidateAllCaches();
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
        self::invalidateAllCaches();
    }

    public static function invalidateAllCaches(): void
    {
        self::$entityCache = null;
        self::invalidateCache();
    }

    // ─── Field Metadata (FR-006-007) ─────────────────────────────

    /**
     * Return field metadata for this entity, consumable by TableView/FieldForm.
     *
     * @return array Entity metadata (FR-006-007 schema)
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'      => 'customer_type',
            'table'       => '0_fa_crm_customer_types',
            'label'       => 'Customer Type',
            'labelPlural' => 'Customer Types',
            'hookPrefix'  => 'CustomerType',
            'pk'          => 'id',
            'fields'      => [
                'id' => [
                    'label' => 'ID',
                    'type'  => 'text',
                    'showInForm' => false,
                    'showInTable' => true,
                ],
                'name' => [
                    'label' => 'Name',
                    'type'  => 'text',
                    'required' => true,
                    'max' => 100,
                    'showInTable' => true,
                    'showInForm' => true,
                    'colClass' => 'col-md-5',
                ],
                'description' => [
                    'label' => 'Description',
                    'type'  => 'textarea',
                    'rows' => 2,
                    'showInTable' => false,
                    'showInForm' => true,
                    'colClass' => 'col-md-7',
                ],
                'inactive' => [
                    'label' => 'Inactive',
                    'type'  => 'checkbox',
                    'default' => 0,
                    'showInTable' => true,
                    'showInForm' => true,
                    'colClass' => 'col-md-2',
                ],
                'sort_order' => [
                    'label' => 'Sort Order',
                    'type'  => 'number',
                    'showInTable' => true,
                    'showInForm' => true,
                    'colClass' => 'col-md-2',
                ],
            ],
            'fk_ddls'   => [],
            'ddlHooks'  => [
                'getCustomerTypes'           => 'hookGetCustomerTypes',
                'getCustomerTypeDDL'         => 'hookGetCustomerTypeDDL',
                'getCustomerTypeHtmlOptions' => 'hookGetCustomerTypeHtmlOptions',
            ],
            'tableSettings' => ['orderBy' => 'sort_order ASC, name ASC'],
        ];
    }

    // ─── Hook Response Methods ────────────────────────────────────

    public function hookGetCustomerTypes(array &$data, $opts = null): array
    {
        $entities = $this->getEntities($data['active_only'] ?? true);
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->toArray();
        }
        return $result;
    }

    public function hookGetCustomerTypeDDL(array &$data, $opts = null): array
    {
        return $this->getDdl(
            $data['active_only'] ?? true,
            $data['blank_label'] ?? '',
            $data['format'] ?? '{name}',
            $data['selected_id'] ?? 0
        );
    }

    public function hookGetCustomerTypeHtmlOptions(array &$data, $opts = null): array
    {
        return $this->getHtmlOptions(
            $data['active_only'] ?? true,
            $data['blank_label'] ?? '',
            $data['format'] ?? '{name}',
            $data['selected_id'] ?? 0
        );
    }
}
