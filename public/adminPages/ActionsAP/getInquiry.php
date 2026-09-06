<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';


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


// SQL query - NEWEST FIRST
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
            i.owner_remarks,
            i.approval_approved_at,
            DATE_FORMAT(i.approval_approved_at, '%b %d, %Y %h:%i %p') AS approval_approved_at_display,
            i.lease_duration,
            u.unit_number AS approved_unit_number
        FROM Inquiry_table i
        LEFT JOIN units_table u 
            ON i.approved_unit_id = u.unit_id
        ORDER BY i.timestamp DESC, i.inq_id DESC";
$result = $conn->query($sql);

// Look up who each pending/answered request actually went to, so the
// admin modal can show real owner/unit info instead of a generic
// "sent to available unit owners" message when reopened.
$requestsStmt = $conn->prepare("
    SELECT
        r.request_id,
        r.unit_id,
        r.request_status,
        r.owner_remarks,
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
                'owner_remarks'  => $reqRow['owner_remarks'] ?? '',
                'requested_at'   => $reqRow['requested_at'],
                'responded_at'   => $reqRow['responded_at'],
            ];
        }

        $requestsJson = htmlspecialchars(json_encode($requestsList), ENT_QUOTES, 'UTF-8');
        $displayUnitPref = !empty($preferred_unit_id) ? $preferred_unit_id : '—';
        $displayMessage = !empty($message) ? $message : '—';

        // Custom status indicators beside Pending status
        $updateBadge = "";
        if ($status_lower === 'pending' || $status_lower === 'onhold') {
            if ($approval_lower === 'approved') {
                $approvedUnitInfo = !empty($approved_unit_number) ? " - Unit " . $approved_unit_number : "";
                $updateBadge = "
                    <span class='group relative inline-flex items-center ml-1.5 align-middle cursor-help' title='Owner has approved{$approvedUnitInfo}'>
                        <span class='inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-300 shadow-2xs hover:bg-emerald-200 transition-all'>
                            <svg class='w-2.5 h-2.5 text-emerald-700' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M5 13l4 4L19 7'/></svg>
                            Approved
                        </span>
                        <span class='pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex flex-col items-center z-30'>
                            <span class='bg-slate-900 text-white text-[11px] font-medium px-2.5 py-1 rounded-lg shadow-lg whitespace-nowrap'>
                                Owner has approved{$approvedUnitInfo}
                            </span>
                            <span class='w-2 h-2 bg-slate-900 rotate-45 -mt-1'></span>
                        </span>
                    </span>
                ";
            } elseif ($approval_lower === 'requested' || $pendingRequestCount > 0) {
                $updateBadge = "
                    <span class='group relative inline-flex items-center ml-1.5 align-middle cursor-help' title='Request is still pending'>
                        <span class='inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold border border-amber-300 shadow-2xs hover:bg-amber-200 transition-all'>
                            !
                        </span>
                        <span class='pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex flex-col items-center z-30'>
                            <span class='bg-slate-900 text-white text-[11px] font-medium px-2.5 py-1 rounded-lg shadow-lg whitespace-nowrap'>
                                Request is still pending
                            </span>
                            <span class='w-2 h-2 bg-slate-900 rotate-45 -mt-1'></span>
                        </span>
                    </span>
                ";
            } elseif ($approval_lower === 'declined') {
                $updateBadge = "
                    <span class='group relative inline-flex items-center ml-1.5 align-middle cursor-help' title='Owner declined request'>
                        <span class='inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-100 text-red-700 text-[10px] font-bold border border-red-300 shadow-2xs hover:bg-red-200 transition-all'>
                            ✕
                        </span>
                        <span class='pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex flex-col items-center z-30'>
                            <span class='bg-slate-900 text-white text-[11px] font-medium px-2.5 py-1 rounded-lg shadow-lg whitespace-nowrap'>
                                Owner declined request
                            </span>
                            <span class='w-2 h-2 bg-slate-900 rotate-45 -mt-1'></span>
                        </span>
                    </span>
                ";
            }
        }

       echo "<tr class='inq-row' 
                data-inq-id='" . (int)$row['inq_id'] . "'
                data-status='{$status}'
                data-approval-status='{$approval_status}'
                data-approved-unit='{$approved_unit_number}'
                data-approved-at='{$approval_approved_at}'
                data-owner-remarks='" . addslashes(htmlspecialchars($row['owner_remarks'] ?? '', ENT_QUOTES, 'UTF-8')) . "'
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
                <td class='px-5 py-4 text-left align-middle'>
                    <div class='min-w-[180px]'>
                        <p class='text-sm font-bold text-slate-900 leading-tight'>
                            {$sender_name}
                        </p>
                        <p class='text-xs text-slate-400 mt-1 leading-tight'>
                            {$sender_email}
                        </p>
                    </div>
                </td>
                <td class='px-4 py-3.5 text-left align-middle whitespace-nowrap'>
                    <span class='text-xs font-semibold text-slate-800'>{$inquiry_type}</span>
                </td>
                <td class='px-4 py-3.5 text-left align-middle text-slate-700 text-xs font-medium whitespace-nowrap'>{$displayUnitPref}</td>
                <td class='px-4 py-3.5 text-left align-middle text-slate-400 text-xs max-w-xs truncate'>{$displayMessage}</td>
                <td class='px-4 py-3.5 text-left align-middle text-slate-500 whitespace-nowrap text-xs' style='font-family:&quot;DM Mono&quot;,monospace'>{$dateOnly}</td>
                <td class='px-4 py-3.5 text-left align-middle whitespace-nowrap'>
                    <span class='status-badge {$status_class} text-xs font-semibold px-2.5 py-0.5 rounded-full inline-flex items-center'>
                        {$displayStatus}
                    </span>
                    {$updateBadge}
                </td>
                <td class='px-4 py-3.5 text-right align-middle whitespace-nowrap'>
                    <button class='btn-press text-xs font-semibold text-slate-500 border border-slate-200 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded-full active:scale-95 transition-all' onclick='event.stopPropagation(); openModal(this.closest(\"tr\"))'>View</button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center px-5 py-8 text-slate-400 text-sm'>No inquiries found.</td></tr>";
}

$requestsStmt->close();
$conn->close();
?>