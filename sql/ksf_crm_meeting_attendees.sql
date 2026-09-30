-- 0_ksf_crm_meeting_attendees
-- Table definition + pre-seed data for ksf_crm_meeting_attendees.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_meeting_attendees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_id` int(11) NOT NULL,
  `attendee_type` varchar(20) DEFAULT 'contact',
  `contact_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `external_name` varchar(100) DEFAULT '',
  `external_email` varchar(100) DEFAULT '',
  `response_status` varchar(20) DEFAULT 'pending',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

