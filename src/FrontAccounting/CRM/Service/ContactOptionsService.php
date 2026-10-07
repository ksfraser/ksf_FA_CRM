<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\CRM\Service;

/**
 * Read-only listing of native FA contacts, for other modules' dropdowns.
 *
 * A contact is a row in FA core's crm_persons. HRM employees and other
 * extension contact types are the same person with extra tables keyed on
 * person_id, so one query covers customer contacts and staff alike — which is
 * why this deliberately does not read the legacy flat ksf_crm_contacts table.
 *
 * @BABOK Related: FR-NT-001-002
 * @since 1.1.0
 */
class ContactOptionsService
{
    /**
     * Contacts as id => display label.
     *
     * @param bool     $activeOnly Skip inactive persons
     * @param string   $blankLabel Leading blank option label; '' for none
     * @param string   $format    Label template using {id}, {name}, {email}
     * @param int|null $selectedId Mark this person selected
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

        foreach ($this->listContacts($activeOnly) as $contact) {
            $options[] = array(
                'value' => (string) $contact['id'],
                'label' => $this->formatLabel($contact, $format),
                'selected' => $selectedId !== null && (int) $contact['id'] === $selectedId,
            );
        }

        return $options;
    }

    /**
     * Contacts as a flat value => label map.
     *
     * @param bool $activeOnly Skip inactive persons
     * @return array<string, string>
     * @since 1.1.0
     */
    public function getOptionMap(bool $activeOnly = true): array
    {
        $map = array();

        foreach ($this->listContacts($activeOnly) as $contact) {
            $map[(string) $contact['id']] = $this->formatLabel($contact, '{name}');
        }

        return $map;
    }

    /**
     * Number of contacts offered.
     *
     * @param bool $activeOnly Skip inactive persons
     * @return int
     * @since 1.1.0
     */
    public function count(bool $activeOnly = true): int
    {
        return count($this->listContacts($activeOnly));
    }

    /**
     * Read the person rows this service is willing to offer.
     *
     * @param bool $activeOnly Skip inactive persons
     * @return array[]
     * @since 1.1.0
     */
    private function listContacts(bool $activeOnly): array
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return array();
        }

        if (function_exists('check_table') && !check_table('crm_persons', TB_PREF)) {
            return array();
        }

        $sql = 'SELECT id, name, email FROM ' . TB_PREF . 'crm_persons';

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
     * @param array  $contact Person row
     * @param string $format  Label template
     * @return string
     * @since 1.1.0
     */
    private function formatLabel(array $contact, string $format): string
    {
        return str_replace(
            array('{id}', '{name}', '{email}'),
            array(
                (string) $contact['id'],
                (string) $contact['name'],
                (string) ($contact['email'] ?? ''),
            ),
            $format
        );
    }

    // ─── Hook responders ────────────────────────────────────────────────

    /**
     * Broadcast seam: value => label map of native persons.
     *
     * @param array      $data Modified by reference; 'options' is populated
     * @param array|null $opts active_only
     * @return array
     * @since 1.1.0
     */
    public function hookGetContactOptions(array &$data, $opts = null): array
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
    public function hookGetContactOptionsHtmlOptions(array &$data, $opts = null): array
    {
        return $data['options'] = $this->getHtmlOptions(
            (bool) ($opts['active_only'] ?? ($data['active_only'] ?? true)),
            (string) ($opts['blank_label'] ?? ($data['blank_label'] ?? '')),
            (string) ($opts['format'] ?? ($data['format'] ?? '{name}')),
            isset($data['selected_id']) ? (int) $data['selected_id'] : null
        );
    }
}