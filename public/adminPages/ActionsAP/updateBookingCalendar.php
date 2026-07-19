<?php
/**
 * updateBookingCalendar.php
 * Edits a reservation's guest info and stay dates from the calendar's
 * Edit modal. Payment/inquiry fields are intentionally not touched here
 * — those live on the Reservations page, which already has a full
 * workflow for payment verification and status changes.
 */

require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

function fail($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$reservationId = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : 0;
$guestName  = trim($_POST['guestName'] ?? '');
$guestEmail = trim($_POST['guestEmail'] ?? '');
$guestPhone = trim($_POST['guestPhone'] ?? '');
$startDate  = trim($_POST['startDate'] ?? '');
$endDate    = trim($_POST['endDate'] ?? '');

if ($reservationId <= 0) fail('Invalid booking.');
if ($guestName === '')   fail('Guest / resident name is required.');
if ($startDate === '' || $endDate === '') fail('Check-in and check-out dates are required.');
if ($endDate < $startDate) fail('End date must be on or after the check-in date.');

// Reservation must exist and be active
$stmt = $conn->prepare("
    SELECT unit_id FROM reservation_table
    WHERE reservation_id = ? AND reservation_status NOT IN ('rejected', 'cancelled')
");
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existing) fail('Booking not found.');
$unitId = (int) $existing['unit_id'];

// Conflict check against other active reservations for the same unit
$stmt = $conn->prepare("
    SELECT reservation_id FROM reservation_table
    WHERE unit_id = ? AND reservation_id != ?
      AND reservation_status NOT IN ('rejected', 'cancelled')
      AND move_in_date IS NOT NULL AND move_out_date IS NOT NULL
      AND ? <= move_out_date AND ? >= move_in_date
");
$stmt->bind_param("iiss", $unitId, $reservationId, $startDate, $endDate);
$stmt->execute();
$conflict = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($conflict) fail('Another booking on this unit already overlaps those dates.');

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        UPDATE reservation_table
        SET client_name = ?, client_email = ?, client_contact = ?,
            move_in_date = ?, move_out_date = ?
        WHERE reservation_id = ?
    ");
    $stmt->bind_param("sssssi", $guestName, $guestEmail, $guestPhone, $startDate, $endDate, $reservationId);
    $stmt->execute();
    $stmt->close();

    // Re-sync the unit's own status (unless it's flagged under maintenance)
    $stmt = $conn->prepare("SELECT unit_current_status FROM units_table WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $unit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($unit && $unit['unit_current_status'] !== 'Under maintenance') {
        $today = date('Y-m-d');
        $newStatus = ($startDate <= $today && $today <= $endDate) ? 'Occupied' : 'Reserved';
        $stmt = $conn->prepare("UPDATE units_table SET unit_current_status = ? WHERE unit_id = ?");
        $stmt->bind_param("si", $newStatus, $unitId);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    fail('Could not update booking: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Booking updated.']);
