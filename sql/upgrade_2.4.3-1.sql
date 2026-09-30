-- ============================================================================
-- ksf_FA_CRM upgrade — table prefix correction
-- ============================================================================
-- Module tables were created as 0_fa_crm_* which does not match the documented
-- convention in AGENTS.md ("SQL tables: 0_ksf_<tablename>"). The 0_fa_ prefix
-- also read as "FrontAccounting's own table", which is what let
-- 0_fa_crm_contacts be mistaken for a core table alongside the real
-- crm_persons/crm_contacts pair (issue #14).
--
-- The per-table install files now create 0_ksf_crm_*; these statements retire
-- the misnamed tables.
--
-- TEMPORARY: these drops are destructive and are removed once every
-- installation has been cut over (see the header note in activate_extension).
-- ============================================================================

DROP TABLE IF EXISTS `0_fa_crm_quote_items`;
DROP TABLE IF EXISTS `0_fa_crm_quotes`;
DROP TABLE IF EXISTS `0_fa_crm_option_lists`;
DROP TABLE IF EXISTS `0_fa_crm_meeting_attendees`;
DROP TABLE IF EXISTS `0_fa_crm_meetings`;
DROP TABLE IF EXISTS `0_fa_crm_realms`;
DROP TABLE IF EXISTS `0_fa_crm_contact_accounts`;
DROP TABLE IF EXISTS `0_fa_crm_leads`;
DROP TABLE IF EXISTS `0_fa_crm_activity_log`;
DROP TABLE IF EXISTS `0_fa_crm_territories`;
DROP TABLE IF EXISTS `0_fa_crm_customer_types`;
DROP TABLE IF EXISTS `0_fa_crm_communications`;
DROP TABLE IF EXISTS `0_fa_crm_opportunities`;
DROP TABLE IF EXISTS `0_fa_crm_life_events`;
DROP TABLE IF EXISTS `0_fa_crm_person_account_roles`;
DROP TABLE IF EXISTS `0_fa_crm_account_relationships`;
DROP TABLE IF EXISTS `0_fa_crm_contact_relationships`;
DROP TABLE IF EXISTS `0_fa_crm_contacts`;
DROP TABLE IF EXISTS `0_fa_crm_customers`;

-- Retired by #25 (EmailManager is the mailbox of record). Kept here so the
-- prefix sweep leaves no 0_fa_crm_* table behind; the handover copy into
-- 0_ksf_em_accounts runs in activate_extension() before this file.
DROP TABLE IF EXISTS `0_fa_crm_email_accounts`;
