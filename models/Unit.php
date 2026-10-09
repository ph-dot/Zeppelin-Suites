<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Unit Model
 * Pure MVC database layer for building units, occupancy, and ownership management.
 * Strictly PDO prepared statements. Zero HTML or direct output.
 */
class Unit extends Model {
    public const UNIT_MAP = [
        'Studio Type A' => [
            'prefix' => 'A',
            'start'  => 101,
            'sqm'    => 37.00,
        ],
        'Studio Type B' => [
            'prefix' => 'B',
            'start'  => 201,
            'sqm'    => 40.65,
        ],
        'One Bedroom' => [
            'prefix' => 'C',
            'start'  => 201,
            'sqm'    => 75.64,
        ],
        'Two Bedroom' => [
            'prefix' => 'D',
            'start'  => 301,
            'sqm'    => 113.00,
        ],
    ];

    /**
     * Synchronize unit statuses based on expired leases.
     */
    public function syncExpiredUnitStatuses(): void {
        try {
            $stmt = $this->db->prepare("
                UPDATE units_table u
                SET u.unit_current_status = 'Ready for Occupancy'
                WHERE LOWER(u.unit_current_status) IN ('occupied', 'reserved')
                  AND NOT EXISTS (
                      SELECT 1
                      FROM reservation_table r
                      WHERE r.unit_id = u.unit_id
                        AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
                        AND (
                            r.move_out_date >= CURDATE()
                            OR (r.reservation_status = 'reserved' AND r.move_in_date >= CURDATE())
                        )
                  )
            ");
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('syncExpiredUnitStatuses error in Unit model: ' . $e->getMessage());
        }
    }

    /**
     * Get owner options (users with user_role 'unit owner' or 'tenant').
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOwnerOptions(): array {
        $stmt = $this->db->prepare("
            SELECT user_id, full_name, email, user_role 
            FROM users_table 
            WHERE user_role IN ('tenant', 'unit owner')
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total count of units.
     */
    public function getTotalUnitsCount(): int {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM units_table");
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /**
     * Get all units with owner details, current tenant info, and availability, grouped by floor.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getAllGroupedByFloor(): array {
        $this->syncExpiredUnitStatuses();

        $stmt = $this->db->prepare("
            SELECT 
                u.unit_id,
                u.unit_number,
                u.unit_type,
                u.sqm,
                u.floor_number,
                u.lease_rate,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS reselling_price,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS resellling_price,
                u.listing_type,
                u.stay_category,
                u.unit_owner_id,
                u.unit_current_status,
                u.created_at,
                uo.full_name AS unit_owner_name,
                uo.email AS unit_owner_email,
                (SELECT r.client_name
                 FROM reservation_table r
                 WHERE r.unit_id = u.unit_id
                   AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
                 ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
                 LIMIT 1) AS tenant_name,
                (SELECT r.client_contact
                 FROM reservation_table r
                 WHERE r.unit_id = u.unit_id
                   AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
                 ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
                 LIMIT 1) AS tenant_contact,
                (SELECT r.client_email
                 FROM reservation_table r
                 WHERE r.unit_id = u.unit_id
                   AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
                 ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
                 LIMIT 1) AS tenant_email,
                (SELECT r.move_in_date
                 FROM reservation_table r
                 WHERE r.unit_id = u.unit_id
                   AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
                 ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
                 LIMIT 1) AS move_in_date,
                (SELECT r.move_out_date
                 FROM reservation_table r
                 WHERE r.unit_id = u.unit_id
                   AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
                 ORDER BY CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END, r.reservation_id DESC
                 LIMIT 1) AS move_out_date
            FROM units_table u
            LEFT JOIN users_table uo ON u.unit_owner_id = uo.user_id
            ORDER BY u.floor_number ASC, u.unit_number ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unitsByFloor = [];
        foreach ($rows as $row) {
            $floor = (int)($row['floor_number'] ?: 1);
            $row['availability_info'] = $this->getUnitAvailabilityInfo((int)$row['unit_id'], (string)$row['unit_current_status'], (string)($row['move_out_date'] ?? ''));
            $unitsByFloor[$floor][] = $row;
        }

        return $unitsByFloor;
    }

    /**
     * Compute availability text and duration for unit card display.
     *
     * @return array{range: string, duration: string, start_date?: string, end_date?: string}
     */
    public function getUnitAvailabilityInfo(int $unitId, string $status, string $currentMoveOutDate): array {
        $statusLower = strtolower(trim($status));
        if ($statusLower === 'resale') {
            return ['range' => 'Available for Resale', 'duration' => 'Ready for purchase'];
        }
        if ($statusLower === 'under maintenance') {
            return ['range' => 'Under Maintenance', 'duration' => 'Temporarily unavailable'];
        }
        if ($statusLower === 'on hold') {
            return ['range' => 'On Hold', 'duration' => 'Listing paused'];
        }

        $todayTs = strtotime('today');
        if (!empty($currentMoveOutDate) && $currentMoveOutDate !== '0000-00-00' && strtotime($currentMoveOutDate) >= $todayTs) {
            $startDate = new DateTime($currentMoveOutDate);
            $startDate->modify('+1 day');
            $startStr = $startDate->format('M j, Y');
        } else {
            $startDate = new DateTime('today');
            $startStr = 'Now';
        }

        $startDateFormatted = $startDate->format('M j, Y');
        $maxEndDate = (clone $startDate)->modify('+2 years');
        $effectiveEndDate = $maxEndDate;
        $durationLabel = 'Duration: 2 Years (Latest)';

        $startDateSql = $startDate->format('Y-m-d');
        $stmtNext = $this->db->prepare("
            SELECT move_in_date 
            FROM reservation_table 
            WHERE unit_id = ? 
              AND move_in_date >= ? 
              AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')
            ORDER BY move_in_date ASC 
            LIMIT 1
        ");
        $stmtNext->execute([$unitId, $startDateSql]);
        $nextRow = $stmtNext->fetch(PDO::FETCH_ASSOC);

        if ($nextRow && !empty($nextRow['move_in_date'])) {
            $nextMoveIn = new DateTime($nextRow['move_in_date']);
            if ($nextMoveIn < $maxEndDate) {
                $effectiveEndDate = $nextMoveIn;
                $diff = $startDate->diff($nextMoveIn);
                $parts = [];
                if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y === 1 ? 'yr' : 'yrs');
                if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m === 1 ? 'mo' : 'mos');
                if ($diff->d > 0 && empty($parts)) $parts[] = $diff->d . ' ' . ($diff->d === 1 ? 'day' : 'days');
                $durationLabel = 'Duration: ' . (!empty($parts) ? implode(' ', $parts) : '1 mo');
            }
        }

        $endStr = $effectiveEndDate->format('M j, Y');
        return [
            'range'      => "Avail: {$startStr} – {$endStr}",
            'duration'   => $durationLabel,
            'start_date' => $startDateFormatted,
            'end_date'   => $endStr,
        ];
    }

    /**
     * Get unit details by ID including owner information.
     */
    public function getUnitDetails(int $unitId): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                u.unit_id,
                u.unit_number,
                u.unit_type,
                u.sqm,
                u.floor_number,
                u.lease_rate,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS reselling_price,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS resellling_price,
                u.listing_type,
                u.stay_category,
                u.unit_owner_id,
                u.unit_current_status,
                u.created_at,
                uo.full_name AS owner_name,
                uo.email AS owner_email,
                uo.contact AS owner_contact,
                uo.created_at AS owner_created_at
            FROM units_table u
            LEFT JOIN users_table uo ON u.unit_owner_id = uo.user_id
            WHERE u.unit_id = ?
            LIMIT 1
        ");
        $stmt->execute([$unitId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Get active tenant for a unit.
     */
    public function getActiveTenant(int $unitId): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                r.reservation_id,
                r.inq_id,
                r.client_name,
                r.client_email,
                r.client_contact,
                r.resident_type,
                r.transaction_type,
                r.reservation_type,
                r.move_in_date,
                r.move_out_date,
                r.reservation_status,
                r.payment_status,
                r.officially_booked_at
            FROM reservation_table r
            WHERE r.unit_id = ?
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
              AND (
                  (r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE())
                  OR (r.reservation_status IN ('reserved', 'occupied', 'handover') AND r.move_out_date >= CURDATE())
              )
            ORDER BY 
                CASE WHEN r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() THEN 0 ELSE 1 END,
                r.move_in_date ASC
            LIMIT 1
        ");
        $stmt->execute([$unitId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Calculate next availability text for unit detail view.
     *
     * @return array{text: string, sub: string, is_occupied: bool}
     */
    public function getNextAvailability(int $unitId, string $status): array {
        $statusLower = strtolower(trim($status));
        if ($statusLower === 'resale') {
            return ['text' => 'Available for Resale', 'sub' => 'Unit is currently listed for sale', 'is_occupied' => false];
        }
        if ($statusLower === 'under maintenance') {
            return ['text' => 'Under Maintenance', 'sub' => 'Temporarily unavailable for occupancy', 'is_occupied' => false];
        }
        if ($statusLower === 'on hold') {
            return ['text' => 'On Hold', 'sub' => 'Listing temporarily paused', 'is_occupied' => false];
        }

        $stmt = $this->db->prepare("
            SELECT MAX(r.move_out_date) AS latest_move_out
            FROM reservation_table r
            WHERE r.unit_id = ?
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
              AND r.move_out_date >= CURDATE()
        ");
        $stmt->execute([$unitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['latest_move_out'])) {
            $moveOutDate = $row['latest_move_out'];
            $nextDate = date('M j, Y', strtotime($moveOutDate . ' +1 day'));
            return [
                'text'        => $nextDate,
                'sub'         => 'Occupied until ' . date('M j, Y', strtotime($moveOutDate)),
                'is_occupied' => true,
            ];
        }

        return [
            'text'        => 'Immediately Available',
            'sub'         => 'Ready for new move-in',
            'is_occupied' => false,
        ];
    }

    /**
     * Get start date of current owner.
     */
    public function getCurrentOwnerStartDate(int $unitId, ?int $currentOwnerId, ?string $unitCreatedAt): ?string {
        if (!$currentOwnerId) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT start_date 
            FROM unit_ownership_history 
            WHERE unit_id = ? AND owner_id = ? AND ownership_status = 'active'
            ORDER BY history_id DESC 
            LIMIT 1
        ");
        $stmt->execute([$unitId, $currentOwnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['start_date'])) {
            return (string)$row['start_date'];
        }

        return !empty($unitCreatedAt) ? date('Y-m-d', strtotime($unitCreatedAt)) : null;
    }

    /**
     * Fetch past owners for a unit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPastOwners(int $unitId, ?int $currentOwnerId): array {
        $dummyOwner = $currentOwnerId ?? -1;
        $stmt = $this->db->prepare("
            SELECT 
                h.history_id,
                h.unit_id,
                h.owner_id,
                h.start_date,
                h.end_date,
                h.ownership_status,
                h.transfer_type,
                h.remarks,
                uo.full_name AS owner_name,
                uo.email AS owner_email,
                uo.contact AS owner_contact
            FROM unit_ownership_history h
            JOIN users_table uo ON h.owner_id = uo.user_id
            WHERE h.unit_id = ?
              AND (
                  h.ownership_status IN ('transferred', 'past') 
                  OR h.end_date IS NOT NULL
                  OR (? IS NOT NULL AND h.owner_id != ?)
              )
            ORDER BY h.start_date DESC, h.history_id DESC
        ");
        $stmt->execute([$unitId, $currentOwnerId, $dummyOwner]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all past and current tenants across unit lifetime.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllTenants(int $unitId, ?string $fallbackOwnerName = null): array {
        $stmt = $this->db->prepare("
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
                r.reservation_status,
                r.payment_status,
                r.officially_booked_at,
                r.created_at,
                (
                    SELECT uo.full_name 
                    FROM unit_ownership_history h
                    JOIN users_table uo ON h.owner_id = uo.user_id
                    WHERE h.unit_id = r.unit_id
                      AND (
                          (h.start_date <= r.move_in_date AND (h.end_date IS NULL OR h.end_date >= r.move_in_date))
                          OR (h.start_date <= DATE(r.created_at) AND (h.end_date IS NULL OR h.end_date >= DATE(r.created_at)))
                      )
                    ORDER BY h.history_id DESC
                    LIMIT 1
                ) AS owner_during_stay
            FROM reservation_table r
            WHERE r.unit_id = ?
            ORDER BY 
                CASE 
                    WHEN (r.move_in_date <= CURDATE() AND r.move_out_date >= CURDATE() AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')) THEN 1
                    WHEN (r.move_in_date > CURDATE() AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')) THEN 2
                    WHEN (r.move_out_date < CURDATE() AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')) THEN 3
                    ELSE 4
                END ASC,
                r.move_in_date DESC,
                r.reservation_id DESC
        ");
        $stmt->execute([$unitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            if (empty($r['owner_during_stay'])) {
                $r['owner_during_stay'] = !empty($fallbackOwnerName) ? $fallbackOwnerName : 'Zeppelin Suites';
            }
        }
        return $rows;
    }

    /**
     * Compute next unit number and SQM based on unit type.
     *
     * @return array{unit_number: string, sqm: float}|null
     */
    public function getNextUnitNumber(string $unitType): ?array {
        if (!array_key_exists($unitType, self::UNIT_MAP)) {
            return null;
        }

        $config = self::UNIT_MAP[$unitType];
        $prefix = $config['prefix'];
        $startNumber = $config['start'];

        $stmt = $this->db->prepare("
            SELECT unit_number 
            FROM units_table 
            WHERE unit_type = ? 
              AND unit_number LIKE ?
            ORDER BY CAST(SUBSTRING(unit_number, 2) AS UNSIGNED) DESC
            LIMIT 1
        ");
        $stmt->execute([$unitType, "{$prefix}%"]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['unit_number'])) {
            $latestNumber = (int)substr((string)$row['unit_number'], 1);
            $nextNumber = $latestNumber + 1;
        } else {
            $nextNumber = $startNumber;
        }

        return [
            'unit_number' => $prefix . $nextNumber,
            'sqm'         => (float)$config['sqm'],
        ];
    }

    /**
     * Create a new unit with optional owner creation or assignment.
     *
     * @param array<string, mixed> $data
     * @return int Created unit_id
     */
    public function create(array $data): int {
        $unitType = trim((string)($data['unit_type'] ?? ''));
        $floorNumber = isset($data['floor_number']) ? max(1, min(10, (int)$data['floor_number'])) : 1;
        $unitCurrentStatus = trim((string)($data['unit_current_status'] ?? 'Ready for Occupancy'));
        $ownerAssignment = trim((string)($data['owner_assignment'] ?? 'none'));

        $nextInfo = $this->getNextUnitNumber($unitType);
        if (!$nextInfo) {
            throw new InvalidArgumentException('Invalid unit type selected.');
        }

        $unitNumber = $nextInfo['unit_number'];
        $sqm = $nextInfo['sqm'];
        $unitOwnerId = null;

        $this->db->beginTransaction();

        try {
            if ($ownerAssignment === 'existing') {
                $existingId = (int)($data['existing_owner_id'] ?? 0);
                if ($existingId > 0) {
                    $unitOwnerId = $existingId;
                }
            } elseif ($ownerAssignment === 'new') {
                $newName = trim((string)($data['new_owner_name'] ?? ''));
                $newEmail = trim((string)($data['new_owner_email'] ?? ''));
                $newContact = trim((string)($data['new_owner_contact'] ?? ''));

                if ($newName === '' || $newEmail === '') {
                    throw new InvalidArgumentException('New unit owner requires full name and email.');
                }

                $stmtUser = $this->db->prepare("
                    INSERT INTO users_table (full_name, email, contact, user_role, resident_status, created_at)
                    VALUES (?, ?, ?, 'unit owner', 'Active', NOW())
                ");
                $stmtUser->execute([$newName, $newEmail, $newContact]);
                $unitOwnerId = (int)$this->db->lastInsertId();
            }

            $stmtUnit = $this->db->prepare("
                INSERT INTO units_table (
                    unit_number, 
                    unit_type, 
                    floor_number, 
                    sqm, 
                    unit_owner_id, 
                    unit_current_status, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtUnit->execute([
                $unitNumber,
                $unitType,
                $floorNumber,
                $sqm,
                $unitOwnerId,
                $unitCurrentStatus,
            ]);
            $unitId = (int)$this->db->lastInsertId();

            if ($unitOwnerId !== null && $unitOwnerId > 0) {
                $stmtHistory = $this->db->prepare("
                    INSERT INTO unit_ownership_history (
                        unit_id, 
                        owner_id, 
                        start_date, 
                        end_date, 
                        ownership_status, 
                        transfer_type, 
                        remarks
                    ) VALUES (?, ?, CURDATE(), NULL, 'active', 'Initial Assignment', 'Unit creation and first owner assignment')
                ");
                $stmtHistory->execute([$unitId, $unitOwnerId]);
            }

            $this->db->commit();
            return $unitId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update unit ownership assignment and track ownership history.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateOwnership(int $unitId, array $data): array {
        $unit = $this->getUnitDetails($unitId);
        if (!$unit) {
            throw new RuntimeException('Unit not found.');
        }

        $ownerAction = trim((string)($data['owner_action'] ?? 'keep'));
        $transferReason = trim((string)($data['transfer_reason'] ?? 'Reassigned by Admin'));
        $oldOwnerId = $unit['unit_owner_id'] !== null ? (int)$unit['unit_owner_id'] : null;
        $newOwnerId = $oldOwnerId;

        $this->db->beginTransaction();

        try {
            if ($ownerAction === 'remove') {
                $newOwnerId = null;
            } elseif ($ownerAction === 'assign') {
                $assignedId = (int)($data['existing_owner_id'] ?? 0);
                if ($assignedId > 0) {
                    $newOwnerId = $assignedId;
                }
            } elseif ($ownerAction === 'new') {
                $newName = trim((string)($data['new_owner_name'] ?? ''));
                $newEmail = trim((string)($data['new_owner_email'] ?? ''));
                $newContact = trim((string)($data['new_owner_contact'] ?? ''));

                if ($newName === '' || $newEmail === '') {
                    throw new InvalidArgumentException('New unit owner requires full name and email.');
                }

                $stmtUser = $this->db->prepare("
                    INSERT INTO users_table (full_name, email, contact, user_role, resident_status, created_at)
                    VALUES (?, ?, ?, 'unit owner', 'Active', NOW())
                ");
                $stmtUser->execute([$newName, $newEmail, $newContact]);
                $newOwnerId = (int)$this->db->lastInsertId();
            }

            // Update units_table
            $stmtUpdate = $this->db->prepare("
                UPDATE units_table 
                SET unit_owner_id = ?
                WHERE unit_id = ?
            ");
            $stmtUpdate->execute([$newOwnerId, $unitId]);

            // Handle ownership history tracking if changed
            if ($oldOwnerId !== $newOwnerId) {
                $today = date('Y-m-d');

                // 1. Close old owner's active record if exists
                if ($oldOwnerId !== null && $oldOwnerId > 0) {
                    $stmtCloseOld = $this->db->prepare("
                        UPDATE unit_ownership_history 
                        SET end_date = ?, 
                            ownership_status = 'transferred', 
                            remarks = CONCAT(IFNULL(remarks, ''), ' [Transferred on ', ?, ']')
                        WHERE unit_id = ? AND owner_id = ? AND ownership_status = 'active'
                    ");
                    $stmtCloseOld->execute([$today, $today, $unitId, $oldOwnerId]);
                }

                // 2. Open new active history record
                if ($newOwnerId !== null && $newOwnerId > 0) {
                    $stmtNewHistory = $this->db->prepare("
                        INSERT INTO unit_ownership_history (unit_id, owner_id, start_date, end_date, ownership_status, transfer_type, remarks)
                        VALUES (?, ?, ?, NULL, 'active', ?, ?)
                    ");
                    $remarkText = $transferReason !== '' ? $transferReason : 'Ownership updated by Administrator';
                    $stmtNewHistory->execute([$unitId, $newOwnerId, $today, $transferReason, $remarkText]);
                }
            }

            $this->db->commit();

            // Fetch updated owner details
            $newOwnerData = null;
            if ($newOwnerId !== null && $newOwnerId > 0) {
                $stmtOwnerInfo = $this->db->prepare("
                    SELECT user_id, full_name, email, contact 
                    FROM users_table 
                    WHERE user_id = ? 
                    LIMIT 1
                ");
                $stmtOwnerInfo->execute([$newOwnerId]);
                $newOwnerData = $stmtOwnerInfo->fetch(PDO::FETCH_ASSOC);
            }

            return [
                'unit_id'    => $unitId,
                'unit_owner' => $newOwnerData,
                'has_owner'  => ($newOwnerData !== null),
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
