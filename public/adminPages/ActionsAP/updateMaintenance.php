<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/owner_notifications.php';

header('Content-Type: text/plain; charset=UTF-8');

function respond($statusCode, $message) {
    http_response_code($statusCode);
    echo $message;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Invalid request method.');
}

$maintenance_id = isset($_POST['maintenance_id']) ? (int)$_POST['maintenance_id'] : 0;
$status = strtolower(trim($_POST['status'] ?? ''));
$admin_remarks = trim($_POST['admin_remarks'] ?? '');
$allowedStatuses = ['pending', 'in progress', 'resolved', 'cancelled'];

if ($maintenance_id <= 0) {
    respond(422, 'Invalid maintenance request ID.');
}

if (!in_array($status, $allowedStatuses, true)) {
    respond(422, 'Invalid maintenance status.');
}

if (strlen($admin_remarks) > 2000) {
    respond(422, 'Admin remarks must not exceed 2,000 characters.');
}

$conn->begin_transaction();

try {
    $checkSql = "
        SELECT m.maintenance_id, m.subject, owner.full_name AS owner_name, owner.email AS owner_email
        FROM maintenance_requests m
        LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
        WHERE m.maintenance_id = ?
        FOR UPDATE
    ";

    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        throw new Exception("Unable to validate maintenance request.");
    }

    $checkStmt->bind_param("i", $maintenance_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if (!$checkResult || $checkResult->num_rows === 0) {
        $checkStmt->close();
        $conn->rollback();
        respond(404, 'Maintenance request not found.');
    }

    $maintenanceRow = $checkResult->fetch_assoc();
    $checkStmt->close();

    $updateSql = "
        UPDATE maintenance_requests
        SET status = ?,
            admin_remarks = ?,
            updated_at = NOW(),
            resolved_at = CASE
                WHEN ? = 'resolved' THEN COALESCE(resolved_at, NOW())
                ELSE NULL
            END
        WHERE maintenance_id = ?
    ";

    $stmt = $conn->prepare($updateSql);
    if (!$stmt) {
        throw new Exception("Unable to prepare maintenance update.");
    }

    $stmt->bind_param("sssi", $status, $admin_remarks, $status, $maintenance_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update maintenance request.");
    }

    $stmt->close();
    $conn->commit();

    notifyOwnerOfMaintenanceFeedback(
        $maintenanceRow['owner_email'] ?? '',
        $maintenanceRow['owner_name'] ?? 'Unit Owner',
        $maintenanceRow['subject'] ?? 'your maintenance request',
        $status,
        $admin_remarks
    );

    respond(200, 'Maintenance updated successfully.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    respond(500, 'Unable to update maintenance request.');
}