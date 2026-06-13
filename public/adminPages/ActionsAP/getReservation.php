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

        if ($status === 'pending review' || $status === 'submitted' || $status === 'under review' || $status === 'requirements pending' || $status === 'requested') {
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
        r.payment_proof,
        r.payment_status,
        r.reservation_status,
        r.admin_remarks,
        r.created_at,
        r.two_valid_ids_status,
        r.tin_number_status,
        r.reservation_agreement_status,
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
        data-reservation-id='" . e($row['reservation_id']) . "'
        data-inquiry-id='" . e($row['inq_id']) . "'
        data-unit-id='" . e($row['unit_id']) . "'
        data-client-name='" . e($row['client_name']) . "'
        data-client-email='" . e($row['client_email']) . "'
        data-client-contact='" . e($row['client_contact']) . "'
        data-inquiry-type='" . e($row['inquiry_type']) . "'
        data-resident-type='" . e($row['resident_type'] ?? '-') . "'
        data-transaction-type='" . e($row['transaction_type'] ?? '-') . "'
        data-reservation-type='" . e($row['reservation_type'] ?? '-') . "'
        data-unit='" . e($unitDisplay) . "'
        data-unit-current-status='" . e($row['unit_current_status'] ?? '-') . "'
        data-owner-name='" . e($row['owner_name'] ?? 'No owner assigned') . "'
        data-owner-email='" . e($row['owner_email'] ?? '-') . "'
        data-move-in='" . e(format_date_only($row['move_in_date'] ?? null)) . "'
        data-move-out='" . e(format_date_only($row['move_out_date'] ?? null)) . "'
        data-price-basis='" . e(peso($row['price_basis'])) . "'
        data-payment-percentage='" . e(percent_text($row['payment_percentage'])) . "'
        data-required-amount='" . e(peso($row['required_amount'])) . "'
        data-payment-method='" . e($row['payment_method']) . "'
        data-payment-reference='" . e($row['payment_reference']) . "'
        data-payment-proof='" . e($proofUrl) . "'
        data-payment-status='" . e($row['payment_status']) . "'
        data-reservation-status='" . e($row['reservation_status']) . "'
        data-admin-remarks='" . e($row['admin_remarks'] ?? '') . "'
        data-admin-payment-remarks='" . e($row['admin_payment_remarks'] ?? '') . "'
        data-payment-verified-at='" . e(format_datetime_text($row['payment_verified_at'] ?? null)) . "'
        data-payment-rejected-at='" . e(format_datetime_text($row['payment_rejected_at'] ?? null)) . "'
        data-two-valid-ids-status='" . e((int)$row['two_valid_ids_status']) . "'
        data-tin-number-status='" . e((int)$row['tin_number_status']) . "'
        data-reservation-agreement-status='" . e((int)$row['reservation_agreement_status']) . "'
        data-requirements-updated-by-name='" . e($row['requirements_updated_by_name'] ?? 'Not updated yet') . "'
        data-requirements-updated-by-role='" . e($row['requirements_updated_by_role'] ?? '-') . "'
        data-requirements-updated-at='" . e(format_datetime_text($row['requirements_updated_at'] ?? null)) . "'
        data-officially-booked-by-name='" . e($row['officially_booked_by_name'] ?? '-') . "'
        data-officially-booked-by-role='" . e($row['officially_booked_by_role'] ?? '-') . "'
        data-officially-booked-at='" . e(format_datetime_text($row['officially_booked_at'] ?? null)) . "'
        data-cancelled-by-name='" . e($row['cancelled_by_name'] ?? '-') . "'
        data-cancelled-by-role='" . e($row['cancelled_by_role'] ?? '-') . "'
        data-cancelled-at='" . e(format_datetime_text($row['cancelled_at'] ?? null)) . "'
        data-admin-cancel-remarks='" . e($row['admin_cancel_remarks'] ?? '') . "'
        data-cancellation-status='" . e($row['cancellation_status'] ?? 'none') . "'
        data-cancellation-reason='" . e($row['cancellation_reason'] ?? '') . "'
        data-cancellation-requested-by='" . e($cancellationRequestedBy) . "'
        data-cancellation-requested-at='" . e(format_datetime_text($row['cancellation_requested_at'] ?? null)) . "'>

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
            " . badge($row['payment_status']) . "
        </td>

        <td class='px-4 py-3.5 whitespace-nowrap'>
            <div class='flex items-center gap-2'>
                " . badge($row['reservation_status']) . "
                " . (
                    strtolower($row['cancellation_status'] ?? 'none') === 'requested'
                    ? "<button
                        type='button'
                        title='Cancellation requested'
                        class='w-6 h-6 inline-flex items-center justify-center rounded-full bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-all'
                        onclick=\"openEditModal(this.closest('tr'))\">
                        <span class='text-xs font-black'>!</span>
                    </button>"
                    : ""
                ) . "
            </div>
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            " . e($submittedDate) . "
        </td>

        <td class='px-4 py-3.5 text-right'>
            <button
                type='button'
                class='view-btn btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all'
                onclick=\"openEditModal(this.closest('tr'))\">
                View
            </button>
        </td>
    </tr>";
}
?>
