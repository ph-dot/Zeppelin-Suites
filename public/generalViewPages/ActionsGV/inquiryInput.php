<?php

session_start();

include(
    $_SERVER['DOCUMENT_ROOT'] .
    "/Zeppelin-Suites/public/php_files/db.php"
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../contact.php");
    exit();
}

$sender_name = trim($_POST['sender_name'] ?? '');
$sender_email = trim($_POST['sender_email'] ?? '');
$sender_contact = trim($_POST['sender_contact'] ?? '');
$inquiry_type = trim($_POST['inquiry_type'] ?? '');

$preferred_unit_id = isset($_POST['Preferred_unit_id'])
    ? trim($_POST['Preferred_unit_id'])
    : null;

$preferred_move_in_time =
    isset($_POST['preferred_move_in_time'])
        ? trim($_POST['preferred_move_in_time'])
        : null;

$lease_duration = isset($_POST['lease_duration'])
    ? trim($_POST['lease_duration'])
    : null;

$message = trim($_POST['Message'] ?? '');

$status = 'pending';

$validInquiryTypes = [
    'Unit Reservation',
    'Resale Inquiry',
    'Lease Inquiry',
    'General Inquiry',
    'Others'
];

$validUnits = [
    'Studio Type A',
    'Studio Type B',
    'One Bedroom',
    'Two Bedroom'
];

$validMoveInTimes = [
    'Immediately',
    'Within 1 month',
    'Within 1–3 months',
    'Within 3–6 months',
    'Not sure yet'
];

$validLeaseDurations = [
    '3 months',
    '6 months',
    '1 year',
    '2 years',
    'Longer than 2 years',
    'Not sure yet'
];

$needsLeaseDetails = in_array(
    $inquiry_type,
    ['Unit Reservation', 'Lease Inquiry'],
    true
);

$needsUnitPreference =
    $needsLeaseDetails ||
    $inquiry_type === 'Resale Inquiry';

$isInvalid =
    $sender_name === '' ||
    !filter_var($sender_email, FILTER_VALIDATE_EMAIL) ||
    !in_array($inquiry_type, $validInquiryTypes, true) ||
    $message === '';

if (
    $needsUnitPreference &&
    !in_array($preferred_unit_id, $validUnits, true)
) {
    $isInvalid = true;
}

if (
    $needsLeaseDetails &&
    (
        !in_array(
            $preferred_move_in_time,
            $validMoveInTimes,
            true
        ) ||
        !in_array(
            $lease_duration,
            $validLeaseDurations,
            true
        )
    )
) {
    $isInvalid = true;
}

if ($isInvalid) {
    $_SESSION['error_message'] =
        'Please complete all required inquiry fields.';

    header("Location: ../contact.php");
    exit();
}

if (!$needsUnitPreference) {
    $preferred_unit_id = null;
}

if (!$needsLeaseDetails) {
    $preferred_move_in_time = null;
    $lease_duration = null;
}

$sql = "
    INSERT INTO inquiry_table (
        sender_name,
        sender_email,
        sender_contact,
        inquiry_type,
        Preferred_unit_id,
        preferred_move_in_time,
        lease_duration,
        message,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error_message'] =
        'There was an issue submitting the form. Please try again later.';

    header("Location: ../contact.php");
    exit();
}

$stmt->bind_param(
    'sssssssss',
    $sender_name,
    $sender_email,
    $sender_contact,
    $inquiry_type,
    $preferred_unit_id,
    $preferred_move_in_time,
    $lease_duration,
    $message,
    $status
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: ../inquiryConfirmation.html");
    exit();
}

$stmt->close();
$conn->close();

$_SESSION['error_message'] =
    'There was an issue submitting the form. Please try again later.';

header("Location: ../contact.php");
exit();