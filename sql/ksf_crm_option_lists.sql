-- 0_ksf_crm_option_lists
-- Table definition + pre-seed data for ksf_crm_option_lists.
-- Applied by activate_extension() via update_databases();
-- the FA install engine replaces 0_ with TB_PREF.

CREATE TABLE IF NOT EXISTS `0_ksf_crm_option_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_key` varchar(40) NOT NULL,
  `option_value` varchar(40) NOT NULL,
  `option_label` varchar(80) NOT NULL,
  `probability` decimal(5,2) NOT NULL DEFAULT '0.00',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_list_value` (`list_key`,`option_value`),
  KEY `idx_list` (`list_key`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `0_ksf_crm_option_lists` (`list_key`, `option_value`, `option_label`, `probability`, `sort_order`) VALUES
('opportunity_stage', 'prospecting',  'Prospecting',  10.00, 10),
('opportunity_stage', 'qualification', 'Qualification', 25.00, 20),
('opportunity_stage', 'value_proposition', 'Value Proposition', 30.00, 30),
('opportunity_stage', 'identify_decision_makers', 'Identify Decision Makers', 35.00, 40),
('opportunity_stage', 'perception_analysis', 'Perception Analysis', 40.00, 50),
('opportunity_stage', 'needs_analysis', 'Needs Analysis', 45.00, 60),
('opportunity_stage', 'proposal',     'Proposal',     40.00, 70),
('opportunity_stage', 'quote',        'Quote',        50.00, 80),
('opportunity_stage', 'negotiation',  'Negotiation',  75.00, 90),
('opportunity_stage', 'review',       'Review',       60.00, 100),
('opportunity_stage', 'closed_lost',  'Closed Lost',   0.00, 110),
('opportunity_stage', 'closed_won',   'Closed Won',  100.00, 120),
('opportunity_stage', 'closed_abandoned', 'Closed Abandoned', 0.00, 130);

INSERT IGNORE INTO `0_ksf_crm_option_lists` (`list_key`, `option_value`, `option_label`, `probability`, `sort_order`) VALUES
('opportunity_type', 'existing_business', 'Existing Business', 0.00, 10),
('opportunity_type', 'new_business', 'New Business', 0.00, 20);

INSERT IGNORE INTO `0_ksf_crm_option_lists` (`list_key`, `option_value`, `option_label`, `probability`, `sort_order`) VALUES
('opportunity_source', 'existing_customer', 'Existing Customer', 0.00, 10),
('opportunity_source', 'cold_call', 'Cold Call', 0.00, 20),
('opportunity_source', 'self_generated', 'Self Generated', 0.00, 30),
('opportunity_source', 'employee', 'Employee', 0.00, 40),
('opportunity_source', 'partner_referral', 'Partner Referral', 0.00, 50),
('opportunity_source', 'customer_referral', 'Customer Referral', 0.00, 60),
('opportunity_source', 'employee_referral', 'Employee Referral', 0.00, 70),
('opportunity_source', 'public_relations', 'Public Relations', 0.00, 80),
('opportunity_source', 'direct_mail', 'Direct Mail', 0.00, 90),
('opportunity_source', 'trade_show', 'Trade Show', 0.00, 100),
('opportunity_source', 'website', 'Website', 0.00, 110),
('opportunity_source', 'email', 'Email', 0.00, 120),
('opportunity_source', 'campaign', 'Campaign', 0.00, 130),
('opportunity_source', 'other', 'Other', 0.00, 140);

