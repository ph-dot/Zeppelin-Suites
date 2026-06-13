<?php
require_once '../../php_files/admin_auth.php';
require_once '../../php_files/db.php';

header('Content-Type: application/json');

$unit_type = $_GET['unit_type'] ?? '';

if (trim($unit_type) === '') {
    echo json_encode([
        'success' => false,
        'message' => 'No unit preference found.'
    ]);
    exit;
}

$sql = "SELECT 
            u.unit_id,
            u.unit_number,
            u.unit_type,
            u.lease_rate,
            u.unit_owner_id,
            owner.full_name AS owner_name
        FROM units_table u
        LEFT JOIN users_table owner
            ON u.unit_owner_id = owner.user_id
        WHERE u.unit_type = ?
        AND u.unit_current_status = 'Ready for Occupancy'
        AND u.unit_owner_id IS NOT NULL
        ORDER BY u.unit_number ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("s", $unit_type);
$stmt->execute();
$result = $stmt->get_result();

$units = [];

while ($row = $result->fetch_assoc()) {
    $units[] = [
        'unit_id' => $row['unit_id'],
        'unit_number' => $row['unit_number'],
        'unit_type' => $row['unit_type'],
        'lease_rate' => $row['lease_rate'],
        'unit_owner_id' => $row['unit_owner_id'],
        'owner_name' => $row['owner_name']
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($units),
    'units' => $units
]);

$stmt->close();
$conn->close();
?>