<?php
require_once __DIR__ . '/db.php';

echo "=== Zeppelin Suites: Starting SQM Column Migration & Backfill ===\n";

// 1. Check if column exists
$checkCol = $conn->query("SHOW COLUMNS FROM units_table LIKE 'sqm'");
if ($checkCol && $checkCol->num_rows === 0) {
    $alterSql = "ALTER TABLE units_table ADD COLUMN sqm DECIMAL(6,2) NOT NULL DEFAULT 0.00 AFTER unit_type";
    if ($conn->query($alterSql)) {
        echo "[SUCCESS] Added 'sqm' column to units_table.\n";
    } else {
        die("[ERROR] Failed to add column: " . $conn->error . "\n");
    }
} else {
    echo "[INFO] 'sqm' column already exists in units_table.\n";
}

// 2. Define SQM mapping per unit type
$sqmMapping = [
    'Studio Type A' => 37.00,
    'Studio Type B' => 40.65,
    'One Bedroom'   => 75.64,
    'Two Bedroom'   => 113.00,
];

// 3. Update existing units in units_table
$updateStmt = $conn->prepare("UPDATE units_table SET sqm = ? WHERE unit_type = ?");
foreach ($sqmMapping as $typeName => $sqmValue) {
    if ($updateStmt) {
        $updateStmt->bind_param('ds', $sqmValue, $typeName);
        $updateStmt->execute();
        $affected = $updateStmt->affected_rows;
        echo "[UPDATED] {$typeName} => {$sqmValue} SQM (affected rows: {$affected})\n";
    }
}
if ($updateStmt) {
    $updateStmt->close();
}

// Fallback for any variations in casing or spacing
$conn->query("UPDATE units_table SET sqm = 37.00 WHERE (sqm = 0 OR sqm IS NULL) AND (LOWER(TRIM(unit_type)) LIKE '%studio%a%' OR LOWER(TRIM(unit_type)) = 'studio a')");
$conn->query("UPDATE units_table SET sqm = 40.65 WHERE (sqm = 0 OR sqm IS NULL) AND (LOWER(TRIM(unit_type)) LIKE '%studio%b%' OR LOWER(TRIM(unit_type)) = 'studio b')");
$conn->query("UPDATE units_table SET sqm = 75.64 WHERE (sqm = 0 OR sqm IS NULL) AND (LOWER(TRIM(unit_type)) LIKE '%one%bed%')");
$conn->query("UPDATE units_table SET sqm = 113.00 WHERE (sqm = 0 OR sqm IS NULL) AND (LOWER(TRIM(unit_type)) LIKE '%two%bed%')");

// 4. Print summary of all units with their assigned SQM
echo "\n=== Units & Assigned SQM Verification ===\n";
$res = $conn->query("SELECT unit_id, unit_number, unit_type, sqm, floor_number, unit_current_status FROM units_table ORDER BY floor_number ASC, unit_number ASC");

$totalCount = 0;
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $totalCount++;
        echo sprintf("Unit #%-5s | Type: %-15s | Floor: %-2d | SQM: %-6.2f | Status: %s\n",
            $row['unit_number'],
            $row['unit_type'],
            $row['floor_number'],
            $row['sqm'],
            $row['unit_current_status']
        );
    }
}
echo "=== Total Units Verified: {$totalCount} ===\n";
