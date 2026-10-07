<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Reservation Model
 * Handles all database operations for reservation_table, reservation_documents, and unit occupancy sync.
 * Strictly NO direct HTML or presentation logic.
 */
class Reservation extends Model {

    /**
     * Synchronize unit statuses whose lease has expired back to 'Ready for Occupancy'.
     *
     * @return int Number of affected units
     */
    public function syncExpiredUnitStatuses(): int {
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

        try {
            return $this->db->exec($sql) ?: 0;
        } catch (PDOException $e) {
            error_log('syncExpiredUnitStatuses error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Fetch all reservations with full joined details for lease management.
     *
     * @return array
     */
    public function getAllWithDetails(): array {
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
                r.declared_amount,
                r.amount_match_status,
                r.payment_proof,
                r.payment_status,
                r.reservation_status,
                r.admin_remarks,
                r.created_at,
                r.payment_verified_at,
                r.payment_rejected_at,
                r.admin_payment_remarks,
                r.requirements_updated_by,
                r.requirements_updated_by_role,
                r.requirements_updated_at,
                r.officially_booked_at,
                r.officially_booked_by,
                r.officially_booked_by_role,
                r.cancelled_at,
                r.cancelled_by,
                r.cancelled_by_role,
                r.admin_cancel_remarks,
                r.cancellation_status,
                r.cancellation_reason,
                r.cancellation_requested_by,
                r.cancellation_requested_at,
                r.cancellation_requested_by_role,

                u.unit_number,
                u.unit_type,
                u.unit_current_status,

                owner.full_name AS owner_name,
                owner.email AS owner_email,
                updater.full_name AS requirements_updated_by_name,
                official_user.full_name AS officially_booked_by_name,
                cancelled_user.full_name AS cancelled_by_name,
                cancel_requester.full_name AS cancellation_requested_by_name,

                (SELECT COUNT(*) FROM reservation_documents d WHERE d.reservation_id = r.reservation_id) AS total_docs,
                (SELECT COUNT(*) FROM reservation_documents d WHERE d.reservation_id = r.reservation_id AND d.status = 'complete') AS completed_docs
            FROM reservation_table r
            LEFT JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
            LEFT JOIN users_table official_user ON r.officially_booked_by = official_user.user_id
            LEFT JOIN users_table cancelled_user ON r.cancelled_by = cancelled_user.user_id
            LEFT JOIN users_table cancel_requester ON r.cancellation_requested_by = cancel_requester.user_id
            ORDER BY r.reservation_id DESC
        ";

        return $this->fetchAll($sql);
    }

    /**
     * Find a single reservation record by ID.
     */
    public function findById(int $reservationId): ?array {
        $sql = "
            SELECT r.*, u.unit_number, u.unit_type, u.unit_current_status
            FROM reservation_table r
            LEFT JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.reservation_id = ?
            LIMIT 1
        ";
        return $this->fetchOne($sql, [$reservationId]);
    }

    /**
     * Count reservations filtered by date range and optional unit type.
     */
    public function countReservations(string $start, string $end, ?string $unitType = null, string $extraWhere = '', array $extraParams = []): int {
        $sql = "
            SELECT COUNT(*) AS total
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.created_at >= ? AND r.created_at < ?
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND u.unit_type = ?";
            $params[] = $unitType;
        }

        if ($extraWhere !== '') {
            $sql .= " " . $extraWhere;
            foreach ($extraParams as $p) {
                $params[] = $p;
            }
        }

        $row = $this->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get aggregate sales summary for a given time window.
     */
    public function getSalesSummary(string $start, string $end, ?string $unitType = null): array {
        $dateExpr = "COALESCE(r.payment_verified_at, r.created_at)";
        $sql = "
            SELECT
                COALESCE(SUM(r.required_amount), 0) AS collected_sales,
                COALESCE(SUM(r.price_basis), 0) AS contract_value,
                COUNT(*) AS paid_reservations,
                COALESCE(SUM(CASE WHEN r.transaction_type = 'Unit Leasing' THEN r.required_amount ELSE 0 END), 0) AS leasing_sales,
                COALESCE(SUM(CASE WHEN r.transaction_type = 'Unit Resale' THEN r.required_amount ELSE 0 END), 0) AS resale_sales
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.payment_status = 'verified'
              AND r.reservation_status NOT IN ('cancelled', 'rejected')
              AND $dateExpr >= ? AND $dateExpr < ?
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND u.unit_type = ?";
            $params[] = $unitType;
        }

        $row = $this->fetchOne($sql, $params);
        return $row ?: [
            'collected_sales'   => 0,
            'contract_value'    => 0,
            'paid_reservations' => 0,
            'leasing_sales'     => 0,
            'resale_sales'      => 0,
        ];
    }

    /**
     * Get daily sales amounts array indexed by day of month.
     */
    public function getDailySales(string $start, string $end, int $daysInMonth, ?string $unitType = null): array {
        $counts = array_fill(1, $daysInMonth, 0.0);
        $dateExpr = "COALESCE(r.payment_verified_at, r.created_at)";
        $sql = "
            SELECT DAY($dateExpr) AS day_num, COALESCE(SUM(r.required_amount), 0) AS total
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.payment_status = 'verified'
              AND r.reservation_status NOT IN ('cancelled', 'rejected')
              AND $dateExpr >= ? AND $dateExpr < ?
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND u.unit_type = ?";
            $params[] = $unitType;
        }

        $sql .= " GROUP BY DAY($dateExpr) ORDER BY day_num";

        $rows = $this->fetchAll($sql, $params);
        foreach ($rows as $row) {
            $day = (int)$row['day_num'];
            if ($day >= 1 && $day <= $daysInMonth) {
                $counts[$day] = (float)$row['total'];
            }
        }

        return array_values($counts);
    }

    /**
     * Get top revenue generating units for the given window.
     */
    public function getTopRevenueUnits(string $start, string $end, ?string $unitType = null, int $limit = 8): array {
        $sql = "
            SELECT
                u.unit_number,
                u.unit_type,
                COALESCE(owner.full_name, 'No owner assigned') AS owner_name,
                u.unit_current_status,
                COALESCE(SUM(CASE WHEN r.payment_status = 'verified' THEN r.required_amount ELSE 0 END), 0) AS revenue
            FROM units_table u
            LEFT JOIN users_table owner ON owner.user_id = u.unit_owner_id
            LEFT JOIN reservation_table r ON r.unit_id = u.unit_id AND r.created_at >= ? AND r.created_at < ?
            WHERE 1 = 1
        ";
        $params = [$start, $end];

        if ($unitType !== null) {
            $sql .= " AND u.unit_type = ?";
            $params[] = $unitType;
        }

        $sql .= "
            GROUP BY u.unit_id, u.unit_number, u.unit_type, owner.full_name, u.unit_current_status
            ORDER BY revenue DESC, u.unit_number ASC
            LIMIT " . (int)$limit;

        return $this->fetchAll($sql, $params);
    }

    /**
     * Process handover workflow: updates reservation, unit occupancy, and provisions active tenant user account.
     *
     * @param int $reservationId
     * @param string|null $customPassword
     * @return array Result array ['success' => bool, 'message' => string, 'tenant' => ?array]
     */
    public function handover(int $reservationId, ?string $customPassword = null): array {
        $this->beginTransaction();

        try {
            // 1. Lock and fetch reservation details
            $fetchSql = "
                SELECT 
                    r.reservation_id,
                    r.inq_id,
                    r.unit_id,
                    r.client_name,
                    r.client_email,
                    r.client_contact,
                    r.payment_status,
                    r.reservation_status,
                    u.unit_number,
                    u.unit_type,
                    u.unit_current_status
                FROM reservation_table r
                INNER JOIN units_table u ON r.unit_id = u.unit_id
                WHERE r.reservation_id = ?
                LIMIT 1
                FOR UPDATE
            ";
            $res = $this->fetchOne($fetchSql, [$reservationId]);

            if (!$res) {
                throw new RuntimeException("Reservation record #{$reservationId} not found.");
            }

            $unitId = (int)$res['unit_id'];
            $clientName = trim((string)($res['client_name'] ?? ''));
            $clientEmail = trim((string)($res['client_email'] ?? ''));
            $clientContact = trim((string)($res['client_contact'] ?? ''));

            if ($clientEmail === '') {
                throw new RuntimeException("Client email is missing in the reservation record.");
            }

            // 2. Update reservation status to 'handover'
            $updateResSql = "
                UPDATE reservation_table
                SET reservation_status = 'handover',
                    officially_booked_at = COALESCE(officially_booked_at, NOW())
                WHERE reservation_id = ?
            ";
            $this->execute($updateResSql, [$reservationId]);

            // 3. Update unit status to 'Occupied'
            $updateUnitSql = "UPDATE units_table SET unit_current_status = 'Occupied' WHERE unit_id = ?";
            $this->execute($updateUnitSql, [$unitId]);

            // 4. Update inquiry status if linked
            if (!empty($res['inq_id'])) {
                $this->execute("UPDATE inquiry_table SET status = 'officially booked' WHERE inq_id = ?", [(int)$res['inq_id']]);
            }

            // 5. Check if user already exists in users_table by email
            $existingUser = $this->fetchOne("SELECT user_id, full_name, user_role, resident_status FROM users_table WHERE email = ? LIMIT 1", [$clientEmail]);

            $defaultPassword = !empty($customPassword) ? $customPassword : 'password123';
            $tenantUserId = 0;

            if ($existingUser) {
                $tenantUserId = (int)$existingUser['user_id'];
                $currentRole = strtolower((string)$existingUser['user_role']);
                $newRole = ($currentRole === 'admin') ? $existingUser['user_role'] : 'tenant';

                $updateUserSql = "
                    UPDATE users_table 
                    SET resident_status = 'Active',
                        user_role = ?,
                        full_name = COALESCE(NULLIF(full_name, ''), ?),
                        contact = COALESCE(NULLIF(contact, ''), ?)
                    WHERE user_id = ?
                ";
                $this->execute($updateUserSql, [$newRole, $clientName, $clientContact, $tenantUserId]);
            } else {
                $hashedPassword = password_hash($defaultPassword, PASSWORD_BCRYPT);
                $insertUserSql = "
                    INSERT INTO users_table (full_name, email, password, contact, user_role, resident_status, created_at)
                    VALUES (?, ?, ?, ?, 'tenant', 'Active', NOW())
                ";
                $this->execute($insertUserSql, [$clientName, $clientEmail, $hashedPassword, $clientContact]);
                $tenantUserId = (int)$this->lastInsertId();
            }

            $this->commit();

            return [
                'success' => true,
                'message' => 'Handover completed! Reservation is now Moved In, unit status is Occupied, and tenant account is active.',
                'tenant'  => [
                    'user_id'  => $tenantUserId,
                    'name'     => $clientName,
                    'email'    => $clientEmail,
                    'role'     => 'tenant',
                    'status'   => 'Active',
                    'password' => $defaultPassword,
                ]
            ];
        } catch (Throwable $e) {
            $this->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'tenant'  => null,
            ];
        }
    }

    /**
     * Get full details for a reservation record by ID with joined units, owners, signers, etc.
     */
    public function getDetailsById(int $reservationId): ?array {
        $sql = "
            SELECT
                r.*,
                u.unit_number,
                u.unit_type,
                u.sqm,
                u.floor_number,
                u.lease_rate,
                u.listing_type,
                u.stay_category,
                u.unit_current_status,
                u.unit_owner_id,

                owner.user_id AS owner_id,
                owner.full_name AS owner_name,
                owner.email AS owner_email,
                owner.contact AS owner_contact,
                owner.additional_contact AS owner_additional_contact,
                owner.additional_email AS owner_additional_email,

                client_user.user_id AS client_user_id,
                client_user.resident_status AS client_resident_status,
                client_user.contact AS client_user_contact,
                client_user.date_of_birth AS client_dob,

                updater.full_name AS requirements_updated_by_name,
                official_user.full_name AS officially_booked_by_name,
                cancelled_user.full_name AS cancelled_by_name,
                cancel_requester.full_name AS cancellation_requested_by_name,
                signer.full_name AS lease_signed_by_name,
                inq.lease_duration AS inq_lease_duration
            FROM reservation_table r
            LEFT JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
            LEFT JOIN users_table official_user ON r.officially_booked_by = official_user.user_id
            LEFT JOIN users_table cancelled_user ON r.cancelled_by = cancelled_user.user_id
            LEFT JOIN users_table cancel_requester ON r.cancellation_requested_by = cancel_requester.user_id
            LEFT JOIN users_table client_user ON r.client_email = client_user.email
            LEFT JOIN users_table signer ON r.lease_signed_by = signer.user_id
            LEFT JOIN inquiry_table inq ON r.inq_id = inq.inq_id
            WHERE r.reservation_id = ?
            LIMIT 1
        ";

        return $this->fetchOne($sql, [$reservationId]);
    }
}
