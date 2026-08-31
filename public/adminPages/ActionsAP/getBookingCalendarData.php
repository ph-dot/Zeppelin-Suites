<?php
/**
 * getBookingCalendarData.php
 * Returns everything the Booking Calendar page needs to render:
 *   - unitTypes: units_table grouped by unit_type, each room flagged
 *     as under-maintenance directly from unit_current_status.
 *   - bookings: active reservation_table rows (joined to units_table),
 *     with a display status of Occupied/Reserved computed from today's
 *     date vs move_in_date/move_out_date.
 *
 * "Maintenance" is intentionally NOT a booking here — per how this
 * calendar is wired, maintenance lives on the unit itself
 * (units_table.unit_current_status = 'Under maintenance'), not on a
 * date range. The front-end renders that as a full-row indicator.
 */

require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Units, grouped by unit_type ─────────────────────────────
$unitsSql = "SELECT unit_id, unit_type, unit_number, unit_current_status
             FROM units_table
             ORDER BY unit_type, unit_number";
$unitsResult = $conn->query($unitsSql);

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

$unitTypes = [];
foreach ($grouped as $type => $rooms) {
    $unitTypes[] = ['key' => $type, 'rooms' => $rooms];
}

// ── Active reservations (exclude rejected / cancelled) ──────
$bookingsSql = "SELECT
        r.reservation_id, r.client_name, r.client_email, r.client_contact,
        r.move_in_date, r.move_out_date, r.reservation_status,
        u.unit_id, u.unit_type, u.unit_number
    FROM reservation_table r
    JOIN units_table u ON r.unit_id = u.unit_id
    WHERE r.reservation_status NOT IN ('rejected', 'cancelled')
      AND r.move_in_date IS NOT NULL
      AND r.move_out_date IS NOT NULL
    ORDER BY r.move_in_date";
$bookingsResult = $conn->query($bookingsSql);

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
        // kept for reference/debugging in the quick-view / edit modal
        'reservationStatus' => $row['reservation_status'],
    ];
}

echo json_encode([
    'success'   => true,
    'unitTypes' => $unitTypes,
    'bookings'  => $bookings,
]);
