<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'unit owner') {
    echo "
    <tr>
      <td colspan='7' class='px-4 py-6 text-center text-sm text-red-500'>
        Unauthorized access.
      </td>
    </tr>";
    return;
}

$owner_id = (int)$_SESSION['user_id'];

$sql = "
    SELECT 
        m.maintenance_id,
        m.unit_id,
        m.subject,
        m.category,
        m.description,
        m.priority,
        m.status,
        m.photo_paths,
        m.admin_remarks,
        m.submitted_at,
        m.resolved_at,
        u.unit_number,
        u.unit_type
    FROM maintenance_requests m
    INNER JOIN units_table u ON m.unit_id = u.unit_id
    WHERE m.unit_owner_id = ?
    ORDER BY m.maintenance_id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "
    <tr>
      <td colspan='7' class='px-4 py-6 text-center text-sm text-red-500'>
        Unable to load maintenance requests.
      </td>
    </tr>";
    return;
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function statusBadge($status) {
    $statusLower = strtolower(trim($status));

    if ($statusLower === 'pending') {
        return "<span class='bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-amber-100'>Pending</span>";
    }

    if ($statusLower === 'in progress') {
        return "<span class='bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-100'>In Progress</span>";
    }

    if ($statusLower === 'resolved') {
        return "<span class='bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-emerald-100'>Resolved</span>";
    }

    if ($statusLower === 'cancelled') {
        return "<span class='bg-red-50 text-red-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-red-100'>Cancelled</span>";
    }

    return "<span class='bg-slate-50 text-slate-600 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-slate-100'>" . e($status) . "</span>";
}

if (!$result || $result->num_rows === 0) {
    echo "
    <tr>
      <td colspan='7' class='px-4 py-6 text-center text-sm text-slate-400'>
        No maintenance requests found.
      </td>
    </tr>";
    return;
}

while ($row = $result->fetch_assoc()) {
    $requestId = 'MR-' . str_pad($row['maintenance_id'], 3, '0', STR_PAD_LEFT);
    $unitLabel = trim(($row['unit_number'] ?? '') . ' - ' . ($row['unit_type'] ?? ''));
    $submittedDate = !empty($row['submitted_at']) ? date('M d, Y H:i', strtotime($row['submitted_at'])) : '-';
    $resolvedDate = !empty($row['resolved_at']) ? date('M d, Y H:i', strtotime($row['resolved_at'])) : 'Not yet resolved';

    $photoUrls = [];

    if (!empty($row['photo_paths'])) {
        $savedPhotos = explode(',', $row['photo_paths']);

        foreach ($savedPhotos as $photo) {
            $photo = str_replace('\\/', '/', $photo);
            $photo = trim($photo, " \t\n\r\0\x0B[]\"'");

            if (strpos($photo, 'uploads/maintenance/') === 0) {
                $photoUrls[] = '../' . ltrim($photo, '/');
            }
        }
    }

    $photosValue = implode(',', $photoUrls);
    $adminRemarks = $row['admin_remarks'] ?: 'No admin remarks yet.';
    

    echo "
    <tr class='group transition-colors hover:bg-slate-50/50'>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
        " . e($requestId) . "
      </td>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
        <span class='bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-100'>
          " . e($unitLabel) . "
        </span>
      </td>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
      " . e($row['category']) . "
    </td>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
        " . e($row['subject']) . "
      </td>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
        " . e($submittedDate) . "
      </td>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
        " . statusBadge($row['status']) . "
      </td>

      <td class='px-4 py-3.5 border-b border-slate-100/50 text-right'>
        <button 
          type='button'
          class='btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all opacity-0 group-hover:opacity-100 whitespace-nowrap'
          data-id='" . e($requestId) . "'
          data-unit='" . e($unitLabel) . "'
          data-issue='" . e($row['subject']) . "'
          data-category='" . e($row['category']) . "'
          data-priority='" . e(ucfirst($row['priority'])) . "'
          data-date='" . e($submittedDate) . "'
          data-resolved-date='" . e($resolvedDate) . "'
          data-status='" . e(ucwords($row['status'])) . "'
          data-description='" . e($row['description']) . "'
          data-remarks='" . e($adminRemarks) . "'
          data-photos='" . e($photosValue) . "'
          onclick='event.stopPropagation(); openViewModal(this)'>
          View
        </button>
      </td>
    </tr>";
}

$stmt->close();
?>
