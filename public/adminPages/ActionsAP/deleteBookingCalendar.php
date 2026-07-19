<?php
/**
 * deleteBookingCalendar.php
 * Hard-deletes a reservation row (per admin's choice — this does NOT
 * soft-cancel like the Reservations page does). After deleting, the
 * unit's own status is re-synced: if another active reservation still
 * covers it, that wins; otherwise it resets to "Ready for Occupancy"
 * (unless it's flagged Under maintenance, which is left alone).
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
if ($reservationId <= 0) fail('Invalid booking.');

$stmt = $conn->prepare("SELECT unit_id FROM reservation_table WHERE reservation_id = ?");
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existing) fail('Booking not found.');
$unitId = (int) $existing['unit_id'];

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("DELETE FROM reservation_table WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $stmt->close();

    // Re-sync unit status
    $stmt = $conn->prepare("SELECT unit_current_status FROM units_table WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $unit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($unit && $unit['unit_current_status'] !== 'Under maintenance') {
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            SELECT move_in_date, move_out_date FROM reservation_table
            WHERE unit_id = ? AND reservation_status NOT IN ('rejected', 'cancelled')
            ORDER BY move_in_date LIMIT 1
        ");
        $stmt->bind_param("i", $unitId);
        $stmt->execute();
        $remaining = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($remaining) {
            $newStatus = ($remaining['move_in_date'] <= $today && $today <= $remaining['move_out_date'])
                ? 'Occupied' : 'Reserved';
        } else {
            $newStatus = 'Ready for Occupancy';
        }

        $stmt = $conn->prepare("UPDATE units_table SET unit_current_status = ? WHERE unit_id = ?");
        $stmt->bind_param("si", $newStatus, $unitId);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    fail('Could not delete booking: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Booking deleted.']);
