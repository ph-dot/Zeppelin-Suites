<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';
require_once '../../php_files/sync_unit_status.php';

header('Content-Type: application/json');

$userData = requireRole($conn, ['admin']);

syncExpiredUnitStatuses($conn);

$unit_type = $_GET['unit_type'] ?? '';
$inq_id = $_GET['inq_id'] ?? '';

if (trim($unit_type) === '' || trim($inq_id) === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing inquiry information.'
    ]);
    exit;
}

// GET INQUIRY DETAILS
$inqStmt = $conn->prepare("
    SELECT 
        inquiry_type,
        preferred_move_in_time,
        lease_duration
    FROM inquiry_table
    WHERE inq_id = ?
");

$inqStmt->bind_param("i", $inq_id);
$inqStmt->execute();

$inqResult = $inqStmt->get_result();
$inquiry = $inqResult->fetch_assoc();

$inqStmt->close();

if (!$inquiry) {
    echo json_encode([
        'success' => false,
        'message' => 'Inquiry not found.'
    ]);
    exit;
}

$inquiryTypeNormalized = strtolower(trim($inquiry['inquiry_type'] ?? ''));
$isResale = ($inquiryTypeNormalized === 'resale inquiry');

if ($isResale) {
    // =========================================================================
    // RESALE INQUIRY: Find units of this type that are under Resale
    // =========================================================================
    $sql = "
    SELECT 
        u.unit_id,
        u.unit_number,
        u.unit_type,
        u.sqm,
        u.lease_rate,
        u.unit_owner_id,
        u.unit_current_status,
        owner.full_name AS owner_name
    FROM units_table u
    LEFT JOIN users_table owner
        ON u.unit_owner_id = owner.user_id
    WHERE u.unit_type = ?
    AND u.unit_current_status = 'Resale'
    AND u.unit_owner_id IS NOT NULL
    AND u.unit_id NOT IN (
        SELECT unit_id FROM owner_approval_requests
        WHERE inq_id = ? AND request_status IN ('pending', 'approved')
    )
    ORDER BY u.unit_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'SQL Error: ' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("si", $unit_type, $inq_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $units = [];
    while ($row = $result->fetch_assoc()) {
        $units[] = [
            'unit_id' => (int)$row['unit_id'],
            'unit_number' => $row['unit_number'],
            'unit_type' => $row['unit_type'],
            'sqm' => (float)($row['sqm'] ?? 0),
            'lease_rate' => $row['lease_rate'],
            'unit_owner_id' => $row['unit_owner_id'],
            'owner_name' => $row['owner_name'],
            'unit_status' => $row['unit_current_status'],
            'is_resale' => true,
            'availability_start' => 'Available for Resale',
            'availability_end' => 'For Resale',
            'limited_availability' => false,
            'next_booking_date' => null
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'is_resale' => true,
        'count' => count($units),
        'units' => $units
    ]);
    exit;
}

// DETERMINE CUSTOMER MOVE-IN WINDOW

$today = new DateTime();

$movePreference = strtolower(trim($inquiry['preferred_move_in_time']));
$earliestMoveIn = clone $today;
$latestMoveIn = clone $today;

switch ($movePreference) {
    case 'immediately':
        $earliestMoveIn->modify('+0 days');
        $latestMoveIn->modify('+30 days');
        break;

    case 'within 1 month':
        $earliestMoveIn->modify('+0 days');
        $latestMoveIn->modify('+1 month');
        break;

    case 'within 1–3 months':
    case 'within 1-3 months':
        $earliestMoveIn->modify('+1 month');
        $latestMoveIn->modify('+3 months');

        break;
    case 'within 3–6 months':
    case 'within 3-6 months':
        $earliestMoveIn->modify('+3 months');
        $latestMoveIn->modify('+6 months');
        break;

    default:
        // Not sure yet
        $earliestMoveIn->modify('+0 days');
        $latestMoveIn->modify('+6 months');
        break;
}
// DETERMINE LEASE DURATION

$leaseDuration = strtolower(trim($inquiry['lease_duration']));
$months = 12;

if (strpos($leaseDuration, 'month') !== false) {
    preg_match('/\d+/', $leaseDuration, $matches);
    if (!empty($matches)) {
        $months = intval($matches[0]);
    }
}

elseif (strpos($leaseDuration, 'year') !== false) {
    preg_match('/\d+/', $leaseDuration, $matches);
    if (!empty($matches)) {
        $months = intval($matches[0]) * 12;
    }
}

elseif (strpos($leaseDuration, 'longer') !== false) {
    $months = 36;
}

// Business rule: shortest lease/stay the company will book is 30 days.
// A unit whose free gap before its next reservation is shorter than this
// isn't actually rentable, so it shouldn't be listed at all.
const MIN_STAY_DAYS = 30;

// FIND UNITS
$sql = "
SELECT 
    u.unit_id,
    u.unit_number,
    u.unit_type,
    u.sqm,
    u.lease_rate,
    u.unit_owner_id,
    owner.full_name AS owner_name,
    MAX(r.move_out_date) AS latest_move_out,
    MAX(b.end_date) AS latest_blocked_out
FROM units_table u
LEFT JOIN users_table owner
    ON u.unit_owner_id = owner.user_id
LEFT JOIN reservation_table r
    ON u.unit_id = r.unit_id
    AND LOWER(r.reservation_status) NOT IN ('cancelled','rejected')
    AND r.move_in_date <= CURDATE()
    AND r.move_out_date >= CURDATE()
LEFT JOIN unit_blocked_dates b
    ON u.unit_id = b.unit_id
    AND b.start_date <= CURDATE()
    AND b.end_date >= CURDATE()
WHERE u.unit_type = ?
AND u.unit_current_status NOT IN ('Resale', 'On Hold', 'Under maintenance', 'Archived')
AND u.unit_owner_id IS NOT NULL
AND u.unit_id NOT IN (
    SELECT unit_id FROM owner_approval_requests
    WHERE inq_id = ? AND request_status IN ('pending', 'approved')
)
GROUP BY 
    u.unit_id
ORDER BY u.unit_number ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success'=>false,
        'message'=>'SQL Error: '.$conn->error
    ]);
    exit;
}

$stmt->bind_param("si", $unit_type, $inq_id);
$stmt->execute();
$result = $stmt->get_result();
$candidates = [];

// CHECK EACH UNIT'S IMMEDIATE AVAILABILITY (considering active reservations & blocked dates today)
while ($row = $result->fetch_assoc()) {
    $busyEndDates = [];
    if (!empty($row['latest_move_out'])) {
        $busyEndDates[] = new DateTime($row['latest_move_out']);
    }
    if (!empty($row['latest_blocked_out'])) {
        $busyEndDates[] = new DateTime($row['latest_blocked_out']);
    }

    if (empty($busyEndDates)) {
        $availableDate = new DateTime();
    } else {
        $availableDate = max($busyEndDates);
    }

    if ($availableDate < $today) {
        $availableDate = clone $today;
    }

    // Check customer preference only for future available units
    // Units available today can still be suggested
    if ($availableDate > $latestMoveIn) {
        continue;
    }

    $candidates[$row['unit_id']] = [
        'row' => $row,
        'availableDate' => $availableDate
    ];
}

// FETCH EVERY UPCOMING RESERVATION & BLOCKED DATE FOR EACH CANDIDATE UNIT,
// sorted by start date. A unit that's blocked by a booking or blocked date starting
// too soon (gap < MIN_STAY_DAYS) isn't necessarily unrentable for this
// inquiry - it may open back up again once that period passes.
$bookingsByUnit = [];
if (!empty($candidates)) {
    $unitIds = array_keys($candidates);
    $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
    $types = str_repeat('i', count($unitIds));

    $nextStmt = $conn->prepare("
        SELECT unit_id, move_in_date, move_out_date
        FROM reservation_table
        WHERE unit_id IN ($placeholders)
        AND LOWER(reservation_status) NOT IN ('cancelled','rejected')
        AND move_in_date IS NOT NULL
        AND (move_out_date >= CURDATE() OR move_out_date IS NULL)
        ORDER BY move_in_date ASC
    ");
    if ($nextStmt) {
        $nextStmt->bind_param($types, ...$unitIds);
        $nextStmt->execute();
        $nextResult = $nextStmt->get_result();

        while ($nextRow = $nextResult->fetch_assoc()) {
            $bookingsByUnit[$nextRow['unit_id']][] = [
                'move_in'  => new DateTime($nextRow['move_in_date']),
                'move_out' => $nextRow['move_out_date'] ? new DateTime($nextRow['move_out_date']) : null,
                'source'   => 'reservation'
            ];
        }
        $nextStmt->close();
    }

    // Fetch administrative & owner blocked dates (maintenance / unavailable)
    $blockStmt = $conn->prepare("
        SELECT unit_id, start_date, end_date, block_type, remarks
        FROM unit_blocked_dates
        WHERE unit_id IN ($placeholders)
        AND start_date IS NOT NULL
        AND (end_date >= CURDATE() OR end_date IS NULL)
        ORDER BY start_date ASC
    ");
    if ($blockStmt) {
        $blockStmt->bind_param($types, ...$unitIds);
        $blockStmt->execute();
        $blockResult = $blockStmt->get_result();

        while ($bRow = $blockResult->fetch_assoc()) {
            $bookingsByUnit[$bRow['unit_id']][] = [
                'move_in'    => new DateTime($bRow['start_date']),
                'move_out'   => !empty($bRow['end_date']) ? new DateTime($bRow['end_date']) : null,
                'source'     => 'blocked_date',
                'block_type' => $bRow['block_type'] ?? 'Blocked'
            ];
        }
        $blockStmt->close();
    }

    // Sort every unit's combined busy intervals chronologically
    foreach ($bookingsByUnit as $uId => &$intervals) {
        usort($intervals, function($a, $b) {
            if ($a['move_in'] == $b['move_in']) {
                return 0;
            }
            return ($a['move_in'] < $b['move_in']) ? -1 : 1;
        });
    }
    unset($intervals);
}

$units = [];
foreach ($candidates as $unitId => $candidate) {
    $row = $candidate['row'];
    $availableDate = clone $candidate['availableDate'];

    $limitedAvailability = false;
    $nextBookingDate = null;
    $cappingBookingMoveIn = null;
    $cappingSource = 'reservation';
    $cappingType = null;

    // Walk the unit's upcoming busy periods (reservations + blocked dates) in order.
    // If one starts too soon to satisfy the minimum stay, jump availableDate past it
    // (to its move-out / end date) and keep looking. Stop at the first interval that leaves a real gap;
    // that one caps the lease window.
    foreach (($bookingsByUnit[$unitId] ?? []) as $booking) {
        if ($booking['move_in'] <= $availableDate) {
            if ($booking['move_out'] !== null && $booking['move_out'] > $availableDate) {
                $availableDate = clone $booking['move_out'];
            }
            continue;
        }

        $gapDays = (int) $availableDate->diff($booking['move_in'])->days;

        if ($gapDays < MIN_STAY_DAYS) {
            if ($booking['move_out'] === null) {
                // Open-ended booking with no known move-out date - we can't
                // tell when the unit would free up again, so give up on it.
                $availableDate = null;
                break;
            }
            $availableDate = clone $booking['move_out'];
            continue;
        }

        $cappingBookingMoveIn = clone $booking['move_in'];
        $cappingSource = $booking['source'] ?? 'reservation';
        $cappingType = $booking['block_type'] ?? null;
        break;
    }

    if ($availableDate === null) {
        continue;
    }

    // The real opening we landed on (possibly after skipping past several
    // short busy periods) still has to fall inside the customer's stated
    // move-in window.
    if ($availableDate > $latestMoveIn) {
        continue;
    }

    // Calculate lease end based on requested duration
    $leaseEnd = clone $availableDate;
    $leaseEnd->modify("+".$months." months");

    // If an upcoming booking or blocked period follows this opening within the requested lease term,
    // cap the displayed availability window there instead of showing a
    // lease period that runs straight through it.
    if ($cappingBookingMoveIn !== null && $cappingBookingMoveIn < $leaseEnd) {
        $leaseEnd = clone $cappingBookingMoveIn;
        $limitedAvailability = true;
        $nextBookingDate = $cappingBookingMoveIn->format('F d, Y');
    }

    $units[] = [
        'unit_id' => (int)$row['unit_id'],
        'unit_number' => $row['unit_number'],
        'unit_type' => $row['unit_type'],
        'sqm' => (float)($row['sqm'] ?? 0),
        'lease_rate' => $row['lease_rate'],
        'unit_owner_id' => $row['unit_owner_id'],
        'owner_name' => $row['owner_name'],
        'availability_start' =>
            $availableDate->format('F d, Y'),
        'availability_end' =>
            $leaseEnd->format('F d, Y'),
        'limited_availability' => $limitedAvailability,
        'next_booking_date' => $nextBookingDate,
        'limited_reason' => ($cappingSource === 'blocked_date') ? ($cappingType ?: 'Blocked') : 'Reserved'
    ];
}

echo json_encode([
    'success'=>true,
    'count'=>count($units),
    'units'=>$units
]);

$stmt->close();
$conn->close();
?>