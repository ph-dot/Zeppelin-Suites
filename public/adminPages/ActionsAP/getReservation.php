<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
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

if (!function_exists('percent_text')) {
    function percent_text($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        $number = (float)$value;

        // Current reservation_table stores percentages as decimals like 0.50 and 0.35.
        // This also stays safe if an older row stored 50 instead of 0.50.
        if ($number <= 1) {
            $number *= 100;
        }

        return rtrim(rtrim(number_format($number, 2), '0'), '.') . '%';
    }
}

if (!function_exists('format_date_only')) {
    function format_date_only($value) {
        if (empty($value) || $value === '0000-00-00') {
            return '-';
        }

        $time = strtotime($value);
        return $time ? date('Y-m-d', $time) : '-';
    }
}

if (!function_exists('format_datetime_text')) {
    function format_datetime_text($value) {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return '-';
        }

        $time = strtotime($value);
        return $time ? date('Y-m-d h:i A', $time) : '-';
    }
}

if (!function_exists('badge')) {
    function badge($value) {
        $text = trim((string)($value ?? ''));
        $status = strtolower($text);

        if ($status === 'verified' || $status === 'reserved' || $status === 'requirements completed') {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200'>" . e(ucwords($text)) . "</span>";
        }

        if ($status === 'pending review' || $status === 'submitted' || $status === 'under review' || $status === 'requirements pending' || $status === 'requested' || $status === 'flagged for review') {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200'>" . e(ucwords($text)) . "</span>";
        }

        if ($status === 'rejected' || $status === 'cancelled' || $status === 'declined') {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200'>" . e(ucwords($text)) . "</span>";
        }

        if ($status === 'approved') {
            return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200'>" . e(ucwords($text)) . "</span>";
        }

        return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-50 text-slate-600 border border-slate-200'>" . e($text !== '' ? ucwords($text) : '-') . "</span>";
    }
}

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
        r.declared_amount,
        r.amount_match_status,
        r.payment_proof,
        r.payment_status,
        r.reservation_status,
        r.admin_remarks,
        r.created_at,
        r.payment_verified_at,
        r.payment_rejected_at,
        r.admin_payment_remarks,
        r.requirements_updated_by,
        r.requirements_updated_by_role,
        r.requirements_updated_at,
        r.officially_booked_at,
        r.officially_booked_by,
        r.officially_booked_by_role,
        r.cancelled_at,
        r.cancelled_by,
        r.cancelled_by_role,
        r.admin_cancel_remarks,
        r.cancellation_status,
        r.cancellation_reason,
        r.cancellation_requested_by,
        r.cancellation_requested_at,
        r.cancellation_requested_by_role,

        u.unit_number,
        u.unit_type,
        u.unit_current_status,

        owner.full_name AS owner_name,
        owner.email AS owner_email,
        updater.full_name AS requirements_updated_by_name,
        official_user.full_name AS officially_booked_by_name,
        cancelled_user.full_name AS cancelled_by_name,
        cancel_requester.full_name AS cancellation_requested_by_name
    FROM reservation_table r
    LEFT JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
    LEFT JOIN users_table official_user ON r.officially_booked_by = official_user.user_id
    LEFT JOIN users_table cancelled_user ON r.cancelled_by = cancelled_user.user_id
    LEFT JOIN users_table cancel_requester ON r.cancellation_requested_by = cancel_requester.user_id
    ORDER BY r.reservation_id DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo "
    <tr>
        <td colspan='9' class='px-4 py-6 text-center text-sm text-red-500'>
            Unable to load reservations. " . e($conn->error) . "
        </td>
    </tr>";
    return;
}

if ($result->num_rows === 0) {
    echo "
    <tr>
        <td colspan='9' class='px-4 py-6 text-center text-sm text-slate-400'>
            No reservations found.
        </td>
    </tr>";
    return;
}

while ($row = $result->fetch_assoc()) {
    $reservationId = str_pad((string)$row['reservation_id'], 3, '0', STR_PAD_LEFT);

    $unitParts = array_filter([
        $row['unit_type'] ?? '',
        !empty($row['unit_number']) ? 'Unit ' . $row['unit_number'] : ''
    ]);
    $unitDisplay = trim(implode(' ', $unitParts));
    if ($unitDisplay === '') {
        $unitDisplay = 'Unit not found';
    }

    $proofUrl = '';
    if (!empty($row['payment_proof'])) {
        $proofUrl = '../' . ltrim($row['payment_proof'], '/');
    }

    $submittedDate = format_date_only($row['created_at'] ?? null);

    $cancellationRequestedBy = 'Unit Owner';
    if (($row['cancellation_requested_by_role'] ?? '') === 'client') {
        $cancellationRequestedBy = 'Client';
    } elseif (!empty($row['cancellation_requested_by_name'])) {
        $cancellationRequestedBy = $row['cancellation_requested_by_name'];
    }

    echo "
    <tr class='res-row cursor-pointer hover:bg-slate-50 transition-colors'
        onclick=\"window.location.href='viewReservation.php?reservation_id=" . e($row['reservation_id']) . "'\">

        <td class='px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . e($reservationId) . "
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            <p class='font-semibold res-name text-slate-800'>" . e($row['client_name']) . "</p>
            <p class='text-xs text-slate-400'>" . e($row['client_email']) . "</p>
        </td>

        <td class='px-4 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap'>
            " . e($unitDisplay) . "
        </td>

        <td class='px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap'>
            " . e($row['transaction_type'] ?? '-') . "
        </td>

        <td class='px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . e(peso($row['required_amount'])) . "
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            <div class='flex items-center gap-2'>
                " . badge($row['payment_status']) . "
                " . (
                    in_array($row['amount_match_status'] ?? '', ['short', 'over'], true)
                    ? "<span
                        title='Declared amount does not match required amount'
                        class='w-6 h-6 inline-flex items-center justify-center rounded-full bg-amber-50 text-amber-600 border border-amber-200'>
                        <span class='text-xs font-black'>!</span>
                    </span>"
                    : ""
                ) . "
            </div>
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            <div class='flex items-center gap-2'>
                " . badge($row['reservation_status']) . "
                " . (
                    strtolower($row['cancellation_status'] ?? 'none') === 'requested'
                    ? "<a
                        href='viewReservation.php?reservation_id=" . e($row['reservation_id']) . "'
                        title='Cancellation requested'
                        class='w-6 h-6 inline-flex items-center justify-center rounded-full bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-all'
                        onclick='event.stopPropagation();'>
                        <span class='text-xs font-black'>!</span>
                    </a>"
                    : ""
                ) . "
            </div>
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . e($submittedDate) . "
        </td>

        <td class='px-4 py-3.5 text-right'>
            <a
                href='viewReservation.php?reservation_id=" . e($row['reservation_id']) . "'
                class='view-btn btn-press inline-flex items-center text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all'
                onclick='event.stopPropagation();'>
                View
            </a>
        </td>
    </tr>";
}
?>
