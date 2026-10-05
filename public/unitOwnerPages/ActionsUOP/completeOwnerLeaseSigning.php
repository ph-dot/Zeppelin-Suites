<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || $role !== 'unit owner') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Only unit owners can perform this action.'
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

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$action = trim($_POST['action'] ?? 'complete');
$remarks = trim($_POST['remarks'] ?? '');
$owner_id = (int)$_SESSION['user_id'];

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

try {
    // Verify reservation belongs to a unit owned by this owner
    $checkStmt = $conn->prepare("
        SELECT r.reservation_id 
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.reservation_id = ? AND u.unit_owner_id = ?
        LIMIT 1
    ");
    $checkStmt->bind_param("ii", $reservation_id, $owner_id);
    $checkStmt->execute();
    $resExists = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$resExists) {
        throw new Exception("You do not have permission to manage this reservation.");
    }

    $now = date('Y-m-d H:i:s');
    if ($action === 'complete') {
        $status = 'Completed';
        $stmt = $conn->prepare("
            UPDATE reservation_table 
            SET lease_signing_status = ?, 
                lease_signed_at = ?, 
                lease_signed_by = ?, 
                lease_signing_remarks = ?
            WHERE reservation_id = ?
        ");
        $stmt->bind_param("ssisi", $status, $now, $owner_id, $remarks, $reservation_id);
    } else {
        $status = 'Pending Signing';
        $stmt = $conn->prepare("
            UPDATE reservation_table 
            SET lease_signing_status = ?, 
                lease_signed_at = NULL, 
                lease_signed_by = NULL, 
                lease_signing_remarks = ?
            WHERE reservation_id = ?
        ");
        $stmt->bind_param("ssi", $status, $remarks, $reservation_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update lease signing status: " . $stmt->error);
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => $action === 'complete' ? 'Lease signing marked as completed successfully.' : 'Lease signing status reset.',
        'signed_at' => $now,
        'status' => $status
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
