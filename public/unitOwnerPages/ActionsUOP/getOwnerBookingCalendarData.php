<?php
/**
 * getOwnerBookingCalendarData.php
 * Owner-scoped, READ-ONLY twin of adminPages/ActionsAP/getBookingCalendarData.php.
 *
 * Returns the same shape (unitTypes + bookings) the Booking Calendar
 * front-end expects, but every query is filtered to
 * units_table.unit_owner_id = the logged-in unit owner's user_id, so an
 * owner only ever receives rooms/bookings that belong to them.
 *
 * There is no create / update / delete counterpart to this file on
 * purpose — unit owners can only view availability, never edit it.
 */

require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'unit owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$owner_id = (int) $_SESSION['user_id'];

// ── Units owned by this owner, grouped by unit_type ─────────
$unitsSql = "SELECT unit_id, unit_type, unit_number, unit_current_status
             FROM units_table
             WHERE unit_owner_id = ?
             ORDER BY unit_type, unit_number";
$stmt = $conn->prepare($unitsSql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$unitsResult = $stmt->get_result();

$grouped = []; // unit_type => [ {room, unitId, maintenance}, ... ]
while ($row = $unitsResult->fetch_assoc()) {
    $type = $row['unit_type'];
    if (!isset($grouped[$type])) {
        $grouped[$type] = [];
    }
    $grouped[$type][] = [
        'room'        => $row['unit_number'],
        'unitId'      => (int) $row['unit_id'],
        'maintenance' => $row['unit_current_status'] === 'Under maintenance',
    ];
}
$stmt->close();

$unitTypes = [];
foreach ($grouped as $type => $rooms) {
    $unitTypes[] = ['key' => $type, 'rooms' => $rooms];
}

// ── Active reservations, but only for units this owner owns ─
$bookingsSql = "SELECT
        r.reservation_id, r.client_name, r.client_email, r.client_contact,
        r.move_in_date, r.move_out_date, r.reservation_status,
        u.unit_id, u.unit_type, u.unit_number
    FROM reservation_table r
    JOIN units_table u ON r.unit_id = u.unit_id
    WHERE u.unit_owner_id = ?
      AND r.reservation_status NOT IN ('rejected', 'cancelled')
      AND r.move_in_date IS NOT NULL
      AND r.move_out_date IS NOT NULL
    ORDER BY r.move_in_date";
$stmt = $conn->prepare($bookingsSql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$bookingsResult = $stmt->get_result();

$today = date('Y-m-d');
$bookings = [];
while ($row = $bookingsResult->fetch_assoc()) {
    $isCurrent = $row['move_in_date'] <= $today && $today <= $row['move_out_date'];

    $bookings[] = [
        'id'         => (int) $row['reservation_id'],
        'guestName'  => $row['client_name'],
        'email'      => $row['client_email'],
        'phone'      => $row['client_contact'],
        'unitType'   => $row['unit_type'],
        'roomNumber' => $row['unit_number'],
        'unitId'     => (int) $row['unit_id'],
        'startDate'  => $row['move_in_date'],
        'endDate'    => $row['move_out_date'],
        'status'     => $isCurrent ? 'Occupied' : 'Reserved',
        'reservationStatus' => $row['reservation_status'],
    ];
}
$stmt->close();

// ── Blocked dates for units this owner owns ─────────────────
$blockedSql = "SELECT
        b.block_id, b.unit_id, b.start_date, b.end_date, b.block_type, b.remarks,
        b.created_by_role, b.created_at,
        u.unit_type, u.unit_number
    FROM unit_blocked_dates b
    JOIN units_table u ON b.unit_id = u.unit_id
    WHERE u.unit_owner_id = ?
    ORDER BY b.start_date";
$stmtBlocked = $conn->prepare($blockedSql);
$stmtBlocked->bind_param("i", $owner_id);
$stmtBlocked->execute();
$blockedResult = $stmtBlocked->get_result();

$blockedDates = [];
while ($row = $blockedResult->fetch_assoc()) {
    $blockedDates[] = [
        'blockId'       => (int)$row['block_id'],
        'unitId'        => (int)$row['unit_id'],
        'unitType'      => $row['unit_type'],
        'roomNumber'    => $row['unit_number'],
        'startDate'     => $row['start_date'],
        'endDate'       => $row['end_date'],
        'blockType'     => $row['block_type'], // 'Not Available' | 'Maintenance'
        'remarks'       => $row['remarks'] ?? '',
        'createdByRole' => $row['created_by_role'],
    ];
}
$stmtBlocked->close();

echo json_encode([
    'success'      => true,
    'unitTypes'    => $unitTypes,
    'bookings'     => $bookings,
    'blockedDates' => $blockedDates,
]);

