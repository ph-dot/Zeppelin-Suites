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

    /**
     * Load all reservation form data and precomputations for a given reservation token.
     */
    public function getReservationFormData(string $token): array {
        if (trim($token) === '') {
            return ['status' => 'error', 'message' => 'Invalid reservation link.'];
        }

        $sql = "
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
                i.preferred_move_in_time,

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
            FROM inquiry_table i
            INNER JOIN units_table u ON i.approved_unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            WHERE i.reservation_token = ?
            LIMIT 1
        ";

        $data = $this->fetchOne($sql, [$token]);
        if (!$data) {
            return ['status' => 'error', 'message' => 'Reservation link not found.'];
        }

        // Check if unit owner has a valid uploaded GCash QR code
        $ownerHasQr = false;
        $ownerQrPath = '';
        if (!empty($data['owner_gcash_qr'])) {
            $qrClean = ltrim((string)$data['owner_gcash_qr'], '/');
            $projectRoot = dirname(__DIR__);
            $qrFullPath = $projectRoot . '/' . $qrClean;
            if (file_exists($qrFullPath)) {
                $ownerHasQr = true;
                $ownerQrPath = $qrClean;
            } elseif (file_exists($projectRoot . '/public/' . $qrClean)) {
                $ownerHasQr = true;
                $ownerQrPath = $qrClean;
            }
        }

        // Check if already submitted
        $alreadySubmitted = $this->fetchOne(
            "SELECT reservation_id FROM reservation_table WHERE inq_id = ? LIMIT 1",
            [(int)$data['inq_id']]
        );

        if ($alreadySubmitted) {
            return [
                'status' => 'already_submitted',
                'token'  => $token,
                'inq_id' => (int)$data['inq_id']
            ];
        }

        if ($data['approval_status'] !== 'approved') {
            return ['status' => 'error', 'message' => 'This inquiry is not approved for reservation.'];
        }

        if (!empty($data['reservation_token_expires_at']) && strtotime((string)$data['reservation_token_expires_at']) < time()) {
            return ['status' => 'error', 'message' => 'This reservation link has expired.'];
        }

        $inqTypeLower = strtolower(trim((string)$data['inquiry_type']));
        $isLease = (
            $inqTypeLower === 'lease inquiry' ||
            $inqTypeLower === 'unit reservation' ||
            strpos($inqTypeLower, 'lease') !== false ||
            strpos($inqTypeLower, 'rental') !== false
        );

        if ($data['unit_current_status'] === 'Under maintenance') {
            return ['status' => 'error', 'message' => 'This unit is currently unavailable (under maintenance).'];
        }

        if (!$isLease && !in_array($data['unit_current_status'], ['Ready for Occupancy', 'Resale'], true)) {
            return ['status' => 'error', 'message' => 'This unit is no longer available for reservation.'];
        }

        if ($isLease) {
            $priceBasis = (float)($data['lease_rate'] ?? 0);
            $priceLabel = "Monthly Lease Rate";
            $transactionType = "Unit Leasing";
            $residentType = "New Tenant";
            $reservationType = "New Lease";
        } elseif (
            $inqTypeLower === 'resale inquiry' ||
            strpos($inqTypeLower, 'resale') !== false ||
            strpos($inqTypeLower, 'buy') !== false ||
            strpos($inqTypeLower, 'purchase') !== false
        ) {
            $priceBasis = (float)($data['reselling_price'] ?? $data['lease_rate'] ?? 0);
            $priceLabel = "Selling Price";
            $transactionType = "Unit Resale";
            $residentType = "Buyer";
            $reservationType = "Unit Purchase";
        } else {
            return ['status' => 'error', 'message' => 'Reservation form is only available for Lease or Resale inquiries.'];
        }

        $leaseMonths = (int)preg_replace('/[^0-9]/', '', (string)($data['lease_duration'] ?? '12'));
        if ($leaseMonths <= 0) {
            $leaseMonths = 12;
        }

        $data['furnishing'] = !empty($data['furnishing']) ? $data['furnishing'] : 'Fully Furnished.';
        $tokenExpiresAt = !empty($data['reservation_token_expires_at'])
            ? (string)$data['reservation_token_expires_at']
            : date('Y-m-d H:i:s', strtotime('+30 days'));
        $maxSigningDate = date('Y-m-d', strtotime($tokenExpiresAt));

        // Blocked ranges
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
        ", [(int)$data['unit_id']]);

        foreach ($blockedRows as $row) {
            $blockedRanges[] = [
                'start' => $row['move_in_date'],
                'end'   => $row['move_out_date'] ?: $row['move_in_date'],
            ];
        }

        return [
            'status'           => 'ok',
            'data'             => $data,
            'owner_has_qr'     => $ownerHasQr,
            'owner_qr_path'    => $ownerQrPath,
            'is_lease'         => $isLease,
            'price_basis'      => $priceBasis,
            'price_label'      => $priceLabel,
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

        if ($moveInDate === '') {
            return ['success' => false, 'error' => 'Move-in date / appointment date is required.'];
        }

        $formDetails = $this->getReservationFormData($token);
        if ($formDetails['status'] !== 'ok') {
            return ['success' => false, 'error' => $formDetails['message'] ?? 'Invalid reservation session.'];
        }

        $data = $formDetails['data'];
        $isLease = $formDetails['is_lease'];
        $priceBasis = $formDetails['price_basis'];
        $transactionType = $formDetails['transaction_type'];
        $residentType = $formDetails['resident_type'];
        $reservationType = $formDetails['reservation_type'];

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
                    notifyOwnerOfNewReservation(
                        (string)($data['owner_email'] ?? ''),
                        (string)($data['owner_name'] ?? 'Unit Owner'),
                        (string)($data['unit_number'] ?? ''),
                        (string)($data['sender_name'] ?? 'A tenant'),
                        $moveInDate
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
}

