<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json');

// MAIN SOURCE: reservation_table
$sql = "
    SELECT 
        r.reservation_id,
        r.unit_id,
        r.start_date,
        r.end_date,
        r.status,
        u.unit_number,
        u.unit_type
    FROM reservation_table r
    LEFT JOIN units_table u ON r.unit_id = u.unit_id
    WHERE r.status IN ('approved','paid','reserved','occupied')
";

$result = $conn->query($sql);

$events = [];

while ($row = $result->fetch_assoc()) {

    $status = strtolower($row['status']);

    $events[] = [
        "id" => $row['reservation_id'],
        "title" => $row['unit_type'] . " " . $row['unit_number'] . " - " . strtoupper($status),
        "start" => $row['start_date'],
        "end" => $row['end_date'],
        "status" => $status,
        "unit_id" => $row['unit_id']
    ];
}

echo json_encode($events);
?>