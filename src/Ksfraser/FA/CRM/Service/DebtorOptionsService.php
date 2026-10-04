<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Service;

/**
 * Read-only listing of debtors, for other modules' dropdowns.
 *
 * Debtors live in FA core's debtors_master, so this deliberately adds no
 * table of its own and writes nothing. It exists so that a module building a
 * customer picker (Notes, Calendar, ...) asks CRM rather than reaching into
 * debtors_master itself: CRM decides the display format and which debtors are
 * offered.
 *
 * @BABOK Related: FR-NT-001-002
 * @since 1.1.0
 */
class DebtorOptionsService
{
    /**
     * Debtors as debtor_no => display label.
     *
     * @param bool     $activeOnly Skip inactive debtors
     * @param string   $blankLabel Leading blank option label; '' for none
     * @param string   $format    Label template using {id}, {name}, {ref}
     * @param int|null $selectedId Mark this debtor selected
     * @return array<int, array{value:string,label:string,selected:bool}>
     * @since 1.1.0
     */
    public function getHtmlOptions(
        bool $activeOnly = true,
        string $blankLabel = '',
        string $format = '{name}',
        ?int $selectedId = null
    ): array {
        $options = array();

        if ($blankLabel !== '') {
            $options[] = array('value' => '', 'label' => $blankLabel, 'selected' => false);
        }

        foreach ($this->listDebtors($activeOnly) as $debtor) {
            $options[] = array(
                'value' => (string) $debtor['debtor_no'],
                'label' => $this->formatLabel($debtor, $format),
                'selected' => $selectedId !== null && (int) $debtor['debtor_no'] === $selectedId,
            );
        }

        return $options;
    }

    /**
     * Debtors as a flat value => label map, which is the shape most callers
     * (and FA's own array_selector) want.
     *
     * @param bool $activeOnly Skip inactive debtors
     * @return array<string, string>
     * @since 1.1.0
     */
    public function getOptionMap(bool $activeOnly = true): array
    {
        $map = array();

        foreach ($this->listDebtors($activeOnly) as $debtor) {
            $map[(string) $debtor['debtor_no']] = $this->formatLabel($debtor, '{name}');
        }

        return $map;
    }

    /**
     * Number of debtors offered.
     *
     * @param bool $activeOnly Skip inactive debtors
     * @return int
     * @since 1.1.0
     */
    public function count(bool $activeOnly = true): int
    {
        return count($this->listDebtors($activeOnly));
    }

    /**
     * Read the debtor rows this service is willing to offer.
     *
     * @param bool $activeOnly Skip inactive debtors
     * @return array[]
     * @since 1.1.0
     */
    private function listDebtors(bool $activeOnly): array
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return array();
        }

        if (function_exists('check_table') && !check_table('debtors_master', TB_PREF)) {
            return array();
        }

        $sql = 'SELECT debtor_no, name, curr_code FROM ' . TB_PREF . 'debtors_master';

        if ($activeOnly) {
            $sql .= ' WHERE inactive = 0';
        }

        $sql .= ' ORDER BY name';

        $result = db_query($sql);

        if ($result === false || !function_exists('db_fetch_assoc')) {
            return array();
        }

        $rows = array();

        while ($row = db_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array  $debtor Debtor row
     * @param string $format Label template
     * @return string
     * @since 1.1.0
     */
    private function formatLabel(array $debtor, string $format): string
    {
        return str_replace(
            array('{id}', '{name}', '{ref}'),
            array(
                (string) $debtor['debtor_no'],
                (string) $debtor['name'],
                (string) ($debtor['curr_code'] ?? ''),
            ),
            $format
        );
    }

    // ─── Hook responders ────────────────────────────────────────────────

    /**
     * Broadcast seam: value => label map of debtors.
     *
     * @param array      $data  Modified by reference; 'options' is populated
     * @param array|null $opts  active_only, blank_label
     * @return array
     * @since 1.1.0
     */
    public function hookGetDebtorOptions(array &$data, $opts = null): array
    {
        $activeOnly = $opts['active_only'] ?? ($data['active_only'] ?? true);

        return $data['options'] = $this->getOptionMap((bool) $activeOnly);
    }

    /**
     * Broadcast seam: option records with selection state.
     *
     * @param array      $data Modified by reference; 'options' is populated
     * @param array|null $opts active_only, blank_label, format, selected_id
     * @return array
     * @since 1.1.0
     */
    public function hookGetDebtorOptionsHtmlOptions(array &$data, $opts = null): array
    {
        return $data['options'] = $this->getHtmlOptions(
            (bool) ($opts['active_only'] ?? ($data['active_only'] ?? true)),
            (string) ($opts['blank_label'] ?? ($data['blank_label'] ?? '')),
            (string) ($opts['format'] ?? ($data['format'] ?? '{name}')),
            isset($data['selected_id']) ? (int) $data['selected_id'] : null
        );
    }
}