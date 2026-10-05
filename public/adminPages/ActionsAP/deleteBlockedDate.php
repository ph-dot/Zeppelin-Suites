<?php
/**
 * deleteBlockedDate.php (Admin)
 * Unblocks a previously blocked date range from unit_blocked_dates.
 */

require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json; charset=utf-8');

$userData = requireRole($conn, ['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$blockId = isset($_POST['block_id']) ? (int)$_POST['block_id'] : 0;
if ($blockId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid block ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM unit_blocked_dates WHERE block_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $blockId);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    $stmt->close();
    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Unit dates successfully unblocked.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Block record not found or already removed.']);
    }
} else {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to unblock dates: ' . $err]);
}
