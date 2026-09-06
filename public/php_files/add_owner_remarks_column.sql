ALTER TABLE owner_approval_requests 
ADD COLUMN owner_remarks TEXT NULL AFTER request_status;

ALTER TABLE inquiry_table 
ADD COLUMN owner_remarks TEXT NULL AFTER approved_unit_id;
