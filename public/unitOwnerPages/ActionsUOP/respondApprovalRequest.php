<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'unit owner') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../ownersReservations.php");
    exit();
}

$owner_id = (int)$_SESSION['user_id'];
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$action = $_POST['action'] ?? '';

if ($request_id <= 0) {
    $_SESSION['error_message'] = "Invalid request ID.";
    header("Location: ../ownersReservations.php");
    exit();
}

if ($action !== 'approve' && $action !== 'decline') {
    $_SESSION['error_message'] = "Invalid action.";
    header("Location: ../ownersReservations.php");
    exit();
}

$conn->begin_transaction();

try {
    $sql = "SELECT 
                request_id,
                inq_id,
                unit_id,
                unit_owner_id,
                request_status
            FROM owner_approval_requests
            WHERE request_id = ?
            AND unit_owner_id = ?
            FOR UPDATE";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $request_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if (!$request) {
        throw new Exception("Request not found.");
    }

    if ($request['request_status'] !== 'pending') {
        throw new Exception("This request has already been responded to.");
    }

    $inq_id = (int)$request['inq_id'];
    $unit_id = (int)$request['unit_id'];

    if ($action === 'approve') {

        $checkInquirySql = "SELECT approval_status
                            FROM Inquiry_table
                            WHERE inq_id = ?
                            FOR UPDATE";

        $checkStmt = $conn->prepare($checkInquirySql);
        $checkStmt->bind_param("i", $inq_id);
        $checkStmt->execute();
        $inquiryResult = $checkStmt->get_result();
        $inquiry = $inquiryResult->fetch_assoc();
        $checkStmt->close();

        if ($inquiry && $inquiry['approval_status'] === 'approved') {
            $expireThisSql = "UPDATE owner_approval_requests
                              SET request_status = 'expired',
                                  responded_at = NOW()
                              WHERE request_id = ?";

            $expireThisStmt = $conn->prepare($expireThisSql);
            $expireThisStmt->bind_param("i", $request_id);
            $expireThisStmt->execute();
            $expireThisStmt->close();

            throw new Exception("Another unit owner already approved this inquiry first.");
        }

        $approveSql = "UPDATE owner_approval_requests
                       SET request_status = 'approved',
                           responded_at = NOW()
                       WHERE request_id = ?";

        $approveStmt = $conn->prepare($approveSql);
        $approveStmt->bind_param("i", $request_id);
        $approveStmt->execute();
        $approveStmt->close();

        $expireOthersSql = "UPDATE owner_approval_requests
                            SET request_status = 'expired',
                                responded_at = NOW()
                            WHERE inq_id = ?
                            AND request_id != ?
                            AND request_status = 'pending'";

        $expireStmt = $conn->prepare($expireOthersSql);
        $expireStmt->bind_param("ii", $inq_id, $request_id);
        $expireStmt->execute();
        $expireStmt->close();

       $reservation_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

        $updateInquirySql = "
            UPDATE inquiry_table
            SET approval_status = 'approved',
                approved_unit_id = ?,
                reservation_token = ?,
                reservation_token_expires_at = ?,
                approval_approved_at = NOW()
            WHERE inq_id = ?
        ";

        $updateInquiryStmt = $conn->prepare($updateInquirySql);

        if (!$updateInquiryStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $updateInquiryStmt->bind_param("issi", $unit_id, $reservation_token, $expires_at, $inq_id);
        $updateInquiryStmt->execute();
        $updateInquiryStmt->close();

        $conn->commit();

        $_SESSION['success_message'] = "Reservation request approved successfully.";
        header("Location: ../ownersReservations.php");
        exit();
    }

    if ($action === 'decline') {

        $declineSql = "UPDATE owner_approval_requests
                       SET request_status = 'declined',
                           responded_at = NOW()
                       WHERE request_id = ?";

        $declineStmt = $conn->prepare($declineSql);
        $declineStmt->bind_param("i", $request_id);
        $declineStmt->execute();
        $declineStmt->close();

        $pendingSql = "SELECT COUNT(*) AS pending_count
                       FROM owner_approval_requests
                       WHERE inq_id = ?
                       AND request_status = 'pending'";

        $pendingStmt = $conn->prepare($pendingSql);
        $pendingStmt->bind_param("i", $inq_id);
        $pendingStmt->execute();
        $pendingResult = $pendingStmt->get_result();
        $pendingRow = $pendingResult->fetch_assoc();
        $pendingStmt->close();

        $pendingCount = (int)$pendingRow['pending_count'];

        if ($pendingCount === 0) {
            $updateInquirySql = "UPDATE Inquiry_table
                                 SET status = 'declined',
                                     approval_status = 'declined'
                                 WHERE inq_id = ?
                                 AND approval_status != 'approved'";

            $updateInquiryStmt = $conn->prepare($updateInquirySql);
            $updateInquiryStmt->bind_param("i", $inq_id);
            $updateInquiryStmt->execute();
            $updateInquiryStmt->close();
        }

        $conn->commit();

        $_SESSION['success_message'] = "Reservation request declined.";
        header("Location: ../ownersReservations.php");
        exit();
    }

} catch (Exception $e) {
    $conn->rollback();

    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../ownersReservations.php");
    exit();
}

$conn->close();
?>