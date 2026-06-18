<?php
/*
  Zeppelin Suites - Analytics backend
  This file is page-load based PHP + Chart.js. No AJAX endpoint is needed.
*/
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../php_files/auth.php';
require_once __DIR__ . '/../php_files/db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bindParams(mysqli_stmt $stmt, string $types, array &$params): void {
    if ($types === '' || empty($params)) {
        return;
    }

    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

function fetchRows(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function fetchOne(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $rows = fetchRows($conn, $sql, $types, $params);
    return $rows[0] ?? [];
}

function fetchScalar(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $row = fetchOne($conn, $sql, $types, $params);
    if (!$row) {
        return 0;
    }
    return reset($row) ?: 0;
}

function percentNumber(float $part, float $whole, int $decimals = 1): float {
    if ($whole <= 0) {
        return 0.0;
    }
    return round(($part / $whole) * 100, $decimals);
}

function formatPercent(float $value): string {
    return number_format($value, 1) . '%';
}

function moneyShort(float $amount): string {
    if ($amount >= 1000000) {
        return '₱' . number_format($amount / 1000000, 1) . 'M';
    }
    if ($amount >= 1000) {
        return '₱' . number_format($amount / 1000, 0) . 'k';
    }
    return '₱' . number_format($amount, 0);
}

function statusClass(string $status): string {
    $status = strtolower($status);
    if (in_array($status, ['occupied', 'reserved'], true)) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-100';
    }
    if (in_array($status, ['on hold', 'under maintenance'], true)) {
        return 'bg-amber-50 text-amber-700 border-amber-100';
    }
    if ($status === 'resale') {
        return 'bg-blue-50 text-blue-700 border-blue-100';
    }
    return 'bg-slate-50 text-slate-500 border-slate-100';
}

function countInquiries(mysqli $conn, string $start, string $end, ?string $unitType = null): int {
    $sql = "SELECT COUNT(DISTINCT i.inq_id) AS total
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            WHERE i.timestamp >= ? AND i.timestamp < ?";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND (u.unit_type = ? OR i.Preferred_unit_id = ?)";
        $types .= 'ss';
        $params[] = $unitType;
        $params[] = $unitType;
    }

    return (int)fetchScalar($conn, $sql, $types, $params);
}

function countHoaChecked(mysqli $conn, string $start, string $end, ?string $unitType = null): int {
    $sql = "SELECT COUNT(DISTINCT i.inq_id) AS total
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            WHERE i.timestamp >= ? AND i.timestamp < ?
              AND (i.status IN ('responded', 'onhold') OR i.approval_status IN ('requested', 'approved', 'declined'))";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND (u.unit_type = ? OR i.Preferred_unit_id = ?)";
        $types .= 'ss';
        $params[] = $unitType;
        $params[] = $unitType;
    }

    return (int)fetchScalar($conn, $sql, $types, $params);
}

function countOwnerApproved(mysqli $conn, string $start, string $end, ?string $unitType = null): int {
    $sql = "SELECT COUNT(DISTINCT i.inq_id) AS total
            FROM inquiry_table i
            LEFT JOIN units_table u ON i.approved_unit_id = u.unit_id
            WHERE i.timestamp >= ? AND i.timestamp < ?
              AND i.approval_status = 'approved'";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND (u.unit_type = ? OR i.Preferred_unit_id = ?)";
        $types .= 'ss';
        $params[] = $unitType;
        $params[] = $unitType;
    }

    return (int)fetchScalar($conn, $sql, $types, $params);
}

function countReservations(mysqli $conn, string $start, string $end, ?string $unitType = null, string $extraWhere = ''): int {
    $sql = "SELECT COUNT(*) AS total
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.created_at >= ? AND r.created_at < ?";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND u.unit_type = ?";
        $types .= 's';
        $params[] = $unitType;
    }

    if ($extraWhere !== '') {
        $sql .= " " . $extraWhere;
    }

    return (int)fetchScalar($conn, $sql, $types, $params);
}

function dailyMaintenance(mysqli $conn, string $start, string $end, int $daysInMonth, ?string $unitType = null, bool $resolvedOnly = false): array {
    $counts = array_fill(1, $daysInMonth, 0);

    if ($resolvedOnly) {
        $dateExpr = "COALESCE(m.updated_at, m.submitted_at)";
        $sql = "SELECT DAY($dateExpr) AS day_num, COUNT(*) AS total
                FROM maintenance_requests m
                INNER JOIN units_table u ON m.unit_id = u.unit_id
                WHERE m.status = 'resolved'
                  AND $dateExpr >= ? AND $dateExpr < ?";
    } else {
        $dateExpr = "m.submitted_at";
        $sql = "SELECT DAY($dateExpr) AS day_num, COUNT(*) AS total
                FROM maintenance_requests m
                INNER JOIN units_table u ON m.unit_id = u.unit_id
                WHERE $dateExpr >= ? AND $dateExpr < ?";
    }

    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND u.unit_type = ?";
        $types .= 's';
        $params[] = $unitType;
    }

    $sql .= " GROUP BY DAY($dateExpr) ORDER BY day_num";

    foreach (fetchRows($conn, $sql, $types, $params) as $row) {
        $day = (int)$row['day_num'];
        if ($day >= 1 && $day <= $daysInMonth) {
            $counts[$day] = (int)$row['total'];
        }
    }

    return array_values($counts);
}

function salesSummary(mysqli $conn, string $start, string $end, ?string $unitType = null): array {
    $dateExpr = "COALESCE(r.payment_verified_at, r.created_at)";
    $sql = "SELECT
                COALESCE(SUM(r.required_amount), 0) AS collected_sales,
                COALESCE(SUM(r.price_basis), 0) AS contract_value,
                COUNT(*) AS paid_reservations,
                COALESCE(SUM(CASE WHEN r.transaction_type = 'Unit Leasing' THEN r.required_amount ELSE 0 END), 0) AS leasing_sales,
                COALESCE(SUM(CASE WHEN r.transaction_type = 'Unit Resale' THEN r.required_amount ELSE 0 END), 0) AS resale_sales
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.payment_status = 'verified'
              AND r.reservation_status NOT IN ('cancelled', 'rejected')
              AND $dateExpr >= ? AND $dateExpr < ?";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND u.unit_type = ?";
        $types .= 's';
        $params[] = $unitType;
    }

    return fetchOne($conn, $sql, $types, $params) ?: [
        'collected_sales' => 0,
        'contract_value' => 0,
        'paid_reservations' => 0,
        'leasing_sales' => 0,
        'resale_sales' => 0,
    ];
}

function dailySales(mysqli $conn, string $start, string $end, int $daysInMonth, ?string $unitType = null): array {
    $counts = array_fill(1, $daysInMonth, 0.0);
    $dateExpr = "COALESCE(r.payment_verified_at, r.created_at)";
    $sql = "SELECT DAY($dateExpr) AS day_num, COALESCE(SUM(r.required_amount), 0) AS total
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.payment_status = 'verified'
              AND r.reservation_status NOT IN ('cancelled', 'rejected')
              AND $dateExpr >= ? AND $dateExpr < ?";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND u.unit_type = ?";
        $types .= 's';
        $params[] = $unitType;
    }

    $sql .= " GROUP BY DAY($dateExpr) ORDER BY day_num";

    foreach (fetchRows($conn, $sql, $types, $params) as $row) {
        $day = (int)$row['day_num'];
        if ($day >= 1 && $day <= $daysInMonth) {
            $counts[$day] = (float)$row['total'];
        }
    }

    return array_values($counts);
}

function roomMaintenanceTrends(mysqli $conn, string $start, string $end, ?string $unitType = null): array {
    $sql = "SELECT
                u.unit_id,
                u.unit_number,
                u.unit_type,
                u.unit_current_status,
                COALESCE(owner.full_name, 'No owner assigned') AS owner_name,
                COUNT(m.maintenance_id) AS total_requests,
                SUM(CASE WHEN m.status IN ('pending', 'in progress') THEN 1 ELSE 0 END) AS open_requests,
                SUM(CASE WHEN m.status = 'pending' THEN 1 ELSE 0 END) AS pending_requests,
                SUM(CASE WHEN m.status = 'in progress' THEN 1 ELSE 0 END) AS in_progress_requests,
                SUM(CASE WHEN m.status = 'resolved' THEN 1 ELSE 0 END) AS resolved_requests,
                SUM(CASE WHEN m.priority = 'urgent' THEN 1 ELSE 0 END) AS urgent_requests,
                MAX(m.submitted_at) AS latest_submitted_at,
                SUBSTRING_INDEX(GROUP_CONCAT(m.subject ORDER BY m.submitted_at DESC SEPARATOR '||'), '||', 1) AS latest_issue,
                SUBSTRING_INDEX(GROUP_CONCAT(m.category ORDER BY m.submitted_at DESC SEPARATOR '||'), '||', 1) AS latest_category,
                SUBSTRING_INDEX(GROUP_CONCAT(m.status ORDER BY m.submitted_at DESC SEPARATOR '||'), '||', 1) AS latest_status
            FROM maintenance_requests m
            INNER JOIN units_table u ON m.unit_id = u.unit_id
            LEFT JOIN users_table owner ON owner.user_id = u.unit_owner_id
            WHERE m.submitted_at >= ? AND m.submitted_at < ?";
    $types = 'ss';
    $params = [$start, $end];

    if ($unitType !== null) {
        $sql .= " AND u.unit_type = ?";
        $types .= 's';
        $params[] = $unitType;
    }

    $sql .= " GROUP BY u.unit_id, u.unit_number, u.unit_type, u.unit_current_status, owner.full_name
              ORDER BY open_requests DESC, urgent_requests DESC, total_requests DESC, latest_submitted_at DESC
              LIMIT 8";

    $rows = [];
    foreach (fetchRows($conn, $sql, $types, $params) as $row) {
        $latestDate = '';
        if (!empty($row['latest_submitted_at'])) {
            $latestDate = date('M j, Y', strtotime($row['latest_submitted_at']));
        }

        $openRequests = (int)($row['open_requests'] ?? 0);
        $urgentRequests = (int)($row['urgent_requests'] ?? 0);
        $attentionLevel = 'Normal';
        $attentionClass = 'bg-slate-50 text-slate-600 border-slate-100';

        if ($urgentRequests > 0) {
            $attentionLevel = 'Urgent';
            $attentionClass = 'bg-red-50 text-red-700 border-red-100';
        } elseif ($openRequests > 0) {
            $attentionLevel = 'Needs Review';
            $attentionClass = 'bg-amber-50 text-amber-700 border-amber-100';
        } elseif ((int)($row['resolved_requests'] ?? 0) > 0) {
            $attentionLevel = 'Resolved';
            $attentionClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
        }

        $rows[] = [
            'unit' => $row['unit_number'],
            'type' => $row['unit_type'],
            'owner' => $row['owner_name'],
            'unitStatus' => $row['unit_current_status'],
            'total' => (int)($row['total_requests'] ?? 0),
            'open' => $openRequests,
            'pending' => (int)($row['pending_requests'] ?? 0),
            'inProgress' => (int)($row['in_progress_requests'] ?? 0),
            'resolved' => (int)($row['resolved_requests'] ?? 0),
            'urgent' => $urgentRequests,
            'latestIssue' => $row['latest_issue'] ?: 'No issue recorded',
            'latestCategory' => $row['latest_category'] ?: 'N/A',
            'latestStatus' => $row['latest_status'] ?: 'N/A',
            'latestDate' => $latestDate,
            'attentionLevel' => $attentionLevel,
            'attentionClass' => $attentionClass,
        ];
    }

    return $rows;
}

function trendMeta(float $current, float $previous): array {
    if ($previous <= 0) {
        if ($current > 0) {
            return ['text' => 'New this month', 'class' => 'trend-up'];
        }
        return ['text' => 'No previous data', 'class' => 'trend-neu'];
    }

    $difference = round((($current - $previous) / $previous) * 100, 1);
    if ($difference > 0) {
        return ['text' => '↑ ' . number_format(abs($difference), 1) . '%', 'class' => 'trend-up'];
    }
    if ($difference < 0) {
        return ['text' => '↓ ' . number_format(abs($difference), 1) . '%', 'class' => 'trend-down'];
    }
    return ['text' => '— 0.0%', 'class' => 'trend-neu'];
}

$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

$unitTypeOptions = [
    'all'     => 'Entire Portfolio',
    'studioA' => 'Studio Type A',
    'studioB' => 'Studio Type B',
    'one'     => 'One Bedroom',
    'two'     => 'Two Bedroom',
];

$monthInput = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$selectedMonthIndex = ($monthInput !== null && $monthInput !== false && $monthInput >= 0 && $monthInput <= 11)
    ? $monthInput
    : ((int)date('n') - 1);

$yearInput = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$selectedYear = ($yearInput !== null && $yearInput !== false && $yearInput >= 2020 && $yearInput <= 2100)
    ? $yearInput
    : (int)date('Y');

$selectedUnitKey = $_GET['unit'] ?? 'all';
if (!array_key_exists($selectedUnitKey, $unitTypeOptions)) {
    $selectedUnitKey = 'all';
}

$selectedUnitType = $selectedUnitKey === 'all' ? null : $unitTypeOptions[$selectedUnitKey];
$selectedMonthName = $months[$selectedMonthIndex];
$selectedUnitLabel = $unitTypeOptions[$selectedUnitKey];

$startObj = new DateTime(sprintf('%04d-%02d-01 00:00:00', $selectedYear, $selectedMonthIndex + 1));
$endObj = (clone $startObj)->modify('+1 month');
$prevStartObj = (clone $startObj)->modify('-1 month');
$prevEndObj = clone $startObj;

$start = $startObj->format('Y-m-d H:i:s');
$end = $endObj->format('Y-m-d H:i:s');
$prevStart = $prevStartObj->format('Y-m-d H:i:s');
$prevEnd = $prevEndObj->format('Y-m-d H:i:s');
$daysInMonth = (int)$startObj->format('t');
$dayLabels = range(1, $daysInMonth);

$yearOptions = range(max(2023, $selectedYear - 2), max((int)date('Y') + 1, $selectedYear + 1));

/* Sidebar counts */
$pendingInquiryCount = (int)fetchScalar(
    $conn,
    "SELECT COUNT(*) FROM inquiry_table WHERE status = 'pending' OR approval_status = 'requested'"
);
$pendingReservationCount = (int)fetchScalar(
    $conn,
    "SELECT COUNT(*) FROM reservation_table
     WHERE payment_status = 'pending review'
        OR reservation_status IN ('submitted', 'under review', 'requirements pending')"
);

/* Occupancy / active-unit KPI */
$unitSql = "SELECT
                COUNT(*) AS total_units,
                SUM(CASE WHEN unit_current_status IN ('Occupied', 'Reserved') THEN 1 ELSE 0 END) AS active_units
            FROM units_table
            WHERE 1 = 1";
$unitTypes = '';
$unitParams = [];
if ($selectedUnitType !== null) {
    $unitSql .= " AND unit_type = ?";
    $unitTypes = 's';
    $unitParams[] = $selectedUnitType;
}
$unitRow = fetchOne($conn, $unitSql, $unitTypes, $unitParams);
$totalUnits = (int)($unitRow['total_units'] ?? 0);
$activeUnits = (int)($unitRow['active_units'] ?? 0);
$occupancyRate = percentNumber($activeUnits, $totalUnits);

/* Funnel and conversion */
$totalInquiries = countInquiries($conn, $start, $end, $selectedUnitType);
$hoaChecked = countHoaChecked($conn, $start, $end, $selectedUnitType);
$ownerApproved = countOwnerApproved($conn, $start, $end, $selectedUnitType);
$webformSubmitted = countReservations($conn, $start, $end, $selectedUnitType);
$officiallyBooked = countReservations($conn, $start, $end, $selectedUnitType, "AND r.reservation_status = 'reserved'");

$conversionRate = percentNumber($officiallyBooked, $totalInquiries);
$previousTotalInquiries = countInquiries($conn, $prevStart, $prevEnd, $selectedUnitType);
$previousOfficiallyBooked = countReservations($conn, $prevStart, $prevEnd, $selectedUnitType, "AND r.reservation_status = 'reserved'");
$previousConversionRate = $previousTotalInquiries > 0 ? percentNumber($previousOfficiallyBooked, $previousTotalInquiries) : null;

if ($previousConversionRate === null) {
    $conversionTrendText = 'No previous data';
    $conversionTrendClass = 'trend-neu';
} else {
    $difference = round($conversionRate - $previousConversionRate, 1);
    if ($difference > 0) {
        $conversionTrendText = '↑ ' . number_format(abs($difference), 1) . '%';
        $conversionTrendClass = 'trend-up';
    } elseif ($difference < 0) {
        $conversionTrendText = '↓ ' . number_format(abs($difference), 1) . '%';
        $conversionTrendClass = 'trend-down';
    } else {
        $conversionTrendText = '— 0.0%';
        $conversionTrendClass = 'trend-neu';
    }
}

/* Cancellation donut */
$cancelledReservations = countReservations($conn, $start, $end, $selectedUnitType, "AND r.reservation_status = 'cancelled'");
$activeReservations = countReservations($conn, $start, $end, $selectedUnitType, "AND r.reservation_status NOT IN ('cancelled', 'rejected')");

/* Bar chart: demand by unit category */
$categoryLabels = ['Studio Type A', 'Studio Type B', 'One Bedroom', 'Two Bedroom'];
$barInquiries = [];
$barConfirmed = [];
foreach ($categoryLabels as $label) {
    if ($selectedUnitType !== null && $selectedUnitType !== $label) {
        $barInquiries[] = 0;
        $barConfirmed[] = 0;
        continue;
    }

    $barInquiries[] = countInquiries($conn, $start, $end, $label);
    $barConfirmed[] = countReservations($conn, $start, $end, $label, "AND r.reservation_status NOT IN ('cancelled', 'rejected')");
}
$barInquiries[] = $totalInquiries;
$barConfirmed[] = $activeReservations;

/* Maintenance trend line */
$maintenanceRequests = dailyMaintenance($conn, $start, $end, $daysInMonth, $selectedUnitType, false);
$maintenanceCompleted = dailyMaintenance($conn, $start, $end, $daysInMonth, $selectedUnitType, true);

/* Room maintenance trends by unit / room */
$roomMaintenanceRows = roomMaintenanceTrends($conn, $start, $end, $selectedUnitType);
$roomsWithMaintenance = count($roomMaintenanceRows);
$openRoomMaintenance = array_sum(array_column($roomMaintenanceRows, 'open'));
$urgentRoomMaintenance = array_sum(array_column($roomMaintenanceRows, 'urgent'));
$roomMaintenanceLabels = array_column($roomMaintenanceRows, 'unit');
$roomMaintenanceTotals = array_column($roomMaintenanceRows, 'total');
$roomMaintenanceOpen = array_column($roomMaintenanceRows, 'open');

/* Sales analytics from verified reservation payments */
$salesSummary = salesSummary($conn, $start, $end, $selectedUnitType);
$previousSalesSummary = salesSummary($conn, $prevStart, $prevEnd, $selectedUnitType);
$totalSales = (float)($salesSummary['collected_sales'] ?? 0);
$totalContractValue = (float)($salesSummary['contract_value'] ?? 0);
$paidReservationCount = (int)($salesSummary['paid_reservations'] ?? 0);
$salesTrend = trendMeta($totalSales, (float)($previousSalesSummary['collected_sales'] ?? 0));
$salesCollectionRate = percentNumber($totalSales, $totalContractValue);
$salesDaily = dailySales($conn, $start, $end, $daysInMonth, $selectedUnitType);

/* Optional top unit data, preserved for the hidden table renderer */
$topSql = "SELECT
              u.unit_number,
              u.unit_type,
              COALESCE(owner.full_name, 'No owner assigned') AS owner_name,
              u.unit_current_status,
              COALESCE(SUM(CASE WHEN r.payment_status = 'verified' THEN r.required_amount ELSE 0 END), 0) AS revenue
           FROM units_table u
           LEFT JOIN users_table owner ON owner.user_id = u.unit_owner_id
           LEFT JOIN reservation_table r ON r.unit_id = u.unit_id AND r.created_at >= ? AND r.created_at < ?
           WHERE 1 = 1";
$topTypes = 'ss';
$topParams = [$start, $end];
if ($selectedUnitType !== null) {
    $topSql .= " AND u.unit_type = ?";
    $topTypes .= 's';
    $topParams[] = $selectedUnitType;
}
$topSql .= " GROUP BY u.unit_id, u.unit_number, u.unit_type, owner.full_name, u.unit_current_status
             ORDER BY revenue DESC, u.unit_number ASC
             LIMIT 8";
$topUnits = [];
$rank = 1;
foreach (fetchRows($conn, $topSql, $topTypes, $topParams) as $row) {
    $topUnits[] = [
        'rank' => $rank++,
        'unit' => $row['unit_number'],
        'type' => $row['unit_type'],
        'owner' => $row['owner_name'],
        'revenue' => moneyShort((float)$row['revenue']),
        'occ' => in_array($row['unit_current_status'], ['Occupied', 'Reserved'], true) ? '100%' : '0%',
        'status' => $row['unit_current_status'],
        'statusClass' => statusClass($row['unit_current_status']),
    ];
}

$analyticsData = [
    'kpi' => [
        'occupancy' => formatPercent($occupancyRate),
        'occBar' => $occupancyRate,
        'occActive' => $activeUnits,
        'occTotal' => $totalUnits,
        'occText' => $activeUnits . ' / ' . $totalUnits . ' units occupied/reserved',
        'conversion' => formatPercent($conversionRate),
        'convBar' => $conversionRate,
        'convTotal' => $totalInquiries,
        'convConfirmed' => $officiallyBooked,
        'convText' => $totalInquiries . ' inquiries → ' . $officiallyBooked . ' officially booked',
        'conversionTrendText' => $conversionTrendText,
        'conversionTrendClass' => $conversionTrendClass,
        'sales' => moneyShort($totalSales),
        'salesFull' => '₱' . number_format($totalSales, 2),
        'salesTrendText' => $salesTrend['text'],
        'salesTrendClass' => $salesTrend['class'],
        'salesBar' => min(100, $salesCollectionRate),
        'salesText' => $paidReservationCount . ' verified payment' . ($paidReservationCount === 1 ? '' : 's') . ' · ' . moneyShort($totalContractValue) . ' contract value',
        'roomsMaintenance' => (string)$roomsWithMaintenance,
        'roomsMaintenanceBar' => min(100, $totalUnits > 0 ? percentNumber($roomsWithMaintenance, $totalUnits) : 0),
        'roomsMaintenanceText' => $openRoomMaintenance . ' open request' . ($openRoomMaintenance === 1 ? '' : 's') . ' · ' . $urgentRoomMaintenance . ' urgent',
    ],
    'funnel' => [$totalInquiries, $hoaChecked, $ownerApproved, $webformSubmitted, $officiallyBooked],
    'donut' => [$activeReservations, $cancelledReservations],
    'bar' => [
        'labels' => ['Studio Type A', 'Studio Type B', 'One Bedroom', 'Two Bedroom', 'Entire Portfolio'],
        'inquiries' => $barInquiries,
        'confirmed' => $barConfirmed,
    ],
    'line' => [
        'labels' => $dayLabels,
        'requests' => $maintenanceRequests,
        'completed' => $maintenanceCompleted,
    ],
    'sales' => [
        'labels' => $dayLabels,
        'collected' => $salesDaily,
        'leasing' => (float)($salesSummary['leasing_sales'] ?? 0),
        'resale' => (float)($salesSummary['resale_sales'] ?? 0),
    ],
    'roomMaintenance' => [
        'labels' => $roomMaintenanceLabels,
        'total' => $roomMaintenanceTotals,
        'open' => $roomMaintenanceOpen,
        'rows' => $roomMaintenanceRows,
    ],
];

$selectedMeta = [
    'monthIndex' => $selectedMonthIndex,
    'monthName' => $selectedMonthName,
    'year' => $selectedYear,
    'unitKey' => $selectedUnitKey,
    'unitLabel' => $selectedUnitLabel,
    'generatedAt' => date('M j, Y g:i A'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zeppelin Suites – Analytics</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['DM Sans','sans-serif'],mono:['DM Mono','monospace']}}}}</script>
<style>
* { font-family: 'DM Sans', sans-serif; }

/* ── Sidebar ───────────────────────────────────────────── */
.sidebar {
  width: 256px;
  transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1);
  background: rgba(255,255,255,0.92);
  backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
}
.sidebar.collapsed { width: 68px; }
@media (max-width: 767px) {
  .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; width: 256px !important; }
  .sidebar.open { transform: translateX(0); }
}
.main-wrapper { margin-left: 256px; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }
.main-wrapper.sidebar-collapsed { margin-left: 68px; }
@media (max-width: 767px) { .main-wrapper { margin-left: 0 !important; } }
.sidebar-logo { transition: opacity 0.2s ease, width 0.2s ease; }
.sidebar.collapsed .sidebar-logo { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }
.overlay { display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }

/* ── Sidebar links ─────────────────────────────────────── */
.sidebar-link { position: relative; transition: all 0.18s ease; white-space: nowrap; overflow: hidden; }
.sidebar-link.active { background: #0f172a; color: #fff; }
.sidebar-link.active .nav-icon { color: #60a5fa; }
.sidebar-link:not(.active):hover { background: #eff6ff; color: #1d4ed8; }
.sidebar-link:not(.active):hover .nav-icon { color: #3b82f6; }
.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-badge,
.sidebar.collapsed .logo-text,.sidebar.collapsed .notice-section { display: none; }
.sidebar.collapsed .sidebar-link { justify-content: center; padding-left:0; padding-right:0; }
.sidebar.collapsed .collapse-icon { transform: rotate(180deg); }
.sidebar.collapsed .sidebar-link:hover::after {
  content: attr(data-tooltip);
  position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
  background: #0f172a; color: #fff; font-size: 12px; padding: 5px 10px;
  border-radius: 8px; white-space: nowrap; z-index: 999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.18); pointer-events: none;
}
.nav-label,.logo-text { transition: opacity 0.2s ease; }
.collapse-icon { transition: transform 0.3s ease; }

/* ── Dropdowns ─────────────────────────────────────────── */
.notice-panel { max-height:0; overflow:hidden; opacity:0; transition: max-height 0.3s ease, opacity 0.3s ease; }
.notice-panel.open { max-height:120px; opacity:1; }
.notice-chevron { transition: transform 0.3s ease; }
.notice-chevron.rotated { transform: rotate(180deg); }
.profile-dropdown { opacity:0; visibility:hidden; transform:translateY(-6px); transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
.profile-dropdown.open { opacity:1; visibility:visible; transform:translateY(0); }

/* ── Stat card hover ───────────────────────────────────── */
.stat-card { transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.10); border-color: #0f172a; }

/* ── Chart card hover ──────────────────────────────────── */
.chart-card { transition: box-shadow 0.22s ease, border-color 0.22s ease; }
.chart-card:hover { box-shadow: 0 12px 30px rgba(0,0,0,0.08); }

/* ── Table rows ────────────────────────────────────────── */
.tbl-row { transition: background 0.15s ease; }
.tbl-row:hover { background: #f8fafc; }

/* ── Scrollbar ─────────────────────────────────────────── */
::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:#f1f5f9; }
::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

/* ── Buttons / inputs ──────────────────────────────────── */
.btn-press { transition: all 0.15s ease; }
.btn-press:active { transform: scale(0.95); }
.zep-input:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }
.zep-select:focus { outline:none; border-color:#0f172a; box-shadow:0 0 0 3px rgba(15,23,42,0.07); }

/* ── Glass header ──────────────────────────────────────── */
.glass-header { background:rgba(255,255,255,0.85); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }

/* ── Funnel bars ───────────────────────────────────────── */
.funnel-bar { transition: width 0.8s cubic-bezier(0.4,0,0.2,1); }

/* ── Filter bar ────────────────────────────────────────── */
.filter-bar { background: rgba(255,255,255,0.75); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }

/* ── Progress ring ─────────────────────────────────────── */
.ring-track { stroke: #f1f5f9; }
.ring-fill { transition: stroke-dashoffset 1s cubic-bezier(0.4,0,0.2,1); transform-origin: center; transform: rotate(-90deg); }

/* ── Independent column scroll ─────────────────────────── */
.content-area {
  height: calc(100vh - 65px);
  display: flex;
  overflow: hidden;
}
.col-scroll {
  overflow-y: auto;
  height: 100%;
}
@media (max-width: 1023px) {
  .content-area { display: block; height: auto; overflow: visible; }
  .col-scroll { overflow-y: visible; height: auto; }
  .mobile-scroll-wrap { overflow-y: auto; height: calc(100vh - 65px); }
}

/* ── Trend badges ──────────────────────────────────────── */
.trend-up   { color: #10b981; }
.trend-down { color: #ef4444; }
.trend-neu  { color: #6b7280; }
</style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">

<!-- Overlay -->
<div class="overlay fixed inset-0 bg-transparent z-40" id="overlay" onclick="closeMobileSidebar()"></div>

<!-- ══════════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════════ -->
<aside class="sidebar fixed left-0 top-0 h-full border-r border-slate-100/80 flex flex-col z-50 md:z-40 shadow-2xl md:shadow-none" id="sidebar">
 <div class="px-4 py-5 border-b border-slate-100 flex items-center justify-between shrink-0">
    <a href="../adminPages/homeAdmin.php" class="sidebar-logo shrink-0 flex items-center">
      <img src="../images/zeppelin-logo.png" alt="Zeppelin Suites" class="h-10 w-auto object-contain">
    </a>
    <button onclick="toggleCollapse()" class="hidden md:flex btn-press p-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95 shrink-0 ml-1">
      <svg class="collapse-icon w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
  </div>

  <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
      <a href="../adminPages/homeAdmin.php" data-tooltip="Home" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      <span class="nav-label">Home</span>
    </a>
    <a href="../adminPages/inquiry.php" data-tooltip="Inquiry" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
      <span class="nav-label">Inquiry</span>
   </a>
    <a href="../adminPages/reservation.php" data-tooltip="Reservation" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="nav-label">Reservation</span>
   </a>
    <a href="../adminPages/units.php" data-tooltip="Units" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14m0 0H3m3 0h14m-7 0v-4h2v4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m4 0h1M9 13h1m4 0h1"/></svg>
      <span class="nav-label">Units</span>
    </a>
    <a href="../adminPages/maintenance.php" data-tooltip="Maintenance" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Maintenance</span>
    </a>
    <a href="../adminPages/residents.php" data-tooltip="Employees" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="nav-label">Residents</span>
    </a>
    <a href="../adminPages/analytics.php" data-tooltip="Analytics" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500">
      <svg class="nav-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      <span class="nav-label">Analytics</span>
    </a>
  </nav>

  <!-- Notice section -->
  <div class="notice-section px-2 py-4 border-t border-slate-100 shrink-0">
    <button onclick="toggleNotice()" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-50 transition-all btn-press active:scale-95">
      <span class="nav-label">Notice</span>
      <svg class="notice-chevron w-3.5 h-3.5 text-slate-400 shrink-0" id="noticeChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="notice-panel open px-2 pt-1 space-y-0.5" id="noticePanel">
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Summer Vacation</span></a>
      <a href="#" class="flex items-center gap-2 py-1.5 px-3 text-xs text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span><span class="nav-label">Employment Notice</span></a>
    </div>
  </div>
</aside>

<!-- ══════════════════════════════════════════════════════════
     MAIN WRAPPER
══════════════════════════════════════════════════════════ -->
<div class="main-wrapper h-screen flex flex-col" id="mainWrapper">

  <!-- TOP BAR -->
  <header class="glass-header border-b border-slate-100/80 px-4 md:px-6 py-3.5 flex items-center gap-4 shrink-0 z-30">
    <button class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95" onclick="openMobileSidebar()">
      <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" placeholder="Search analytics..." class="zep-input w-full pl-10 pr-4 py-2 bg-slate-50/80 border border-slate-200 rounded-full text-sm transition-all">
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <!-- Export button -->
      <button class="btn-press hidden sm:flex items-center gap-2 text-sm font-semibold text-slate-600 border border-slate-200 bg-white hover:bg-slate-50 px-4 py-2 rounded-full transition-all active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export
      </button>
      <button class="relative p-2 rounded-xl hover:bg-slate-100 transition-colors btn-press active:scale-95">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>
      <div class="relative" id="profileWrapper">
        <button onclick="toggleProfile()" class="flex items-center gap-2.5 pl-3 border-l border-slate-200 hover:bg-slate-50 rounded-xl px-3 py-1.5 transition-all btn-press active:scale-95">
          <div class="w-8 h-8 rounded-full bg-slate-900 flex items-center justify-center text-white text-xs font-bold shrink-0">A</div>
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-800 leading-none">Admin User</p>
          </div>
          <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="profileChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="profile-dropdown absolute right-0 top-full mt-2 w-48 bg-white backdrop-blur-xl border border-slate-200 rounded-2xl shadow-2xl py-2 z-50" id="profileDropdown">
          <a href="../adminPages/myProfileAdmin.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>My Profile</a>
          <a href="../adminPages/settingsAdmin.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Settings</a>
          <div class="border-t border-slate-100 my-1 mx-3"></div>
          <a href="../unitOwnerPages/login.html" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors rounded-xl mx-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Sign out</a>
        </div>
      </div>
    </div>
  </header>

  <!-- ══════════════════════════════════════════════════════
       CONTENT AREA
  ══════════════════════════════════════════════════════ -->
  <div class="content-area flex-1 max-w-screen-2xl mx-auto w-full mobile-scroll-wrap" id="contentArea">
    <div class="col-scroll flex-1 min-w-0 p-4 md:p-6 space-y-5">

      <!-- ── PAGE HEADER ─────────────────────────────────── -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Analytics</h1>
          <p class="text-xs text-slate-400 mt-0.5">Property performance overview for Zeppelin Suites</p>
        </div>
        <span class="text-xs font-semibold text-slate-400 border border-slate-200 bg-white rounded-full px-3 py-1.5" style="font-family:'DM Mono',monospace;" id="lastUpdatedLabel">Last updated: <?= e($selectedMeta['generatedAt']) ?></span>
      </div>

      <!-- ── FILTER BAR ───────────────────────────────────── -->
      <div class="filter-bar border border-slate-200/80 rounded-2xl px-4 py-3 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Filters</span>
        </div>
        <div class="w-px h-5 bg-slate-200"></div>
        <!-- Month -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-400">Month</label>
          <select id="filterMonth" onchange="submitAnalyticsFilters()" class="zep-select btn-press text-sm border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 cursor-pointer transition-all">
            <?php foreach ($months as $index => $monthName): ?>
              <option value="<?= $index ?>" <?= $index === $selectedMonthIndex ? 'selected' : '' ?>><?= e($monthName) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Year -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-400">Year</label>
          <select id="filterYear" onchange="submitAnalyticsFilters()" class="zep-select btn-press text-sm border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 cursor-pointer transition-all">
            <?php foreach ($yearOptions as $yearOption): ?>
              <option value="<?= $yearOption ?>" <?= (int)$yearOption === (int)$selectedYear ? 'selected' : '' ?>><?= $yearOption ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Unit Type -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-slate-400">Unit Type</label>
          <select id="filterUnit" onchange="submitAnalyticsFilters()" class="zep-select btn-press text-sm border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 cursor-pointer transition-all">
            <?php foreach ($unitTypeOptions as $unitKey => $unitLabelOption): ?>
              <option value="<?= e($unitKey) ?>" <?= $unitKey === $selectedUnitKey ? 'selected' : '' ?>><?= e($unitLabelOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Active filter chips -->
        <div class="ml-auto flex items-center gap-2">
          <span id="activeFilterChip" class="text-xs font-semibold bg-slate-900 text-white rounded-full px-3 py-1"><?= e($selectedMonthName) ?> <?= e($selectedYear) ?> · <?= e($selectedUnitLabel) ?></span>
          <button onclick="resetFilters()" class="btn-press text-xs font-medium text-slate-400 hover:text-slate-700 border border-slate-200 bg-white rounded-full px-3 py-1 transition-all active:scale-95">Reset</button>
        </div>
      </div>

      <!-- ── KPI CARDS ────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        <!-- Occupancy Rate -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Occupancy Rate</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiOccupancy"><?= e($analyticsData['kpi']['occupancy']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold trend-neu">Current unit status</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['occBar']) ?>%" id="barOccupancy"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="occupancyDetail"><?= e($analyticsData['kpi']['occText']) ?></p>
        </div>

        <!-- Inquiry Conversion -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Inquiry Conversion</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiConversion"><?= e($analyticsData['kpi']['conversion']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold <?= e($analyticsData['kpi']['conversionTrendClass']) ?>" id="conversionTrend"><?= e($analyticsData['kpi']['conversionTrendText']) ?></span> <span class="text-slate-400">vs last month</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['convBar']) ?>%" id="barConversion"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="conversionDetail"><?= e($analyticsData['kpi']['convText']) ?></p>
        </div>

        <!-- Sales Revenue -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-amber-50 rounded-xl flex items-center justify-center shrink-0">
              <span class="text-amber-600 font-bold text-sm" style="font-family:'DM Mono',monospace">₱</span>
            </div>
            <span class="text-sm font-semibold text-slate-600">Sales Revenue</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiSales"><?= e($analyticsData['kpi']['sales']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold <?= e($analyticsData['kpi']['salesTrendClass']) ?>" id="salesTrend"><?= e($analyticsData['kpi']['salesTrendText']) ?></span> <span class="text-slate-400">vs last month</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-amber-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['salesBar']) ?>%" id="barSales"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="salesDetail"><?= e($analyticsData['kpi']['salesText']) ?></p>
        </div>

        <!-- Rooms Needing Maintenance -->
        <div class="stat-card bg-white rounded-2xl p-5 border border-slate-100">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-red-50 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V9a2 2 0 00-2-2h-3V5a2 2 0 00-2-2H8a2 2 0 00-2 2v16m13 0H5m14 0h2M5 21H3m6-13h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1"/></svg>
            </div>
            <span class="text-sm font-semibold text-slate-600">Rooms Need Care</span>
          </div>
          <p class="text-3xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="kpiRoomsMaintenance"><?= e($analyticsData['kpi']['roomsMaintenance']) ?></p>
          <p class="text-xs mt-1"><span class="font-semibold trend-neu">rooms with request records</span></p>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="bg-red-500 h-1.5 rounded-full transition-all duration-700" style="width:<?= e($analyticsData['kpi']['roomsMaintenanceBar']) ?>%" id="barRoomsMaintenance"></div>
          </div>
          <p class="text-xs text-slate-400 mt-1.5" style="font-family:'DM Mono',monospace" id="roomsMaintenanceDetail"><?= e($analyticsData['kpi']['roomsMaintenanceText']) ?></p>
        </div>

      </div>

      <!-- ── ROW 2: FUNNEL + DONUT ─────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Reservation Funnel -->
        <div class="chart-card bg-white rounded-2xl border border-slate-100 p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-bold text-slate-900 text-sm">Reservation Funnel</h2>
              <p class="text-xs text-slate-400 mt-0.5">Inquiry → Confirmation pipeline</p>
            </div>
            <span class="text-xs font-semibold bg-slate-100 text-slate-500 rounded-full px-2.5 py-1"><?= e($selectedMonthName) ?></span>
          </div>
          <!-- Custom funnel bars -->
          <div class="space-y-3" id="funnelContainer">
            <!-- Steps rendered by JS -->
          </div>
        </div>

        <!-- Cancellation Rate Donut -->
        <div class="chart-card bg-white rounded-2xl border border-slate-100 p-5 flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="font-bold text-slate-900 text-sm">Cancellation Rate</h2>
              <p class="text-xs text-slate-400 mt-0.5">30-day rule applied</p>
            </div>
          </div>
          <div class="flex-1 flex flex-col items-center justify-center gap-4">
            <div class="relative w-36 h-36">
              <canvas id="donutChart"></canvas>
              <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span class="text-2xl font-bold text-slate-900" style="font-family:'DM Mono',monospace" id="donutCenter">0%</span>
                <span class="text-xs text-slate-400">Completed</span>
              </div>
            </div>
            <div class="flex items-center gap-5 text-xs">
              <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span><span class="text-slate-500">Completed <span class="font-semibold text-slate-700" style="font-family:'DM Mono',monospace" id="completedCount"><?= e($analyticsData['donut'][0]) ?></span></span></div>
              <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400 shrink-0"></span><span class="text-slate-500">Canceled <span class="font-semibold text-slate-700" style="font-family:'DM Mono',monospace" id="canceledCount"><?= e($analyticsData['donut'][1]) ?></span></span></div>
            </div>
          </div>
        </div>

      </div>

      <!-- ── ROW 3: BAR + LINE ──────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Unit Performance Bar Chart -->
        <div class="chart-card bg-white rounded-2xl border border-slate-100 p-5">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-bold text-slate-900 text-sm">Unit Performance</h2>
              <p class="text-xs text-slate-400 mt-0.5">Demand by unit category</p>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-slate-900 shrink-0"></span>
              <span class="text-xs text-slate-400">Inquiries</span>
              <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0 ml-2"></span>
              <span class="text-xs text-slate-400">Confirmed</span>
            </div>
          </div>
          <div class="relative h-52">
            <canvas id="barChart"></canvas>
          </div>
        </div>

        <!-- Maintenance Trends Line Chart -->
        <div class="chart-card bg-white rounded-2xl border border-slate-100 p-5">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-bold text-slate-900 text-sm">Maintenance Trends</h2>
              <p class="text-xs text-slate-400 mt-0.5">Requests vs. completions over month</p>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
              <span class="text-xs text-slate-400">Requests</span>
              <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 ml-2"></span>
              <span class="text-xs text-slate-400">Completed</span>
            </div>
          </div>
          <div class="relative h-52">
            <canvas id="lineChart"></canvas>
          </div>
        </div>

      </div>

      <!-- ── ROW 4: SALES ANALYTICS ───────────────────────────── -->
      <div class="chart-card bg-white rounded-2xl border border-slate-100 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
          <div>
            <h2 class="font-bold text-slate-900 text-sm">Sales Analytics</h2>
            <p class="text-xs text-slate-400 mt-0.5">Verified reservation payments collected per day</p>
          </div>
          <div class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-900 shrink-0"></span><span class="text-slate-400">Daily sales</span></div>
            <span class="font-semibold text-slate-700" style="font-family:'DM Mono',monospace">Leasing: <?= e(moneyShort((float)($salesSummary['leasing_sales'] ?? 0))) ?></span>
            <span class="font-semibold text-slate-700" style="font-family:'DM Mono',monospace">Resale: <?= e(moneyShort((float)($salesSummary['resale_sales'] ?? 0))) ?></span>
          </div>
        </div>
        <div class="relative h-64">
          <canvas id="salesChart"></canvas>
        </div>
      </div>

      <!-- ── ROW 5: ROOM MAINTENANCE TRENDS ───────────────────────────── -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
        <div class="chart-card bg-white rounded-2xl border border-slate-100 p-5 xl:col-span-2">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-bold text-slate-900 text-sm">Room Maintenance Trends</h2>
              <p class="text-xs text-slate-400 mt-0.5">Which rooms have the most maintenance requests</p>
            </div>
            <span class="text-xs font-semibold bg-red-50 text-red-600 rounded-full px-2.5 py-1"><?= e($roomsWithMaintenance) ?> room<?= $roomsWithMaintenance === 1 ? '' : 's' ?></span>
          </div>
          <div class="relative h-72">
            <canvas id="roomMaintenanceChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white rounded-2xl border border-slate-100 overflow-hidden xl:col-span-3">
          <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div>
              <h2 class="font-bold text-slate-900 text-sm">Rooms Needing Maintenance</h2>
              <p class="text-xs text-slate-400 mt-0.5">Ranked by open, urgent, and total requests</p>
            </div>
            <a href="../adminPages/maintenance.php" class="btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full active:scale-95 transition-all">Open Maintenance</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Room</th>
                  <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Latest Issue</th>
                  <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-400 uppercase tracking-wider">Open</th>
                  <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-400 uppercase tracking-wider">Urgent</th>
                  <th class="px-4 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Level</th>
                </tr>
              </thead>
              <tbody id="roomMaintenanceTable" class="divide-y divide-slate-100">
                <?php if (empty($roomMaintenanceRows)): ?>
                  <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">No maintenance requests found for this filter.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($roomMaintenanceRows as $room): ?>
                    <tr class="tbl-row">
                      <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="font-bold text-slate-800" style="font-family:'DM Mono',monospace"><?= e($room['unit']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($room['type']) ?></div>
                      </td>
                      <td class="px-4 py-3.5 min-w-[220px]">
                        <div class="font-semibold text-slate-700 text-xs"><?= e($room['latestIssue']) ?></div>
                        <div class="text-xs text-slate-400 mt-0.5"><?= e($room['latestCategory']) ?> · <?= e($room['latestDate']) ?></div>
                      </td>
                      <td class="px-4 py-3.5 text-center font-bold text-slate-800" style="font-family:'DM Mono',monospace"><?= e($room['open']) ?></td>
                      <td class="px-4 py-3.5 text-center font-bold text-red-600" style="font-family:'DM Mono',monospace"><?= e($room['urgent']) ?></td>
                      <td class="px-4 py-3.5 whitespace-nowrap"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= e($room['attentionClass']) ?>"><?= e($room['attentionLevel']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── TOP PERFORMING UNITS TABLE (hidden — re-add HTML here to restore) ───
           Data is preserved: TOP_UNITS array and renderTable() function are intact in JS.
           To restore: paste back the table markup and call renderTable() in DOMContentLoaded.
      ──────────────────────────────────────────────────────────────────────────────── -->

      <!-- Bottom spacer -->
      <div class="h-4"></div>

    </div><!-- /col-scroll -->
  </div><!-- /content-area -->
</div><!-- /main-wrapper -->

<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ -->
<script>
/* ─── Sidebar ─────────────────────────────────────────────── */
let sidebarCollapsed = false;
function toggleCollapse() {
  sidebarCollapsed = !sidebarCollapsed;
  document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
  document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed', sidebarCollapsed);
}
function openMobileSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('overlay').classList.add('show');
}
function closeMobileSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
function toggleNotice() {
  document.getElementById('noticePanel').classList.toggle('open');
  document.getElementById('noticeChevron').classList.toggle('rotated');
}
function toggleProfile() {
  const dd = document.getElementById('profileDropdown');
  const ch = document.getElementById('profileChevron');
  const open = dd.classList.toggle('open');
  ch.style.transform = open ? 'rotate(180deg)' : '';
}
document.addEventListener('click', e => {
  const w = document.getElementById('profileWrapper');
  if (w && !w.contains(e.target)) {
    document.getElementById('profileDropdown').classList.remove('open');
    document.getElementById('profileChevron').style.transform = '';
  }
});

/* ─── Data loaded from PHP backend ───────────────────────── */
const DATASET = {
  current: <?= json_encode($analyticsData, JSON_NUMERIC_CHECK) ?>
};

const SELECTED = <?= json_encode($selectedMeta, JSON_NUMERIC_CHECK) ?>;

const TOP_UNITS = <?= json_encode($topUnits, JSON_NUMERIC_CHECK) ?>;

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const FUNNEL_STEPS = ['Total Inquiries','HOA Checked','Owner Approved','Webform Submitted','Confirmed'];
const FUNNEL_COLORS = ['bg-slate-800','bg-blue-600','bg-blue-400','bg-violet-400','bg-emerald-500'];
const FUNNEL_TEXT   = ['text-slate-100','text-white','text-white','text-white','text-white'];

/* ─── Chart instances ─────────────────────────────────────── */
let donutInst=null, barInst=null, lineInst=null, salesInst=null, roomMaintenanceInst=null;

/* ─── Funnel ──────────────────────────────────────────────── */
function renderFunnel(values) {
  const container = document.getElementById('funnelContainer');
  const max = values[0] > 0 ? values[0] : 1;
  container.innerHTML = '';
  FUNNEL_STEPS.forEach((label, i) => {
    const pct = Math.round((values[i]/max)*100);
    const convRate = values[0] > 0 ? (i === 0 ? 100 : Math.round((values[i] / values[0]) * 100)) : 0;
    container.innerHTML += `
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-xs font-medium text-slate-500">${label}</span>
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-700" style="font-family:'DM Mono',monospace">${values[i]}</span>
            <span class="text-xs text-slate-400" style="font-family:'DM Mono',monospace">${convRate}%</span>
          </div>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-6 overflow-hidden">
          <div class="funnel-bar h-6 rounded-full ${FUNNEL_COLORS[i]} flex items-center px-3" style="width:${pct}%">
            <span class="text-xs font-semibold ${FUNNEL_TEXT[i]} whitespace-nowrap hidden sm:block">${label}</span>
          </div>
        </div>
      </div>`;
  });
}

/* ─── Donut chart ─────────────────────────────────────────── */
function renderDonut(values) {
  const ctx = document.getElementById('donutChart').getContext('2d');
  const total = values[0] + values[1];
  const pct = total > 0 ? Math.round((values[0] / total) * 100) : 0;
  document.getElementById('donutCenter').textContent = pct + '%';
  document.getElementById('completedCount').textContent = values[0];
  document.getElementById('canceledCount').textContent = values[1];
  if (donutInst) donutInst.destroy();
  donutInst = new Chart(ctx, {
    type:'doughnut',
    data:{
      labels:['Active', 'Cancelled'],
      datasets:[{
        data: values,
        backgroundColor:['#10b981','#f87171'],
        borderWidth:0,
        hoverOffset:4
      }]
    },
    options:{
      cutout:'75%',
      plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${ctx.raw}`}} },
      animation:{ animateRotate:true, duration:900 }
    }
  });
}

/* ─── Bar chart ───────────────────────────────────────────── */
function renderBar(barData) {
  const ctx = document.getElementById('barChart').getContext('2d');
  if (barInst) barInst.destroy();
  barInst = new Chart(ctx, {
    type:'bar',
    data:{
      labels:barData.labels,
      datasets:[
        { label:'Inquiries', data: barData.inquiries, backgroundColor:'#0f172a', borderRadius:6, barPercentage:0.5 },
        { label:'Confirmed', data: barData.confirmed, backgroundColor:'#60a5fa', borderRadius:6, barPercentage:0.5 }
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false} },
      scales:{
        x:{ grid:{display:false}, ticks:{font:{family:'DM Sans',size:11}, color:'#94a3b8'} },
        y:{ grid:{color:'#f1f5f9'}, ticks:{font:{family:'DM Mono',size:10}, color:'#94a3b8'}, beginAtZero:true }
      }
    }
  });
}

/* ─── Line chart ──────────────────────────────────────────── */
function renderLine(lineData) {
  const ctx = document.getElementById('lineChart').getContext('2d');
  const days = lineData.labels;
  if (lineInst) lineInst.destroy();
  lineInst = new Chart(ctx, {
    type:'line',
    data:{
      labels: days,
      datasets:[
        { label:'Requests',  data:lineData.requests,  borderColor:'#fbbf24', backgroundColor:'rgba(251,191,36,0.07)', tension:0.4, fill:true, pointRadius:0, borderWidth:2 },
        { label:'Completed', data:lineData.completed, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.07)', tension:0.4, fill:true, pointRadius:0, borderWidth:2 }
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{ legend:{display:false} },
      scales:{
        x:{ grid:{display:false}, ticks:{font:{family:'DM Mono',size:10}, color:'#94a3b8', maxTicksLimit:8} },
        y:{ grid:{color:'#f1f5f9'}, ticks:{font:{family:'DM Mono',size:10}, color:'#94a3b8'}, beginAtZero:true }
      }
    }
  });
}

/* ─── Sales chart ─────────────────────────────────────────── */
function moneyLabel(value) {
  const amount = Number(value) || 0;
  if (amount >= 1000000) return '₱' + (amount / 1000000).toFixed(1) + 'M';
  if (amount >= 1000) return '₱' + Math.round(amount / 1000) + 'k';
  return '₱' + amount.toLocaleString();
}

function renderSalesChart(salesData) {
  const canvas = document.getElementById('salesChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (salesInst) salesInst.destroy();
  salesInst = new Chart(ctx, {
    type:'line',
    data:{
      labels: salesData.labels,
      datasets:[
        { label:'Verified Sales', data:salesData.collected, borderColor:'#0f172a', backgroundColor:'rgba(15,23,42,0.07)', tension:0.35, fill:true, pointRadius:2, pointHoverRadius:4, borderWidth:2 }
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{label:ctx=>`Sales: ${moneyLabel(ctx.raw)}`}}
      },
      scales:{
        x:{ grid:{display:false}, ticks:{font:{family:'DM Mono',size:10}, color:'#94a3b8', maxTicksLimit:10} },
        y:{ grid:{color:'#f1f5f9'}, ticks:{font:{family:'DM Mono',size:10}, color:'#94a3b8', callback:value=>moneyLabel(value)}, beginAtZero:true }
      }
    }
  });
}


/* ─── Room maintenance chart ───────────────────────────────── */
function renderRoomMaintenanceChart(roomData) {
  const canvas = document.getElementById('roomMaintenanceChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (roomMaintenanceInst) roomMaintenanceInst.destroy();

  const labels = roomData.labels && roomData.labels.length ? roomData.labels : ['No data'];
  const total = roomData.total && roomData.total.length ? roomData.total : [0];
  const open = roomData.open && roomData.open.length ? roomData.open : [0];

  roomMaintenanceInst = new Chart(ctx, {
    type:'bar',
    data:{
      labels,
      datasets:[
        { label:'Total Requests', data:total, backgroundColor:'#0f172a', borderRadius:6, barPercentage:0.55 },
        { label:'Open Requests', data:open, backgroundColor:'#f87171', borderRadius:6, barPercentage:0.55 }
      ]
    },
    options:{
      indexAxis:'y',
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{label:ctx=>`${ctx.dataset.label}: ${ctx.raw}`}}
      },
      scales:{
        x:{ grid:{color:'#f1f5f9'}, ticks:{font:{family:'DM Mono',size:10}, color:'#94a3b8', precision:0}, beginAtZero:true },
        y:{ grid:{display:false}, ticks:{font:{family:'DM Mono',size:11}, color:'#64748b'} }
      }
    }
  });
}

/* ─── KPI card update ─────────────────────────────────────── */
function updateKPIs(d) {
  document.getElementById('kpiOccupancy').textContent  = d.occupancy;
  document.getElementById('kpiConversion').textContent = d.conversion;
  document.getElementById('kpiSales').textContent = d.sales;
  document.getElementById('kpiRoomsMaintenance').textContent = d.roomsMaintenance;
  document.getElementById('barOccupancy').style.width  = d.occBar + '%';
  document.getElementById('barConversion').style.width = d.convBar + '%';
  document.getElementById('barSales').style.width = d.salesBar + '%';
  document.getElementById('barRoomsMaintenance').style.width = d.roomsMaintenanceBar + '%';
  document.getElementById('occupancyDetail').textContent = d.occText;
  document.getElementById('conversionDetail').textContent = d.convText;
  document.getElementById('salesDetail').textContent = d.salesText;
  document.getElementById('roomsMaintenanceDetail').textContent = d.roomsMaintenanceText;
  const trend = document.getElementById('conversionTrend');
  trend.textContent = d.conversionTrendText;
  trend.className = 'font-semibold ' + d.conversionTrendClass;
  const salesTrend = document.getElementById('salesTrend');
  salesTrend.textContent = d.salesTrendText;
  salesTrend.className = 'font-semibold ' + d.salesTrendClass;
}

/* ─── Table render ────────────────────────────────────────── */
function renderTable() {
  const tbody = document.getElementById('topUnitsTable');
  tbody.innerHTML = TOP_UNITS.map(u=>`
    <tr class="tbl-row cursor-pointer">
      <td class="px-5 py-3.5 text-xs text-slate-400 font-medium" style="font-family:'DM Mono',monospace">${u.rank}</td>
      <td class="px-4 py-3.5 font-semibold text-slate-800 whitespace-nowrap" style="font-family:'DM Mono',monospace;font-size:12px">${u.unit}</td>
      <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap">${u.type}</td>
      <td class="px-4 py-3.5 text-slate-700 font-medium whitespace-nowrap">${u.owner}</td>
      <td class="px-4 py-3.5 font-bold text-slate-800 whitespace-nowrap" style="font-family:'DM Mono',monospace">${u.revenue}</td>
      <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap" style="font-family:'DM Mono',monospace;font-size:12px">${u.occ}</td>
      <td class="px-4 py-3.5"><span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border ${u.statusClass}">${u.status}</span></td>
      <td class="px-4 py-3.5 text-right"><button class="btn-press text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-full active:scale-95 transition-all">View</button></td>
    </tr>`).join('');
}

/* ─── Filter application ──────────────────────────────────── */
function submitAnalyticsFilters() {
  const params = new URLSearchParams();
  params.set('month', document.getElementById('filterMonth').value);
  params.set('year', document.getElementById('filterYear').value);
  params.set('unit', document.getElementById('filterUnit').value);
  window.location.href = window.location.pathname + '?' + params.toString();
}

function applyFilters() {
  document.getElementById('activeFilterChip').textContent = `${SELECTED.monthName} ${SELECTED.year} · ${SELECTED.unitLabel}`;
  document.getElementById('lastUpdatedLabel').textContent = `Last updated: ${SELECTED.generatedAt}`;

  const d = DATASET.current;
  updateKPIs(d.kpi);
  renderFunnel(d.funnel);
  renderDonut(d.donut);
  renderBar(d.bar);
  renderLine(d.line);
  renderSalesChart(d.sales);
  renderRoomMaintenanceChart(d.roomMaintenance);
}

function resetFilters() {
  window.location.href = window.location.pathname;
}

/* ─── Init ────────────────────────────────────────────────── */
window.addEventListener('DOMContentLoaded', () => {
  // renderTable() is intentionally not called here — table section is hidden.
  // Restore the table HTML and uncomment renderTable() below to re-enable it.
  // renderTable();
  applyFilters();
});
</script>
</body>
</html>