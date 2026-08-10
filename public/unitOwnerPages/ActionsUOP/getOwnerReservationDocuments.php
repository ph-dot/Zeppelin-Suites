<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!function_exists('format_datetime_text')) {
    function format_datetime_text($value) {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return '-';
        }
        $time = strtotime($value);
        return $time ? date('Y-m-d h:i A', $time) : '-';
    }
}

$role = strtolower($_SESSION['role'] ?? $_SESSION['user_role'] ?? '');

if (!isset($_SESSION['user_id']) || $role !== 'unit owner') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

$owner_id = (int)$_SESSION['user_id'];
$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

// Ownership check: only return documents for reservations tied to this owner's unit.
$ownsSql = "
    SELECT r.reservation_id
    FROM reservation_table r
    INNER JOIN units_table u ON r.unit_id = u.unit_id
    WHERE r.reservation_id = ? AND u.unit_owner_id = ?
    LIMIT 1
";

$ownsStmt = $conn->prepare($ownsSql);
$ownsStmt->bind_param("ii", $reservation_id, $owner_id);
$ownsStmt->execute();
$owns = $ownsStmt->get_result()->fetch_assoc();
$ownsStmt->close();

if (!$owns) {
    echo json_encode([
        'success' => false,
        'message' => 'Reservation not found for your unit.'
    ]);
    exit;
}

$sql = "
    SELECT
        d.document_id,
        d.document_key,
        d.document_name,
        d.status,
        d.storage,
        d.storage_other_label,
        d.document_link,
        d.updated_at,
        d.updated_by_role,
        u.full_name AS updated_by_name
    FROM reservation_documents d
    LEFT JOIN users_table u ON d.updated_by = u.user_id
    WHERE d.reservation_id = ?
    ORDER BY d.document_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

$documents = [];
$totalCount = 0;
$completeCount = 0;

while ($row = $result->fetch_assoc()) {
    $totalCount++;
    if ($row['status'] === 'complete') {
        $completeCount++;
    }

    $documents[] = [
        'document_id' => (int)$row['document_id'],
        'document_key' => $row['document_key'],
        'document_name' => $row['document_name'],
        'status' => $row['status'],
        'storage' => $row['storage'],
        'storage_other_label' => $row['storage_other_label'],
        'document_link' => $row['document_link'],
        'updated_by_name' => $row['updated_by_name'] ?: 'Not updated yet',
        'updated_by_role' => $row['updated_by_role'] ?: '-',
        'updated_at' => format_datetime_text($row['updated_at'])
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'documents' => $documents,
    'all_completed' => $totalCount > 0 && $completeCount === $totalCount
]);
exit;
