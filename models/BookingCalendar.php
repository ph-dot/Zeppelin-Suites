<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Booking Calendar Model
 * Pure MVC database layer for calendar timeline, bookings, and blocked dates.
 * Strictly PDO prepared statements. Zero direct HTML or output.
 */
class BookingCalendar extends Model {
    /**
     * Retrieve all units, active bookings, and blocked dates formatted for the timeline calendar.
     *
     * @return array{unitTypes: array, bookings: array, blockedDates: array}
     */
    public function getCalendarData(): array {
        // 1. Units, grouped by unit_type
        $unitsStmt = $this->db->query("
            SELECT unit_id, unit_type, unit_number, unit_current_status
            FROM units_table
            ORDER BY unit_type, unit_number
        ");

        $grouped = [];
        while ($row = $unitsStmt->fetch(PDO::FETCH_ASSOC)) {
            $type = (string)$row['unit_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = [
                'room'        => (string)$row['unit_number'],
                'unitId'      => (int)$row['unit_id'],
                'maintenance' => trim((string)$row['unit_current_status']) === 'Under maintenance',
            ];
        }

        $unitTypes = [];
        foreach ($grouped as $type => $rooms) {
            $unitTypes[] = ['key' => $type, 'rooms' => $rooms];
        }

        // 2. Active reservations (exclude rejected / cancelled)
        $bookingsStmt = $this->db->query("
            SELECT
                r.reservation_id, r.client_name, r.client_email, r.client_contact,
                r.move_in_date, r.move_out_date, r.reservation_status,
                u.unit_id, u.unit_type, u.unit_number
            FROM reservation_table r
            JOIN units_table u ON r.unit_id = u.unit_id
            WHERE LOWER(r.reservation_status) NOT IN ('rejected', 'cancelled')
              AND r.move_in_date IS NOT NULL
              AND r.move_out_date IS NOT NULL
            ORDER BY r.move_in_date
        ");

        $today = date('Y-m-d');
        $bookings = [];
        while ($row = $bookingsStmt->fetch(PDO::FETCH_ASSOC)) {
            $isCurrent = $row['move_in_date'] <= $today && $today <= $row['move_out_date'];

            $bookings[] = [
                'id'                => (int)$row['reservation_id'],
                'guestName'         => (string)$row['client_name'],
                'email'             => (string)$row['client_email'],
                'phone'             => (string)$row['client_contact'],
                'unitType'          => (string)$row['unit_type'],
                'roomNumber'        => (string)$row['unit_number'],
                'unitId'            => (int)$row['unit_id'],
                'startDate'         => (string)$row['move_in_date'],
                'endDate'           => (string)$row['move_out_date'],
                'status'            => $isCurrent ? 'Occupied' : 'Reserved',
                'reservationStatus' => (string)$row['reservation_status'],
            ];
        }

        // 3. Blocked dates (Maintenance / Not Available)
        $blockedStmt = $this->db->query("
            SELECT
                b.block_id, b.unit_id, b.start_date, b.end_date, b.block_type, b.remarks,
                b.created_by_role, b.created_at,
                u.unit_type, u.unit_number
            FROM unit_blocked_dates b
            JOIN units_table u ON b.unit_id = u.unit_id
            ORDER BY b.start_date
        ");

        $blockedDates = [];
        while ($row = $blockedStmt->fetch(PDO::FETCH_ASSOC)) {
            $blockedDates[] = [
                'blockId'       => (int)$row['block_id'],
                'unitId'        => (int)$row['unit_id'],
                'unitType'      => (string)$row['unit_type'],
                'roomNumber'    => (string)$row['unit_number'],
                'startDate'     => (string)$row['start_date'],
                'endDate'       => (string)$row['end_date'],
                'blockType'     => (string)$row['block_type'],
                'remarks'       => (string)($row['remarks'] ?? ''),
                'createdByRole' => (string)($row['created_by_role'] ?? 'admin'),
            ];
        }

        return [
            'unitTypes'    => $unitTypes,
            'bookings'     => $bookings,
            'blockedDates' => $blockedDates,
        ];
    }

    /**
     * Block unit dates with validation against overlapping active reservations.
     *
     * @return array{success: bool, message: string}
     */
    public function saveBlockedDate(
        int $unitId,
        string $startDate,
        string $endDate,
        string $blockType,
        string $remarks,
        int $userId,
        string $role = 'admin'
    ): array {
        if ($unitId <= 0) {
            return ['success' => false, 'message' => 'Please select a valid unit.'];
        }

        $startDate = trim($startDate);
        $endDate = trim($endDate);

        if ($startDate === '' || $endDate === '') {
            return ['success' => false, 'message' => 'Start date and end date are required.'];
        }

        $today = date('Y-m-d');
        if ($startDate < $today) {
            return ['success' => false, 'message' => 'Start date cannot be in the past. It must be today or a future date.'];
        }

        if ($endDate < $startDate) {
            return ['success' => false, 'message' => 'End date cannot be earlier than start date.'];
        }

        $allowedTypes = ['Not Available', 'Maintenance'];
        if (!in_array($blockType, $allowedTypes, true)) {
            $blockType = 'Not Available';
        }

        // 1. Verify unit exists
        $unitStmt = $this->db->prepare("SELECT unit_id, unit_number, unit_type FROM units_table WHERE unit_id = ? LIMIT 1");
        $unitStmt->execute([$unitId]);
        $unit = $unitStmt->fetch(PDO::FETCH_ASSOC);

        if (!$unit) {
            return ['success' => false, 'message' => 'Selected unit not found.'];
        }

        // 2. Check for overlapping active reservations
        $resStmt = $this->db->prepare("
            SELECT reservation_id, client_name, move_in_date, move_out_date 
            FROM reservation_table 
            WHERE unit_id = ? 
              AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')
              AND move_in_date <= ? AND move_out_date >= ?
            LIMIT 1
        ");
        $resStmt->execute([$unitId, $endDate, $startDate]);
        $overlap = $resStmt->fetch(PDO::FETCH_ASSOC);

        if ($overlap) {
            $msg = sprintf(
                'Cannot block dates: Unit %s already has an active lease for "%s" from %s to %s.',
                $unit['unit_number'],
                $overlap['client_name'],
                date('M j, Y', strtotime((string)$overlap['move_in_date'])),
                date('M j, Y', strtotime((string)$overlap['move_out_date']))
            );
            return ['success' => false, 'message' => $msg];
        }

        // 3. Insert into unit_blocked_dates
        $insertStmt = $this->db->prepare("
            INSERT INTO unit_blocked_dates 
                (unit_id, start_date, end_date, block_type, remarks, created_by_user_id, created_by_role)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $unitId,
            $startDate,
            $endDate,
            $blockType,
            $remarks !== '' ? $remarks : null,
            $userId,
            $role,
        ]);

        return ['success' => true, 'message' => 'Unit dates blocked successfully.'];
    }

    /**
     * Unblock previously blocked date range.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteBlockedDate(int $blockId): array {
        if ($blockId <= 0) {
            return ['success' => false, 'message' => 'Invalid block ID.'];
        }

        $stmt = $this->db->prepare("DELETE FROM unit_blocked_dates WHERE block_id = ? LIMIT 1");
        $stmt->execute([$blockId]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Unit dates successfully unblocked.'];
        }

        return ['success' => false, 'message' => 'Block record not found or already removed.'];
    }
}
