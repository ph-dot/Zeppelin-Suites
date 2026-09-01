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
        r.requirements_updated_by,
        r.requirements_updated_by_role,
        r.requirements_updated_at,
        updater.full_name AS requirements_updated_by_name,

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

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function peso($amount) {
    if ($amount === null || $amount === '') {
        return '-';
    }

    return '₱' . number_format((float)$amount, 2);
}

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

while ($row = $result->fetch_assoc()) {
    $reservationId = str_pad($row['reservation_id'], 3, '0', STR_PAD_LEFT);
    $unitDisplay = trim(($row['unit_type'] ?? '') . ' Unit ' . ($row['unit_number'] ?? ''));

    $proofUrl = '';
    if (!empty($row['payment_proof'])) {
        $proofUrl = '../' . ltrim($row['payment_proof'], '/');
    }

    $submittedDate = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '-';

    echo "
    <tr class='res-row cursor-pointer hover:bg-slate-50 transition-colors'
        onclick=\"window.location.href='ownersViewReservation.php?reservation_id=" . e($row['reservation_id']) . "'\">

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
            <a
                href='ownersViewReservation.php?reservation_id=" . e($row['reservation_id']) . "'
                class='view-btn btn-press inline-flex items-center text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all'
                onclick='event.stopPropagation();'>
                View
            </a>
        </td>
    </tr>";
}

$stmt->close();
?>