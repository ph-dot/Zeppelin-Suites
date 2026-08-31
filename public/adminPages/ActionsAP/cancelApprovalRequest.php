<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once '../../php_files/eligible_units.php';

header('Content-Type: application/json');

$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$inq_id = isset($_POST['inq_id']) ? (int)$_POST['inq_id'] : 0;

if ($request_id <= 0 || $inq_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$conn->begin_transaction();

try {
    // Lock the row and make sure it's still pending and belongs to this inquiry -
    // an owner may have just approved/declined it, so re-check inside the transaction.
    $checkSql = "SELECT request_id, request_status
                 FROM owner_approval_requests
                 WHERE request_id = ? AND inq_id = ?
                 FOR UPDATE";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $request_id, $inq_id);
    $checkStmt->execute();
    $request = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$request) {
        throw new Exception("Request not found.");
    }

    if ($request['request_status'] !== 'pending') {
        throw new Exception("This request has already been responded to and can no longer be cancelled.");
    }

    // The request never got a response, so it's safe to remove outright
    // rather than keep it around with a special status.
    $deleteSql = "DELETE FROM owner_approval_requests WHERE request_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $request_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Figure out what the inquiry's overall approval_status should be now.
    $pendingSql = "SELECT COUNT(*) AS pending_count
                   FROM owner_approval_requests
                   WHERE inq_id = ? AND request_status = 'pending'";
    $pendingStmt = $conn->prepare($pendingSql);
    $pendingStmt->bind_param("i", $inq_id);
    $pendingStmt->execute();
    $pendingCount = (int)$pendingStmt->get_result()->fetch_assoc()['pending_count'];
    $pendingStmt->close();

    $totalSql = "SELECT COUNT(*) AS total_count
                 FROM owner_approval_requests
                 WHERE inq_id = ?";
    $totalStmt = $conn->prepare($totalSql);
    $totalStmt->bind_param("i", $inq_id);
    $totalStmt->execute();
    $totalCount = (int)$totalStmt->get_result()->fetch_assoc()['total_count'];
    $totalStmt->close();

    if ($pendingCount > 0) {
        // Other owners are still pending - nothing else to change.
    } elseif ($totalCount === 0) {
        // That was the only request ever sent, and it's gone now - reset
        // the inquiry back to a clean "not requested" state.
        $resetSql = "UPDATE inquiry_table
                     SET approval_status = 'not_requested',
                         approval_requested_at = NULL
                     WHERE inq_id = ? AND approval_status != 'approved'";
        $resetStmt = $conn->prepare($resetSql);
        $resetStmt->bind_param("i", $inq_id);
        $resetStmt->execute();
        $resetStmt->close();
    } else {
        // No pending requests left, but there's history (declined/expired
        // ones). Same rule as a normal decline: only fully close the
        // inquiry if there are truly no other eligible owners/units left.
        $remainingEligible = getRemainingEligibleUnitCount($conn, $inq_id);

        if ($remainingEligible === 0) {
            $closeSql = "UPDATE inquiry_table
                         SET status = 'declined',
                             approval_status = 'declined'
                         WHERE inq_id = ? AND approval_status != 'approved'";
            $closeStmt = $conn->prepare($closeSql);
            $closeStmt->bind_param("i", $inq_id);
            $closeStmt->execute();
            $closeStmt->close();
        }
        // else: leave approval_status as 'requested' so the admin can
        // check units again and send to someone else.
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Request cancelled.',
        'pending_count' => $pendingCount
    ]);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
