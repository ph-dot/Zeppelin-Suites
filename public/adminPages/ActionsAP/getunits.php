<?php
require_once '../php_files/admin_auth.php';
require_once '../php_files/db.php';
require_once '../php_files/sync_unit_status.php';

syncExpiredUnitStatuses($conn);

function clean($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function peso($value, $isLease = false) {
    if ($value === null || $value === '' || ($isLease && (float)$value === 0)) {
        return $isLease ? '—' : '₱0.00';
    }
    return '₱' . number_format((float)$value, 2);
}

// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM units_table";
$countResult = $conn->query($countSql);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// SQL query with pagination
$sql = "SELECT 
            u.unit_id,
            u.unit_number,
            u.unit_type,
            u.base_rate,
            u.lease_rate,
            u.unit_owner_id,
            u.unit_current_status,  -- add this
            u.created_at,
            uo.full_name AS unit_owner_name
        FROM units_table u
        LEFT JOIN users_table uo 
            ON u.unit_owner_id = uo.user_id
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $itemsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$startItem = $totalItems > 0 ? $offset + 1 : 0;
$endItem = min($offset + $result->num_rows, $totalItems);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $unit_id = clean($row['unit_id']);
        $unit_number = clean($row['unit_number']);
        $unit_type = clean($row['unit_type']);
        $base_rate = peso($row['base_rate']);             
        $lease_rate = peso($row['lease_rate'], true); 
        $unit_owner_id = clean($row['unit_owner_id']);
        $unit_current_status = clean($row['unit_current_status']);
        $unit_owner_name = clean($row['unit_owner_name'] ?: 'No owner');

        // Unit Status Badge
        $status_lower = strtolower(trim($unit_current_status));

            if ($status_lower === 'ready for occupancy') {
                $status_class = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
            } elseif ($status_lower === 'on hold') {
                $status_class = 'bg-amber-50 text-amber-700 border border-amber-200';
            } elseif ($status_lower === 'resale') {
                $status_class = 'bg-blue-50 text-blue-700 border border-blue-200';
            } elseif ($status_lower === 'reserved') {
                $status_class = 'bg-red-50 text-red-700 border border-red-200';
            } elseif ($status_lower === 'occupied') {
                $status_class = 'bg-rose-50 text-rose-700 border border-rose-200';
            } elseif ($status_lower === 'under maintenance') {
                $status_class = 'bg-orange-50 text-orange-700 border border-orange-200';
            } else {
                $status_class = 'bg-slate-50 text-slate-700 border border-slate-200';
            }

            if (strtolower(trim($unit_current_status)) === 'resale') {
                $price_value = peso($row['lease_rate']); // use raw DB value
            } else {
                $price_value = peso($row['lease_rate'], true); // use raw DB value
            }
        // Price display logic

        echo "
            <tr class='unit-row hover:bg-slate-50 transition-colors'
        data-unit-id='" . $unit_id . "'
        data-unit-number='" . $unit_number . "'
        data-unit-type='" . $unit_type . "'
        data-base-rate='" . $base_rate . "'
        data-lease-rate='" . $lease_rate . "'
        data-unit-current-status='" . $unit_current_status . "'
        data-owner-name='" . $unit_owner_name . "'>

        <td class='px-4 py-3 whitespace-nowrap'>
                <div class='unit-num font-semibold text-slate-900'>" . $unit_number . "</div>
                <div class='text-xs text-slate-400 mt-0.5'>" . $unit_type . "</div>
        </td>
        <td class='px-3 py-3 text-slate-700 whitespace-nowrap'>" . $base_rate . "</td>
        <td class='px-3 py-3 text-slate-700 whitespace-nowrap'>" . $unit_owner_name . "</td>
        <td class='px-3 py-3 text-slate-700 whitespace-nowrap'>" . $price_value . "</td>
        <td class='px-3 py-3 whitespace-nowrap'>
            <span class='status-badge " . $status_class . " text-xs font-semibold px-2.5 py-0.5 rounded-full'>
                " . $unit_current_status . "
            </span>
        </td>
        <td class='px-3 py-3 w-[90px] min-w-[90px] whitespace-nowrap'>
            <div class='flex justify-center items-center'>
                <button class='edit-btn inline-flex items-center justify-center text-xs font-semibold text-white bg-slate-900 hover:bg-slate-700 px-3 py-1 rounded-full active:scale-95 transition-all'>
                    Edit
                </button>
            </div>
        </td>
    </tr>";
    }
} else {
    echo "
    <tr>
        <td colspan='6' class='text-center px-5 py-12 text-slate-500'>
            No units found.
        </td>
    </tr>";
}

$stmt->close();
?>