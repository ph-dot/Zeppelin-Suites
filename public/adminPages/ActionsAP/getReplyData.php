<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

function replyClean($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function replyPeso($value) {
    if ($value === null || $value === '') {
        return '—';
    }

    return '₱' . number_format((float)$value, 2);
}

$inq_id = isset($_GET['inq_id']) ? (int)$_GET['inq_id'] : 0;

if ($inq_id <= 0) {
    die("Invalid inquiry ID.");
}

$sql = "SELECT 
            i.inq_id,
            i.sender_name,
            i.sender_email,
            i.sender_contact,
            i.inquiry_type,
            i.Preferred_unit_id,
            i.preferred_move_in_time,
            i.lease_duration,
            i.message,
            i.status,
            i.approval_status,
            i.approved_unit_id,
            i.approval_approved_at,
            i.reservation_token,
            DATE_FORMAT(i.approval_approved_at, '%b %d, %Y %h:%i %p') AS approved_at_display,

            u.unit_number AS approved_unit_number,
            u.unit_type AS approved_unit_type,
            u.lease_rate AS approved_lease_rate,

            owner.full_name AS approved_owner_name

        FROM Inquiry_table i

        LEFT JOIN units_table u
            ON i.approved_unit_id = u.unit_id

        LEFT JOIN owner_approval_requests r
            ON r.inq_id = i.inq_id
            AND r.unit_id = i.approved_unit_id
            AND r.request_status = 'approved'

        LEFT JOIN users_table owner
            ON r.unit_owner_id = owner.user_id

        WHERE i.inq_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $inq_id);
$stmt->execute();
$result = $stmt->get_result();
$inquiry = $result->fetch_assoc();

if (!$inquiry) {
    die("Inquiry not found.");
}

$sender_name = $inquiry['sender_name'] ?? 'Client';
$sender_email = $inquiry['sender_email'] ?? '';
$sender_contact = $inquiry['sender_contact'] ?? '';
$inquiry_type = $inquiry['inquiry_type'] ?? 'Inquiry';
$preferred_unit = $inquiry['Preferred_unit_id'] ?? '—';
$preferred_move_in_time = $inquiry['preferred_move_in_time'] ?? '—';
$lease_duration = $inquiry['lease_duration'] ?? '—';
$message = $inquiry['message'] ?? '—';

$status = strtolower($inquiry['status'] ?? 'pending');
$approval_status = strtolower($inquiry['approval_status'] ?? 'not_requested');

$approved_unit_number = $inquiry['approved_unit_number'] ?? '';
$approved_unit_type = $inquiry['approved_unit_type'] ?? '';
$approved_owner_name = $inquiry['approved_owner_name'] ?? '';
$approved_rate = $inquiry['approved_lease_rate'] ?? null;
$approved_at = $inquiry['approved_at_display'] ?? '';

$avatar = strtoupper(substr(trim($sender_name), 0, 1));
if ($avatar === '') {
    $avatar = '?';
}

$is_general = stripos($inquiry_type, 'general') !== false || stripos($inquiry_type, 'other') !== false;
$is_lease_flow = in_array( $inquiry_type, ['Unit Reservation', 'Lease Inquiry'], true);

$unit_display = $preferred_unit ?: '—';
$owner_display = '—';
$rate_display = '—';
$lease_display = $lease_duration ?: '—';

$status_badge_text = 'Ready to Reply';
$status_badge_class = 'text-slate-700 bg-slate-50 border-slate-200';

$reply_subject = 'Inquiry Update - Zeppelin Suites';

$is_resale = strtolower(trim($inquiry_type)) === 'resale inquiry';

if ($approval_status === 'approved') {
    $unit_display = trim($approved_unit_number . ' - ' . $approved_unit_type);

    if ($unit_display === '-') {
        $unit_display = $preferred_unit;
    }

    $owner_display = $approved_owner_name ?: 'Unit Owner';
    $rate_display = $is_resale ? replyPeso($approved_rate) : (replyPeso($approved_rate) . ' / month');
    $reservation_form_link = "";

    if (!empty($inquiry['reservation_token'])) {
        $reservation_form_link = "http://localhost/Zeppelin-Suites/public/generalViewPages/reservationform.php?token=" . urlencode($inquiry['reservation_token']);
    }
    $status_badge_text = 'Ready to Send';
    $status_badge_class = 'text-emerald-700 bg-emerald-50 border-emerald-100';
    $reply_subject = 'Reservation Request Approved - Zeppelin Suites';

    $lease_info_line = $is_resale ? "" : "\n    Requested Lease Duration: {$lease_display}";
    $rate_label = $is_resale ? "Selling Price" : "Rate";

    $email_body = "Hello {$sender_name},

    Thank you for reaching out to Zeppelin Suites.

    We are pleased to inform you that a unit owner has approved a unit for your reservation inquiry.

    Here are the approved unit details:

    Unit: {$unit_display}
    Unit Owner: {$owner_display}
    {$rate_label}: {$rate_display}{$lease_info_line}
    Approved On: " . ($approved_at ?: '—') . "

    To proceed with reserving this unit, please complete the reservation form through the link below:

    {$reservation_form_link}

    Kindly complete the form only if you wish to proceed with the reservation. This form will allow us to collect your required reservation details, identification files, and proof of payment for admin review.

    Please note that completing the form starts the reservation process, but the reservation will only be finalized after verification and confirmation by the admin.

    Thank you, and we look forward to assisting you.

    Best regards,
    Zeppelin Suites Team";

} elseif ($approval_status === 'requested' || $status === 'onhold') {
    $unit_display = $preferred_unit ?: '—';
    $owner_display = 'Waiting for owner approval';
    $rate_display = '—';

    $status_badge_text = 'Waiting Approval';
    $status_badge_class = 'text-blue-700 bg-blue-50 border-blue-100';
    $reply_subject = 'Reservation Request Update - Zeppelin Suites';

    $email_body = "Hello {$sender_name},

Thank you for reaching out to Zeppelin Suites.

We have received your reservation inquiry and are currently coordinating with available unit owners for your selected unit preference.

Inquiry details:

Preferred Unit: {$unit_display}
Requested Lease Duration: {$lease_display}

At the moment, your request is still waiting for unit owner approval. We will update you once a unit owner confirms availability.

Thank you for your patience, and we look forward to assisting you.

Best regards,
Zeppelin Suites Team";

} elseif ($approval_status === 'declined') {
    $unit_display = $preferred_unit ?: '—';
    $owner_display = 'No approval received';
    $rate_display = '—';

    $status_badge_text = 'No Approved Unit';
    $status_badge_class = 'text-red-700 bg-red-50 border-red-100';
    $reply_subject = 'Reservation Request Update - Zeppelin Suites';

    $email_body = "Hello {$sender_name},

Thank you for reaching out to Zeppelin Suites.

We would like to update you regarding your reservation inquiry.

Inquiry details:

Preferred Unit: {$unit_display}
Requested Lease Duration: {$lease_display}

At this time, we were unable to secure approval from the available unit owner/s for this request. You may still inquire about other available unit types or future availability, and we will be glad to assist you.

Thank you for your understanding.

Best regards,
Zeppelin Suites Team";

} else {
    $unit_display = $preferred_unit ?: '—';
    $owner_display = 'Not yet requested';
    $rate_display = '—';

    $status_badge_text = 'Ready to Reply';
    $status_badge_class = 'text-slate-700 bg-slate-50 border-slate-200';
    $reply_subject = 'Inquiry Update - Zeppelin Suites';

    $email_body = "Hello {$sender_name},

Thank you for reaching out to Zeppelin Suites.

We have received your inquiry.

Inquiry details:

Inquiry Type: {$inquiry_type}
Preferred Unit: {$unit_display}
Requested Lease Duration: {$lease_display}

Our team will review your request and assist you with the next steps.

Best regards,
Zeppelin Suites Team";
}

$stmt->close();
?>