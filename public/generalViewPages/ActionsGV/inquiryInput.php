<?php

session_start();

require_once __DIR__ . '/../../php_files/db.php';

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

if ($preferred_move_in_time !== null) {
    // Normalize Unicode en-dash, em-dash, or charset-corrupted artifacts to standard ASCII hyphen
    $preferred_move_in_time = str_replace(['–', '—', '?"', 'â€“'], '-', $preferred_move_in_time);
}

$lease_duration = isset($_POST['lease_duration'])
    ? trim($_POST['lease_duration'])
    : null;

$message = trim($_POST['Message'] ?? '');

$status = 'pending';

$validInquiryTypes = [
    'Unit Lease / Rental Reservation',
    'Buy / Purchase a Unit (Resale)',
    'Buy / Purchase a Unit',
    'General Inquiry & Amenities',
    'Other Concerns'
];

$validUnits = [
    'Studio Type A',
    'Studio Type B',
    'One Bedroom',
    'Two Bedroom'
];

$validMoveInTimes = [
    'Immediately (Within 30 days)',
    'Next Month (1-2 months)',
    'In 2-3 Months',
    'In 3-6 Months',
    'Flexible / Not sure yet'
];

$validLeaseDurations = [
    '3 months',
    '6 months',
    '1 year',
    '2 years',
    'Longer than 2 years',
    'Not sure yet'
];

$inqLower = strtolower($inquiry_type);

$isLeaseType = in_array($inquiry_type, ['Unit Reservation', 'Lease Inquiry'], true)
    || strpos($inqLower, 'lease') !== false
    || strpos($inqLower, 'rental') !== false
    || strpos($inqLower, 'unit reservation') !== false;

$isResaleType = ($inquiry_type === 'Resale Inquiry')
    || strpos($inqLower, 'resale') !== false
    || strpos($inqLower, 'buy') !== false
    || strpos($inqLower, 'purchase') !== false;

$needsLeaseDetails = $isLeaseType;
$needsUnitPreference = $isLeaseType || $isResaleType;

// Server-side security validation for Email
$isValidEmail = false;
$emailErrorMsg = null;

if (!filter_var($sender_email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $sender_email)) {
    $emailErrorMsg = 'Please provide a valid email address (e.g. name@domain.com).';
} else {
    $parts = explode('@', $sender_email);
    $user = strtolower($parts[0]);
    $domain = strtolower($parts[1]);

    $typoMap = [
        'gmaidla.com' => 'gmail.com', 'gmaild.com' => 'gmail.com', 'gamil.com' => 'gmail.com',
        'gmial.com'   => 'gmail.com', 'gmaill.com' => 'gmail.com', 'gmai.com'  => 'gmail.com',
        'gmal.com'    => 'gmail.com', 'gmeil.com'  => 'gmail.com', 'gmaio.com' => 'gmail.com',
        'gmail.co'    => 'gmail.com', 'gmaill.co'  => 'gmail.com', 'yaho.com'  => 'yahoo.com',
        'yahooo.com'  => 'yahoo.com', 'yaho.co'    => 'yahoo.com', 'outlok.com' => 'outlook.com',
        'hotmial.com' => 'hotmail.com','iclou.com'  => 'icloud.com'
    ];

    if (isset($typoMap[$domain])) {
        $emailErrorMsg = 'Please provide a valid email domain provider (e.g. name@gmail.com).';
    } else {
        // Levenshtein typo check
        $majors = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'];
        foreach ($majors as $m) {
            if ($domain !== $m && levenshtein($domain, $m) <= 2) {
                $emailErrorMsg = 'Please provide a valid email domain provider (e.g. name@gmail.com).';
                break;
            }
        }
        // Check DNS MX record if no typo detected yet
        if (!$emailErrorMsg && !checkdnsrr($domain, 'MX')) {
            $emailErrorMsg = 'Please provide a valid email domain provider (e.g. name@gmail.com).';
        }
    }

    if ($emailErrorMsg === null) {
        $isValidEmail = true;
    }
}

// Server-side security validation for Phone
$cleanedPhone = preg_replace('/[\s\-\(\)]/', '', $sender_contact);
$digitsOnly = preg_replace('/\D/', '', $cleanedPhone);
$hasPlus = (strpos($cleanedPhone, '+') === 0);

$isValidPhone = false;
$phoneErrorMsg = null;

if (!preg_match('/^[0-9+\s\-()]+$/', $sender_contact) || strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
    $phoneErrorMsg = 'Please provide a valid phone number (e.g. 09XX-XXX-XXXX or +63 9XX...).';
} elseif (preg_match('/(.)\1{5,}/', $digitsOnly)) {
    $phoneErrorMsg = 'Please enter a valid, active phone number (repeated dummy digits detected).';
} elseif (strpos($digitsOnly, '12345678') !== false || strpos($digitsOnly, '87654321') !== false || strpos($digitsOnly, '01234567') !== false) {
    $phoneErrorMsg = 'Please enter a valid, active phone number (sequential test pattern detected).';
} else {
    if ($hasPlus) {
        if (strpos($digitsOnly, '63') === 0) {
            $isValidPhone = (strlen($digitsOnly) === 12 && strpos($digitsOnly, '639') === 0);
            if (!$isValidPhone) {
                $phoneErrorMsg = 'Philippine mobile number with +63 must be 12 digits (e.g. +63 917 123 4567).';
            }
        } else {
            $isValidPhone = (strlen($digitsOnly) >= 8 && strlen($digitsOnly) <= 15);
        }
    } else {
        if (strpos($digitsOnly, '09') === 0) {
            if (strlen($digitsOnly) !== 11) {
                $phoneErrorMsg = 'Philippine mobile number must be 11 digits (e.g. 0917-123-4567).';
            } else {
                $prefix4 = substr($digitsOnly, 0, 4);
                if (in_array($prefix4, ['0900', '0901', '0902', '0903', '0904'], true)) {
                    $phoneErrorMsg = "Prefix '{$prefix4}' is not a valid Philippine mobile network prefix.";
                } else {
                    $isValidPhone = true;
                }
            }
        } elseif (strpos($digitsOnly, '639') === 0) {
            $isValidPhone = (strlen($digitsOnly) === 12);
            if (!$isValidPhone) {
                $phoneErrorMsg = 'Philippine mobile number must be 12 digits (e.g. 639171234567).';
            }
        } elseif (strpos($digitsOnly, '0') === 0) {
            $isValidPhone = (strlen($digitsOnly) >= 9 && strlen($digitsOnly) <= 11);
            if (!$isValidPhone) {
                $phoneErrorMsg = 'Landline number must be 9–11 digits including area code.';
            }
        } else {
            $phoneErrorMsg = 'Please enter a valid phone number (e.g. 0917-123-4567 or +63 917 123 4567).';
        }
    }
}

$errorMessage = null;

if ($sender_name === '') {
    $errorMessage = 'Please provide your full name.';
} elseif (!$isValidEmail) {
    $errorMessage = $emailErrorMsg ?: 'Please provide a valid email address (e.g. name@domain.com).';
} elseif (!$isValidPhone) {
    $errorMessage = $phoneErrorMsg ?: 'Please provide a valid phone number (e.g. 0917-123-4567 or +63 917 123 4567).';
} elseif (!in_array($inquiry_type, $validInquiryTypes, true)) {
    $errorMessage = 'Please select a valid inquiry type.';
} elseif ($message === '') {
    $errorMessage = 'Please enter your inquiry message.';
} elseif ($needsUnitPreference && !in_array($preferred_unit_id, $validUnits, true)) {
    $errorMessage = 'Please select a preferred unit type.';
} elseif (
    $needsLeaseDetails &&
    (
        !in_array($preferred_move_in_time, $validMoveInTimes, true) ||
        !in_array($lease_duration, $validLeaseDurations, true)
    )
) {
    $errorMessage = 'Please select your preferred move-in time and lease duration.';
}

if ($errorMessage !== null) {
    $_SESSION['error_message'] = $errorMessage;
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