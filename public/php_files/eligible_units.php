<?php
/**
 * eligible_units.php
 *
 * Why this exists:
 * Previously, respondApprovalRequest.php closed an inquiry out completely
 * (Inquiry_table.status = 'declined', approval_status = 'declined') the
 * moment there were zero *pending* owner_approval_requests rows left for
 * that inquiry - even if the admin had only ever sent the request to ONE
 * of several available owners. That meant: 3 units available, admin sends
 * to 1 owner, that owner declines -> inquiry gets marked fully "Declined",
 * even though 2 more owners with available units were never asked.
 *
 * getRemainingEligibleUnitCount() re-runs the same "is this unit actually
 * available for this inquiry" logic used in checkAvailableUnits.php, but
 * excludes any unit that has ALREADY been sent a request (approved,
 * declined, expired, or still pending) for this specific inquiry. If the
 * result is > 0, there are still untried owners the admin could contact,
 * so the inquiry should NOT be auto-closed as declined.
 *
 * Usage:
 *   require_once __DIR__ . '/eligible_units.php';
 *   $remaining = getRemainingEligibleUnitCount($conn, $inq_id);
 */


function getRemainingEligibleUnitCount(mysqli $conn, int $inq_id): int
{
    $inqStmt = $conn->prepare("
        SELECT inquiry_type, Preferred_unit_id AS unit_type, preferred_move_in_time, lease_duration
        FROM inquiry_table
        WHERE inq_id = ?
    ");
    $inqStmt->bind_param("i", $inq_id);
    $inqStmt->execute();
    $inquiry = $inqStmt->get_result()->fetch_assoc();
    $inqStmt->close();
 
    if (!$inquiry) {
        return 0;
    }
 
    $unit_type = $inquiry['unit_type'];
    $inquiryTypeNormalized = strtolower(trim($inquiry['inquiry_type'] ?? ''));
    $isResale = ($inquiryTypeNormalized === 'resale inquiry');

    if ($isResale) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM units_table u
            WHERE u.unit_type = ?
            AND u.unit_current_status = 'Resale'
            AND u.unit_owner_id IS NOT NULL
            AND u.unit_id NOT IN (
                SELECT unit_id FROM owner_approval_requests WHERE inq_id = ?
            )
        ");
        if (!$stmt) {
            error_log('getRemainingEligibleUnitCount failed: ' . $conn->error);
            return 0;
        }
        $stmt->bind_param("si", $unit_type, $inq_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['total'] ?? 0);
    }
 
    // --- Same move-in window logic as checkAvailableUnits.php ---
    $today = new DateTime();
    $movePreference = strtolower(trim($inquiry['preferred_move_in_time'] ?? ''));
    $earliestMoveIn = clone $today;
    $latestMoveIn = clone $today;
 
    switch ($movePreference) {
        case 'immediately':
            $latestMoveIn->modify('+30 days');
            break;
        case 'within 1 month':
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
            $latestMoveIn->modify('+6 months');
            break;
    }
 
    // --- Same lease-duration parsing as checkAvailableUnits.php ---
    $leaseDuration = strtolower(trim($inquiry['lease_duration'] ?? ''));
    $months = 12;
 
    if (strpos($leaseDuration, 'month') !== false) {
        preg_match('/\d+/', $leaseDuration, $matches);
        if (!empty($matches)) {
            $months = intval($matches[0]);
        }
    } elseif (strpos($leaseDuration, 'year') !== false) {
        preg_match('/\d+/', $leaseDuration, $matches);
        if (!empty($matches)) {
            $months = intval($matches[0]) * 12;
        }
    } elseif (strpos($leaseDuration, 'longer') !== false) {
        $months = 36;
    }
 
    // Business rule: shortest lease/stay the company will book is 30 days.
    $minStayDays = 30;
 
    // --- Units of the right type, with an owner, not on hold/resale/maintenance,
    //     and NOT already contacted (in owner_approval_requests) for this inquiry ---
    $sql = "
        SELECT
            u.unit_id,
            MAX(r.move_out_date) AS latest_move_out
        FROM units_table u
        LEFT JOIN reservation_table r
            ON u.unit_id = r.unit_id
            AND LOWER(r.reservation_status) NOT IN ('cancelled','rejected')
            AND r.move_in_date <= CURDATE()
            AND r.move_out_date >= CURDATE()
        WHERE u.unit_type = ?
        AND u.unit_current_status NOT IN ('Resale', 'On Hold', 'Under maintenance')
        AND u.unit_owner_id IS NOT NULL
        AND u.unit_id NOT IN (
            SELECT unit_id FROM owner_approval_requests WHERE inq_id = ?
        )
        GROUP BY u.unit_id
    ";
 
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('getRemainingEligibleUnitCount failed: ' . $conn->error);
        return 0;
    }
 
    $stmt->bind_param("si", $unit_type, $inq_id);
    $stmt->execute();
    $result = $stmt->get_result();
 
    $candidates = [];
    while ($row = $result->fetch_assoc()) {
        $availableDate = $row['latest_move_out'] === null
            ? new DateTime()
            : new DateTime($row['latest_move_out']);
 
        if ($availableDate > $latestMoveIn) {
            continue;
        }
 
        $candidates[$row['unit_id']] = $availableDate;
    }
    $stmt->close();
 
    if (empty($candidates)) {
        return 0;
    }
 
    // Find each candidate unit's next upcoming reservation (if any), so a
    // unit with only a few days free before its next booking doesn't count
    // as "eligible" when it can't actually meet the minimum stay.
    $unitIds = array_keys($candidates);
    $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
    $types = str_repeat('i', count($unitIds));
 
    $nextStmt = $conn->prepare("
        SELECT unit_id, move_in_date
        FROM reservation_table
        WHERE unit_id IN ($placeholders)
        AND LOWER(reservation_status) NOT IN ('cancelled','rejected')
        AND move_in_date IS NOT NULL
    ");
    $nextStmt->bind_param($types, ...$unitIds);
    $nextStmt->execute();
    $nextResult = $nextStmt->get_result();
 
    $nextBookingByUnit = [];
    while ($nextRow = $nextResult->fetch_assoc()) {
        $unitId = $nextRow['unit_id'];
        $moveIn = new DateTime($nextRow['move_in_date']);
        $availableDate = $candidates[$unitId];
 
        if ($moveIn <= $availableDate) {
            continue;
        }
 
        if (!isset($nextBookingByUnit[$unitId]) || $moveIn < $nextBookingByUnit[$unitId]) {
            $nextBookingByUnit[$unitId] = $moveIn;
        }
    }
    $nextStmt->close();
 
    $count = 0;
    foreach ($candidates as $unitId => $availableDate) {
        $leaseEnd = clone $availableDate;
        $leaseEnd->modify("+".$months." months");
 
        if (isset($nextBookingByUnit[$unitId]) && $nextBookingByUnit[$unitId] < $leaseEnd) {
            $gapDays = (int) $availableDate->diff($nextBookingByUnit[$unitId])->days;
            if ($gapDays < $minStayDays) {
                continue;
            }
        }
 
        $count++;
    }
 
    return $count;
}