<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Unit Owner Model
 * Handles database operations for unit owner portal: overview, units, leases, tenants, maintenance, and calendar.
 * Pure MVC: strictly PDO prepared statements. Zero direct HTML or output.
 */
class UnitOwner extends Model {

    /**
     * Retrieve overview counts, recent tenants, maintenance, and approval requests for this owner.
     */
    public function getOverviewData(int $ownerId): array {
        // Counts
        $countSql = "
            SELECT 
                COUNT(*) AS total_owned,
                SUM(CASE WHEN unit_current_status = 'Occupied' THEN 1 ELSE 0 END) AS total_occupied,
                SUM(CASE WHEN unit_current_status = 'Ready for Occupancy' THEN 1 ELSE 0 END) AS total_available,
                SUM(CASE WHEN unit_current_status = 'Reserved' THEN 1 ELSE 0 END) AS total_reserved
            FROM units_table 
            WHERE unit_owner_id = ?
        ";
        $counts = $this->fetchOne($countSql, [$ownerId]) ?: [];

        // Recent tenants
        $tenantsSql = "
            SELECT r.client_name, r.client_contact, r.move_in_date, u.unit_number
            FROM reservation_table r
            INNER JOIN units_table u ON u.unit_id = r.unit_id
            WHERE u.unit_owner_id = ? AND r.officially_booked_at IS NOT NULL
            ORDER BY r.officially_booked_at DESC
            LIMIT 5
        ";
        $recentTenants = $this->fetchAll($tenantsSql, [$ownerId]);

        // Maintenance requests
        $maintSql = "
            SELECT m.maintenance_id, m.status, u.unit_number
            FROM maintenance_requests m
            LEFT JOIN units_table u ON u.unit_id = m.unit_id
            WHERE m.unit_owner_id = ?
            ORDER BY m.submitted_at DESC
            LIMIT 5
        ";
        $maintenanceRequests = $this->fetchAll($maintSql, [$ownerId]);

        // Pending approval requests
        $resReqSql = "
            SELECT oar.request_id, i.sender_name, un.unit_number
            FROM owner_approval_requests oar
            LEFT JOIN inquiry_table i ON i.inq_id = oar.inq_id
            LEFT JOIN units_table un ON un.unit_id = oar.unit_id
            WHERE oar.unit_owner_id = ? AND oar.request_status = 'pending'
            ORDER BY oar.requested_at DESC
            LIMIT 5
        ";
        $reservationRequests = $this->fetchAll($resReqSql, [$ownerId]);

        return [
            'ownedUnits'          => (int)($counts['total_owned'] ?? 0),
            'occupiedUnits'       => (int)($counts['total_occupied'] ?? 0),
            'availableUnits'      => (int)($counts['total_available'] ?? 0),
            'reservedUnits'       => (int)($counts['total_reserved'] ?? 0),
            'recentTenants'       => $recentTenants,
            'maintenanceRequests' => $maintenanceRequests,
            'reservationRequests' => $reservationRequests,
        ];
    }

    /**
     * Retrieve all units owned by this owner with current tenant details.
     */
    public function getOwnerUnits(int $ownerId): array {
        $sql = "
            SELECT 
                u.unit_id,
                u.unit_number,
                u.unit_type,
                u.sqm,
                u.floor_number,
                u.lease_rate,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS reselling_price,
                u.listing_type,
                u.stay_category,
                u.unit_current_status,
                r.client_name AS tenant_name,
                r.client_contact AS tenant_contact,
                r.client_email AS tenant_email,
                r.move_in_date,
                r.move_out_date,
                r.reservation_status
            FROM units_table u
            LEFT JOIN reservation_table r ON r.unit_id = u.unit_id 
                AND LOWER(r.reservation_status) IN ('handover', 'moved in', 'reserved', 'active')
            WHERE u.unit_owner_id = ?
            ORDER BY u.floor_number ASC, u.unit_number ASC
        ";
        return $this->fetchAll($sql, [$ownerId]);
    }

    /**
     * Retrieve single unit details verified for this owner.
     */
    public function getUnitDetails(int $ownerId, int $unitId): ?array {
        $sql = "
            SELECT u.*, owner.full_name AS owner_name, owner.email AS owner_email, owner.contact AS owner_contact
            FROM units_table u
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            WHERE u.unit_id = ? AND u.unit_owner_id = ?
            LIMIT 1
        ";
        $unit = $this->fetchOne($sql, [$unitId, $ownerId]);
        if (!$unit) {
            return null;
        }

        // Active tenant
        $tenantSql = "
            SELECT * FROM reservation_table 
            WHERE unit_id = ? AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')
              AND move_in_date <= CURDATE() AND move_out_date >= CURDATE()
            ORDER BY move_in_date DESC LIMIT 1
        ";
        $activeTenant = $this->fetchOne($tenantSql, [$unitId]);

        // All leases (current, upcoming, past)
        $leasesSql = "
            SELECT * FROM reservation_table
            WHERE unit_id = ?
            ORDER BY 
                CASE 
                    WHEN (move_in_date <= CURDATE() AND move_out_date >= CURDATE() AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')) THEN 1
                    WHEN (move_in_date > CURDATE() AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')) THEN 2
                    WHEN (move_out_date < CURDATE() AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')) THEN 3
                    ELSE 4
                END ASC,
                move_in_date DESC,
                reservation_id DESC
        ";
        $leasesList = $this->fetchAll($leasesSql, [$unitId]);

        // Latest move out for availability
        $availSql = "
            SELECT MAX(move_out_date) AS latest_move_out
            FROM reservation_table
            WHERE unit_id = ? AND LOWER(reservation_status) NOT IN ('cancelled', 'rejected')
              AND move_out_date >= CURDATE()
        ";
        $availRow = $this->fetchOne($availSql, [$unitId]);

        return [
            'unit'          => $unit,
            'activeTenant'  => $activeTenant,
            'pastTenants'   => $leasesList,
            'leasesList'    => $leasesList,
            'latestMoveOut' => $availRow['latest_move_out'] ?? null,
        ];
    }

    /**
     * Retrieve inquiries / approval requests for units owned by this owner.
     */
    public function getOwnerInquiries(int $ownerId): array {
        $sql = "
            SELECT 
                r.request_id,
                r.inq_id,
                r.request_status,
                r.owner_remarks,
                r.requested_at,
                i.sender_name,
                i.sender_email,
                i.sender_contact,
                i.inquiry_type,
                i.preferred_move_in_time,
                i.lease_duration,
                i.message,
                i.status AS inquiry_status,
                i.approval_status,
                u.unit_id,
                u.unit_number,
                u.floor_number,
                u.unit_type,
                u.lease_rate,
                u.sqm,
                u.listing_type,
                COALESCE(u.resellling_price, u.reselling_price, NULL) AS reselling_price,
                u.unit_current_status,
                (SELECT res.move_in_date 
                 FROM reservation_table res 
                 WHERE res.unit_id = u.unit_id 
                   AND LOWER(res.reservation_status) NOT IN ('cancelled', 'rejected') 
                   AND res.move_out_date >= CURDATE()
                 ORDER BY res.move_out_date DESC LIMIT 1) AS active_move_in,
                (SELECT res.move_out_date 
                 FROM reservation_table res 
                 WHERE res.unit_id = u.unit_id 
                   AND LOWER(res.reservation_status) NOT IN ('cancelled', 'rejected') 
                   AND res.move_out_date >= CURDATE()
                 ORDER BY res.move_out_date DESC LIMIT 1) AS active_move_out
            FROM owner_approval_requests r
            INNER JOIN inquiry_table i ON r.inq_id = i.inq_id
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE r.unit_owner_id = ?
            ORDER BY r.requested_at DESC
        ";
        return $this->fetchAll($sql, [$ownerId]);
    }

    /**
     * Retrieve all leases and reservations for units owned by this owner.
     */
    public function getOwnerReservations(int $ownerId): array {
        $sql = "
            SELECT 
                r.*,
                u.unit_number,
                u.unit_type,
                u.floor_number,
                u.unit_current_status,
                (SELECT COUNT(*) FROM reservation_documents d WHERE d.reservation_id = r.reservation_id) AS total_docs,
                (SELECT COUNT(*) FROM reservation_documents d WHERE d.reservation_id = r.reservation_id AND d.status = 'complete') AS completed_docs
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE u.unit_owner_id = ?
            ORDER BY r.created_at DESC
        ";
        return $this->fetchAll($sql, [$ownerId]);
    }

    /**
     * Retrieve single reservation details for this owner's unit.
     */
    public function getReservationDetails(int $ownerId, int $reservationId): ?array {
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
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            LEFT JOIN users_table updater ON r.requirements_updated_by = updater.user_id
            LEFT JOIN users_table official_user ON r.officially_booked_by = official_user.user_id
            LEFT JOIN users_table cancelled_user ON r.cancelled_by = cancelled_user.user_id
            LEFT JOIN users_table cancel_requester ON r.cancellation_requested_by = cancel_requester.user_id
            LEFT JOIN users_table client_user ON r.client_email = client_user.email
            LEFT JOIN users_table signer ON r.lease_signed_by = signer.user_id
            LEFT JOIN users_table confirmer ON r.confirmed_signing_by = confirmer.user_id
            LEFT JOIN inquiry_table inq ON r.inq_id = inq.inq_id
            WHERE r.reservation_id = ? AND u.unit_owner_id = ?
            LIMIT 1
        ";
        return $this->fetchOne($sql, [$reservationId, $ownerId]);
    }

    /**
     * Retrieve tenants across all units owned by this owner.
     */
    public function getOwnerTenants(int $ownerId): array {
        $sql = "
            SELECT DISTINCT
                r.client_name,
                r.client_email,
                r.client_contact,
                r.move_in_date,
                r.move_out_date,
                r.reservation_status,
                u.unit_id,
                u.unit_number,
                u.unit_type
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE u.unit_owner_id = ? 
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
            ORDER BY r.move_in_date DESC
        ";
        return $this->fetchAll($sql, [$ownerId]);
    }

    /**
     * Retrieve maintenance requests and filter metadata for this owner's assigned units.
     */
    public function getOwnerMaintenance(int $ownerId): array {
        // 1. Distinct unit types
        $unitTypesSql = "
            SELECT DISTINCT unit_type 
            FROM units_table 
            WHERE unit_owner_id = ? 
              AND unit_type IS NOT NULL 
              AND TRIM(unit_type) != '' 
            ORDER BY unit_type ASC
        ";
        $utRows = $this->fetchAll($unitTypesSql, [$ownerId]);
        $unitTypeOptions = [];
        foreach ($utRows as $ut) {
            $cleanType = trim((string)$ut['unit_type']);
            if ($cleanType !== '') {
                $unitTypeOptions[] = $cleanType;
            }
        }

        // 2. Units owned by this owner
        $ownerUnitsSql = "
            SELECT unit_id, unit_number, unit_type, floor_number 
            FROM units_table 
            WHERE unit_owner_id = ? 
            ORDER BY unit_number ASC
        ";
        $ownerUnitsList = $this->fetchAll($ownerUnitsSql, [$ownerId]);

        // 3. Tickets
        $ticketsSql = "
            SELECT 
                m.maintenance_id,
                m.unit_id,
                m.unit_owner_id,
                m.submitted_by_user_id,
                m.submitted_by_role,
                m.subject,
                m.category,
                m.description,
                m.priority,
                m.status,
                m.photo_paths,
                m.admin_remarks,
                m.submitted_at,
                m.updated_at,
                m.resolved_at,
                u.unit_number,
                u.unit_type,
                u.floor_number,
                owner.full_name AS owner_name,
                owner.email AS owner_email,
                (SELECT r.client_name 
                 FROM reservation_table r 
                 WHERE r.unit_id = u.unit_id 
                   AND (r.reservation_status = 'reserved' OR r.officially_booked_at IS NOT NULL) 
                 ORDER BY r.reservation_id DESC LIMIT 1) AS tenant_name
            FROM maintenance_requests m
            INNER JOIN units_table u ON m.unit_id = u.unit_id
            LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
            WHERE m.unit_owner_id = ? OR m.submitted_by_user_id = ?
            ORDER BY m.submitted_at DESC
        ";
        $tickets = $this->fetchAll($ticketsSql, [$ownerId, $ownerId]);

        $activeTickets = [];
        $unassignedTickets = [];
        $closedTickets = [];

        foreach ($tickets as $row) {
            $st = strtolower(trim((string)($row['status'] ?? 'pending')));
            if ($st === 'in progress') {
                $activeTickets[] = $row;
            } elseif ($st === 'pending') {
                $unassignedTickets[] = $row;
            } else {
                $closedTickets[] = $row;
            }
        }

        return [
            'unitTypeOptions'   => $unitTypeOptions,
            'ownerUnitsList'    => $ownerUnitsList,
            'tickets'           => $tickets,
            'activeTickets'     => $activeTickets,
            'unassignedTickets' => $unassignedTickets,
            'closedTickets'     => $closedTickets,
            'totalTicketsCount' => count($tickets),
            'activeCount'       => count($activeTickets),
            'unassignedCount'   => count($unassignedTickets),
            'closedCount'       => count($closedTickets),
        ];
    }

    /**
     * Retrieve timeline calendar data filtered for this owner's units.
     */
    public function getCalendarData(int $ownerId): array {
        // Units
        $unitsSql = "
            SELECT unit_id, unit_type, unit_number, unit_current_status
            FROM units_table
            WHERE unit_owner_id = ?
            ORDER BY unit_type, unit_number
        ";
        $units = $this->fetchAll($unitsSql, [$ownerId]);

        $grouped = [];
        foreach ($units as $u) {
            $type = (string)$u['unit_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = [
                'room'        => (string)$u['unit_number'],
                'unitId'      => (int)$u['unit_id'],
                'maintenance' => trim((string)$u['unit_current_status']) === 'Under maintenance',
            ];
        }

        $unitTypes = [];
        foreach ($grouped as $type => $rooms) {
            $unitTypes[] = ['key' => $type, 'rooms' => $rooms];
        }

        // Bookings
        $bookingsSql = "
            SELECT 
                r.reservation_id, r.client_name, r.client_email, r.client_contact,
                r.move_in_date, r.move_out_date, r.reservation_status,
                u.unit_id, u.unit_type, u.unit_number
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            WHERE u.unit_owner_id = ?
              AND LOWER(r.reservation_status) NOT IN ('rejected', 'cancelled')
              AND r.move_in_date IS NOT NULL
              AND r.move_out_date IS NOT NULL
            ORDER BY r.move_in_date
        ";
        $bookingsRows = $this->fetchAll($bookingsSql, [$ownerId]);

        $today = date('Y-m-d');
        $bookings = [];
        foreach ($bookingsRows as $row) {
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

        // Blocked dates
        $blockedSql = "
            SELECT 
                b.block_id, b.unit_id, b.start_date, b.end_date, b.block_type, b.remarks,
                b.created_by_role, b.created_at,
                u.unit_type, u.unit_number
            FROM unit_blocked_dates b
            INNER JOIN units_table u ON b.unit_id = u.unit_id
            WHERE u.unit_owner_id = ?
            ORDER BY b.start_date
        ";
        $blockedRows = $this->fetchAll($blockedSql, [$ownerId]);

        $blockedDates = [];
        foreach ($blockedRows as $row) {
            $blockedDates[] = [
                'blockId'       => (int)$row['block_id'],
                'unitId'        => (int)$row['unit_id'],
                'unitType'      => (string)$row['unit_type'],
                'roomNumber'    => (string)$row['unit_number'],
                'startDate'     => (string)$row['start_date'],
                'endDate'       => (string)$row['end_date'],
                'blockType'     => (string)$row['block_type'],
                'remarks'       => (string)($row['remarks'] ?? ''),
                'createdByRole' => (string)($row['created_by_role'] ?? 'unit owner'),
            ];
        }

        return [
            'unitTypes'    => $unitTypes,
            'bookings'     => $bookings,
            'blockedDates' => $blockedDates,
        ];
    }

    /**
     * Retrieve unit owner account details, units, and maintenance requests.
     */
    public function getAccountData(int $ownerId): ?array {
        $owner = $this->fetchOne("SELECT * FROM users_table WHERE user_id = ? LIMIT 1", [$ownerId]);
        if (!$owner) return null;

        $hasDobCol = false;
        $hasAddPhoneCol = false;
        $hasAddEmailCol = false;
        $hasQrCol = false;

        try {
            $colsStmt = $this->db->query("SHOW COLUMNS FROM users_table");
            $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
            $hasDobCol = in_array('date_of_birth', $cols, true);
            $hasAddPhoneCol = in_array('additional_contact', $cols, true);
            $hasAddEmailCol = in_array('additional_email', $cols, true);
            $hasQrCol = in_array('gcash_QR', $cols, true);
            if (!$hasQrCol) {
                $this->db->exec("ALTER TABLE users_table ADD COLUMN gcash_QR VARCHAR(255) NULL DEFAULT NULL AFTER resident_status");
                $hasQrCol = true;
            }
        } catch (Throwable $e) {}

        // Fetch owned units with current tenant
        $uSql = "
            SELECT 
                u.unit_id, 
                u.unit_number, 
                u.unit_type, 
                u.floor_number, 
                u.unit_current_status, 
                u.lease_rate, 
                u.created_at,
                (
                    SELECT r.client_name 
                    FROM reservation_table r 
                    WHERE r.unit_id = u.unit_id 
                      AND (r.officially_booked_at IS NOT NULL OR r.reservation_status IN ('Approved', 'Completed', 'Confirmed', 'Active', 'reserved', 'moved in'))
                    ORDER BY r.created_at DESC 
                    LIMIT 1
                ) AS current_tenant_name
            FROM units_table u 
            WHERE u.unit_owner_id = ? 
               OR u.unit_owner_id IN (SELECT user_id FROM users_table WHERE email = ?)
            ORDER BY u.unit_number ASC
        ";
        $units = $this->fetchAll($uSql, [$ownerId, $owner['email'] ?? '']);

        // Fetch recent maintenance requests
        $mSql = "
            SELECT 
                m.maintenance_id,
                m.unit_id,
                m.unit_owner_id,
                m.submitted_by_user_id,
                COALESCE(m.subject, m.category, 'Maintenance Request') AS issue_title,
                m.category,
                m.description,
                m.priority,
                m.status,
                m.submitted_at,
                u.unit_number, 
                u.unit_type 
            FROM maintenance_requests m 
            LEFT JOIN units_table u ON m.unit_id = u.unit_id 
            WHERE m.submitted_by_user_id = ? 
               OR m.unit_owner_id = ? 
               OR u.unit_owner_id = ? 
            ORDER BY m.submitted_at DESC LIMIT 50
        ";
        $maintenance = $this->fetchAll($mSql, [$ownerId, $ownerId, $ownerId]);

        return [
            'owner'           => $owner,
            'hasDobCol'       => $hasDobCol,
            'hasAddPhoneCol'  => $hasAddPhoneCol,
            'hasAddEmailCol'  => $hasAddEmailCol,
            'hasQrCol'        => $hasQrCol,
            'units'           => $units,
            'maintenance'     => $maintenance,
            'unitsCount'      => count($units),
            'requestsCount'   => count($maintenance),
            'pendingRequests' => count(array_filter($maintenance, fn($item) => strtolower($item['status'] ?? '') === 'pending')),
        ];
    }

    /**
     * Upload and save owner GCash QR code image.
     */
    public function uploadGcashQr(int $ownerId, array $file): array {
        if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Please select a GCash QR image file to upload.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Image upload failed. Please try again.'];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'QR image must be 5MB or below.'];
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $origName = $file['name'];
        $tmpName = $file['tmp_name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, and WEBP image files are allowed.'];
        }

        $uploadDir = dirname(__DIR__) . '/images/payment_qr/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $newName = 'gcash_qr_' . $ownerId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $newName;
        $dbPath = 'images/payment_qr/' . $newName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            // Remove old QR image file if it exists
            $oldRow = $this->fetchOne("SELECT gcash_QR FROM users_table WHERE user_id = ? LIMIT 1", [$ownerId]);
            if (!empty($oldRow['gcash_QR'])) {
                $oldFile = dirname(__DIR__) . '/' . ltrim($oldRow['gcash_QR'], '/');
                if (file_exists($oldFile) && is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $this->execute("UPDATE users_table SET gcash_QR = ? WHERE user_id = ?", [$dbPath, $ownerId]);
            return ['success' => true, 'message' => 'Your GCash QR code has been saved successfully!'];
        }

        return ['success' => false, 'message' => 'Failed to save the uploaded image file.'];
    }

    /**
     * Remove owner GCash QR code image.
     */
    public function deleteGcashQr(int $ownerId): array {
        $oldRow = $this->fetchOne("SELECT gcash_QR FROM users_table WHERE user_id = ? LIMIT 1", [$ownerId]);
        if (!empty($oldRow['gcash_QR'])) {
            $oldFile = dirname(__DIR__) . '/' . ltrim($oldRow['gcash_QR'], '/');
            if (file_exists($oldFile) && is_file($oldFile)) {
                @unlink($oldFile);
            }
        }
        $this->execute("UPDATE users_table SET gcash_QR = NULL WHERE user_id = ?", [$ownerId]);
        return ['success' => true, 'message' => 'Your GCash QR code has been removed.'];
    }

    /**
     * Update unit owner profile settings.
     */
    public function updateProfile(int $ownerId, array $data): array {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $contact = trim((string)($data['contact'] ?? ''));
        $dob = !empty($data['date_of_birth']) ? (string)$data['date_of_birth'] : null;
        $addContact = !empty($data['additional_contact']) ? trim((string)$data['additional_contact']) : null;
        $addEmail = !empty($data['additional_email']) ? trim((string)$data['additional_email']) : null;
        $newPassword = (string)($data['new_password'] ?? '');

        if ($fullName === '') {
            return ['success' => false, 'message' => 'Full name cannot be empty.'];
        }

        try {
            $updates = ["full_name = ?", "contact = ?"];
            $params = [$fullName, $contact];

            $colsStmt = $this->db->query("SHOW COLUMNS FROM users_table");
            $columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

            if (in_array('date_of_birth', $columns, true)) {
                $updates[] = "date_of_birth = ?";
                $params[] = $dob;
            }
            if (in_array('additional_contact', $columns, true)) {
                $updates[] = "additional_contact = ?";
                $params[] = $addContact;
            }
            if (in_array('additional_email', $columns, true)) {
                $updates[] = "additional_email = ?";
                $params[] = $addEmail;
            }
            if (!empty($newPassword)) {
                $updates[] = "password = ?";
                $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
            }

            $params[] = $ownerId;
            $sql = "UPDATE users_table SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $this->execute($sql, $params);

            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }

    /**
     * Respond to an owner approval request (Approve or Decline).
     */
    public function respondApprovalRequest(int $ownerId, int $requestId, string $action, string $remarks = ''): array {
        if ($ownerId <= 0 || $requestId <= 0 || !in_array($action, ['approve', 'decline'], true)) {
            return ['success' => false, 'message' => 'Invalid request parameters.'];
        }

        $this->db->beginTransaction();

        try {
            $sql = "
                SELECT 
                    request_id,
                    inq_id,
                    unit_id,
                    unit_owner_id,
                    request_status
                FROM owner_approval_requests
                WHERE request_id = ?
                  AND unit_owner_id = ?
                FOR UPDATE
            ";
            $request = $this->fetchOne($sql, [$requestId, $ownerId]);

            if (!$request) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Approval request not found or unauthorized.'];
            }

            if (strtolower((string)$request['request_status']) !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'This request has already been responded to.'];
            }

            $inqId = (int)$request['inq_id'];
            $unitId = (int)$request['unit_id'];

            if ($action === 'approve') {
                // 1. Approve this owner's request (no FCFS: do not expire other owners, do not block)
                $this->execute(
                    "UPDATE owner_approval_requests SET request_status = 'approved', owner_remarks = ?, responded_at = NOW() WHERE request_id = ?",
                    [$remarks, $requestId]
                );

                // 2. Fetch current inquiry state
                $checkInqSql = "SELECT approval_status, reservation_token FROM inquiry_table WHERE inq_id = ? FOR UPDATE";
                $inquiry = $this->fetchOne($checkInqSql, [$inqId]);

                // If inquiry is not marked approved, set approval_status to approved without assigning a specific unit
                if (!$inquiry || strtolower((string)($inquiry['approval_status'] ?? '')) !== 'approved') {
                    $reservationToken = !empty($inquiry['reservation_token']) ? $inquiry['reservation_token'] : bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

                    $updateInqSql = "
                        UPDATE inquiry_table
                        SET approval_status = 'approved',
                            owner_remarks = ?,
                            reservation_token = ?,
                            reservation_token_expires_at = ?,
                            approval_approved_at = NOW()
                        WHERE inq_id = ?
                    ";
                    $this->execute($updateInqSql, [$remarks, $reservationToken, $expiresAt, $inqId]);
                }

                $this->db->commit();

                return ['success' => true, 'message' => 'Reservation request approved successfully.'];
            }

            if ($action === 'decline') {
                // 1. Decline this request
                $this->execute(
                    "UPDATE owner_approval_requests SET request_status = 'declined', owner_remarks = ?, responded_at = NOW() WHERE request_id = ?",
                    [$remarks, $requestId]
                );

                // 2. Check pending and approved count for this inquiry
                $countsRow = $this->fetchOne(
                    "SELECT 
                        SUM(CASE WHEN request_status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                        SUM(CASE WHEN request_status = 'approved' THEN 1 ELSE 0 END) AS approved_count
                     FROM owner_approval_requests WHERE inq_id = ?",
                    [$inqId]
                );
                $pendingCount = (int)($countsRow['pending_count'] ?? 0);
                $approvedCount = (int)($countsRow['approved_count'] ?? 0);

                // If all owners responded and none approved, mark inquiry as declined
                if ($pendingCount === 0 && $approvedCount === 0) {
                    $this->execute(
                        "UPDATE inquiry_table SET status = 'declined', approval_status = 'declined', owner_remarks = ? WHERE inq_id = ? AND approval_status != 'approved'",
                        [$remarks, $inqId]
                    );
                }

                $this->db->commit();

                return ['success' => true, 'message' => 'Reservation request declined.'];
            }

            $this->db->rollBack();
            return ['success' => false, 'message' => 'Invalid action specified.'];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('respondApprovalRequest error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred while processing approval: ' . $e->getMessage()];
        }
    }

    /**
     * Update unit settings (listing mode, stay category, lease rate, reselling price) for an owned unit.
     */
    public function updateUnitSettings(int $ownerId, int $unitId, array $data): array {
        // 1. Verify that the unit belongs to this owner
        $sql = "SELECT unit_id, unit_current_status, stay_category, lease_rate, COALESCE(resellling_price, reselling_price, NULL) as reselling_price FROM units_table WHERE unit_id = ? AND unit_owner_id = ? LIMIT 1";
        $unit = $this->fetchOne($sql, [$unitId, $ownerId]);
        if (!$unit) {
            throw new RuntimeException('Unit not found or access denied.');
        }

        // 2. Validate listing_type
        $listingType = trim((string)($data['listing_type'] ?? 'For Lease'));
        if (!in_array($listingType, ['For Lease', 'Resale'], true)) {
            $listingType = 'For Lease';
        }

        // 3. Validate stay_category
        $existingStay = !empty($unit['stay_category']) ? (string)$unit['stay_category'] : 'Long term';
        $stayCategory = isset($data['stay_category']) && trim((string)$data['stay_category']) !== '' 
            ? trim((string)$data['stay_category']) 
            : $existingStay;
        if (!in_array($stayCategory, ['Long term', 'Short term'], true)) {
            $stayCategory = $existingStay;
        }

        // 4. Validate lease_rate and reselling_price
        $leaseRate = isset($data['lease_rate']) && is_numeric($data['lease_rate'])
            ? round((float)$data['lease_rate'], 2)
            : (float)($unit['lease_rate'] ?? 0);
        if ($leaseRate < 0) $leaseRate = 0.0;

        $rawResale = $data['resellling_price'] ?? $data['reselling_price'] ?? null;
        $resellingPrice = ($rawResale !== null && is_numeric($rawResale))
            ? round((float)$rawResale, 2)
            : ($unit['reselling_price'] !== null ? (float)$unit['reselling_price'] : null);
        if ($resellingPrice !== null && $resellingPrice < 0) $resellingPrice = 0.0;

        // 5. Update unit_current_status based on listing_type if not currently occupied/under maintenance
        $currentStatus = (string)($unit['unit_current_status'] ?? 'Ready for Occupancy');
        $newStatus = $currentStatus;
        if (!in_array(strtolower($currentStatus), ['occupied', 'under maintenance'], true)) {
            if ($listingType === 'Resale') {
                $newStatus = 'Resale';
            } elseif ($listingType === 'For Lease' && strtolower($currentStatus) === 'resale') {
                $newStatus = 'Ready for Occupancy';
            }
        }

        // 6. Update both resellling_price and reselling_price columns for database compatibility
        $updateSql = "
            UPDATE units_table 
            SET listing_type = ?,
                stay_category = ?,
                lease_rate = ?,
                resellling_price = ?,
                reselling_price = ?,
                unit_current_status = ?
            WHERE unit_id = ? AND unit_owner_id = ?
        ";
        $this->execute($updateSql, [
            $listingType,
            $stayCategory,
            $leaseRate,
            $resellingPrice,
            $resellingPrice,
            $newStatus,
            $unitId,
            $ownerId
        ]);

        return [
            'unit_id'                    => $unitId,
            'listing_type'               => $listingType,
            'stay_category'              => $stayCategory,
            'lease_rate'                 => $leaseRate,
            'lease_rate_formatted'       => '₱' . number_format($leaseRate, 2),
            'reselling_price'            => $resellingPrice,
            'reselling_price_formatted'  => $resellingPrice !== null ? '₱' . number_format($resellingPrice, 2) : '—',
            'resellling_price_formatted' => $resellingPrice !== null ? '₱' . number_format($resellingPrice, 2) : '—',
            'unit_current_status'        => $newStatus,
        ];
    }

    /**
     * Confirm / choose agreed lease signing date for this owner's unit.
     */
    public function confirmSigningDate(int $ownerId, int $reservationId, string $date): array {
        $res = $this->getReservationDetails($ownerId, $reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation not found or unauthorized.'];
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
        $success = $this->execute($sql, [$cleanDate, $ownerId, $reservationId]);
        if (!$success) {
            return ['success' => false, 'message' => 'Database error while saving confirmed signing date.'];
        }

        return [
            'success'        => true,
            'message'        => 'Lease signing appointment confirmed successfully.',
            'confirmed_date' => $cleanDate,
            'formatted_date' => date('l, F j, Y', $ts),
        ];
    }

    /**
     * Complete or reset lease signing status for this owner's unit.
     */
    public function updateLeaseSigningStatus(int $ownerId, int $reservationId, string $action, string $remarks): array {
        $res = $this->getReservationDetails($ownerId, $reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation not found or unauthorized.'];
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
            $params = [$status, $now, $ownerId, $remarks, $reservationId];
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

        if ($action === 'complete') {
            require_once __DIR__ . '/Reservation.php';
            (new Reservation())->checkAndPromoteToOfficiallyBooked($reservationId, $ownerId, 'unit owner');
        }

        return [
            'success'   => true,
            'message'   => $action === 'complete' ? 'Lease signing marked as completed successfully.' : 'Lease signing status reset to pending.',
            'status'    => $status,
            'signed_at' => $now,
        ];
    }

    /**
     * Verify (complete) or reject payment for this owner's reservation.
     */
    public function updatePaymentStatus(int $ownerId, int $reservationId, string $action, string $remarks): array {
        $res = $this->getReservationDetails($ownerId, $reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation not found or unauthorized.'];
        }

        $action = strtolower(trim($action));
        if (!in_array($action, ['verify', 'reject'], true)) {
            return ['success' => false, 'message' => 'Invalid payment action.'];
        }

        $now = date('Y-m-d H:i:s');

        if ($action === 'verify') {
            $sql = "
                UPDATE reservation_table
                SET payment_status = 'verified',
                    payment_verified_at = ?,
                    admin_payment_remarks = ?,
                    reservation_status = CASE 
                        WHEN LOWER(reservation_status) = 'submitted' THEN 'pending' 
                        ELSE reservation_status 
                    END
                WHERE reservation_id = ?
            ";
            $success = $this->execute($sql, [$now, $remarks, $reservationId]);
            if (!$success) {
                return ['success' => false, 'message' => 'Database error while marking payment as complete.'];
            }

            require_once __DIR__ . '/Reservation.php';
            (new Reservation())->checkAndPromoteToOfficiallyBooked($reservationId, $ownerId, 'unit owner');

            return [
                'success'        => true,
                'message'        => 'Payment marked as complete and verified successfully.',
                'payment_status' => 'verified',
                'verified_at'    => $now,
            ];
        }

        // Reject / Not Received
        $inquiryType = strtolower(trim((string)($res['inquiry_type'] ?? '')));
        $releasedStatus = ($inquiryType === 'resale inquiry' || strpos($inquiryType, 'resale') !== false) 
            ? 'Resale' 
            : 'Ready for Occupancy';

        $sql = "
            UPDATE reservation_table
            SET payment_status = 'rejected',
                reservation_status = 'rejected',
                payment_rejected_at = ?,
                admin_payment_remarks = ?
            WHERE reservation_id = ?
        ";
        $success = $this->execute($sql, [$now, $remarks, $reservationId]);
        if (!$success) {
            return ['success' => false, 'message' => 'Database error while rejecting payment.'];
        }

        // Release the unit back
        if (!empty($res['unit_id'])) {
            $this->execute("UPDATE units_table SET unit_current_status = ? WHERE unit_id = ?", [
                $releasedStatus,
                (int)$res['unit_id']
            ]);
        }

        return [
            'success'        => true,
            'message'        => 'Payment marked as not received. Reservation has been rejected and the unit is released.',
            'payment_status' => 'rejected',
            'rejected_at'    => $now,
        ];
    }

    /**
     * Retrieve documents for a reservation owned by this unit owner.
     */
    public function getDocuments(int $ownerId, int $reservationId): array {
        $res = $this->getReservationDetails($ownerId, $reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation not found or unauthorized.', 'documents' => [], 'all_completed' => false];
        }

        require_once __DIR__ . '/Reservation.php';
        $reservationModel = new Reservation();
        return $reservationModel->getDocuments($reservationId);
    }

    /**
     * Save documents for a reservation owned by this unit owner.
     */
    public function saveDocuments(int $ownerId, int $reservationId, array $documents): array {
        $res = $this->getReservationDetails($ownerId, $reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation not found or unauthorized.'];
        }

        require_once __DIR__ . '/Reservation.php';
        $reservationModel = new Reservation();
        return $reservationModel->saveDocuments($reservationId, $documents, $ownerId, 'unit owner');
    }

    /**
     * Submit a cancellation request to admin (Unit Owner).
     */
    public function requestCancellation(int $ownerId, int $reservationId, string $reason): array {
        $res = $this->getReservationDetails($ownerId, $reservationId);
        if (!$res) {
            return ['success' => false, 'message' => 'Reservation not found or unauthorized.'];
        }

        $now = date('Y-m-d H:i:s');

        $success = $this->execute(
            "UPDATE reservation_table 
             SET cancellation_status = 'requested',
                 cancellation_reason = ?,
                 cancellation_requested_by = ?,
                 cancellation_requested_by_role = 'unit owner',
                 cancellation_requested_at = ?
             WHERE reservation_id = ?",
            [$reason, $ownerId, $now, $reservationId]
        );

        if (!$success) {
            return ['success' => false, 'message' => 'Database error while submitting cancellation request.'];
        }

        return ['success' => true, 'message' => 'Cancellation request submitted to admin successfully.'];
    }
}


