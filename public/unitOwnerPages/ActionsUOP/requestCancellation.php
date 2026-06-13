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
$reason = trim($_POST['reason'] ?? '');

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

if ($reason === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Cancellation reason is required.'
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $sql = "
        SELECT 
            r.reservation_id,
            r.unit_id,
            r.reservation_status,
            r.cancellation_status,
            u.unit_owner_id
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.reservation_id = ?
        AND u.unit_owner_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);

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

    if (in_array($reservation['reservation_status'], ['cancelled', 'rejected', 'reserved'])) {
        throw new Exception("Cancellation request is not allowed for this reservation status.");
    }

    if ($reservation['cancellation_status'] === 'requested') {
        throw new Exception("A cancellation request is already pending for this reservation.");
    }

    if ($reservation['cancellation_status'] === 'approved') {
        throw new Exception("This cancellation request was already approved.");
    }

    $updateSql = "
        UPDATE reservation_table
        SET cancellation_status = 'requested',
            cancellation_reason = ?,
            cancellation_requested_by = ?,
            cancellation_requested_at = NOW()
        WHERE reservation_id = ?
    ";

    $stmt = $conn->prepare($updateSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sii", $reason, $owner_id, $reservation_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to submit cancellation request.");
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Cancellation request submitted. Admin will review it.'
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