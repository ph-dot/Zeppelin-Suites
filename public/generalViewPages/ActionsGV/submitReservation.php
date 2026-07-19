<?php
require_once __DIR__ . '/../../php_files/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$reservation_token = trim($_POST['reservation_token'] ?? '');
$payment_percentage = floatval($_POST['payment_percentage'] ?? 0);
$payment_reference = trim($_POST['payment_reference'] ?? '');
$move_in_date = trim($_POST['move_in_date'] ?? '');
$move_out_date = trim($_POST['move_out_date'] ?? '');

if ($reservation_token === '') {
    die("Missing reservation token.");
}

if (!in_array($payment_percentage, [0.35, 0.50])) {
    die("Invalid payment percentage.");
}

if ($payment_reference === '') {
    die("GCash reference number is required.");
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
        u.base_rate,
        u.lease_rate,
        u.unit_current_status

    FROM inquiry_table i
    INNER JOIN units_table u 
        ON i.approved_unit_id = u.unit_id

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
//lock the unit
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
//Check availability again while locked
    if (
        !$lockedUnit ||
        !in_array(
            $lockedUnit['unit_current_status'],
            [
                'Ready for Occupancy',
                'Resale'
            ]
        )
    ) {

        throw new Exception(
            "This unit is no longer available."
        );

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

    //upload payment proof
    if (
        !isset($_FILES['payment_proof']) ||
        $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK
    ) {
        throw new Exception(
            "Payment proof is required."
        );
    }
    $allowed_extensions =
        ['jpg','jpeg','png','pdf'];
    $file_name =
        $_FILES['payment_proof']['name'];
    $file_tmp =
        $_FILES['payment_proof']['tmp_name'];
    $file_size =
        $_FILES['payment_proof']['size'];
    $file_ext =
        strtolower(
            pathinfo(
                $file_name,
                PATHINFO_EXTENSION
            )
        );

    if (!in_array($file_ext, $allowed_extensions)) {

        throw new Exception(
            "Invalid file type."
        );
    }

    if ($file_size > 10 * 1024 * 1024) {
        throw new Exception(
            "File too large."
        );

    }

    $upload_dir =__DIR__ .'/../../uploads/payment_proofs/';

    if (!is_dir($upload_dir)) {
        mkdir(
            $upload_dir,
            0777,
            true
        );

    }

    $new_file_name =
        'payment_' .
        $data['inq_id'] .
        '_' .
        time() .
        '_' .
        bin2hex(random_bytes(6)) .
        '.' .
        $file_ext;


    $upload_path =
        $upload_dir .
        $new_file_name;



    if (!move_uploaded_file(
        $file_tmp,
        $upload_path
    )) {

        throw new Exception(
            "Failed to upload payment proof."
        );

    }


    $db_file_path =
        'uploads/payment_proofs/' .
        $new_file_name;

//insert reservation
    $insertSql = "
        INSERT INTO reservation_table (

            inq_id,
            unit_id,
            client_name,
            client_email,
            client_contact,
            inquiry_type,
            resident_type,
            transaction_type,
            reservation_type,
            move_in_date,
            move_out_date,
            price_basis,
            payment_percentage,
            required_amount,
            payment_method,
            payment_reference,
            payment_proof,
            payment_status,
            reservation_status

        )

        VALUES (

            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'GCash QR',
            ?,
            ?,
            'pending review',
            'submitted'

        )
    ";


    $insertStmt =
        $conn->prepare($insertSql);


    $insertStmt->bind_param(

        "iisssssssssdddss",

        $data['inq_id'],
        $data['unit_id'],
        $data['sender_name'],
        $data['sender_email'],
        $data['sender_contact'],
        $data['inquiry_type'],
        $resident_type,
        $transaction_type,
        $reservation_type,
        $move_in_date,
        $move_out_date,
        $price_basis,
        $payment_percentage,
        $required_amount,
        $payment_reference,
        $db_file_path

    );


    if (!$insertStmt->execute()) {

        throw new Exception(
            "Failed to save reservation."
        );

    }


    $insertStmt->close();

//update unit status to "On Hold"
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