<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

use Ksfraser\FA\CRM\Repository\OptionListRepository;

/**
 * OptionListsService — DAO-backed service for the generic CRM option lists.
 *
 * One controller/wiring class serves the four opportunity DDL admin views
 * (Sources / Types / Realms / Stages). The current list key is derived from
 * the active view key (`opportunity_sources` => `opportunity_source`, ...).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (service SRP)
 * @BABOK Related: FR-CRM-001
 */
class OptionListsService
{
    /** @var array<string,string> view key => option list key */
    public const VIEW_MAP = [
        'opportunity_sources' => 'opportunity_source',
        'opportunity_types'   => 'opportunity_type',
        'opportunity_realms'  => 'opportunity_realm',
        'opportunity_stages'  => 'opportunity_stage',
    ];

    private OptionListRepository $repo;

    public function __construct(?OptionListRepository $repo = null)
    {
        $this->repo = $repo ?? new OptionListRepository();
    }

    /** @return string Option list key for the given view key ('' if unknown). */
    public static function listKeyForView(string $view): string
    {
        return self::VIEW_MAP[$view] ?? '';
    }

    public function listAll(string $listKey): array
    {
        return $this->repo->findByList($listKey);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(string $listKey, array $data): int
    {
        $data['list_key'] = $listKey;
        return $this->repo->save($data);
    }

    public function update(int $id, array $data): void
    {
        $this->repo->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /** @return array<string,string> value => label options for the given list key. */
    public function optionsFor(string $listKey): array
    {
        $out = [];
        foreach ($this->repo->findByList($listKey) as $row) {
            if (!empty($row['inactive'])) {
                continue;
            }
            $out[$row['option_value']] = $row['option_label'];
        }
        return $out;
    }

    /** @return float|null Probability configured for a stage value ('' if none). */
    public function stageProbability(string $value): ?float
    {
        if ($value === '') {
            return null;
        }
        $row = $this->repo->findByListAndValue('opportunity_stage', $value);
        if ($row === null || $row['probability'] === '' || $row['probability'] === null) {
            return null;
        }
        return (float) $row['probability'];
    }

    /**
     * Field metadata for the generic option-list admin form (FR-006-007 schema).
     * Stage lists additionally expose the probability column.
     *
     * @param string $listKey Current option list key
     * @return array
     */
    public static function getFieldMetadata(string $listKey): array
    {
        $isStage = ($listKey === 'opportunity_stage');

        $fields = [
            'id' => [
                'label' => 'ID', 'type' => 'text',
                'showInForm' => false, 'showInTable' => true,
            ],
            'option_value' => [
                'label' => 'Value', 'type' => 'text', 'required' => true, 'max' => 40,
                'showInTable' => true, 'showInForm' => true,
            ],
            'option_label' => [
                'label' => 'Label', 'type' => 'text', 'required' => true, 'max' => 80,
                'showInTable' => true, 'showInForm' => true,
            ],
        ];

        if ($isStage) {
            $fields['probability'] = [
                'label' => 'Probability %', 'type' => 'number',
                'showInTable' => true, 'showInForm' => true,
            ];
        }

        $fields['sort_order'] = [
            'label' => 'Sort Order', 'type' => 'number',
            'showInTable' => true, 'showInForm' => true,
        ];
        $fields['inactive'] = [
            'label' => 'Inactive', 'type' => 'checkbox', 'default' => 0,
            'showInTable' => true, 'showInForm' => true,
        ];

        $labels = [
            'opportunity_source' => ['Source', 'Sources'],
            'opportunity_type'   => ['Type', 'Types'],
            'opportunity_realm'  => ['Realm', 'Realms'],
            'opportunity_stage'  => ['Stage', 'Stages'],
        ];
        [$label, $labelPlural] = $labels[$listKey] ?? ['Option', 'Options'];

        return [
            'entity'       => 'option',
            'table'        => '0_fa_crm_option_lists',
            'label'        => $label,
            'labelPlural'  => $labelPlural,
            'hookPrefix'   => 'Option',
            'pk'           => 'id',
            'fields'       => $fields,
            'fk_ddls'      => [],
            'ddlHooks'     => [],
            'tableSettings' => ['orderBy' => 'sort_order ASC, option_label ASC'],
        ];
    }
}