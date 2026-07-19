<?php
require_once '../../php_files/admin_auth.php';
require_once '../../php_files/db.php';
require_once '../../php_files/sync_unit_status.php';

header('Content-Type: application/json');

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

// FIND UNITS
$sql = "
SELECT 
    u.unit_id,
    u.unit_number,
    u.unit_type,
    u.lease_rate,
    u.unit_owner_id,
    owner.full_name AS owner_name,
    MAX(
        CASE
            WHEN LOWER(r.reservation_status) NOT IN ('cancelled','rejected')
            THEN r.move_out_date
            ELSE NULL
        END
    ) AS latest_move_out
FROM units_table u
LEFT JOIN users_table owner
    ON u.unit_owner_id = owner.user_id
LEFT JOIN reservation_table r
    ON u.unit_id = r.unit_id
WHERE u.unit_type = ?
AND u.unit_current_status NOT IN ('Resale', 'On Hold', 'Under maintenance')
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
$units = [];

// CHECK EACH UNIT AVAILABILITY
while ($row = $result->fetch_assoc()) {
    // If no reservation, available today
    if ($row['latest_move_out'] === null) {
        $availableDate = new DateTime();
    } else {
        $availableDate = new DateTime($row['latest_move_out']);
    }
    // Check customer preference only for future available units
// Units available today can still be suggested

    if ($availableDate > $latestMoveIn) {
        continue;
    }
    // Calculate lease end
    $leaseEnd = clone $availableDate;
    $leaseEnd->modify("+".$months." months");
    $units[] = [
        'unit_id' => $row['unit_id'],
        'unit_number' => $row['unit_number'],
        'unit_type' => $row['unit_type'],
        'lease_rate' => $row['lease_rate'],
        'unit_owner_id' => $row['unit_owner_id'],
        'owner_name' => $row['owner_name'],
        'availability_start' =>
            $availableDate->format('F d, Y'),
        'availability_end' =>
            $leaseEnd->format('F d, Y')
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