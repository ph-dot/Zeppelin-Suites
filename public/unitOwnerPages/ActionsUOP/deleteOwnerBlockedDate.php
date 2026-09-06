<?php
/**
 * deleteOwnerBlockedDate.php (Unit Owner)
 * Unblocks a previously blocked date range on a unit owned by this owner.
 */

require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = requireRole($conn, ['unit owner']);
$ownerId = (int)$user['user_id'];

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

// Verify this block belongs to a unit owned by the logged-in owner
$stmtCheck = $conn->prepare("
    SELECT b.block_id 
    FROM unit_blocked_dates b
    JOIN units_table u ON b.unit_id = u.unit_id
    WHERE b.block_id = ? AND u.unit_owner_id = ?
    LIMIT 1
");
if (!$stmtCheck) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmtCheck->bind_param('ii', $blockId, $ownerId);
$stmtCheck->execute();
$hasPerm = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$hasPerm) {
    echo json_encode(['success' => false, 'message' => 'Block record not found or you do not have permission to modify it.']);
    exit;
}

$stmtDel = $conn->prepare("DELETE FROM unit_blocked_dates WHERE block_id = ? LIMIT 1");
if (!$stmtDel) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}
$stmtDel->bind_param('i', $blockId);

if ($stmtDel->execute()) {
    $stmtDel->close();
    echo json_encode(['success' => true, 'message' => 'Unit dates successfully unblocked.']);
} else {
    $err = $stmtDel->error;
    $stmtDel->close();
    echo json_encode(['success' => false, 'message' => 'Failed to unblock dates: ' . $err]);
}
