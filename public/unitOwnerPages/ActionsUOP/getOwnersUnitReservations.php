<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'unit owner') {
    echo "
    <tr>
        <td colspan='9' class='px-4 py-6 text-center text-sm text-red-500'>
            Unauthorized access.
        </td>
    </tr>";
    return;
}

$owner_id = (int)$_SESSION['user_id'];

$sql = "
    SELECT 
        r.reservation_id,
        r.inq_id,
        r.unit_id,
        r.client_name,
        r.client_email,
        r.client_contact,
        r.inquiry_type,
        r.resident_type,
        r.transaction_type,
        r.reservation_type,
        r.move_in_date,
        r.move_out_date,
        r.price_basis,
        r.payment_percentage,
        r.required_amount,
        r.payment_method,
        r.payment_reference,
        r.payment_proof,
        r.payment_status,
        r.reservation_status,
        r.created_at,
        r.payment_verified_at,
        r.payment_rejected_at,
        r.officially_booked_at,
        r.requirements_updated_by,
        r.requirements_updated_by_role,
        r.requirements_updated_at,
        updater.full_name AS requirements_updated_by_name,

        (SELECT COUNT(*) FROM reservation_documents d WHERE d.reservation_id = r.reservation_id) AS total_docs,
        (SELECT COUNT(*) FROM reservation_documents d WHERE d.reservation_id = r.reservation_id AND d.status = 'complete') AS completed_docs,

        u.unit_number,
        u.unit_type,
        u.unit_owner_id,

        owner.full_name AS owner_name,
        owner.email AS owner_email
    FROM reservation_table r
    INNER JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
    WHERE u.unit_owner_id = ?
    ORDER BY r.reservation_id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "
    <tr>
        <td colspan='9' class='px-4 py-6 text-center text-sm text-red-500'>
            Prepare failed: " . htmlspecialchars($conn->error) . "
        </td>
    </tr>";
    return;
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "
    <tr>
        <td colspan='9' class='px-4 py-6 text-center text-sm text-slate-400'>
            No reservations found for your units.
        </td>
    </tr>";
    return;
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('peso')) {
    function peso($amount) {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return '₱' . number_format((float)$amount, 2);
    }
}

if (!function_exists('badge')) {
    function badge($value) {
        $status = strtolower(trim($value));

        if (
            $status === 'verified' || 
            $status === 'reserved' || 
            $status === 'requirements completed'
        ) {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200'>" . e(ucwords($value)) . "</span>";
        }

        if (
            $status === 'pending review' || 
            $status === 'submitted' || 
            $status === 'under review' || 
            $status === 'requirements pending'
        ) {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200'>" . e(ucwords($value)) . "</span>";
        }

        if ($status === 'rejected' || $status === 'cancelled') {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200'>" . e(ucwords($value)) . "</span>";
        }

        return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-50 text-slate-600 border border-slate-200'>" . e(ucwords($value)) . "</span>";
    }
}

if (!function_exists('format_timeline_datetime')) {
    function format_timeline_datetime($value) {
        if (empty($value) || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return '';
        }
        $time = strtotime($value);
        return $time ? date('M d, Y \• h:i A', $time) : '';
    }
}

while ($row = $result->fetch_assoc()) {
    $reservationId = str_pad((string)$row['reservation_id'], 3, '0', STR_PAD_LEFT);
    $unitDisplay = trim(($row['unit_type'] ?? '') . ' Unit ' . ($row['unit_number'] ?? ''));

    $proofUrl = '';
    if (!empty($row['payment_proof'])) {
        $proofUrl = '../' . ltrim($row['payment_proof'], '/');
    }

    $submittedDate = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '-';
    $resStatus = strtolower(trim($row['reservation_status'] ?? ''));
    $isMovedIn = in_array($resStatus, ['handover', 'moved in', 'active', 'completed'], true);

    $timelineData = [
        'reservation_id' => (int)$row['reservation_id'],
        'formatted_id' => $reservationId,
        'client_name' => $row['client_name'] ?? 'Client',
        'client_email' => $row['client_email'] ?? '',
        'unit_display' => $unitDisplay,
        'created_at' => format_timeline_datetime($row['created_at'] ?? null),
        'payment_status' => strtolower(trim($row['payment_status'] ?? 'pending review')),
        'payment_verified_at' => format_timeline_datetime($row['payment_verified_at'] ?? null),
        'payment_rejected_at' => format_timeline_datetime($row['payment_rejected_at'] ?? null),
        'reservation_status' => $resStatus,
        'officially_booked_at' => format_timeline_datetime($row['officially_booked_at'] ?? null),
        'requirements_updated_at' => format_timeline_datetime($row['requirements_updated_at'] ?? null),
        'move_in_date' => !empty($row['move_in_date']) && $row['move_in_date'] !== '0000-00-00' ? date('M d, Y', strtotime($row['move_in_date'])) : '',
        'total_docs' => (int)($row['total_docs'] ?? 0),
        'completed_docs' => (int)($row['completed_docs'] ?? 0),
        'is_moved_in' => $isMovedIn,
        'view_url' => 'ownersViewReservation.php?reservation_id=' . urlencode((string)$row['reservation_id'])
    ];
    $timelineJson = htmlspecialchars(json_encode($timelineData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');

    echo "
    <tr class='res-row cursor-pointer hover:bg-slate-50 transition-colors'
        data-timeline='{$timelineJson}'
        onclick='openActivityTimelineModal(this)'>

        <td class='px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            {$reservationId}
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            <p class='font-semibold res-name text-slate-800'>" . e($row['client_name']) . "</p>
            <p class='text-xs text-slate-400'>" . e($row['client_email']) . "</p>
        </td>

        <td class='px-4 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap'>
            " . e($unitDisplay) . "
        </td>

        <td class='px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap'>
            " . e($row['transaction_type']) . "
        </td>

        <td class='px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . peso($row['required_amount']) . "
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            " . badge($row['payment_status']) . "
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            " . badge($row['reservation_status']) . "
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . e($submittedDate) . "
        </td>

        <td class='px-4 py-3.5 text-right'>
            <button
                type='button'
                onclick='openActivityTimelineModal(this.closest(\"tr\")); event.stopPropagation();'
                class='view-btn btn-press inline-flex items-center text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all'
                title='View activity timeline summary'>
                View
            </button>
        </td>
    </tr>";
}

$stmt->close();
?>