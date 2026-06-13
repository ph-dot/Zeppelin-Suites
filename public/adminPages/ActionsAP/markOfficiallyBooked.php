<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

$admin_id = (int)($_SESSION['user_id'] ?? 0);
$admin_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'admin';

$conn->begin_transaction();

try {
    /*
    Lock the reservation and unit row.
    This helps prevent two admins from booking the same unit at the same time.
    */
    $sql = "
        SELECT 
            r.reservation_id,
            r.inq_id,
            r.unit_id,
            r.payment_status,
            r.reservation_status,
            r.two_valid_ids_status,
            r.tin_number_status,
            r.reservation_agreement_status,
            r.move_in_date,
            r.move_out_date,

            u.unit_current_status
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.reservation_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception("Reservation not found.");
    }

    if ($reservation['payment_status'] !== 'verified') {
        throw new Exception("Payment must be verified before officially booking.");
    }

    if ($reservation['reservation_status'] !== 'requirements completed') {
        throw new Exception("Requirements must be completed before officially booking.");
    }

    if (
        (int)$reservation['two_valid_ids_status'] !== 1 ||
        (int)$reservation['tin_number_status'] !== 1 ||
        (int)$reservation['reservation_agreement_status'] !== 1
    ) {
        throw new Exception("All requirements must be checked before officially booking.");
    }

    $unit_id = (int)$reservation['unit_id'];

    /*
    Double booking protection.
    Check if another reservation for the same unit is already active/reserved.
    */
    $conflictSql = "
        SELECT reservation_id
        FROM reservation_table
        WHERE unit_id = ?
        AND reservation_id != ?
        AND reservation_status IN (
            'submitted',
            'under review',
            'requirements pending',
            'requirements completed',
            'reserved'
        )
        LIMIT 1
        FOR UPDATE
    ";

    $conflictStmt = $conn->prepare($conflictSql);

    if (!$conflictStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $conflictStmt->bind_param("ii", $unit_id, $reservation_id);
    $conflictStmt->execute();
    $conflictResult = $conflictStmt->get_result();
    $conflict = $conflictResult->fetch_assoc();
    $conflictStmt->close();

    if ($conflict) {
        throw new Exception("This unit already has another active reservation. Official booking cannot continue.");
    }

    /*
    Mark reservation as officially reserved.
    */
    $updateReservationSql = "
        UPDATE reservation_table
        SET reservation_status = 'reserved',
            officially_booked_at = NOW(),
            officially_booked_by = ?,
            officially_booked_by_role = ?
        WHERE reservation_id = ?
    ";

    $stmt = $conn->prepare($updateReservationSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isi", $admin_id, $admin_role, $reservation_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update reservation status.");
    }

    $stmt->close();

    /*
    Set unit status to Reserved.
    */
    $updateUnitSql = "
        UPDATE units_table
        SET unit_current_status = 'Reserved'
        WHERE unit_id = ?
    ";

    $stmt = $conn->prepare($updateUnitSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $unit_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update unit status.");
    }

    $stmt->close();

    /*
    Optional: update inquiry status too.
    */
    $updateInquirySql = "
        UPDATE inquiry_table
        SET status = 'officially booked'
        WHERE inq_id = ?
    ";

    $stmt = $conn->prepare($updateInquirySql);

    if ($stmt) {
        $stmt->bind_param("i", $reservation['inq_id']);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reservation has been officially booked. Unit status is now Reserved.'
    ]);
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>