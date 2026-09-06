<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || $role !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Only administrators can perform this action.'
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
$admin_id = (int)$_SESSION['user_id'];

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

try {
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
        $stmt->bind_param("ssisi", $status, $now, $admin_id, $remarks, $reservation_id);
    } else {
        $status = 'Pending Signing';
        $nullDate = null;
        $nullId = null;
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
