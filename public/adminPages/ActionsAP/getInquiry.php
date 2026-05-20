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
$sql = "SELECT inq_id, sender_name, sender_email, sender_contact, inquiry_type, Preferred_unit_id, message, 
        DATE(timestamp) as date_only, status, lease_duration
        FROM Inquiry_table 
        ORDER BY timestamp DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$endItem = min($offset + $result->num_rows, $totalItems);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Escape data to prevent XSS
        $sender_name = htmlspecialchars($row['sender_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $sender_email = htmlspecialchars($row['sender_email'] ?? '', ENT_QUOTES, 'UTF-8');
        $sender_contact = htmlspecialchars($row['sender_contact'] ?? '', ENT_QUOTES, 'UTF-8');
        $inquiry_type = htmlspecialchars($row['inquiry_type'] ?? '', ENT_QUOTES, 'UTF-8');
        $preferred_unit_id = htmlspecialchars($row['Preferred_unit_id'] ?? '', ENT_QUOTES, 'UTF-8');
        $lease_duration = htmlspecialchars($row['lease_duration'] ?? '', ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $dateOnly = htmlspecialchars($row['date_only'] ?? '', ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($row['status'] ?? 'pending', ENT_QUOTES, 'UTF-8'); // ✅ Default 'pending'

        echo "<tr class='inq-row' 
                data-inq-id='{$row['inq_id']}'
                data-status='{$status}'  ✅ ADDED: Missing for filtering/stats
                data-name='" . addslashes($sender_name) . "'
                data-email='" . addslashes($sender_email) . "'
                data-contact='" . addslashes($sender_contact) . "'
                data-inquiry-type='" . addslashes($inquiry_type) . "'
                data-unitpref='" . addslashes($preferred_unit_id) . "'
                data-lease-duration='" . addslashes($lease_duration) . "'
                data-message='" . addslashes($message) . "'
                onclick='openModal(this)'>
                <td class='px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap'>{$sender_name}</td>
                <td class='px-4 py-3.5 text-slate-500 text-xs'>{$sender_email}</td>
                <td class='px-4 py-3.5'><span class='text-xs font-semibold px-2.5 py-0.5'>{$inquiry_type}</span></td>
                <td class='px-4 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap'>{$preferred_unit_id}</td>
                <td class='px-4 py-3.5 text-slate-400 text-xs max-w-xs truncate'>{$message}</td>
                      <td class='px-4 py-3.5 text-slate-500 whitespace-nowrap text-xs' style='font-family:&quot;DM Mono&quot;,monospace'>{$dateOnly}</td>
                <td class='px-4 py-3.5'>
                    <span class='status-badge " . ($status === 'responded' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200') . " text-xs font-semibold px-2.5 py-0.5 rounded-full'>{$status}</span>
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
$conn->close();
?></script>