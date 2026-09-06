-- Migration: Add sqm column to units_table and assign SQM values to each unit type
-- Zeppelin Suites Management System

-- 1. Add sqm column if it doesn't already exist
ALTER TABLE `units_table` 
ADD COLUMN IF NOT EXISTS `sqm` DECIMAL(6,2) NOT NULL DEFAULT 0.00 AFTER `unit_type`;

-- 2. Assign SQM values based on unit type
-- Studio Type A -> 37.00 SQM
UPDATE `units_table` 
SET `sqm` = 37.00 
WHERE LOWER(TRIM(`unit_type`)) = 'studio type a' 
   OR LOWER(TRIM(`unit_type`)) LIKE '%studio%a%';

-- Studio Type B -> 40.65 SQM
UPDATE `units_table` 
SET `sqm` = 40.65 
WHERE LOWER(TRIM(`unit_type`)) = 'studio type b' 
   OR LOWER(TRIM(`unit_type`)) LIKE '%studio%b%';

-- One Bedroom -> 75.64 SQM
UPDATE `units_table` 
SET `sqm` = 75.64 
WHERE LOWER(TRIM(`unit_type`)) = 'one bedroom' 
   OR LOWER(TRIM(`unit_type`)) LIKE '%one%bed%';

-- Two Bedroom -> 113.00 SQM
UPDATE `units_table` 
SET `sqm` = 113.00 
WHERE LOWER(TRIM(`unit_type`)) = 'two bedroom' 
   OR LOWER(TRIM(`unit_type`)) LIKE '%two%bed%';
