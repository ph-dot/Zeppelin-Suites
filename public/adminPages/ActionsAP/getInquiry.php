<?php
require_once '../php_files/admin_auth.php';
require_once '../php_files/db.php';


// Handle status update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inq_id = (int)$_POST['inq_id'];
    $new_status = $_POST['new_status'] === 'responded' ? 'responded' : 'pending';
    
    $updateSql = "UPDATE Inquiry_table SET status = ? WHERE inq_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $new_status, $inq_id);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    $updateStmt->close();
    exit;
}

// CALCULATE STATS FIRST - Output immediately
// New today
$newTodaySql = "SELECT COUNT(*) as count FROM Inquiry_table WHERE DATE(timestamp) = CURDATE()";
$newStmt = $conn->prepare($newTodaySql);
$newStmt->execute();
$newTodayResult = $newStmt->get_result();
$newTodayRow = $newTodayResult->fetch_assoc();
$newToday = $newTodayRow['count'];

// Pending & Responded (These remain the same)
$pendingSql = "SELECT COUNT(*) as count FROM Inquiry_table WHERE status = 'pending'";
$pendingResult = $conn->query($pendingSql);
$pendingCount = $pendingResult->fetch_assoc()['count'];

$respondedSql = "SELECT COUNT(*) as count FROM Inquiry_table WHERE status = 'responded'";
$respondedResult = $conn->query($respondedSql);
$respondedCount = $respondedResult->fetch_assoc()['count'];

// OUTPUT STATS SCRIPT IMMEDIATELY (before table)
echo "<script>
window.statsData = {
    newToday: " . (int)$newToday . ",
    pending: " . (int)$pendingCount . ",
    responded: " . (int)$respondedCount . "
};
console.log('Stats loaded:', window.statsData);
</script>";


// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM Inquiry_table";
$countResult = $conn->query($countSql);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// SQL query - NEWEST FIRST with LIMIT and OFFSET
$sql = "SELECT 
            i.inq_id,
            i.sender_name,
            i.sender_email,
            i.sender_contact,
            i.inquiry_type,
            i.Preferred_unit_id,
            i.preferred_move_in_time,
            i.message,
            DATE(i.timestamp) AS date_only,
            i.status,
            i.approval_status,
            i.approved_unit_id,
            i.approval_approved_at,
            DATE_FORMAT(i.approval_approved_at, '%b %d, %Y %h:%i %p') AS approval_approved_at_display,
            i.lease_duration,
            u.unit_number AS approved_unit_number
        FROM Inquiry_table i
        LEFT JOIN units_table u 
            ON i.approved_unit_id = u.unit_id
        ORDER BY i.timestamp DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$endItem = min($offset + $result->num_rows, $totalItems);

// Look up who each pending/answered request actually went to, so the
// admin modal can show real owner/unit info instead of a generic
// "sent to available unit owners" message when reopened.
$requestsStmt = $conn->prepare("
    SELECT
        r.request_id,
        r.unit_id,
        r.request_status,
        r.requested_at,
        r.responded_at,
        u.unit_number,
        owner.full_name AS owner_name
    FROM owner_approval_requests r
    LEFT JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON r.unit_owner_id = owner.user_id
    WHERE r.inq_id = ?
    ORDER BY r.requested_at ASC
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Escape data to prevent XSS
        $sender_name = htmlspecialchars($row['sender_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $sender_email = htmlspecialchars($row['sender_email'] ?? '', ENT_QUOTES, 'UTF-8');
        $sender_contact = htmlspecialchars($row['sender_contact'] ?? '', ENT_QUOTES, 'UTF-8');
        $inquiry_type = htmlspecialchars($row['inquiry_type'] ?? '', ENT_QUOTES, 'UTF-8');
        $preferred_unit_id = htmlspecialchars($row['Preferred_unit_id'] ?? '', ENT_QUOTES, 'UTF-8');
        $preferred_move_in_time = htmlspecialchars($row['preferred_move_in_time'] ?? '', ENT_QUOTES, 'UTF-8');
        $lease_duration = htmlspecialchars($row['lease_duration'] ?? '', ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $dateOnly = htmlspecialchars($row['date_only'] ?? '', ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($row['status'] ?? 'pending', ENT_QUOTES, 'UTF-8'); // ✅ Default 'pending'
        $approval_status = htmlspecialchars($row['approval_status'] ?? 'not_requested', ENT_QUOTES, 'UTF-8');
        $approved_unit_number = htmlspecialchars($row['approved_unit_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $approval_approved_at = htmlspecialchars($row['approval_approved_at_display'] ?? '', ENT_QUOTES, 'UTF-8');

        $status_lower = strtolower(trim($status));
        $approval_lower = strtolower(trim($approval_status));

        $updateBadge = "";

        $statusMap = [
            'pending' => [
                'Pending',
                'bg-amber-50 text-amber-700 border border-amber-200'
            ],

            'onhold' => [
                'On Hold',
                'bg-orange-50 text-orange-700 border border-orange-200'
            ],

            'responded' => [
                'Responded',
                'bg-emerald-50 text-emerald-700 border border-emerald-200'
            ],

            'declined' => [
                'Declined',
                'bg-red-50 text-red-700 border border-red-200'
            ],

            'reservation submitted' => [
                'Reservation Submitted',
                'bg-blue-50 text-blue-700 border border-blue-200'
            ],

            'officially booked' => [
                'Officially Booked',
                'bg-purple-50 text-purple-700 border border-purple-200'
            ]
        ];

        if (isset($statusMap[$status_lower])) {
            [$displayStatus, $status_class] = $statusMap[$status_lower];
        } else {
            $displayStatus = 'Unknown';
            $status_class = 'bg-slate-50 text-slate-700 border border-slate-200';
        }

        if (
            $status_lower === 'pending' &&
            in_array(
                $approval_lower,
                ['requested', 'approved', 'declined'],
                true
            )
        ) {
            $updateBadge = "
                <span class='ml-1 bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded-full'>
                    UPDATED
                </span>
            ";
        }

        // Fetch the list of owners this inquiry's request(s) went to
        $requestsList = [];
        $pendingRequestCount = 0;

        $requestsStmt->bind_param("i", $row['inq_id']);
        $requestsStmt->execute();
        $requestsResult = $requestsStmt->get_result();

        while ($reqRow = $requestsResult->fetch_assoc()) {
            if (strtolower($reqRow['request_status']) === 'pending') {
                $pendingRequestCount++;
            }

            $requestsList[] = [
                'request_id'     => $reqRow['request_id'],
                'unit_number'    => $reqRow['unit_number'] ?? 'Unknown unit',
                'owner_name'     => $reqRow['owner_name'] ?? 'Unknown owner',
                'request_status' => $reqRow['request_status'],
                'requested_at'   => $reqRow['requested_at'],
                'responded_at'   => $reqRow['responded_at'],
            ];
        }

        $requestsJson = htmlspecialchars(json_encode($requestsList), ENT_QUOTES, 'UTF-8');

       echo "<tr class='inq-row' 
                data-inq-id='" . (int)$row['inq_id'] . "'
                data-status='{$status}'
                data-approval-status='{$approval_status}'
                data-approved-unit='{$approved_unit_number}'
                data-approved-at='{$approval_approved_at}'
                data-requests='{$requestsJson}'
                data-pending-count='{$pendingRequestCount}'
                data-name='" . addslashes($sender_name) . "'
                data-email='" . addslashes($sender_email) . "'
                data-contact='" . addslashes($sender_contact) . "'
                data-inquiry-type='" . addslashes($inquiry_type) . "'
                data-unitpref='" . addslashes($preferred_unit_id) . "'
                data-move-in-time='" . addslashes($preferred_move_in_time) . "'
                data-lease-duration='" . addslashes($lease_duration) . "'
                data-message='" . addslashes($message) . "'
                onclick='openModal(this)'>
                <td class='px-5 py-4'>
                    <div class='min-w-[180px]'>
                        <p class='text-sm font-bold text-slate-900 leading-tight'>
                            {$sender_name}
                        </p>
                        <p class='text-xs text-slate-400 mt-1 leading-tight'>
                            {$sender_email}
                        </p>
                    </div>
                </td>
                <td class='px-4 py-3.5'><span class='text-xs font-semibold px-2.5 py-0.5'>{$inquiry_type}</span></td>
                <td class='px-4 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap'>{$preferred_unit_id}</td>
                <td class='px-4 py-3.5 text-slate-400 text-xs max-w-xs truncate'>{$message}</td>
                      <td class='px-4 py-3.5 text-slate-500 whitespace-nowrap text-xs' style='font-family:&quot;DM Mono&quot;,monospace'>{$dateOnly}</td>
                <td class='px-4 py-3.5'>
                    <span class='status-badge {$status_class} text-xs font-semibold px-2.5 py-0.5 rounded-full'>
                        {$displayStatus}
                    </span>
                    {$updateBadge}
                </td>
                <td class='px-4 py-3.5 text-right'>
                    <button class='btn-press text-xs font-semibold text-slate-500 border border-slate-200 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded-full active:scale-95 transition-all' onclick='event.stopPropagation(); openModal(this.closest(\"tr\"))'>View</button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center px-5 py-3.5 text-slate-500'>No inquiries found.</td></tr>";
}

$stmt->close();
$requestsStmt->close();
$conn->close();
?>