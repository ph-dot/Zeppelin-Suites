<?php
/**
 * saveBlockedDate.php (Admin)
 * Blocks a specific date range for a unit (for Maintenance or Not Available)
 * with custom remarks, WITHOUT creating a reservation or booking.
 */

require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json; charset=utf-8');

$userData = requireRole($conn, ['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$unitId    = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
$startDate = trim($_POST['start_date'] ?? '');
$endDate   = trim($_POST['end_date'] ?? '');
$blockType = trim($_POST['block_type'] ?? 'Not Available');
$remarks   = trim($_POST['remarks'] ?? '');

if ($unitId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid unit.']);
    exit;
}

if (empty($startDate) || empty($endDate)) {
    echo json_encode(['success' => false, 'message' => 'Start date and end date are required.']);
    exit;
}

if ($endDate < $startDate) {
    echo json_encode(['success' => false, 'message' => 'End date cannot be earlier than start date.']);
    exit;
}

$allowedTypes = ['Not Available', 'Maintenance'];
if (!in_array($blockType, $allowedTypes, true)) {
    $blockType = 'Not Available';
}

// 1. Verify unit exists
$stmtUnit = $conn->prepare("SELECT unit_id, unit_number, unit_type FROM units_table WHERE unit_id = ? LIMIT 1");
if (!$stmtUnit) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmtUnit->bind_param('i', $unitId);
$stmtUnit->execute();
$unitRes = $stmtUnit->get_result();
$unit = $unitRes ? $unitRes->fetch_assoc() : null;
$stmtUnit->close();

if (!$unit) {
    echo json_encode(['success' => false, 'message' => 'Selected unit not found.']);
    exit;
}

// 2. Check for overlapping active reservations in reservation_table
$stmtRes = $conn->prepare("
    SELECT reservation_id, client_name, move_in_date, move_out_date 
    FROM reservation_table 
    WHERE unit_id = ? 
      AND reservation_status NOT IN ('cancelled', 'rejected')
      AND move_in_date <= ? AND move_out_date >= ?
    LIMIT 1
");
if ($stmtRes) {
    $stmtRes->bind_param('iss', $unitId, $endDate, $startDate);
    $stmtRes->execute();
    $overlapRes = $stmtRes->get_result()->fetch_assoc();
    $stmtRes->close();

    if ($overlapRes) {
        $msg = sprintf(
            'Cannot block dates: Unit %s already has an active lease for "%s" from %s to %s.',
            $unit['unit_number'],
            $overlapRes['client_name'],
            date('M j, Y', strtotime($overlapRes['move_in_date'])),
            date('M j, Y', strtotime($overlapRes['move_out_date']))
        );
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}

// 3. Insert into unit_blocked_dates
$userId = (int)$userData['user_id'];
$stmtInsert = $conn->prepare("
    INSERT INTO unit_blocked_dates 
        (unit_id, start_date, end_date, block_type, remarks, created_by_user_id, created_by_role)
    VALUES (?, ?, ?, ?, ?, ?, 'admin')
");

if (!$stmtInsert) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$stmtInsert->bind_param('issssi', $unitId, $startDate, $endDate, $blockType, $remarks, $userId);

if ($stmtInsert->execute()) {
    $blockId = (int)$stmtInsert->insert_id;
    $stmtInsert->close();
    echo json_encode([
        'success' => true,
        'message' => "Unit {$unit['unit_number']} dates successfully blocked for {$blockType}.",
        'block_id' => $blockId
    ]);
} else {
    $err = $stmtInsert->error;
    $stmtInsert->close();
    echo json_encode(['success' => false, 'message' => 'Failed to save date block: ' . $err]);
}
