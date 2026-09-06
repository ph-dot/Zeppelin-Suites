<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/sync_unit_status.php';

// Ensure user is logged in as unit owner or admin
$user = requireRole($conn, ['unit owner', 'admin']);
$userRole = normalizeRole($_SESSION['role'] ?? '');
$isAdmin = ($userRole === 'admin');
$ownerId = (int)$user['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$unitId = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
$listingType = trim($_POST['listing_type'] ?? 'For Lease');
$stayCategory = trim($_POST['stay_category'] ?? 'Long term');
$leaseRateRaw = trim($_POST['lease_rate'] ?? '0');

if ($unitId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid unit ID.']);
    exit;
}

// Clean and validate lease rate
$leaseRateClean = preg_replace('/[^\d.]/', '', $leaseRateRaw);
$leaseRate = (float)$leaseRateClean;
if ($leaseRate < 0) {
    echo json_encode(['success' => false, 'message' => 'Lease rate cannot be negative.']);
    exit;
}

// Validate listing type
$validListingTypes = ['For Lease', 'Resale'];
if (!in_array($listingType, $validListingTypes, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing type selected.']);
    exit;
}

// Validate stay category (Short term / Long term)
$validStayCategories = ['Short term', 'Long term'];
// Normalize case
if (strtolower($stayCategory) === 'short term') {
    $stayCategory = 'Short term';
} else {
    $stayCategory = 'Long term';
}

// Verify unit exists (and belongs to this owner if not admin)
if ($isAdmin) {
    $stmtCheck = $conn->prepare("SELECT unit_id, unit_current_status FROM units_table WHERE unit_id = ? LIMIT 1");
    if (!$stmtCheck) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmtCheck->bind_param('i', $unitId);
} else {
    $stmtCheck = $conn->prepare("SELECT unit_id, unit_current_status FROM units_table WHERE unit_id = ? AND unit_owner_id = ? LIMIT 1");
    if (!$stmtCheck) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmtCheck->bind_param('ii', $unitId, $ownerId);
}
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$unitData = $resCheck->fetch_assoc();
$stmtCheck->close();

if (!$unitData) {
    echo json_encode(['success' => false, 'message' => 'Unit not found or you do not have permission to modify it.']);
    exit;
}

$currentStatus = $unitData['unit_current_status'];
$newStatus = $currentStatus;

// If switching to Resale and unit is currently vacant ("Ready for Occupancy"), update status to "Resale"
if ($listingType === 'Resale' && $currentStatus === 'Ready for Occupancy') {
    $newStatus = 'Resale';
} elseif ($listingType === 'For Lease' && $currentStatus === 'Resale') {
    // If switching back to For Lease and status was Resale, set back to Ready for Occupancy
    $newStatus = 'Ready for Occupancy';
}

if ($isAdmin) {
    $stmtUpdate = $conn->prepare("
        UPDATE units_table 
        SET listing_type = ?,
            stay_category = ?,
            lease_rate = ?,
            unit_current_status = ?
        WHERE unit_id = ?
    ");
    if (!$stmtUpdate) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    $stmtUpdate->bind_param('ssdsi', $listingType, $stayCategory, $leaseRate, $newStatus, $unitId);
} else {
    $stmtUpdate = $conn->prepare("
        UPDATE units_table 
        SET listing_type = ?,
            stay_category = ?,
            lease_rate = ?,
            unit_current_status = ?
        WHERE unit_id = ? AND unit_owner_id = ?
    ");
    if (!$stmtUpdate) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    $stmtUpdate->bind_param('ssdsii', $listingType, $stayCategory, $leaseRate, $newStatus, $unitId, $ownerId);
}

if ($stmtUpdate->execute()) {
    $stmtUpdate->close();
    echo json_encode([
        'success' => true,
        'message' => 'Unit details updated successfully!',
        'data' => [
            'unit_id' => $unitId,
            'listing_type' => $listingType,
            'stay_category' => $stayCategory,
            'lease_rate' => $leaseRate,
            'lease_rate_formatted' => '₱' . number_format($leaseRate, 2),
            'unit_current_status' => $newStatus
        ]
    ]);
} else {
    $err = $stmtUpdate->error;
    $stmtUpdate->close();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $err]);
}
