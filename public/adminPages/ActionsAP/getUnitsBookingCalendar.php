<?php
require_once '../php_files/admin_auth.php';
require_once '../php_files/db.php';

// Fetch all units
$unitsByType = [];
$sql = "SELECT unit_type, unit_number, unit_id, unit_status, is_open_for_rent
        FROM units_table
        ORDER BY unit_type, unit_number";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $type = $row['unit_type'];
        if (!isset($unitsByType[$type])) {
            $unitsByType[$type] = [];
        }
        $unitsByType[$type][] = [          
            'id' => $row['unit_id'],
            'number' => $row['unit_number'],
            'status' => $row['unit_status'],
            'open' => $row['is_open_for_rent']
        ];
    }
}
?>