<?php
require_once __DIR__ . '/db.php';

echo "--- Checking and adding gcash_QR column to users_table ---\n";

$checkCol = $conn->query("SHOW COLUMNS FROM users_table LIKE 'gcash_QR'");
if ($checkCol && $checkCol->num_rows === 0) {
    $alterSql = "ALTER TABLE users_table ADD COLUMN gcash_QR VARCHAR(255) NULL DEFAULT NULL AFTER resident_status";
    if ($conn->query($alterSql)) {
        echo "Successfully added 'gcash_QR' column to users_table.\n";
    } else {
        die("Error adding column: " . $conn->error . "\n");
    }
} else {
    echo "'gcash_QR' column already exists in users_table.\n";
}

echo "--- Migration Completed Successfully ---\n";
?>
