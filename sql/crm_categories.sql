-- Extend FA's crm_categories with new contact types
-- All contacts (employees, leads, emergency contacts, dependents) are persons
-- in 0_crm_persons, linked via 0_crm_contacts with these categories

INSERT IGNORE INTO `0_crm_categories` (`type`, `action`, `name`, `description`, `system`) VALUES
('employee', 'general', 'Employee', 'Employee contact record', 0),
('employee', 'emergency', 'Emergency Contact', 'Emergency contact for employee', 0),
('employee', 'dependent', 'Dependent', 'Employee dependent for benefits', 0),
('lead', 'general', 'Lead', 'Sales lead contact', 0),
('opportunity', 'general', 'Opportunity', 'Sales opportunity contact', 0);
