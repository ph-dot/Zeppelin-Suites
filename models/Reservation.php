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
                confirmer.full_name AS confirmed_signing_by_name,
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
            LEFT JOIN users_table confirmer ON r.confirmed_signing_by = confirmer.user_id
            LEFT JOIN inquiry_table inq ON r.inq_id = inq.inq_id
            WHERE r.reservation_id = ?
            LIMIT 1
        ";

        return $this->fetchOne($sql, [$reservationId]);
    }

    /**
     * Load all reservation form data and precomputations for a given reservation token.
     */
    public function getReservationFormData(string $token): array {
        if (trim($token) === '') {
            return ['status' => 'error', 'message' => 'Invalid reservation link.'];
        }

        // 1. Fetch inquiry by token
        $inqSql = "
            SELECT 
                i.inq_id,
                i.sender_name,
                i.sender_email,
                i.sender_contact,
                i.inquiry_type,
                i.lease_duration,
                i.approval_status,
                i.approved_unit_id,
                i.reservation_token_expires_at,
                i.preferred_move_in_time
            FROM inquiry_table i
            WHERE i.reservation_token = ?
            LIMIT 1
        ";
        $inquiry = $this->fetchOne($inqSql, [$token]);
        if (!$inquiry) {
            return ['status' => 'error', 'message' => 'Reservation link not found.'];
        }

        // 2. Check if already submitted
        $alreadySubmitted = $this->fetchOne(
            "SELECT reservation_id FROM reservation_table WHERE inq_id = ? LIMIT 1",
            [(int)$inquiry['inq_id']]
        );

        if ($alreadySubmitted) {
            return [
                'status' => 'already_submitted',
                'token'  => $token,
                'inq_id' => (int)$inquiry['inq_id']
            ];
        }

        if ($inquiry['approval_status'] !== 'approved') {
            return ['status' => 'error', 'message' => 'This inquiry is not approved for reservation.'];
        }

        if (!empty($inquiry['reservation_token_expires_at']) && strtotime((string)$inquiry['reservation_token_expires_at']) < time()) {
            return ['status' => 'error', 'message' => 'This reservation link has expired.'];
        }

        // 3. Fetch all approved units from owner_approval_requests
        $approvedUnitsSql = "
            SELECT 
                r.request_id,
                r.inq_id,
                r.unit_id,
                r.unit_owner_id,
                r.request_status,
                r.owner_remarks,
                u.unit_id,
                u.unit_type,
                u.unit_number,
                u.sqm,
                u.floor_number,
                u.listing_type,
                u.stay_category,
                u.lease_rate,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS reselling_price,
                u.unit_current_status,
                owner.full_name AS owner_name,
                owner.email AS owner_email,
                owner.contact AS owner_contact,
                owner.gcash_QR AS owner_gcash_qr
            FROM owner_approval_requests r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON r.unit_owner_id = owner.user_id
            WHERE r.inq_id = ? AND r.request_status = 'approved'
            ORDER BY u.unit_number ASC
        ";
        $rawUnits = $this->fetchAll($approvedUnitsSql, [(int)$inquiry['inq_id']]);

        // Fallback if no owner_approval_requests row exists but approved_unit_id is set
        if (empty($rawUnits) && !empty($inquiry['approved_unit_id'])) {
            $fallbackSql = "
                SELECT 
                    0 AS request_id,
                    ? AS inq_id,
                    u.unit_id,
                    u.unit_owner_id,
                    'approved' AS request_status,
                    '' AS owner_remarks,
                    u.unit_id,
                    u.unit_type,
                    u.unit_number,
                    u.sqm,
                    u.floor_number,
                    u.listing_type,
                    u.stay_category,
                    u.lease_rate,
                    COALESCE(u.resellling_price, u.reselling_price, NULL) AS reselling_price,
                    u.unit_current_status,
                    owner.full_name AS owner_name,
                    owner.email AS owner_email,
                    owner.contact AS owner_contact,
                    owner.gcash_QR AS owner_gcash_qr
                FROM units_table u
                LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
                WHERE u.unit_id = ?
                LIMIT 1
            ";
            $fb = $this->fetchOne($fallbackSql, [(int)$inquiry['inq_id'], (int)$inquiry['approved_unit_id']]);
            if ($fb) {
                $rawUnits = [$fb];
            }
        }

        if (empty($rawUnits)) {
            return ['status' => 'error', 'message' => 'No approved units found for this inquiry.'];
        }

        $inqTypeLower = strtolower(trim((string)$inquiry['inquiry_type']));
        $isLease = (
            $inqTypeLower === 'lease inquiry' ||
            $inqTypeLower === 'unit reservation' ||
            strpos($inqTypeLower, 'lease') !== false ||
            strpos($inqTypeLower, 'rental') !== false
        ) && strpos($inqTypeLower, 'resale') === false;

        $projectRoot = dirname(__DIR__);
        $processedUnits = [];

        foreach ($rawUnits as $u) {
            $uid = (int)$u['unit_id'];
            if ($isLease) {
                $priceBasis = (float)($u['lease_rate'] ?? 0);
                $priceLabel = "Monthly Lease Rate";
            } else {
                $priceBasis = (float)(!empty($u['reselling_price']) ? $u['reselling_price'] : ($u['lease_rate'] ?? 0));
                $priceLabel = "Selling Price";
            }

            // QR verification
            $ownerHasQr = false;
            $ownerQrPath = '';
            if (!empty($u['owner_gcash_qr'])) {
                $qrClean = ltrim((string)$u['owner_gcash_qr'], '/');
                if (file_exists($projectRoot . '/' . $qrClean) || file_exists($projectRoot . '/public/' . $qrClean)) {
                    $ownerHasQr = true;
                    $ownerQrPath = $qrClean;
                }
            }

            $processedUnits[$uid] = [
                'unit_id'                => $uid,
                'unit_number'            => (string)$u['unit_number'],
                'unit_type'              => (string)$u['unit_type'],
                'floor_number'           => (string)($u['floor_number'] ?? '1'),
                'sqm'                    => (string)($u['sqm'] ?? '37'),
                'furnishing'             => 'Fully Furnished.',
                'listing_type'           => (string)($u['listing_type'] ?? ($isLease ? 'For Lease' : 'Resale')),
                'stay_category'          => (string)($u['stay_category'] ?? 'Long term'),
                'unit_current_status'    => (string)($u['unit_current_status'] ?? 'Ready for Occupancy'),
                'lease_rate'             => (float)($u['lease_rate'] ?? 0),
                'reselling_price'        => !empty($u['reselling_price']) ? (float)$u['reselling_price'] : null,
                'owner_name'             => (string)($u['owner_name'] ?? 'Assigned Owner'),
                'owner_email'            => (string)($u['owner_email'] ?? ''),
                'owner_contact'          => (string)($u['owner_contact'] ?? '—'),
                'owner_has_qr'           => $ownerHasQr,
                'owner_qr_path'          => $ownerQrPath,
                'price_basis'            => $priceBasis,
                'price_basis_formatted'  => number_format($priceBasis, 2),
                'price_label'            => $priceLabel,
                'downpayment_35'         => number_format($priceBasis * 0.35, 2),
                'downpayment_35_raw'     => round($priceBasis * 0.35, 2),
                'downpayment_50'         => number_format($priceBasis * 0.50, 2),
                'downpayment_50_raw'     => round($priceBasis * 0.50, 2),
                'downpayment_75'         => number_format($priceBasis * 0.75, 2),
                'downpayment_75_raw'     => round($priceBasis * 0.75, 2),
                'dropdown_label'         => (string)$u['unit_number'] . ' (' . (string)$u['unit_type'] . ') - ₱' . number_format($priceBasis, 0) . ' (' . (string)($u['owner_name'] ?? 'Owner') . ')',
            ];
        }

        // Determine currently selected unit
        $selectedUnitId = !empty($inquiry['approved_unit_id']) && isset($processedUnits[(int)$inquiry['approved_unit_id']])
            ? (int)$inquiry['approved_unit_id']
            : (int)array_key_first($processedUnits);
        
        $selectedUnit = $processedUnits[$selectedUnitId];

        // Status metadata
        if ($isLease) {
            $transactionType = "Unit Leasing";
            $residentType = "New Tenant";
            $reservationType = "New Lease";
        } else {
            $transactionType = "Unit Resale";
            $residentType = "Buyer";
            $reservationType = "Unit Purchase";
        }

        $rawDuration = (string)($inquiry['lease_duration'] ?? '1 year');
        if (stripos($rawDuration, 'longer') !== false || stripos($rawDuration, '3 year') !== false) {
            $inquiry['lease_duration'] = '1 year';
            $leaseMonths = 12;
        } else {
            $leaseMonths = (int)preg_replace('/[^0-9]/', '', $rawDuration);
            if ($leaseMonths <= 0) {
                $leaseMonths = 12;
            } elseif (stripos($rawDuration, 'year') !== false) {
                $leaseMonths = $leaseMonths * 12;
            }
        }

        $tokenExpiresAt = !empty($inquiry['reservation_token_expires_at'])
            ? (string)$inquiry['reservation_token_expires_at']
            : date('Y-m-d H:i:s', strtotime('+30 days'));
        $maxSigningDate = date('Y-m-d', strtotime($tokenExpiresAt));

        // Combined data
        $data = array_merge($inquiry, $selectedUnit);

        // Blocked ranges for calendar
        $blockedRanges = [];
        if ($isLease) {
            $blockedTypeFilter = "(inquiry_type IN ('Lease Inquiry', 'Unit Reservation') OR inquiry_type LIKE '%Lease%' OR inquiry_type LIKE '%Rental%')";
        } else {
            $blockedTypeFilter = "(inquiry_type = 'Resale Inquiry' OR inquiry_type LIKE '%Resale%' OR inquiry_type LIKE '%Buy%' OR inquiry_type LIKE '%Purchase%')";
        }

        $blockedRows = $this->fetchAll("
            SELECT move_in_date, move_out_date
            FROM reservation_table
            WHERE unit_id = ?
              AND reservation_status NOT IN ('cancelled', 'rejected')
              AND move_in_date IS NOT NULL
              AND {$blockedTypeFilter}
        ", [(int)$selectedUnit['unit_id']]);

        foreach ($blockedRows as $row) {
            $blockedRanges[] = [
                'start' => $row['move_in_date'],
                'end'   => $row['move_out_date']
            ];
        }

        return [
            'status'           => 'ok',
            'data'             => $data,
            'client_name'      => (string)($inquiry['sender_name'] ?? ''),
            'client_email'     => (string)($inquiry['sender_email'] ?? ''),
            'client_contact'   => (string)($inquiry['sender_contact'] ?? ''),
            'approved_units'   => array_values($processedUnits),
            'owner_has_qr'     => $selectedUnit['owner_has_qr'],
            'owner_qr_path'    => $selectedUnit['owner_qr_path'],
            'is_lease'         => $isLease,
            'price_basis'      => $selectedUnit['price_basis'],
            'price_label'      => $selectedUnit['price_label'],
            'transaction_type' => $transactionType,
            'resident_type'    => $residentType,
            'reservation_type' => $reservationType,
            'lease_months'     => $leaseMonths,
            'max_signing_date' => $maxSigningDate,
            'blocked_ranges'   => $blockedRanges,
            'token'            => $token,
        ];
    }

    /**
     * Process public reservation form submission with file upload, validation, and database storage.
     */
    public function submitPublicReservation(array $post, array $files): array {
        $token = trim((string)($post['reservation_token'] ?? ''));
        $paymentPercentage = (float)($post['payment_percentage'] ?? 0);
        $paymentReference = trim((string)($post['payment_reference'] ?? ''));
        $declaredAmount = (float)($post['declared_amount'] ?? 0);
        $moveInDate = trim((string)($post['move_in_date'] ?? ''));
        $moveOutDate = trim((string)($post['move_out_date'] ?? ''));
        $leaseDuration = trim((string)($post['lease_duration'] ?? ''));
        if (stripos($leaseDuration, 'longer') !== false || stripos($leaseDuration, '3 year') !== false) {
            $leaseDuration = '1 year';
        }

        $paymentMethod = trim((string)($post['payment_method'] ?? 'GCash QR'));
        if (!in_array($paymentMethod, ['GCash QR', 'In-House'], true)) {
            $paymentMethod = 'GCash QR';
        }

        $clientSex = trim((string)($post['client_sex'] ?? ''));
        $clientAge = !empty($post['client_age']) ? (int)$post['client_age'] : null;
        $clientNationality = trim((string)($post['client_nationality'] ?? ''));

        $leaseSigningDate = !empty($post['lease_signing_date']) ? trim((string)$post['lease_signing_date']) : null;
        $isFlexibleSigning = !empty($post['is_flexible_signing']) && (string)$post['is_flexible_signing'] === '1' ? 1 : 0;
        if ($isFlexibleSigning) {
            $leaseSigningDate = null;
        }

        $clientRemarks = trim((string)($post['remarks'] ?? ''));
        if (mb_strlen($clientRemarks) > 500) {
            $clientRemarks = mb_substr($clientRemarks, 0, 500);
        }

        if ($token === '') {
            return ['success' => false, 'error' => 'Missing reservation token.'];
        }

        if (!in_array($paymentPercentage, [0.35, 0.50, 0.75], true)) {
            return ['success' => false, 'error' => 'Invalid payment percentage.'];
        }

        if ($paymentReference === '') {
            $paymentReference = 'N/A';
        }

        $formDetails = $this->getReservationFormData($token);
        if ($formDetails['status'] !== 'ok') {
            return ['success' => false, 'error' => $formDetails['message'] ?? 'Invalid reservation session.'];
        }

        $data = $formDetails['data'];
        $isLease = (bool)($formDetails['is_lease'] ?? true);
        $priceBasis = $formDetails['price_basis'];
        $transactionType = $formDetails['transaction_type'];
        $residentType = $formDetails['resident_type'];
        $reservationType = $formDetails['reservation_type'];

        if ($isLease && $moveInDate === '') {
            return ['success' => false, 'error' => 'Move-in date is required.'];
        }
        $minMoveIn = date('Y-m-d', strtotime('+3 days'));
        if ($isLease && $moveInDate < $minMoveIn) {
            return ['success' => false, 'error' => "Move-in date must be at least 3 days from today ({$minMoveIn}) for contract execution and building admin clearance."];
        }
        if ($isLease && empty($post['is_flexible_signing']) && !empty($post['lease_signing_date']) && $moveInDate) {
            $rawDates = array_map('trim', explode(',', (string)$post['lease_signing_date']));
            foreach ($rawDates as $sDate) {
                if ($sDate !== '' && $sDate > $moveInDate) {
                    return ['success' => false, 'error' => "Lease signing date ({$sDate}) cannot be scheduled after your move-in date ({$moveInDate})."];
                }
            }
        }
        if (!$isLease) {
            $moveInDate = $moveInDate !== '' ? $moveInDate : null;
            $moveOutDate = $moveOutDate !== '' ? $moveOutDate : null;

            $minResaleSigning = date('Y-m-d', strtotime('+3 days'));
            if (empty($post['is_flexible_signing']) && !empty($post['lease_signing_date'])) {
                $rawDates = array_map('trim', explode(',', (string)$post['lease_signing_date']));
                foreach ($rawDates as $sDate) {
                    if ($sDate !== '' && $sDate < $minResaleSigning) {
                        return ['success' => false, 'error' => "Contract signing date ({$sDate}) must be at least 3 days from today ({$minResaleSigning}) to allow for document preparation."];
                    }
                }
            }
        }

        // If client dynamically selected a specific approved unit from the dropdown:
        $selectedUnitId = (int)($post['selected_unit_id'] ?? $post['unit_id'] ?? 0);
        if ($selectedUnitId > 0 && !empty($formDetails['approved_units'])) {
            foreach ($formDetails['approved_units'] as $au) {
                if ((int)$au['unit_id'] === $selectedUnitId) {
                    $data = array_merge($data, $au);
                    $priceBasis = (float)$au['price_basis'];
                    break;
                }
            }
        }

        if (!$isLease) {
            $moveOutDate = null;
        }

        $requiredAmount = $priceBasis * $paymentPercentage;
        if ($declaredAmount <= 0) {
            $declaredAmount = $requiredAmount;
        }
        $amountMatchStatus = 'match';
        if (abs($declaredAmount - $requiredAmount) > 0.01) {
            $amountMatchStatus = $declaredAmount < $requiredAmount ? 'short' : 'over';
        }

        // Upload payment proof if GCash QR
        $dbFilePath = null;
        $uploadedFsPath = null;
        if ($paymentMethod === 'GCash QR') {
            if (!isset($files['payment_proof']) || $files['payment_proof']['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => 'Proof of payment upload is required for GCash QR payments.'];
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $fileName = $files['payment_proof']['name'];
            $fileTmp  = $files['payment_proof']['tmp_name'];
            $fileSize = $files['payment_proof']['size'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExtensions, true)) {
                return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and WEBP files are accepted.'];
            }

            if ($fileSize > 10 * 1024 * 1024) {
                return ['success' => false, 'error' => 'File too large. Maximum size is 10MB.'];
            }

            $uploadDir = dirname(__DIR__) . '/images/payment_proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'payment_' . $data['inq_id'] . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $fileExt;
            $uploadedFsPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($fileTmp, $uploadedFsPath)) {
                return ['success' => false, 'error' => 'Failed to upload payment proof.'];
            }

            $dbFilePath = 'images/payment_proofs/' . $newFileName;
        } else {
            $dbFilePath = 'Pay In-House (During Lease Signing)';
            $paymentReference = 'In-House';
        }

        try {
            $this->db->beginTransaction();

            // Lock unit
            $stmt = $this->db->prepare("SELECT unit_current_status FROM units_table WHERE unit_id = ? FOR UPDATE");
            $stmt->execute([(int)$data['unit_id']]);
            $lockedUnit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lockedUnit || $lockedUnit['unit_current_status'] === 'Under maintenance') {
                throw new RuntimeException("This unit is currently unavailable.");
            }

            if ($isLease) {
                // Overlap check
                $overlapStmt = $this->db->prepare("
                    SELECT reservation_id
                    FROM reservation_table
                    WHERE unit_id = ?
                      AND reservation_status NOT IN ('cancelled', 'rejected')
                      AND (inquiry_type IN ('Lease Inquiry', 'Unit Reservation') OR inquiry_type LIKE '%Lease%' OR inquiry_type LIKE '%Rental%')
                      AND move_in_date IS NOT NULL
                      AND move_in_date <= ?
                      AND COALESCE(move_out_date, move_in_date) >= ?
                    FOR UPDATE
                ");
                $overlapStmt->execute([(int)$data['unit_id'], $moveOutDate, $moveInDate]);
                if ($overlapStmt->fetch()) {
                    throw new RuntimeException("Those move-in/move-out dates overlap with an existing reservation on this unit.");
                }
            } else {
                if (!in_array($lockedUnit['unit_current_status'], ['Ready for Occupancy', 'Resale'], true)) {
                    throw new RuntimeException("This unit is no longer available.");
                }
            }

            // Check duplicate reservation
            $checkStmt = $this->db->prepare("SELECT reservation_id FROM reservation_table WHERE inq_id = ? LIMIT 1");
            $checkStmt->execute([(int)$data['inq_id']]);
            if ($checkStmt->fetch()) {
                throw new RuntimeException("Reservation already submitted.");
            }

            // Check GCash reference
            if ($paymentReference !== '' && $paymentReference !== 'N/A' && $paymentReference !== 'In-House') {
                $dupRefStmt = $this->db->prepare("
                    SELECT reservation_id
                    FROM reservation_table
                    WHERE payment_reference = ?
                      AND reservation_status NOT IN ('cancelled', 'rejected')
                    LIMIT 1
                ");
                $dupRefStmt->execute([$paymentReference]);
                if ($dupRefStmt->fetch()) {
                    throw new RuntimeException("This GCash reference number has already been used for another reservation.");
                }
            }

            // Insert into reservation_table
            $insertSql = "
                INSERT INTO reservation_table (
                    inq_id, unit_id, client_name, client_email, client_contact,
                    client_sex, client_age, client_nationality,
                    inquiry_type, resident_type, transaction_type, reservation_type,
                    move_in_date, move_out_date, lease_signing_date, is_flexible_signing,
                    price_basis, payment_percentage, required_amount,
                    payment_method, payment_reference, declared_amount, amount_match_status,
                    payment_proof, payment_status, reservation_status, client_remarks
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 'pending review', 'submitted', ?
                )
            ";

            $stmt = $this->db->prepare($insertSql);
            $stmt->execute([
                (int)$data['inq_id'],
                (int)$data['unit_id'],
                $data['sender_name'],
                $data['sender_email'],
                $data['sender_contact'],
                $clientSex,
                $clientAge,
                $clientNationality,
                $data['inquiry_type'],
                $residentType,
                $transactionType,
                $reservationType,
                $moveInDate,
                $moveOutDate,
                $leaseSigningDate,
                $isFlexibleSigning,
                $priceBasis,
                $paymentPercentage,
                $requiredAmount,
                $paymentMethod,
                $paymentReference,
                $declaredAmount,
                $amountMatchStatus,
                $dbFilePath,
                $clientRemarks
            ]);

            $reservationId = (int)$this->db->lastInsertId();

            // Update inquiry approved unit to the chosen unit
            $stmt = $this->db->prepare("UPDATE inquiry_table SET approved_unit_id = ? WHERE inq_id = ?");
            $stmt->execute([(int)$data['unit_id'], (int)$data['inq_id']]);

            // Update lease duration if provided
            if ($isLease && $leaseDuration !== '') {
                $stmt = $this->db->prepare("UPDATE inquiry_table SET lease_duration = ? WHERE inq_id = ?");
                $stmt->execute([$leaseDuration, (int)$data['inq_id']]);
            }

            // Update unit status to 'On Hold' for resale
            if (!$isLease) {
                $stmt = $this->db->prepare("UPDATE units_table SET unit_current_status = 'On Hold' WHERE unit_id = ?");
                $stmt->execute([(int)$data['unit_id']]);
            }

            // Update inquiry status
            $stmt = $this->db->prepare("UPDATE inquiry_table SET status = 'reservation submitted' WHERE inq_id = ?");
            $stmt->execute([(int)$data['inq_id']]);

            $this->db->commit();

            // Notify owner
            $ownerNotificationsFile = dirname(__DIR__) . '/config/owner_notifications.php';
            if (file_exists($ownerNotificationsFile)) {
                require_once $ownerNotificationsFile;
                if (function_exists('notifyOwnerOfNewReservation')) {
                    $appointmentDate = !empty($moveInDate) ? (string)$moveInDate : (!empty($leaseSigningDate) ? (string)$leaseSigningDate : null);
                    notifyOwnerOfNewReservation(
                        (string)($data['owner_email'] ?? ''),
                        (string)($data['owner_name'] ?? 'Unit Owner'),
                        (string)($data['unit_number'] ?? ''),
                        (string)($data['sender_name'] ?? 'A client'),
                        $appointmentDate
                    );
                }
            }

            return ['success' => true, 'token' => $token, 'reservation_id' => $reservationId];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($uploadedFsPath && file_exists($uploadedFsPath)) {
                @unlink($uploadedFsPath);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Retrieve a reservation eligible for client cancellation using token.
     */
    public function getReservationForClientCancellation(string $token): array {
        if (trim($token) === '') {
            return ['status' => 'error', 'message' => 'Invalid cancellation link.'];
        }

        $sql = "
            SELECT 
                r.reservation_id,
                r.client_name,
                r.client_email,
                r.reservation_status,
                r.payment_status,
                r.cancellation_status,
                r.client_cancel_token_expires_at,
                u.unit_type,
                u.unit_number
            FROM reservation_table r
            LEFT JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.client_cancel_token = ?
            LIMIT 1
        ";

        $res = $this->fetchOne($sql, [$token]);
        if (!$res) {
            return ['status' => 'error', 'message' => 'Invalid or expired cancellation link.'];
        }

        if (!empty($res['client_cancel_token_expires_at']) && strtotime((string)$res['client_cancel_token_expires_at']) < time()) {
            return ['status' => 'error', 'message' => 'This cancellation link has expired.'];
        }

        if ($res['payment_status'] !== 'verified') {
            return ['status' => 'error', 'message' => 'Cancellation request is only available after payment verification.'];
        }

        if (in_array(strtolower((string)$res['reservation_status']), ['cancelled', 'rejected', 'reserved'], true)) {
            return ['status' => 'error', 'message' => 'Cancellation request is no longer available for this reservation.'];
        }

        if ($res['cancellation_status'] === 'requested') {
            return ['status' => 'error', 'message' => 'A cancellation request has already been submitted for this reservation.'];
        }

        return ['status' => 'ok', 'reservation' => $res, 'token' => $token];
    }

    /**
     * Submit client cancellation request.
     */
    public function submitClientCancellationRequest(string $token, string $reason): array {
        $token = trim($token);
        $reason = trim($reason);

        if ($token === '' || $reason === '') {
            return ['success' => false, 'error' => 'Token and cancellation reason are required.'];
        }

        try {
            $this->db->beginTransaction();

            $sql = "
                SELECT 
                    reservation_id,
                    payment_status,
                    reservation_status,
                    cancellation_status,
                    client_cancel_token_expires_at
                FROM reservation_table
                WHERE client_cancel_token = ?
                LIMIT 1
                FOR UPDATE
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$res) {
                throw new RuntimeException("Invalid cancellation token.");
            }

            if (!empty($res['client_cancel_token_expires_at']) && strtotime((string)$res['client_cancel_token_expires_at']) < time()) {
                throw new RuntimeException("This cancellation link has expired.");
            }

            if ($res['payment_status'] !== 'verified') {
                throw new RuntimeException("Cancellation request is only available after payment verification.");
            }

            if (in_array(strtolower((string)$res['reservation_status']), ['cancelled', 'rejected', 'reserved'], true)) {
                throw new RuntimeException("Cancellation request is no longer available for this reservation.");
            }

            if ($res['cancellation_status'] === 'requested') {
                throw new RuntimeException("A cancellation request has already been submitted.");
            }

            if ($res['cancellation_status'] === 'approved') {
                throw new RuntimeException("This cancellation request was already approved.");
            }

            $updateSql = "
                UPDATE reservation_table
                SET cancellation_status = 'requested',
                    cancellation_reason = ?,
                    cancellation_requested_by = NULL,
                    cancellation_requested_by_role = 'client',
                    cancellation_requested_at = NOW(),
                    client_cancel_token = NULL,
                    client_cancel_token_expires_at = NULL
                WHERE reservation_id = ?
            ";

            $stmt = $this->db->prepare($updateSql);
            $stmt->execute([$reason, (int)$res['reservation_id']]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Confirm / set the agreed lease signing appointment date.
     */
    public function confirmSigningDate(int $reservationId, string $date, int $userId, string $role): array {
        $res = $this->findById($reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation record not found.'];
        }

        $cleanDate = trim($date);
        $ts = strtotime($cleanDate);
        if (!$ts || date('Y-m-d', $ts) !== $cleanDate) {
            return ['success' => false, 'message' => 'Please provide a valid date in YYYY-MM-DD format.'];
        }

        if (!empty($res['move_in_date']) && $res['move_in_date'] !== '0000-00-00') {
            if ($cleanDate > $res['move_in_date']) {
                return ['success' => false, 'message' => "Lease signing date cannot be scheduled after the Move-in Date ({$res['move_in_date']})."];
            }
        }

        $sql = "
            UPDATE reservation_table
            SET confirmed_signing_date = ?,
                confirmed_signing_by = ?,
                confirmed_signing_at = NOW()
            WHERE reservation_id = ?
        ";
        $success = $this->execute($sql, [$cleanDate, $userId, $reservationId]);
        if (!$success) {
            return ['success' => false, 'message' => 'Database error while saving confirmed signing date.'];
        }

        return [
            'success'        => true,
            'message'        => 'Lease signing date successfully confirmed.',
            'confirmed_date' => $cleanDate,
            'formatted_date' => date('l, F j, Y', $ts),
        ];
    }

    /**
     * Mark lease signing as completed or reset status to pending.
     */
    public function updateLeaseSigningStatus(int $reservationId, string $action, string $remarks, int $userId, string $role): array {
        $res = $this->findById($reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation record not found.'];
        }

        $now = date('Y-m-d H:i:s');
        if ($action === 'complete') {
            $status = 'Completed';
            $sql = "
                UPDATE reservation_table
                SET lease_signing_status = ?,
                    lease_signed_at = ?,
                    lease_signed_by = ?,
                    lease_signing_remarks = ?
                WHERE reservation_id = ?
            ";
            $params = [$status, $now, $userId, $remarks, $reservationId];
        } else {
            $status = 'Pending Signing';
            $sql = "
                UPDATE reservation_table
                SET lease_signing_status = ?,
                    lease_signed_at = NULL,
                    lease_signed_by = NULL,
                    lease_signing_remarks = ?
                WHERE reservation_id = ?
            ";
            $params = [$status, $remarks, $reservationId];
        }

        $success = $this->execute($sql, $params);
        if (!$success) {
            return ['success' => false, 'message' => 'Failed to update lease signing status in database.'];
        }

        return [
            'success'   => true,
            'message'   => $action === 'complete' ? 'Lease signing marked as completed successfully.' : 'Lease signing status reset to pending.',
            'status'    => $status,
            'signed_at' => $now,
        ];
    }
}

