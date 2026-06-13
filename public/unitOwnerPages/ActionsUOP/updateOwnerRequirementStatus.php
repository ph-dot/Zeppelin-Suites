<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');

if (!isset($_SESSION['user_id']) || $role !== 'unit owner') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$owner_id = (int)$_SESSION['user_id'];
$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;

$two_valid_ids = isset($_POST['two_valid_ids_status']) ? (int)$_POST['two_valid_ids_status'] : 0;
$tin_number = isset($_POST['tin_number_status']) ? (int)$_POST['tin_number_status'] : 0;
$reservation_agreement = isset($_POST['reservation_agreement_status']) ? (int)$_POST['reservation_agreement_status'] : 0;

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

$two_valid_ids = $two_valid_ids === 1 ? 1 : 0;
$tin_number = $tin_number === 1 ? 1 : 0;
$reservation_agreement = $reservation_agreement === 1 ? 1 : 0;

$all_completed = $two_valid_ids === 1 && $tin_number === 1 && $reservation_agreement === 1;
$new_status = $all_completed ? 'requirements completed' : 'requirements pending';

$conn->begin_transaction();

try {
    $checkSql = "
        SELECT 
            r.reservation_id,
            r.unit_id,
            r.payment_status,
            r.reservation_status,
            u.unit_owner_id
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.reservation_id = ?
        AND u.unit_owner_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($checkSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $reservation_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception("Reservation not found for your unit.");
    }

    if ($reservation['payment_status'] !== 'verified') {
        throw new Exception("Payment must be verified by admin before updating requirements.");
    }

    if ($reservation['reservation_status'] === 'reserved') {
        throw new Exception("This reservation is already officially booked.");
    }

    if (
        $reservation['reservation_status'] === 'rejected' ||
        $reservation['reservation_status'] === 'cancelled'
    ) {
        throw new Exception("Cannot update requirements for a rejected or cancelled reservation.");
    }

    $updated_by = (int)($_SESSION['user_id'] ?? 0);
    $updated_by_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'unit owner';

    $updateSql = "
        UPDATE reservation_table
        SET two_valid_ids_status = ?,
            tin_number_status = ?,
            reservation_agreement_status = ?,
            reservation_status = ?,
            requirements_updated_by = ?,
            requirements_updated_by_role = ?,
            requirements_updated_at = NOW()
        WHERE reservation_id = ?
    ";

    $stmt = $conn->prepare($updateSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "iiisisi",
        $two_valid_ids,
        $tin_number,
        $reservation_agreement,
        $new_status,
        $updated_by,
        $updated_by_role,
        $reservation_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to update requirement tracking.");
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $all_completed
            ? 'Requirements completed. Admin may now officially book this reservation.'
            : 'Requirement tracking saved.',
        'reservation_status' => $new_status,
        'all_completed' => $all_completed
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