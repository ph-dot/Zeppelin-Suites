<?php
/**
 * sync_unit_status.php
 *
 * Keeps units_table.unit_current_status honest for units whose lease has
 * naturally ended.
 *
 * Why this exists:
 * When a lease reservation is officially booked (markOfficiallyBooked.php),
 * the unit is set to 'Reserved'. Nothing ever automatically reverts that
 * status once the lease's move_out_date passes - it only changes on manual
 * admin actions (cancel / reject). That means pages reading
 * unit_current_status directly (units.php, analytics.php, etc.) can show a
 * unit as "Reserved"/"Occupied" long after the tenant has actually moved out.
 *
 * This function finds units in ('Reserved','Occupied') whose most recent
 * active reservation's move_out_date is in the past, and flips them back to
 * 'Ready for Occupancy'.
 *
 * Notes / deliberate scope limits:
 * - Only touches 'Reserved' and 'Occupied' units. 'Resale', 'On Hold', and
 *   'Under maintenance' are left alone - those are set/cleared by explicit
 *   admin action, not by lease dates.
 * - A reservation counts as "active" if its reservation_status is NOT
 *   'cancelled' or 'rejected'. Resale reservations typically have no
 *   move_out_date, so they're naturally excluded (NULL is never < CURDATE()).
 * - Units with no reservation history at all are untouched (nothing to sync).
 *
 * Usage:
 *   require_once __DIR__ . '/sync_unit_status.php';
 *   syncExpiredUnitStatuses($conn);
 *
 * Safe to call on every page load that displays unit status - it's a single
 * indexed UPDATE...JOIN and a no-op when nothing has expired.
 */

function syncExpiredUnitStatuses(mysqli $conn): int
{
    $sql = "
        UPDATE units_table u
        JOIN (
            SELECT
                unit_id,
                MAX(move_out_date) AS latest_move_out
            FROM reservation_table
            WHERE LOWER(reservation_status) NOT IN ('cancelled', 'rejected')
            GROUP BY unit_id
        ) latest ON latest.unit_id = u.unit_id
        SET u.unit_current_status = 'Ready for Occupancy'
        WHERE u.unit_current_status IN ('Reserved', 'Occupied')
        AND latest.latest_move_out IS NOT NULL
        AND latest.latest_move_out < CURDATE()
    ";

    if (!$conn->query($sql)) {
        // Don't let a sync failure break the page that called it.
        error_log('syncExpiredUnitStatuses failed: ' . $conn->error);
        return 0;
    }

    return $conn->affected_rows;
}
