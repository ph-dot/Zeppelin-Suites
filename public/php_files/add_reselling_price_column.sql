-- Add resellling_price and reselling_price columns to units_table
ALTER TABLE units_table 
ADD COLUMN resellling_price DECIMAL(15,2) NULL DEFAULT NULL AFTER lease_rate,
ADD COLUMN reselling_price DECIMAL(15,2) NULL DEFAULT NULL AFTER resellling_price;
