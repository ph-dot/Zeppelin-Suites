<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/sync_unit_status.php';

// Only admins can execute this
$user = requireRole($conn, ['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$unitId = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
if ($unitId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid unit ID.']);
    exit;
}

// Fetch current unit info
$stmtCheck = $conn->prepare("SELECT unit_id, unit_owner_id, unit_current_status, listing_type, stay_category, lease_rate FROM units_table WHERE unit_id = ? LIMIT 1");
if (!$stmtCheck) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmtCheck->bind_param('i', $unitId);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$currentUnit = $resCheck ? $resCheck->fetch_assoc() : null;
$stmtCheck->close();

if (!$currentUnit) {
    echo json_encode(['success' => false, 'message' => 'Unit not found.']);
    exit;
}

$ownerAction = trim($_POST['owner_action'] ?? 'keep');
$transferReason = trim($_POST['transfer_reason'] ?? 'Reassigned by Admin');

$oldOwnerId = $currentUnit['unit_owner_id'] !== null ? (int)$currentUnit['unit_owner_id'] : null;
$newOwnerId = $oldOwnerId;

// Handle owner change
if ($ownerAction === 'remove') {
    $newOwnerId = null;
} elseif ($ownerAction === 'assign') {
    $assignedId = isset($_POST['existing_owner_id']) ? (int)$_POST['existing_owner_id'] : 0;
    if ($assignedId > 0) {
        $newOwnerId = $assignedId;
    }
} elseif ($ownerAction === 'new') {
    $newName = trim($_POST['new_owner_name'] ?? '');
    $newEmail = trim($_POST['new_owner_email'] ?? '');
    $newContact = trim($_POST['new_owner_contact'] ?? '');

    if (empty($newName) || empty($newEmail)) {
        echo json_encode(['success' => false, 'message' => 'New unit owner requires full name and email.']);
        exit;
    }

    // Insert new unit owner
    $stmtUser = $conn->prepare("INSERT INTO users_table (full_name, email, contact, user_role, created_at) VALUES (?, ?, ?, 'unit owner', NOW())");
    if (!$stmtUser) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare new owner insert: ' . $conn->error]);
        exit;
    }
    $stmtUser->bind_param('sss', $newName, $newEmail, $newContact);
    if (!$stmtUser->execute()) {
        $uErr = $stmtUser->error;
        $stmtUser->close();
        echo json_encode(['success' => false, 'message' => 'Failed to create new unit owner: ' . $uErr]);
        exit;
    }
    $newOwnerId = (int)$stmtUser->insert_id;
    $stmtUser->close();
}

// Update units_table (Admin exclusively manages ownership assignment; listing type, stay category, and rates are set by the owner)
$stmtUpdate = $conn->prepare("
    UPDATE units_table 
    SET unit_owner_id = ?
    WHERE unit_id = ?
");
if (!$stmtUpdate) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
    exit;
}
$stmtUpdate->bind_param('ii', $newOwnerId, $unitId);

if (!$stmtUpdate->execute()) {
    $uErr = $stmtUpdate->error;
    $stmtUpdate->close();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $uErr]);
    exit;
}
$stmtUpdate->close();

// Handle ownership history tracking if owner changed
if ($oldOwnerId !== $newOwnerId) {
    $today = date('Y-m-d');
    
    // 1. Close old owner's active record if exists
    if ($oldOwnerId !== null && $oldOwnerId > 0) {
        $stmtCloseOld = $conn->prepare("
            UPDATE unit_ownership_history 
            SET end_date = ?, ownership_status = 'transferred', remarks = CONCAT(IFNULL(remarks, ''), ' [Transferred on ', ?, ']')
            WHERE unit_id = ? AND owner_id = ? AND ownership_status = 'active'
        ");
        if ($stmtCloseOld) {
            $stmtCloseOld->bind_param('ssii', $today, $today, $unitId, $oldOwnerId);
            $stmtCloseOld->execute();
            $stmtCloseOld->close();
        }
    }

    // 2. Open new owner's active record if new owner is assigned
    if ($newOwnerId !== null && $newOwnerId > 0) {
        $stmtNewHistory = $conn->prepare("
            INSERT INTO unit_ownership_history (unit_id, owner_id, start_date, end_date, ownership_status, transfer_type, remarks)
            VALUES (?, ?, ?, NULL, 'active', ?, ?)
        ");
        if ($stmtNewHistory) {
            $remarkText = !empty($transferReason) ? $transferReason : 'Ownership updated by Administrator';
            $stmtNewHistory->bind_param('iisss', $unitId, $newOwnerId, $today, $transferReason, $remarkText);
            $stmtNewHistory->execute();
            $stmtNewHistory->close();
        }
    }
}

// Fetch updated owner details
$newOwnerData = null;
if ($newOwnerId !== null && $newOwnerId > 0) {
    $stmtOwnerInfo = $conn->prepare("SELECT user_id, full_name, email, contact FROM users_table WHERE user_id = ? LIMIT 1");
    if ($stmtOwnerInfo) {
        $stmtOwnerInfo->bind_param('i', $newOwnerId);
        $stmtOwnerInfo->execute();
        $resOwner = $stmtOwnerInfo->get_result();
        $newOwnerData = $resOwner ? $resOwner->fetch_assoc() : null;
        $stmtOwnerInfo->close();
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Unit details and settings updated successfully!',
    'data' => [
        'unit_id' => $unitId,
        'listing_type' => $listingType,
        'stay_category' => $stayCategory,
        'lease_rate' => $leaseRate,
        'lease_rate_formatted' => '₱' . number_format($leaseRate, 2),
        'unit_current_status' => $unitCurrentStatus,
        'unit_owner' => $newOwnerData,
        'has_owner' => ($newOwnerData !== null)
    ]
]);
