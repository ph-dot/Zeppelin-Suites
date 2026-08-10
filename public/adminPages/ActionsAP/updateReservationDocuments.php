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

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$documents = json_decode($_POST['documents'] ?? '', true);

if ($reservation_id <= 0 || !is_array($documents) || count($documents) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);
    exit;
}

$validStorages = ['dropbox', 'gdrive', 'other'];

$conn->begin_transaction();

try {
    $checkSql = "
        SELECT reservation_id, payment_status, reservation_status
        FROM reservation_table
        WHERE reservation_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $reservation = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception("Reservation not found.");
    }

    if ($reservation['payment_status'] !== 'verified') {
        throw new Exception("Payment must be verified before updating documents.");
    }

    if ($reservation['reservation_status'] === 'reserved') {
        throw new Exception("This reservation is already officially booked.");
    }

    if (in_array($reservation['reservation_status'], ['rejected', 'cancelled'], true)) {
        throw new Exception("Cannot update documents for a rejected or cancelled reservation.");
    }

    $updated_by = (int)($_SESSION['user_id'] ?? 0);
    $updated_by_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'admin';

    $updateDocSql = "
        UPDATE reservation_documents
        SET status = ?,
            storage = ?,
            storage_other_label = ?,
            document_link = ?,
            updated_by = ?,
            updated_by_role = ?,
            updated_at = NOW()
        WHERE document_id = ? AND reservation_id = ?
    ";

    $docStmt = $conn->prepare($updateDocSql);

    if (!$docStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    foreach ($documents as $doc) {
        $document_id = (int)($doc['document_id'] ?? 0);

        if ($document_id <= 0) {
            continue;
        }

        $status = (($doc['status'] ?? 'pending') === 'complete') ? 'complete' : 'pending';

        $storage = (string)($doc['storage'] ?? '');
        $storage = in_array($storage, $validStorages, true) ? $storage : '';
        $storageParam = $storage === '' ? null : $storage;

        $storageOtherLabel = $storage === 'other'
            ? trim((string)($doc['storage_other_label'] ?? ''))
            : '';
        $storageOtherLabelParam = $storageOtherLabel === '' ? null : $storageOtherLabel;

        $link = trim((string)($doc['document_link'] ?? ''));
        $linkParam = $link === '' ? null : $link;

        $docStmt->bind_param(
            "ssssisii",
            $status,
            $storageParam,
            $storageOtherLabelParam,
            $linkParam,
            $updated_by,
            $updated_by_role,
            $document_id,
            $reservation_id
        );

        if (!$docStmt->execute()) {
            throw new Exception("Failed to update document.");
        }
    }

    $docStmt->close();

    $countSql = "
        SELECT
            COUNT(*) AS total,
            SUM(status = 'complete') AS completed
        FROM reservation_documents
        WHERE reservation_id = ?
    ";

    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $reservation_id);
    $countStmt->execute();
    $counts = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();

    $allCompleted = $counts && (int)$counts['total'] > 0 && (int)$counts['completed'] === (int)$counts['total'];
    $newStatus = $allCompleted ? 'requirements completed' : 'requirements pending';

    $updateReservationSql = "
        UPDATE reservation_table
        SET reservation_status = ?,
            requirements_updated_by = ?,
            requirements_updated_by_role = ?,
            requirements_updated_at = NOW()
        WHERE reservation_id = ?
    ";

    $stmt = $conn->prepare($updateReservationSql);
    $stmt->bind_param("sisi", $newStatus, $updated_by, $updated_by_role, $reservation_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update reservation status.");
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $allCompleted
            ? 'All documents completed. You may now mark this reservation as officially booked.'
            : 'Document tracking saved.',
        'reservation_status' => $newStatus,
        'all_completed' => $allCompleted
    ]);
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
