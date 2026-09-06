<?php
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/owner_notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$reservation_token = trim($_POST['reservation_token'] ?? '');
$payment_percentage = floatval($_POST['payment_percentage'] ?? 0);
$payment_reference = trim($_POST['payment_reference'] ?? '');
$declared_amount = floatval($_POST['declared_amount'] ?? 0);
$move_in_date = trim($_POST['move_in_date'] ?? '');
$move_out_date = trim($_POST['move_out_date'] ?? '');
$lease_duration = trim($_POST['lease_duration'] ?? '');

$payment_method = trim($_POST['payment_method'] ?? 'GCash QR');
if (!in_array($payment_method, ['GCash QR', 'In-House'], true)) {
    $payment_method = 'GCash QR';
}

$client_sex = trim($_POST['client_sex'] ?? '');
$client_age = !empty($_POST['client_age']) ? (int)$_POST['client_age'] : null;
$client_nationality = trim($_POST['client_nationality'] ?? '');

$lease_signing_date = !empty($_POST['lease_signing_date']) ? trim($_POST['lease_signing_date']) : null;
$is_flexible_signing = isset($_POST['is_flexible_signing']) && $_POST['is_flexible_signing'] == '1' ? 1 : 0;
if ($is_flexible_signing) {
    $lease_signing_date = null;
}

$client_remarks = trim($_POST['remarks'] ?? '');
if (mb_strlen($client_remarks) > 500) {
    $client_remarks = mb_substr($client_remarks, 0, 500);
}

if ($reservation_token === '') {
    die("Missing reservation token.");
}

if (!in_array($payment_percentage, [0.35, 0.50, 0.75, 1.00])) {
    die("Invalid payment percentage.");
}

if ($payment_reference === '') {
    $payment_reference = 'N/A';
}

if ($move_in_date === '') {
    die("Move-in date / appointment date is required.");
}

$sql = "
    SELECT 
        i.inq_id,
        i.sender_name,
        i.sender_email,
        i.sender_contact,
        i.inquiry_type,
        i.approval_status,
        i.approved_unit_id,
        i.reservation_token_expires_at,
        u.unit_id,
        u.unit_number,
        u.lease_rate,
        u.unit_current_status,
        owner.full_name AS owner_name,
        owner.email AS owner_email

    FROM inquiry_table i
    INNER JOIN units_table u 
        ON i.approved_unit_id = u.unit_id
    LEFT JOIN users_table owner
        ON u.unit_owner_id = owner.user_id

    WHERE i.reservation_token = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $reservation_token);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid reservation link.");
}

$data = $result->fetch_assoc();
$stmt->close();

if ($data['approval_status'] !== 'approved') {
    die("This inquiry is not approved for reservation.");
}

if (
    !empty($data['reservation_token_expires_at']) &&
    strtotime($data['reservation_token_expires_at']) < time()
) {
    die("This reservation link has expired.");
}

$inquiry_type_normalized =
    strtolower(trim($data['inquiry_type']));

if (
    $inquiry_type_normalized === 'unit reservation' ||
    $inquiry_type_normalized === 'lease inquiry'
) {
    $price_basis = (float)$data['lease_rate'];
    $resident_type = "New Tenant";
    $transaction_type = "Unit Leasing";
    $reservation_type = "New Lease";

    if ($move_out_date === '') {
        die("Move-out date is required for lease reservations.");
    }
} elseif ($inquiry_type_normalized === 'resale inquiry') {
    $price_basis = (float)$data['base_rate'];
    $resident_type = "Buyer";
    $transaction_type = "Unit Resale";
    $reservation_type = "Unit Purchase";

    $move_out_date = null;
} else {
    die("Invalid inquiry type.");
}
$required_amount =
    $price_basis * $payment_percentage;
/*
|--------------------------------------------------------------------------
| START TRANSACTION + LOCK UNIT
|--------------------------------------------------------------------------
*/
$conn->begin_transaction();

try {
//lock the unit row so concurrent submissions for the same unit are serialized
    $lockUnitSql = "
        SELECT unit_current_status FROM units_table
        WHERE unit_id = ?
        FOR UPDATE
    ";
    $lockUnitStmt = $conn->prepare($lockUnitSql);
    $lockUnitStmt->bind_param("i", $data['unit_id']);
    $lockUnitStmt->execute();

    $lockedUnit =
        $lockUnitStmt
        ->get_result()
        ->fetch_assoc();
    $lockUnitStmt->close();

    $is_lease = (
        $inquiry_type_normalized === 'unit reservation' ||
        $inquiry_type_normalized === 'lease inquiry'
    );

    if (!$lockedUnit || $lockedUnit['unit_current_status'] === 'Under maintenance') {
        throw new Exception(
            "This unit is currently unavailable."
        );
    }

    if ($is_lease) {
        // Lease-type units can carry several non-overlapping reservations over
        // time (that's what the reservation form's calendar already shows).
        // Real availability for a lease is decided by whether the CHOSEN dates
        // overlap an existing active reservation — not by a single unit-wide
        // status flag, which would incorrectly block unrelated future dates.
        $overlapSql = "
            SELECT reservation_id
            FROM reservation_table
            WHERE unit_id = ?
              AND reservation_status NOT IN ('cancelled', 'rejected')
              AND inquiry_type IN ('Lease Inquiry', 'Unit Reservation')
              AND move_in_date IS NOT NULL
              AND move_in_date <= ?
              AND COALESCE(move_out_date, move_in_date) >= ?
            FOR UPDATE
        ";
        $overlapStmt = $conn->prepare($overlapSql);
        $overlapStmt->bind_param(
            "iss",
            $data['unit_id'],
            $move_out_date,
            $move_in_date
        );
        $overlapStmt->execute();
        $overlapResult = $overlapStmt->get_result();

        if ($overlapResult->num_rows > 0) {
            throw new Exception(
                "Those move-in/move-out dates overlap with an existing reservation on this unit. Please go back and choose different dates."
            );
        }
        $overlapStmt->close();
    } else {
        // Resale is a one-time sale — once a buyer is mid-purchase, the whole
        // unit is off the market for everyone else, regardless of "dates".
        if (
            !in_array(
                $lockedUnit['unit_current_status'],
                ['Ready for Occupancy', 'Resale']
            )
        ) {
            throw new Exception(
                "This unit is no longer available."
            );
        }
    }

//check duplicate reservation
    $checkSql = "
        SELECT reservation_id
        FROM reservation_table
        WHERE inq_id = ?
        LIMIT 1
    ";

    $checkStmt =
        $conn->prepare($checkSql);
    $checkStmt->bind_param(
        "i",
        $data['inq_id']
    );
    $checkStmt->execute();
   
    $existingResult =
        $checkStmt->get_result();
    if ($existingResult->num_rows > 0) {
        throw new Exception(
            "Reservation already submitted."
        );
    }
    $checkStmt->close();

//check for a GCash reference number already used on another ACTIVE reservation.
//Rejected/cancelled reservations don't count, so a corrected resubmission
//with the same reference (e.g. after a typo) is still allowed.
    if ($payment_reference !== '' && $payment_reference !== 'N/A') {
        $dupRefSql = "
            SELECT reservation_id
            FROM reservation_table
            WHERE payment_reference = ?
            AND reservation_status NOT IN ('cancelled', 'rejected')
            LIMIT 1
        ";

        $dupRefStmt = $conn->prepare($dupRefSql);
        $dupRefStmt->bind_param("s", $payment_reference);
        $dupRefStmt->execute();

        $dupRefResult = $dupRefStmt->get_result();
        if ($dupRefResult->num_rows > 0) {
            throw new Exception(
                "This GCash reference number has already been used for another reservation. If this is a mistake, please contact Zeppelin Suites administration."
            );
        }
        $dupRefStmt->close();
    }

//compute how the client-declared amount compares to what's actually required
    if ($declared_amount <= 0) {
        $declared_amount = $required_amount;
    }
    $amount_match_status = 'match';
    if (abs($declared_amount - $required_amount) > 0.01) {
        $amount_match_status = $declared_amount < $required_amount ? 'short' : 'over';
    }

    //upload payment proof if paying with GCash QR
    $db_file_path = null;
    if ($payment_method === 'GCash QR') {
        if (
            !isset($_FILES['payment_proof']) ||
            $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK
        ) {
            throw new Exception("Proof of payment upload is required for GCash QR payments.");
        }
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $file_name = $_FILES['payment_proof']['name'];
        $file_tmp  = $_FILES['payment_proof']['tmp_name'];
        $file_size = $_FILES['payment_proof']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_extensions)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and WEBP files are accepted.");
        }

        if ($file_size > 10 * 1024 * 1024) {
            throw new Exception("File too large. Maximum size is 10MB.");
        }

        $upload_dir = __DIR__ . '/../../uploads/payment_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_file_name = 'payment_' . $data['inq_id'] . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
        $upload_path   = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $upload_path)) {
            throw new Exception("Failed to upload payment proof.");
        }

        $db_file_path = 'uploads/payment_proofs/' . $new_file_name;
    } else {
        // Pay In-House
        $db_file_path = 'Pay In-House (During Lease Signing)';
        $payment_reference = 'In-House';
    }

    //insert reservation
    $insertSql = "
        INSERT INTO reservation_table (
            inq_id,
            unit_id,
            client_name,
            client_email,
            client_contact,
            client_sex,
            client_age,
            client_nationality,
            inquiry_type,
            resident_type,
            transaction_type,
            reservation_type,
            move_in_date,
            move_out_date,
            lease_signing_date,
            is_flexible_signing,
            price_basis,
            payment_percentage,
            required_amount,
            payment_method,
            payment_reference,
            declared_amount,
            amount_match_status,
            payment_proof,
            payment_status,
            reservation_status,
            client_remarks
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending review', 'submitted', ?
        )
    ";

    $insertStmt = $conn->prepare($insertSql);
    if (!$insertStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $insertStmt->bind_param(
        "iissssissssssssidddssdsss",
        $data['inq_id'],
        $data['unit_id'],
        $data['sender_name'],
        $data['sender_email'],
        $data['sender_contact'],
        $client_sex,
        $client_age,
        $client_nationality,
        $data['inquiry_type'],
        $resident_type,
        $transaction_type,
        $reservation_type,
        $move_in_date,
        $move_out_date,
        $lease_signing_date,
        $is_flexible_signing,
        $price_basis,
        $payment_percentage,
        $required_amount,
        $payment_method,
        $payment_reference,
        $declared_amount,
        $amount_match_status,
        $db_file_path,
        $client_remarks
    );


    if (!$insertStmt->execute()) {

        throw new Exception(
            "Failed to save reservation."
        );

    }


    $insertStmt->close();

    if ($is_lease && $lease_duration !== '') {
        $updateInqDurationStmt = $conn->prepare("UPDATE inquiry_table SET lease_duration = ? WHERE inq_id = ?");
        if ($updateInqDurationStmt) {
            $updateInqDurationStmt->bind_param("si", $lease_duration, $data['inq_id']);
            $updateInqDurationStmt->execute();
            $updateInqDurationStmt->close();
        }
    }

//update unit status to "On Hold" — only for resale (single-buyer, whole-unit
//sale). Lease units stay as-is: their availability is governed by the
//per-date overlap check above, so other date ranges must remain bookable.
    if (!$is_lease) {
        $updateUnitSql = "
            UPDATE units_table

            SET unit_current_status = 'On Hold'

            WHERE unit_id = ?
        ";


        $updateUnitStmt =
            $conn->prepare($updateUnitSql);


        $updateUnitStmt->bind_param(
            "i",
            $data['unit_id']
        );


        if (!$updateUnitStmt->execute()) {

            throw new Exception(
                "Failed to update unit."
            );

        }


        $updateUnitStmt->close();
    }

//update inquiry status to "reservation submitted"
    $updateInquirySql = "
        UPDATE inquiry_table

        SET status = 'reservation submitted'

        WHERE inq_id = ?
    ";


    $updateInquiryStmt =
        $conn->prepare($updateInquirySql);


    $updateInquiryStmt->bind_param(
        "i",
        $data['inq_id']
    );


    if (!$updateInquiryStmt->execute()) {

        throw new Exception(
            "Failed to update inquiry."
        );

    }


    $updateInquiryStmt->close();

    //commit
    $conn->commit();

    notifyOwnerOfNewReservation(
        $data['owner_email'] ?? '',
        $data['owner_name'] ?? 'Unit Owner',
        $data['unit_number'] ?? '',
        $data['sender_name'] ?? 'A tenant',
        $move_in_date
    );

    header("Location: ../reservationConfirmation.html?token=" .urlencode($reservation_token));
    exit();

} catch (Exception $e) {

    $conn->rollback();
    if (
        isset($upload_path) &&
        file_exists($upload_path)
    ) {
        unlink($upload_path);
    }
    die($e->getMessage());
}
?>