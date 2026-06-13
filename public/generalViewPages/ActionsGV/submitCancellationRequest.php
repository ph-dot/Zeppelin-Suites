<?php
require_once __DIR__ . '/../../php_files/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$token = trim($_POST['token'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if ($token === '' || $reason === '') {
    die("Token and cancellation reason are required.");
}

$conn->begin_transaction();

try {
    $sql = "
        SELECT 
            reservation_id,
            payment_status,
            reservation_status,
            cancellation_status,
            client_cancel_token_expires_at
        FROM reservation_table
        WHERE client_cancel_token = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception("Invalid cancellation token.");
    }

    if (!empty($reservation['client_cancel_token_expires_at']) && strtotime($reservation['client_cancel_token_expires_at']) < time()) {
        throw new Exception("This cancellation link has expired.");
    }

    if ($reservation['payment_status'] !== 'verified') {
        throw new Exception("Cancellation request is only available after payment verification.");
    }

    if (in_array($reservation['reservation_status'], ['cancelled', 'rejected', 'reserved'])) {
        throw new Exception("Cancellation request is no longer available for this reservation.");
    }

    if ($reservation['cancellation_status'] === 'requested') {
        throw new Exception("A cancellation request has already been submitted.");
    }

    if ($reservation['cancellation_status'] === 'approved') {
        throw new Exception("This cancellation request was already approved.");
    }

    $updateSql = "
    UPDATE reservation_table
    SET cancellation_status = 'requested',
        cancellation_reason = ?,
        cancellation_requested_by = NULL,
        cancellation_requested_by_role = 'client',
        cancellation_requested_at = NOW(),
        client_cancel_token = NULL,
        client_cancel_token_expires_at = NULL
    WHERE reservation_id = ?
    ";

    $stmt = $conn->prepare($updateSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $reason, $reservation['reservation_id']);

    if (!$stmt->execute()) {
        throw new Exception("Failed to submit cancellation request.");
    }

    $stmt->close();

    $conn->commit();

    echo "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:80px auto;padding:24px;border:1px solid #ddd;border-radius:12px;'>
      <h2>Cancellation Request Submitted</h2>
      <p>Your cancellation request has been submitted successfully.</p>
      <p>Zeppelin Suites administration will review your request.</p>
    </div>";
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    echo "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:80px auto;padding:24px;border:1px solid #fecaca;border-radius:12px;background:#fef2f2;color:#991b1b;'>
      <h2>Unable to Submit Request</h2>
      <p>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
    exit;
}
?>