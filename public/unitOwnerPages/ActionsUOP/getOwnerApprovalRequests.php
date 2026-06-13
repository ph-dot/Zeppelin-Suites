<?php
require_once __DIR__ . '/../../php_files/db.php';
require_once __DIR__ . '/../../php_files/auth.php';

$user = requireRole($conn, ['unit owner']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'unit owner') {
    echo "
    <tr>
        <td colspan='10' class='px-4 py-10 text-center text-sm text-red-500'>
            Unauthorized access.
        </td>
    </tr>";
    exit;
}

$owner_id = (int)$_SESSION['user_id'];

function clean($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function peso($value) {
    return '₱' . number_format((float)$value, 2);
}

function titleStatus($value) {
    $value = trim((string)$value);

    if ($value === '') {
        return 'Pending';
    }

    return ucwords(str_replace('_', ' ', $value));
}

function getInquiryStatusDisplay($inquiry_status, $approval_status) {
    $inquiry_status = strtolower(trim((string)$inquiry_status));
    $approval_status = strtolower(trim((string)$approval_status));

    if ($inquiry_status === 'reservation submitted') {
        return ['Reservation Submitted', 'bg-blue-50 text-blue-700 border-blue-100'];
    }

    if ($inquiry_status === 'responded') {
        return ['Responded', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
    }

    if ($inquiry_status === 'declined' || $approval_status === 'declined') {
        return ['Declined', 'bg-red-50 text-red-700 border-red-100'];
    }

    if ($approval_status === 'approved_email_sent') {
        return ['Approval Email Sent', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
    }

    if ($approval_status === 'approved') {
        return ['Owner Approved', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
    }

    if ($approval_status === 'requested') {
        return ['Awaiting Owner Approval', 'bg-amber-50 text-amber-700 border-amber-100'];
    }

    if ($approval_status === 'not_requested' || $approval_status === '') {
        return [titleStatus($inquiry_status), 'bg-slate-50 text-slate-700 border-slate-100'];
    }

    return [titleStatus($inquiry_status), 'bg-slate-50 text-slate-700 border-slate-100'];
}

function getOwnerDecisionDisplay($request_status) {
    $status = strtolower(trim((string)$request_status));

    if ($status === 'pending') {
        return ['Pending', 'bg-amber-50 text-amber-700 border-amber-100'];
    }

    if ($status === 'approved') {
        return ['Approved', 'bg-emerald-50 text-emerald-700 border-emerald-100'];
    }

    if ($status === 'declined') {
        return ['Declined', 'bg-red-50 text-red-700 border-red-100'];
    }

    if ($status === 'expired') {
        return ['Expired', 'bg-slate-50 text-slate-700 border-slate-100'];
    }

    return [titleStatus($status), 'bg-slate-50 text-slate-700 border-slate-100'];
}

$sql = "SELECT 
            r.request_id,
            r.inq_id,
            r.request_status,
            r.requested_at,

            i.sender_name,
            i.sender_email,
            i.sender_contact,
            i.inquiry_type,
            i.lease_duration,
            i.message,
            i.status AS inquiry_status,
            i.approval_status,

            u.unit_id,
            u.unit_number,
            u.unit_type,
            u.lease_rate
        FROM owner_approval_requests r
        INNER JOIN Inquiry_table i 
            ON r.inq_id = i.inq_id
        INNER JOIN units_table u 
            ON r.unit_id = u.unit_id
        WHERE r.unit_owner_id = ?
        ORDER BY r.requested_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "
    <tr>
        <td colspan='10' class='px-4 py-10 text-center text-sm text-red-500'>
            Prepare failed: " . clean($conn->error) . "
        </td>
    </tr>";
    exit;
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "
    <tr>
        <td colspan='10' class='px-4 py-14 text-center'>
            <div class='flex flex-col items-center justify-center gap-2'>
                <div class='w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center'>
                    <svg class='w-6 h-6 text-slate-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/>
                    </svg>
                </div>

                <p class='text-sm font-semibold text-slate-700'>
                    No reservation request yet
                </p>

                <p class='text-xs text-slate-400 max-w-sm'>
                    Approval requests from the admin will appear here once a client inquiry is matched with one of your units.
                </p>
            </div>
        </td>
    </tr>";
    exit;
}

while ($row = $result->fetch_assoc()) {
    [$inquiryStatusText, $inquiryStatusClass] = getInquiryStatusDisplay($row['inquiry_status'] ?? '', $row['approval_status'] ?? '');
    [$ownerStatusText, $ownerStatusClass] = getOwnerDecisionDisplay($row['request_status'] ?? 'pending');

    $requestCode = 'REQ-' . str_pad($row['request_id'], 3, '0', STR_PAD_LEFT);

    echo "
    <tr class='group cursor-pointer transition-colors hover:bg-slate-50/50 approval-row'
        data-request-id='" . clean($row['request_id']) . "'
        data-request-code='" . clean($requestCode) . "'
        data-name='" . clean($row['sender_name']) . "'
        data-email='" . clean($row['sender_email']) . "'
        data-contact='" . clean($row['sender_contact']) . "'
        data-type='" . clean($row['inquiry_type']) . "'
        data-unit='" . clean($row['unit_number']) . "'
        data-unit-type='" . clean($row['unit_type']) . "'
        data-fee='" . clean(peso($row['lease_rate'])) . "'
        data-lease='" . clean($row['lease_duration'] ?: '—') . "'
        data-message='" . clean($row['message']) . "'
        data-status='" . clean($ownerStatusText) . "'
        data-inquiry-status='" . clean($inquiryStatusText) . "'
        onclick='openResModal(this)'>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . clean($requestCode) . "
        </td>

        <td colspan='2' class='px-4 py-3.5 border-b border-slate-100/50 whitespace-nowrap'>
            <div class='flex flex-col gap-0.5'>
                <p class='text-sm font-bold text-slate-900'>
                    " . clean($row['sender_name']) . " - Unit " . clean($row['unit_number']) . "
                </p>

                <p class='text-xs text-slate-400'>
                    " . clean($row['sender_email']) . "
                </p>
            </div>
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . clean($row['sender_contact']) . "
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
            <span class='bg-purple-50 text-purple-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-purple-100'>
                " . clean($row['inquiry_type']) . "
            </span>
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . clean(peso($row['lease_rate'])) . "
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
            " . clean($row['lease_duration'] ?: '—') . "
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600 whitespace-nowrap'>
            <span class='" . $inquiryStatusClass . " text-xs font-semibold px-2.5 py-0.5 rounded-full border'>
                " . clean($inquiryStatusText) . "
            </span>
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-sm text-zinc-600'>
            <span class='" . $ownerStatusClass . " text-xs font-semibold px-2.5 py-0.5 rounded-full border'>
                " . clean($ownerStatusText) . "
            </span>
        </td>

        <td class='px-4 py-3.5 border-b border-slate-100/50 text-right'>
            <button class='btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all opacity-0 group-hover:opacity-100 whitespace-nowrap'
                    onclick='event.stopPropagation(); openResModal(this.closest(\"tr\"))'>
                View
            </button>
        </td>
    </tr>";
}

$stmt->close();
$conn->close();
?>
