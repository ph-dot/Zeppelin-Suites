<?php
/**
 * saveBookingCalendar.php
 * Creates a booking directly from the Booking Calendar's "+ Add Booking"
 * / click-a-cell flow.
 *
 * reservation_table.inq_id is a required foreign key to inquiry_table
 * (ON DELETE CASCADE), so a manual admin booking can't skip that link.
 * This endpoint transparently creates a matching inquiry_table row
 * (marked as admin-originated, already "approved") and then the
 * reservation row that points to it — so from the admin's side it's
 * still just "fill the form, click Save Booking."
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

$unitId       = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : 0;
$guestName    = trim($_POST['guestName'] ?? '');
$guestEmail   = trim($_POST['guestEmail'] ?? '');
$guestPhone   = trim($_POST['guestPhone'] ?? '');
$startDate    = trim($_POST['startDate'] ?? '');
$endDate      = trim($_POST['endDate'] ?? '');
$inquiryType  = trim($_POST['inquiryType'] ?? 'Unit Reservation'); // Unit Reservation | Lease Inquiry | Resale Inquiry
$priceBasis   = isset($_POST['priceBasis']) ? (float) $_POST['priceBasis'] : 0;
$paymentPct   = isset($_POST['paymentPercentage']) ? (float) $_POST['paymentPercentage'] : 0.50;
$paymentMethod    = trim($_POST['paymentMethod'] ?? 'GCash QR');
$paymentReference = trim($_POST['paymentReference'] ?? '');
$paymentStatus     = trim($_POST['paymentStatus'] ?? 'verified'); // pending review | verified | rejected
$displayStatus     = trim($_POST['status'] ?? 'Reserved'); // Occupied | Reserved (from the calendar UI)

$allowedInquiryTypes = ['Unit Reservation', 'Lease Inquiry', 'Resale Inquiry'];
if (!in_array($inquiryType, $allowedInquiryTypes, true)) {
    $inquiryType = 'Unit Reservation';
}
$allowedPaymentStatus = ['pending review', 'verified', 'rejected'];
if (!in_array($paymentStatus, $allowedPaymentStatus, true)) {
    $paymentStatus = 'verified';
}

if ($unitId <= 0)        fail('Please choose a unit / room.');
if ($guestName === '')   fail('Guest / resident name is required.');
if ($startDate === '' || $endDate === '') fail('Check-in and check-out dates are required.');
if ($endDate < $startDate) fail('End date must be on or after the check-in date.');
if ($paymentReference === '') fail('Payment reference is required.');

// ── Unit must exist and not be under maintenance ────────────
$stmt = $conn->prepare("SELECT unit_type, unit_number, unit_current_status FROM units_table WHERE unit_id = ?");
$stmt->bind_param("i", $unitId);
$stmt->execute();
$unit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$unit) fail('Unit not found.');
if ($unit['unit_current_status'] === 'Under maintenance') {
    fail('This unit is marked Under Maintenance. Update its status on the Units page before booking it.');
}

// ── Conflict check against existing active reservations ─────
$stmt = $conn->prepare("
    SELECT reservation_id FROM reservation_table
    WHERE unit_id = ?
      AND reservation_status NOT IN ('rejected', 'cancelled')
      AND move_in_date IS NOT NULL AND move_out_date IS NOT NULL
      AND ? <= move_out_date AND ? >= move_in_date
");
$stmt->bind_param("iss", $unitId, $startDate, $endDate);
$stmt->execute();
$conflict = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($conflict) fail('This unit already has a booking that overlaps those dates.');

$requiredAmount = round($priceBasis * $paymentPct, 2);
$adminId   = (int) ($_SESSION['user_id'] ?? 0);
$adminRole = 'admin';

$conn->begin_transaction();
try {
    // 1) Matching inquiry row (required by reservation_table's FK)
    $stmt = $conn->prepare("
        INSERT INTO inquiry_table
            (sender_name, sender_email, sender_contact, inquiry_type, Preferred_unit_id,
             message, status, approval_status, approval_requested_at, approved_unit_id, approval_approved_at)
        VALUES (?, ?, ?, ?, ?, ?, 'officially booked', 'approved', NOW(), ?, NOW())
    ");
    $preferredUnitLabel = $unit['unit_type'];
    $message = 'Booking created directly by admin via the Booking Calendar.';
    $stmt->bind_param(
        "ssssssi",
        $guestName, $guestEmail, $guestPhone, $inquiryType, $preferredUnitLabel, $message, $unitId
    );
    $stmt->execute();
    $inqId = $conn->insert_id;
    $stmt->close();

    // 2) The reservation itself
    $stmt = $conn->prepare("
        INSERT INTO reservation_table
            (inq_id, unit_id, client_name, client_email, client_contact,
             inquiry_type, move_in_date, move_out_date,
             price_basis, payment_percentage, required_amount,
             payment_method, payment_reference, payment_proof,
             payment_status, reservation_status,
             payment_verified_at, officially_booked_at, officially_booked_by, officially_booked_by_role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reserved',
                CASE WHEN ? = 'verified' THEN NOW() ELSE NULL END,
                NOW(), ?, ?)
    ");
    $paymentProof = 'ADMIN-MANUAL-ENTRY'; // no file upload in the quick-add flow
    $stmt->bind_param(
        "iissssssdddsssssis",
        $inqId, $unitId, $guestName, $guestEmail, $guestPhone,
        $inquiryType, $startDate, $endDate,
        $priceBasis, $paymentPct, $requiredAmount,
        $paymentMethod, $paymentReference, $paymentProof,
        $paymentStatus, $paymentStatus, $adminId, $adminRole
    );
    $stmt->execute();
    $reservationId = $conn->insert_id;
    $stmt->close();

    // 3) Sync the unit's own status
    $today = date('Y-m-d');
    $newUnitStatus = ($startDate <= $today && $today <= $endDate) ? 'Occupied' : 'Reserved';
    $stmt = $conn->prepare("UPDATE units_table SET unit_current_status = ? WHERE unit_id = ?");
    $stmt->bind_param("si", $newUnitStatus, $unitId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    fail('Could not save booking: ' . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'message' => 'Booking saved.',
    'booking' => [
        'id'         => $reservationId,
        'guestName'  => $guestName,
        'email'      => $guestEmail,
        'phone'      => $guestPhone,
        'unitType'   => $unit['unit_type'],
        'roomNumber' => $unit['unit_number'],
        'unitId'     => $unitId,
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'status'     => $displayStatus === 'Occupied' ? 'Occupied' : 'Reserved',
    ],
]);
