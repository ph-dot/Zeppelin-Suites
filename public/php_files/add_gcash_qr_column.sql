-- Add gcash_QR column to users_table
ALTER TABLE `users_table`
ADD COLUMN `gcash_QR` VARCHAR(255) NULL DEFAULT NULL AFTER `resident_status`;
