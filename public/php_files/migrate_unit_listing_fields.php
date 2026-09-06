<?php
require_once __DIR__ . '/db.php';

echo "Running unit listing migration...\n";

// Check if listing_type column exists
$colCheck = $conn->query("SHOW COLUMNS FROM units_table LIKE 'listing_type'");
if ($colCheck && $colCheck->num_rows > 0) {
    echo "listing_type column already exists.\n";
} else {
    $sql = "ALTER TABLE units_table ADD COLUMN listing_type ENUM('For Lease', 'Resale') NOT NULL DEFAULT 'For Lease' AFTER lease_rate";
    if ($conn->query($sql)) {
        echo "Successfully added listing_type column to units_table.\n";
    } else {
        echo "Error adding listing_type: " . $conn->error . "\n";
    }
}

// Backfill existing rows
$updateSql = "UPDATE units_table SET listing_type = 'Resale' WHERE unit_current_status = 'Resale'";
if ($conn->query($updateSql)) {
    echo "Successfully updated listing_type for existing Resale units.\n";
} else {
    echo "Error updating listing_type: " . $conn->error . "\n";
}

echo "Migration complete.\n";
