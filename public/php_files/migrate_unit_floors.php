<?php
require_once __DIR__ . '/db.php';

echo "--- Starting floor_number column check and update ---\n";

// Check if column exists
$checkCol = $conn->query("SHOW COLUMNS FROM units_table LIKE 'floor_number'");
if ($checkCol && $checkCol->num_rows === 0) {
    $alterSql = "ALTER TABLE units_table ADD COLUMN floor_number INT NOT NULL DEFAULT 1 AFTER unit_number";
    if ($conn->query($alterSql)) {
        echo "Successfully added 'floor_number' column to units_table.\n";
    } else {
        die("Error adding column: " . $conn->error . "\n");
    }
} else {
    echo "'floor_number' column already exists.\n";
}

// Map existing unit_ids to floors 1 to 10 for realistic synthetic distribution
$floorMapping = [
    1 => 1,  // A104 -> Floor 1
    2 => 1,  // A105 -> Floor 1
    3 => 2,  // A106 -> Floor 2
    4 => 2,  // A107 -> Floor 2
    5 => 2,  // A108 -> Floor 2
    6 => 3,  // B204 -> Floor 3
    7 => 3,  // B205 -> Floor 3
    8 => 4,  // B206 -> Floor 4
    9 => 4,  // B207 -> Floor 4
    10 => 4, // B208 -> Floor 4
    11 => 5, // C204 -> Floor 5
    12 => 5, // C205 -> Floor 5
    13 => 6, // C206 -> Floor 6
    14 => 6, // C207 -> Floor 6
    15 => 7, // C208 -> Floor 7
    21 => 7, // C209 -> Floor 7
    16 => 8, // D304 -> Floor 8
    17 => 8, // D305 -> Floor 8
    18 => 9, // D306 -> Floor 9
    19 => 9, // D307 -> Floor 9
    20 => 10 // D308 -> Floor 10 (Penthouse)
];

$stmt = $conn->prepare("UPDATE units_table SET floor_number = ? WHERE unit_id = ?");
foreach ($floorMapping as $unitId => $floorNum) {
    $stmt->bind_param("ii", $floorNum, $unitId);
    $stmt->execute();
}
$stmt->close();

echo "Floor distribution updated for existing units.\n";

// Print current units and floors
$res = $conn->query("SELECT unit_id, unit_number, unit_type, floor_number, unit_current_status FROM units_table ORDER BY floor_number ASC, unit_number ASC");
while ($row = $res->fetch_assoc()) {
    echo "Floor {$row['floor_number']}: {$row['unit_number']} ({$row['unit_type']}) - {$row['unit_current_status']}\n";
}

echo "--- Migration Completed Successfully ---\n";
?>
