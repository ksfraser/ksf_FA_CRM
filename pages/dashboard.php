<?php
/**
 * CRM Dashboard
 *
 * Main landing page for the CRM module.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 */

$path_to_root = "../..";

$page_security = 'SA_CRM_DASHBOARD';
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

// -------------------------------------------------------------------
// Quick Stats
// -------------------------------------------------------------------
start_table(TABLESTYLE_NOBORDER);
start_row();
label_cell(_("Customers: ") . "<b>" . CRM_num_customers() . "</b>");
label_cell(_("Opportunities: ") . "<b>" . CRM_num_opportunities() . "</b>");
label_cell(_("Leads: ") . "<b>" . CRM_num_leads() . "</b>");
end_row();
end_table();

echo '<br>';

// -------------------------------------------------------------------
// Upcoming Follow-ups
// -------------------------------------------------------------------
$followups = CRM_upcoming_followups(10);

if (count($followups) > 0) {
    echo '<h3>' . _("Upcoming Follow-ups") . '</h3>';
    start_table(TABLESTYLE);
    table_header(array(_("Customer"), _("Follow-up Date"), _("Account Manager")));
    foreach ($followups as $row) {
        start_row();
        label_cell($row['name']);
        label_cell(sql2date($row['next_followup_date']));
        label_cell($row['account_manager'] ? $row['account_manager'] : _('Not set'));
        end_row();
    }
    end_table();
} else {
    echo '<p>' . _("No upcoming follow-ups.") . '</p>';
}

echo '<br>';

// -------------------------------------------------------------------
// Recent Opportunities
// -------------------------------------------------------------------
$opportunities = CRM_recent_opportunities(5);

if (count($opportunities) > 0) {
    echo '<h3>' . _("Recent Opportunities") . '</h3>';
    start_table(TABLESTYLE);
    table_header(array(_("Opportunity"), _("Value"), _("Stage"), _("Expected Close")));
    foreach ($opportunities as $row) {
        start_row();
        label_cell($row['opportunity_name']);
        amount_cell($row['estimated_value']);
        label_cell($row['stage']);
        label_cell(sql2date($row['expected_close_date']));
        end_row();
    }
    end_table();
} else {
    echo '<p>' . _("No opportunities found.") . '</p>';
}

// -------------------------------------------------------------------
// Helper functions — query CRM tables with safe fallbacks.
// -------------------------------------------------------------------

function CRM_num_customers()
{
    $sql = "SELECT COUNT(*) AS cnt FROM `0_fa_crm_customers` WHERE inactive = 0";
    $result = db_query($sql, false);
    if (!$result) {
        return 0;
    }
    $row = db_fetch_assoc($result);
    return $row ? (int) $row['cnt'] : 0;
}

function CRM_num_opportunities()
{
    $sql = "SELECT COUNT(*) AS cnt FROM `0_fa_crm_opportunities` WHERE inactive = 0";
    $result = db_query($sql, false);
    if (!$result) {
        return 0;
    }
    $row = db_fetch_assoc($result);
    return $row ? (int) $row['cnt'] : 0;
}

function CRM_num_leads()
{
    $sql = "SELECT COUNT(*) AS cnt FROM `0_fa_crm_leads`";
    $result = db_query($sql, false);
    if (!$result) {
        return 0;
    }
    $row = db_fetch_assoc($result);
    return $row ? (int) $row['cnt'] : 0;
}

function CRM_upcoming_followups($limit = 10)
{
    $sql = "SELECT c.id, c.debtor_no, d.name, c.next_followup_date, c.account_manager
            FROM `0_fa_crm_customers` c
            JOIN `0_debtors_master` d ON d.debtor_no = c.debtor_no
            WHERE c.next_followup_date >= DATE(NOW())
              AND c.next_followup_date <= DATE_ADD(DATE(NOW()), INTERVAL 30 DAY)
              AND c.inactive = 0
            ORDER BY c.next_followup_date ASC
            LIMIT " . (int) $limit;

    $result = db_query($sql, false);
    if (!$result) {
        return array();
    }

    $rows = array();
    while ($row = db_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function CRM_recent_opportunities($limit = 5)
{
    $sql = "SELECT opportunity_name, estimated_value, stage, expected_close_date
            FROM `0_fa_crm_opportunities`
            WHERE inactive = 0
            ORDER BY created_at DESC
            LIMIT " . (int) $limit;

    $result = db_query($sql, false);
    if (!$result) {
        return array();
    }

    $rows = array();
    while ($row = db_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
