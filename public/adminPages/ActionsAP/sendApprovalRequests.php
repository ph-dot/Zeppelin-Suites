<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once '../../php_files/sync_unit_status.php';

header('Content-Type: application/json');

syncExpiredUnitStatuses($conn);

$inq_id = isset($_POST['inq_id']) ? (int)$_POST['inq_id'] : 0;
$unit_ids_json = $_POST['unit_ids'] ?? '';

$unit_ids = json_decode($unit_ids_json, true);

if ($inq_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid inquiry ID.'
    ]);
    exit;
}

if (!is_array($unit_ids) || count($unit_ids) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No available units selected.'
    ]);
    exit;
}

$conn->begin_transaction();

try {
    // Only clear out a pending request for a unit that's being re-sent in
    // THIS batch (so re-selecting the same unit resets it cleanly).
    // Requests already sent to OTHER owners/units must be left alone -
    // otherwise sending to a new owner would silently cancel requests
    // still waiting on other owners.
    $deleteSql = "DELETE FROM owner_approval_requests 
                  WHERE inq_id = ? 
                  AND request_status = 'pending'
                  AND unit_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);

    foreach ($unit_ids as $unit_id) {
        $unit_id_int = (int)$unit_id;
        $deleteStmt->bind_param("ii", $inq_id, $unit_id_int);
        $deleteStmt->execute();
    }
    $deleteStmt->close();

    require_once '../../php_files/owner_notifications.php';

    // Determine if inquiry is a resale inquiry
    $inqStmt = $conn->prepare("SELECT inquiry_type FROM inquiry_table WHERE inq_id = ?");
    $inqStmt->bind_param("i", $inq_id);
    $inqStmt->execute();
    $inqRow = $inqStmt->get_result()->fetch_assoc();
    $inqStmt->close();

    $inquiryTypeNormalized = strtolower(trim($inqRow['inquiry_type'] ?? ''));
    $isResale = ($inquiryTypeNormalized === 'resale inquiry');

    if ($isResale) {
        $selectUnitSql = "SELECT u.unit_id, u.unit_number, u.unit_owner_id,
                                  owner.full_name AS owner_name, owner.email AS owner_email
                          FROM units_table u
                          LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
                          WHERE u.unit_id = ?
                          AND u.unit_current_status = 'Resale'
                          AND u.unit_owner_id IS NOT NULL";
    } else {
        $selectUnitSql = "SELECT u.unit_id, u.unit_number, u.unit_owner_id,
                                  owner.full_name AS owner_name, owner.email AS owner_email
                          FROM units_table u
                          LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
                          WHERE u.unit_id = ?
                          AND u.unit_current_status NOT IN ('Resale', 'On Hold', 'Under maintenance')
                          AND u.unit_owner_id IS NOT NULL";
    }

    $insertSql = "INSERT INTO owner_approval_requests
                  (inq_id, unit_id, unit_owner_id, request_status)
                  VALUES (?, ?, ?, 'pending')";

    $selectStmt = $conn->prepare($selectUnitSql);
    $insertStmt = $conn->prepare($insertSql);

    $inserted = 0;
    $notifyList = [];

    foreach ($unit_ids as $unit_id) {
        $unit_id = (int)$unit_id;

        $selectStmt->bind_param("i", $unit_id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $unit_owner_id = (int)$row['unit_owner_id'];

            $insertStmt->bind_param("iii", $inq_id, $unit_id, $unit_owner_id);
            $insertStmt->execute();
            $inserted++;

            $notifyList[] = [
                'unit_number' => $row['unit_number'],
                'owner_name'  => $row['owner_name'],
                'owner_email' => $row['owner_email'],
            ];
        }
    }

    $selectStmt->close();
    $insertStmt->close();

    if ($inserted === 0) {
        throw new Exception("No valid units found for approval request.");
    }

    $updateInquirySql = "UPDATE inquiry_table
                 SET approval_status = 'requested',
                     approval_requested_at = NOW()
                 WHERE inq_id = ?";
    $updateStmt = $conn->prepare($updateInquirySql);
    $updateStmt->bind_param("i", $inq_id);
    $updateStmt->execute();
    $updateStmt->close();

    $conn->commit();

    foreach ($notifyList as $n) {
        notifyOwnerOfApprovalRequest(
            $n['owner_email'] ?? '',
            $n['owner_name'] ?? 'Unit Owner',
            $n['unit_number'] ?? ''
        );
    }

    // Fetch updated requests and pending count for this inquiry so the UI can update dynamically
    $requestsStmt = $conn->prepare("
        SELECT
            r.request_id,
            r.unit_id,
            r.request_status,
            r.requested_at,
            r.responded_at,
            u.unit_number,
            owner.full_name AS owner_name
        FROM owner_approval_requests r
        LEFT JOIN units_table u ON r.unit_id = u.unit_id
        LEFT JOIN users_table owner ON r.unit_owner_id = owner.user_id
        WHERE r.inq_id = ?
        ORDER BY r.requested_at ASC
    ");
    $requestsStmt->bind_param("i", $inq_id);
    $requestsStmt->execute();
    $requestsResult = $requestsStmt->get_result();
    $allRequests = [];
    $pendingCount = 0;
    while ($reqRow = $requestsResult->fetch_assoc()) {
        if (strtolower($reqRow['request_status']) === 'pending') {
            $pendingCount++;
        }
        $allRequests[] = [
            'request_id'     => (int)$reqRow['request_id'],
            'unit_id'        => (int)$reqRow['unit_id'],
            'unit_number'    => $reqRow['unit_number'] ?? 'Unknown unit',
            'owner_name'     => $reqRow['owner_name'] ?? 'Unknown owner',
            'request_status' => $reqRow['request_status'],
            'requested_at'   => $reqRow['requested_at'],
            'responded_at'   => $reqRow['responded_at'],
        ];
    }
    $requestsStmt->close();

    echo json_encode([
        'success'         => true,
        'message'         => 'Approval requests sent successfully.',
        'inserted'        => $inserted,
        'approval_status' => 'requested',
        'pending_count'   => $pendingCount,
        'requests'        => $allRequests
    ]);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>