<?php
require_once __DIR__ . '/../../php_files/db.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid reservation link.");
}

$token = trim($_GET['token']);

$sql = "
    SELECT 
        i.inq_id,
        i.sender_name,
        i.sender_email,
        i.sender_contact,
        i.inquiry_type,
        i.lease_duration,
        i.approval_status,
        i.approved_unit_id,
        i.reservation_token_expires_at,
        i.preferred_move_in_time, 

        u.unit_id,
        u.unit_type,
        u.unit_number,
        u.sqm,
        u.floor_number,
        u.listing_type,
        u.stay_category,
        u.lease_rate,
        u.unit_current_status,

        owner.full_name AS owner_name,
        owner.email AS owner_email,
        owner.contact AS owner_contact,
        owner.gcash_QR AS owner_gcash_qr
    FROM inquiry_table i
    INNER JOIN units_table u ON i.approved_unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    WHERE i.reservation_token = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Reservation link not found.");
}

$data = $result->fetch_assoc();
$stmt->close();

// Check if unit owner has a valid uploaded GCash QR code
$owner_has_qr = false;
$owner_qr_path = '';
if (!empty($data['owner_gcash_qr'])) {
    $qrClean = ltrim($data['owner_gcash_qr'], '/');
    $qrFullPath = __DIR__ . '/../../' . $qrClean;
    if (file_exists($qrFullPath)) {
        $owner_has_qr = true;
        $owner_qr_path = '../' . $qrClean;
    }
}

// If this inquiry already has a reservation on file, the token has already
// been used to submit — always send the visitor to the confirmation page
// instead of showing the (now stale) form again, no matter how they got here.
$alreadySubmittedStmt = $conn->prepare("
    SELECT reservation_id
    FROM reservation_table
    WHERE inq_id = ?
    LIMIT 1
");
$alreadySubmittedStmt->bind_param("i", $data['inq_id']);
$alreadySubmittedStmt->execute();
$alreadySubmittedResult = $alreadySubmittedStmt->get_result();
$already_has_reservation = $alreadySubmittedResult->num_rows > 0;
$alreadySubmittedStmt->close();

if ($already_has_reservation) {
    header("Location: reservationConfirmation.html?token=" . urlencode($token));
    exit();
}

if ($data['approval_status'] !== 'approved') {
    die("This inquiry is not approved for reservation.");
}

if (!empty($data['reservation_token_expires_at']) && strtotime($data['reservation_token_expires_at']) < time()) {
    die("This reservation link has expired.");
}

$showing_submission_result =
    (isset($_GET['submitted']) && $_GET['submitted'] == '1') ||
    (isset($_GET['already_submitted']) && $_GET['already_submitted'] == '1');

$inquiry_type_for_gate = strtolower(trim($data['inquiry_type']));
$is_lease_for_gate = in_array(
    $inquiry_type_for_gate,
    ['lease inquiry', 'unit reservation'],
    true
);

if (!$showing_submission_result) {
    if ($data['unit_current_status'] === 'Under maintenance') {
        die("This unit is currently unavailable (under maintenance).");
    }

    // Resale is a one-time sale, so the blanket status flag is the right
    // check. Lease units can carry several non-overlapping reservations
    // over time, so their real availability is decided further down by the
    // per-date calendar (blocked_ranges), not this unit-wide flag.
    if (
        !$is_lease_for_gate &&
        !in_array($data['unit_current_status'], ['Ready for Occupancy', 'Resale'])
    ) {
        die("This unit is no longer available for reservation.");
    }
}

$inquiry_type = strtolower(trim($data['inquiry_type']));

$inquiry_type = strtolower(trim($data['inquiry_type']));

if (
    $inquiry_type === 'lease inquiry' ||
    $inquiry_type === 'unit reservation'
) {
    $price_basis = (float)$data['lease_rate'];
    $price_label = "Monthly Lease Rate";
    $transaction_type = "Unit Leasing";
    $resident_type = "New Tenant";
    $reservation_type = "New Lease";
    $is_lease = true;

} elseif ($inquiry_type === 'resale inquiry') {
    $price_basis = (float)$data['lease_rate'];
    $price_label = "Selling Price";
    $transaction_type = "Unit Resale";
    $resident_type = "Buyer";
    $reservation_type = "Unit Purchase";
    $is_lease = false;

} else {
    die("This reservation form is only available for Unit Reservation, Lease Inquiry, or Resale Inquiry. Current inquiry type: " . htmlspecialchars($data['inquiry_type']));
}

// Lease duration (months) — parse from inquiry, default to 12
$lease_months = (int)preg_replace('/[^0-9]/', '', $data['lease_duration']);
if ($lease_months <= 0) {
    $lease_months = 12;
}

// Furnishing default
$data['furnishing'] = !empty($data['furnishing']) ? $data['furnishing'] : 'Fully Furnished.';

// Expiration time frame
$token_expires_at = !empty($data['reservation_token_expires_at']) ? $data['reservation_token_expires_at'] : date('Y-m-d H:i:s', strtotime('+30 days'));
$token_expires_date = date('Y-m-d', strtotime($token_expires_at));

// Move-in window: allow booking from today up to 30 days out
$move_in_min = date('Y-m-d');
$move_in_max = date('Y-m-d', strtotime('+30 days'));

// Existing reservations on this unit that still hold the unit (i.e. not
// cancelled or rejected) — used to block already-occupied dates on the
// reservation form's calendar so two bookings of the SAME kind can't overlap.
// A Resale (outright unit purchase) and a Lease (temporary occupancy) are
// different transaction categories, so a Resale appointment date must never
// block a Lease calendar and vice versa — only compare like with like.
$blocked_ranges = [];
if ($is_lease) {
    $blockedTypeFilter = "inquiry_type IN ('Lease Inquiry', 'Unit Reservation')";
} else {
    $blockedTypeFilter = "inquiry_type = 'Resale Inquiry'";
}

$blockedStmt = $conn->prepare("
    SELECT move_in_date, move_out_date
    FROM reservation_table
    WHERE unit_id = ?
      AND reservation_status NOT IN ('cancelled', 'rejected')
      AND move_in_date IS NOT NULL
      AND $blockedTypeFilter
");
if ($blockedStmt) {
    $blockedStmt->bind_param("i", $data['unit_id']);
    $blockedStmt->execute();
    $blockedResult = $blockedStmt->get_result();
    while ($row = $blockedResult->fetch_assoc()) {
        $blocked_ranges[] = [
            'start' => $row['move_in_date'],
            'end'   => $row['move_out_date'] ?: $row['move_in_date'],
        ];
    }
    $blockedStmt->close();
}
?>