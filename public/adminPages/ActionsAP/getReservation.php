<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

$sql = "
    SELECT 
        r.reservation_id,
        r.unit_id,
        r.guest_name,
        r.email,
        r.phone,
        r.lease_start,
        r.lease_end,
        r.status,

        u.unit_number,
        u.unit_type,
        u.base_rate,
        u.lease_rate
    FROM booking_table r
    LEFT JOIN units_table u ON r.unit_id = u.unit_id
    ORDER BY r.reservation_id DESC
";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo "
    <tr>
        <td colspan='13' class='px-4 py-6 text-center text-sm text-slate-400'>
            No reservations found.
        </td>
    </tr>";
    return;
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function peso($amount) {
    if ($amount === null || $amount === '') {
        return '-';
    }

    return '₱' . number_format((float)$amount, 2);
}

function statusBadge($status) {
    $statusLower = strtolower(trim($status));

    if ($statusLower === 'approved') {
        return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200'>Approved</span>";
    }

    if ($statusLower === 'pending') {
        return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200'>Pending</span>";
    }

    if ($statusLower === 'rejected' || $statusLower === 'declined') {
        return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200'>Rejected</span>";
    }

    return "<span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-50 text-slate-600 border border-slate-200'>" . e(ucfirst($status)) . "</span>";
}

while ($row = $result->fetch_assoc()) {
    $reservationId = str_pad($row['reservation_id'], 3, '0', STR_PAD_LEFT);

    $guestName = e($row['guest_name']);
    $email = e($row['email']);
    $phone = e($row['phone']);

    $unitNumber = $row['unit_number'] ? 'Unit ' . e($row['unit_number']) : '-';

    $leaseStart = e($row['lease_start']);
    $leaseEnd = e($row['lease_end']);

    $status = strtolower(e($row['status']));
    $statusHtml = statusBadge($row['status']);

    // Fee: use lease_rate first, if null use base_rate
    $fee = $row['lease_rate'] !== null ? peso($row['lease_rate']) : peso($row['base_rate']);

    // Duration computation
    $duration = '-';
    if (!empty($row['lease_start']) && !empty($row['lease_end'])) {
        $start = new DateTime($row['lease_start']);
        $end = new DateTime($row['lease_end']);
        $diff = $start->diff($end);

        if ($diff->m > 0 || $diff->y > 0) {
            $months = ($diff->y * 12) + $diff->m;
            $duration = $months . ' month' . ($months > 1 ? 's' : '');
        } else {
            $days = $diff->days;
            $duration = $days . ' day' . ($days > 1 ? 's' : '');
        }
    }

    echo "
    <tr class='res-row cursor-pointer' 
        data-status='{$status}'
        data-reservation-id='" . e($row['reservation_id']) . "'
        data-unit-id='" . e($row['unit_id']) . "'
        data-unit-number='" . e($row['unit_number']) . "'
        data-name='{$guestName}'
        data-email='{$email}'
        data-phone='{$phone}'
        data-lease-start='{$leaseStart}'
        data-lease-end='{$leaseEnd}'>

        <td class='px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            {$reservationId}
        </td>

        <td class='px-4 py-3.5 font-semibold res-name text-slate-800 whitespace-nowrap'>
            {$guestName}
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs'>
            {$email}
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            {$phone}
        </td>

        <td class='px-4 py-3.5 text-slate-600 text-xs'>
            Guest
        </td>

        <td class='px-4 py-3.5 text-slate-600 text-xs'>
            Reservation
        </td>

        <td class='px-4 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap'>
            {$unitNumber}
        </td>

        <td class='px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            {$fee}
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            {$duration}
        </td>

        <td class='px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap' style=\"font-family:'DM Mono',monospace\">
            {$leaseStart}
        </td>

        <td class='px-4 py-3.5'>
            {$statusHtml}
        </td>

        <td class='px-4 py-3.5'>
            <span class='text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-50 text-slate-600 border border-slate-200'>-</span>
        </td>

        <td class='px-4 py-3.5 text-right'>
            <button class='view-btn btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all' onclick=\"openEditModal(this.closest('tr'))\">
                View
            </button>
        </td>
    </tr>";
}
?>