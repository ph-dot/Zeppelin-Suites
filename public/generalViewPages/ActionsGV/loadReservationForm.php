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

        u.unit_id,
        u.unit_type,
        u.unit_number,
        u.base_rate,
        u.lease_rate,
        u.unit_current_status,

        owner.full_name AS owner_name,
        owner.email AS owner_email
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

if ($data['approval_status'] !== 'approved') {
    die("This inquiry is not approved for reservation.");
}

if (!empty($data['reservation_token_expires_at']) && strtotime($data['reservation_token_expires_at']) < time()) {
    die("This reservation link has expired.");
}

$showing_submission_result =
    (isset($_GET['submitted']) && $_GET['submitted'] == '1') ||
    (isset($_GET['already_submitted']) && $_GET['already_submitted'] == '1');

if (
    !$showing_submission_result &&
    !in_array($data['unit_current_status'], ['Ready for Occupancy', 'Resale'])
) {
    die("This unit is no longer available for reservation.");
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
    $price_basis = (float)$data['base_rate'];
    $price_label = "Selling Price";
    $transaction_type = "Unit Resale";
    $resident_type = "Buyer";
    $reservation_type = "Unit Purchase";
    $is_lease = false;

} else {
    die("This reservation form is only available for Unit Reservation, Lease Inquiry, or Resale Inquiry. Current inquiry type: " . htmlspecialchars($data['inquiry_type']));
}
?>