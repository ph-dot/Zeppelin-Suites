<?php
require_once 'c:/xampp/htdocs/Zeppelin-Suites/public/php_files/db.php';

// Check owner_approval_requests
$res = $conn->query("SHOW COLUMNS FROM owner_approval_requests LIKE 'owner_remarks'");
if ($res && $res->num_rows === 0) {
    $alter1 = "ALTER TABLE owner_approval_requests ADD COLUMN owner_remarks TEXT NULL AFTER request_status";
    if ($conn->query($alter1)) {
        echo "Added owner_remarks to owner_approval_requests\n";
    } else {
        echo "Error altering owner_approval_requests: " . $conn->error . "\n";
    }
} else {
    echo "owner_remarks already exists in owner_approval_requests\n";
}

// Check inquiry_table
$res2 = $conn->query("SHOW COLUMNS FROM inquiry_table LIKE 'owner_remarks'");
if ($res2 && $res2->num_rows === 0) {
    $alter2 = "ALTER TABLE inquiry_table ADD COLUMN owner_remarks TEXT NULL AFTER approved_unit_id";
    if ($conn->query($alter2)) {
        echo "Added owner_remarks to inquiry_table\n";
    } else {
        echo "Error altering inquiry_table: " . $conn->error . "\n";
    }
} else {
    echo "owner_remarks already exists in inquiry_table\n";
}
