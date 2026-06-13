<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$maintenance_id = isset($_POST['maintenance_id']) ? (int)$_POST['maintenance_id'] : 0;
$status = trim($_POST['status'] ?? '');
$admin_remarks = trim($_POST['admin_remarks'] ?? '');

if ($maintenance_id <= 0 || empty($status)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input.'
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $updateSql = "
        UPDATE maintenance_table
        SET status = ?, 
            admin_remarks = ?, 
            updated_at = NOW()
        WHERE maintenance_id = ?
    ";

    $stmt = $conn->prepare($updateSql);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("ssi", $status, $admin_remarks, $maintenance_id);

    if (!$stmt->execute()) throw new Exception("Failed to update maintenance.");

    $stmt->close();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Maintenance updated successfully.',
        'status' => $status,
        'admin_remarks' => $admin_remarks
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>