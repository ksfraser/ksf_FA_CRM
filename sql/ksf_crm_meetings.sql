-- 0_ksf_crm_meetings
-- Table definition + pre-seed data for ksf_crm_meetings.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_name` varchar(100) NOT NULL,
  `meeting_type` varchar(30) DEFAULT 'meeting',
  `description` text,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `location_type` varchar(10) DEFAULT 'physical',
  `custom_location` varchar(200) DEFAULT '',
  `phone_number` varchar(20) DEFAULT '',
  `conference_url` varchar(500) DEFAULT '',
  `debtor_no` varchar(20) DEFAULT '',
  `opportunity_id` int(11) DEFAULT NULL,
  `agenda` text,
  `notes` text,
  `status` varchar(20) DEFAULT 'planned',
  `priority` varchar(10) DEFAULT 'normal',
  `assigned_to` varchar(100) DEFAULT '',
  `created_by` varchar(100) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

