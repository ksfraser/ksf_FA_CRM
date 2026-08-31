-- Retag contact types previously owned by ksf_FA_Common to their natural module.
-- Idempotent: safe to run on every activation.
UPDATE `0_ksf_contact_types` SET module = 'ksf_FA_CRM' WHERE name = 'crm_contact' AND module = 'ksf_FA_Common';
UPDATE `0_ksf_contact_types` SET module = 'ksf_FA_CRM' WHERE name = 'lead' AND module = 'ksf_FA_Common';
UPDATE `0_ksf_contact_types` SET module = 'ksf_FA_CRM' WHERE name = 'opportunity' AND module = 'ksf_FA_Common';
