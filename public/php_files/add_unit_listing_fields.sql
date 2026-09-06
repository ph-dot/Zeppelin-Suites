-- Migration: Add listing_type column to units_table
-- Date: 2026-09-05
-- Description: Adds listing_type ('For Lease', 'Resale') and backfills based on current status.

ALTER TABLE `units_table` 
ADD COLUMN `listing_type` ENUM('For Lease', 'Resale') NOT NULL DEFAULT 'For Lease' AFTER `lease_rate`;

-- Sync existing resale units
UPDATE `units_table` 
SET `listing_type` = 'Resale' 
WHERE `unit_current_status` = 'Resale';
