<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Tenant Model
 * Handles database operations for tenant overview, profile updates, and maintenance requests.
 * Pure MVC: strictly PDO prepared statements. Zero direct HTML or output.
 */
class Tenant extends Model {

    /**
     * Retrieve tenant overview data (lease, unit info, active maintenance count).
     */
    public function getOverviewData(int $tenantId, string $email, string $name): array {
        // 1. Fetch user record
        $user = $this->fetchOne("SELECT * FROM users_table WHERE user_id = ? LIMIT 1", [$tenantId]);

        // 2. Fetch active lease & unit information
        $leaseSql = "
            SELECT 
                r.reservation_id,
                r.unit_id,
                r.client_name,
                r.client_email,
                r.client_contact,
                r.move_in_date,
                r.move_out_date,
                r.price_basis,
                r.required_amount,
                r.declared_amount,
                r.payment_status,
                r.reservation_status,
                r.officially_booked_at,
                u.unit_number,
                u.unit_type,
                u.floor_number,
                u.unit_current_status,
                u.lease_rate,
                owner.user_id AS owner_id,
                owner.full_name AS owner_name,
                owner.email AS owner_email,
                owner.contact AS owner_contact
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            WHERE (r.client_email = ? OR r.client_name = ?)
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
            ORDER BY 
                CASE 
                    WHEN u.unit_current_status = 'Occupied' THEN 1
                    WHEN r.officially_booked_at IS NOT NULL THEN 2
                    WHEN r.reservation_status IN ('handover', 'moved in', 'reserved', 'Active', 'Approved') THEN 3
                    ELSE 4
                END,
                r.reservation_id DESC
            LIMIT 1
        ";
        $lookupEmail = $email !== '' ? $email : (string)($user['email'] ?? '');
        $lookupName  = $name !== '' ? $name : (string)($user['full_name'] ?? '');
        $leaseInfo = $this->fetchOne($leaseSql, [$lookupEmail, $lookupName]);

        // 3. Count active maintenance requests
        $mCountSql = "
            SELECT COUNT(*) AS total 
            FROM maintenance_requests 
            WHERE submitted_by_user_id = ? 
              AND LOWER(status) IN ('submitted', 'under review', 'in progress')
        ";
        $mCountRow = $this->fetchOne($mCountSql, [$tenantId]);
        $activeMaintenanceCount = (int)($mCountRow['total'] ?? 0);

        // 4. Fetch recent maintenance requests
        $recentTicketsSql = "
            SELECT 
                m.maintenance_id,
                m.subject,
                m.category,
                m.priority,
                m.status,
                m.submitted_at,
                u.unit_number
            FROM maintenance_requests m
            INNER JOIN units_table u ON m.unit_id = u.unit_id
            WHERE m.submitted_by_user_id = ?
            ORDER BY m.submitted_at DESC
            LIMIT 5
        ";
        $recentTickets = $this->fetchAll($recentTicketsSql, [$tenantId]);

        return [
            'user'                   => $user,
            'lease'                  => $leaseInfo,
            'activeMaintenanceCount' => $activeMaintenanceCount,
            'recentTickets'          => $recentTickets,
        ];
    }

    /**
     * Retrieve tenant account information along with leases and maintenance requests.
     */
    public function getAccountData(int $tenantId): ?array {
        $tenant = $this->fetchOne("SELECT * FROM users_table WHERE user_id = ? LIMIT 1", [$tenantId]);
        if (!$tenant) {
            return null;
        }

        $email = (string)($tenant['email'] ?? '');
        $name = (string)($tenant['full_name'] ?? '');

        // Fetch Leases for this Tenant
        $leaseSql = "
            SELECT 
                r.reservation_id,
                r.unit_id,
                r.client_name,
                r.client_email,
                r.client_contact,
                r.move_in_date,
                r.move_out_date,
                r.price_basis,
                r.required_amount,
                r.declared_amount,
                r.payment_status,
                r.reservation_status,
                r.officially_booked_at,
                r.created_at,
                u.unit_number,
                u.unit_type,
                u.floor_number,
                u.unit_current_status,
                u.lease_rate,
                owner.user_id AS owner_id,
                owner.full_name AS owner_name,
                owner.email AS owner_email,
                owner.contact AS owner_contact
            FROM reservation_table r
            INNER JOIN units_table u ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            WHERE r.client_email = ? OR r.client_name = ?
            ORDER BY r.created_at DESC
        ";
        $leases = $this->fetchAll($leaseSql, [$email, $name]);

        // Fetch Maintenance Requests
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
            ORDER BY m.submitted_at DESC 
            LIMIT 50
        ";
        $maintenance = $this->fetchAll($mSql, [$tenantId]);

        return [
            'tenant'      => $tenant,
            'leases'      => $leases,
            'maintenance' => $maintenance,
        ];
    }

    /**
     * Update tenant profile information and optional password.
     */
    public function updateProfile(int $tenantId, array $data): array {
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

            // Inspect optional columns in users_table
            $columnsStmt = $this->db->query("SHOW COLUMNS FROM users_table");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

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

            $params[] = $tenantId;
            $sql = "UPDATE users_table SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $this->execute($sql, $params);

            return ['success' => true, 'message' => 'Your profile has been updated successfully!'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve all units assigned to this tenant.
     */
    public function getTenantUnits(string $email, string $name): array {
        $sql = "
            SELECT DISTINCT u.unit_id, u.unit_number, u.unit_type, u.floor_number, u.unit_owner_id,
                   owner.full_name AS owner_name
            FROM units_table u
            INNER JOIN reservation_table r ON r.unit_id = u.unit_id
            LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
            WHERE (r.client_email = ? OR r.client_name = ?)
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
            ORDER BY u.unit_number ASC
        ";
        return $this->fetchAll($sql, [$email, $name]);
    }

    /**
     * Retrieve maintenance requests submitted by this tenant.
     */
    public function getMaintenanceTickets(int $tenantId, string $name): array {
        $sql = "
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
                ? AS tenant_name
            FROM maintenance_requests m
            INNER JOIN units_table u ON m.unit_id = u.unit_id
            LEFT JOIN users_table owner ON m.unit_owner_id = owner.user_id
            WHERE m.submitted_by_user_id = ?
            ORDER BY m.submitted_at DESC
        ";
        return $this->fetchAll($sql, [$name, $tenantId]);
    }

    /**
     * Create a new maintenance ticket submitted by the tenant.
     */
    public function createMaintenanceRequest(int $tenantId, string $email, string $name, array $data, array $files): array {
        $unitId = (int)($data['unit_id'] ?? 0);
        $subject = trim((string)($data['subject'] ?? ''));
        $category = trim((string)($data['category'] ?? ''));
        $priority = trim((string)($data['priority'] ?? 'normal'));
        $description = trim((string)($data['description'] ?? ''));

        $allowedCategories = ['Plumbing', 'Electrical', 'Cleaning', 'Fixture', 'Structural', 'Other'];
        $allowedPriorities = ['low', 'normal', 'urgent'];

        if ($unitId <= 0 || $subject === '' || $category === '' || $description === '') {
            return ['success' => false, 'message' => 'Please complete all required fields.'];
        }

        if (!in_array($category, $allowedCategories, true)) {
            return ['success' => false, 'message' => 'Invalid maintenance category.'];
        }

        if (!in_array($priority, $allowedPriorities, true)) {
            return ['success' => false, 'message' => 'Invalid priority level.'];
        }

        // Verify tenant authorization for this unit
        $checkSql = "
            SELECT u.unit_id, u.unit_number, u.unit_owner_id 
            FROM units_table u
            INNER JOIN reservation_table r ON r.unit_id = u.unit_id
            WHERE u.unit_id = ? 
              AND (r.client_email = ? OR r.client_name = ?)
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
            LIMIT 1
        ";
        $unit = $this->fetchOne($checkSql, [$unitId, $email, $name]);
        if (!$unit) {
            return [
                'success' => false,
                'message' => 'Unauthorized: You do not have an active lease for this unit.'
            ];
        }

        $unitOwnerId = (int)($unit['unit_owner_id'] ?? 0);

        // Process file uploads
        $photoPaths = [];
        if (!empty($files['maintenance_photos']['name'][0])) {
            $uploadDir = dirname(__DIR__) . '/images/maintenance/';
            $dbDir = 'images/maintenance/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $maxFiles = 5;
            $maxSize = 5 * 1024 * 1024;
            $fileCount = count($files['maintenance_photos']['name']);

            if ($fileCount > $maxFiles) {
                return ['success' => false, 'message' => 'You may upload up to 5 photos only.'];
            }

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['maintenance_photos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($files['maintenance_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                    return ['success' => false, 'message' => 'One of the uploaded photos failed to process.'];
                }

                if ($files['maintenance_photos']['size'][$i] > $maxSize) {
                    return ['success' => false, 'message' => 'Each photo must be 5MB or below.'];
                }

                $originalName = (string)$files['maintenance_photos']['name'][$i];
                $tmpName = (string)$files['maintenance_photos']['tmp_name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExt, true)) {
                    return ['success' => false, 'message' => 'Only JPG, PNG, and WEBP files are allowed.'];
                }

                $newName = 'maintenance_tnt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = $uploadDir . $newName;
                $dbPath = $dbDir . $newName;

                if (!move_uploaded_file($tmpName, $targetPath)) {
                    return ['success' => false, 'message' => 'Failed to upload photo.'];
                }

                $photoPaths[] = $dbPath;
            }
        }

        $photoPathsValue = !empty($photoPaths) ? implode(',', $photoPaths) : null;

        $insertSql = "
            INSERT INTO maintenance_requests (
                submitted_by_user_id,
                submitted_by_role,
                unit_owner_id,
                unit_id,
                subject,
                category,
                description,
                priority,
                status,
                photo_paths,
                submitted_at
            ) VALUES (?, 'tenant', ?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())
        ";

        try {
            $this->execute($insertSql, [
                $tenantId,
                $unitOwnerId > 0 ? $unitOwnerId : null,
                $unitId,
                $subject,
                $category,
                $description,
                $priority,
                $photoPathsValue,
            ]);

            return ['success' => true, 'message' => 'Maintenance request submitted successfully!'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
