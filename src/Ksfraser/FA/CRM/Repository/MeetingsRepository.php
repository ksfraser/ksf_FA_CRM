<?php

declare(strict_types=1);

namespace Ksfraser\FA\CRM\Repository;

/**
 * Repository for CRM meetings (0_fa_crm_meetings).
 *
 * Meeting header row. Attendees live in 0_fa_crm_meeting_attendees (managed
 * out of scope of the tab controller).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_CRM
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (DAO SRP)
 * @BABOK Related: FR-CRM-001
 */
class MeetingsRepository
{
    use FaRepositoryTrait;

    private string $table = 'fa_crm_meetings';

    public function findAll(): array
    {
        $sql = "SELECT m.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " m LEFT JOIN " . TB_PREF . "debtors_master d ON m.debtor_no = d.debtor_no"
            . " ORDER BY m.start_date DESC, m.id DESC";
        return $this->dbFetchAll($this->dbQuery($sql));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT m.*, d.name AS customer_name FROM " . TB_PREF . $this->table
            . " m LEFT JOIN " . TB_PREF . "debtors_master d ON m.debtor_no = d.debtor_no"
            . " WHERE m.id = " . $this->intVal($id);
        return $this->dbFetchAssoc($this->dbQuery($sql));
    }

    public function save(array $data): int
    {
        $createdBy = isset($_SESSION['wa_current_user']->user)
            ? (string) $_SESSION['wa_current_user']->user
            : '';
        if (empty($data['assigned_to'])) {
            $data['assigned_to'] = $createdBy;
        }
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                meeting_name, meeting_type, description, start_date, end_date,
                duration_minutes, location_type, custom_location, phone_number,
                conference_url, debtor_no, opportunity_id, agenda, notes,
                status, priority, assigned_to, created_by)
            VALUES ("
            . $this->escape($data['meeting_name']) . ", "
            . $this->escape($data['meeting_type'] ?? 'meeting') . ", "
            . $this->escape($data['description'] ?? '') . ", "
            . $this->escape($data['start_date']) . ", "
            . $this->escape($data['end_date'] ?? '') . ", "
            . $this->intVal((int) ($data['duration_minutes'] ?? 60)) . ", "
            . $this->escape($data['location_type'] ?? 'physical') . ", "
            . $this->escape($data['custom_location'] ?? '') . ", "
            . $this->escape($data['phone_number'] ?? '') . ", "
            . $this->escape($data['conference_url'] ?? '') . ", "
            . $this->escape($data['debtor_no'] ?? '') . ", "
            . ($data['opportunity_id'] !== '' ? $this->intVal((int) $data['opportunity_id']) : 'NULL') . ", "
            . $this->escape($data['agenda'] ?? '') . ", "
            . $this->escape($data['notes'] ?? '') . ", "
            . $this->escape($data['status'] ?? 'planned') . ", "
            . $this->escape($data['priority'] ?? 'normal') . ", "
            . $this->escape($data['assigned_to']) . ", "
            . $this->escape($createdBy) . ")";
        $this->dbQuery($sql);
        return $this->dbInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = [];
        foreach (['meeting_name', 'meeting_type', 'description', 'start_date',
                  'end_date', 'location_type', 'custom_location', 'phone_number',
                  'conference_url', 'debtor_no', 'agenda', 'notes', 'status',
                  'priority', 'assigned_to'] as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = " . $this->escape($data[$col]);
            }
        }
        if (array_key_exists('duration_minutes', $data)) {
            $sets[] = "duration_minutes = " . $this->intVal((int) $data['duration_minutes']);
        }
        if (array_key_exists('opportunity_id', $data)) {
            $sets[] = "opportunity_id = " . ($data['opportunity_id'] !== ''
                ? $this->intVal((int) $data['opportunity_id'])
                : 'NULL');
        }
        if (empty($sets)) {
            return;
        }
        $sql = "UPDATE " . TB_PREF . $this->table
            . " SET " . implode(', ', $sets)
            . " WHERE id = " . $this->intVal($id);
        $this->dbQuery($sql);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM " . TB_PREF . $this->table
            . " WHERE id = " . $this->intVal($id);
        $this->dbQuery($sql);
    }

    public function customerOptions(): array
    {
        $sql = "SELECT debtor_no, name FROM " . TB_PREF . "debtors_master"
            . " WHERE !inactive ORDER BY name";
        $rows = $this->dbFetchAll($this->dbQuery($sql));
        $out = [];
        foreach ($rows as $row) {
            $out[$row['debtor_no']] = $row['name'];
        }
        return $out;
    }
}