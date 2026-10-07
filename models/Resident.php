<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - Resident Model
 * Handles database operations for resident accounts (unit owners and tenants) in users_table.
 * Strictly prepared statements via PDO. No direct HTML or output.
 */
class Resident extends Model {

    /**
     * Allowed resident roles and statuses.
     */
    public const ALLOWED_ROLES = ['unit owner', 'tenant'];
    public const ALLOWED_STATUSES = ['Active', 'Inactive'];

    /**
     * Get aggregate statistics across all residents.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_residents,
                COALESCE(SUM(CASE WHEN resident_status = 'Active' THEN 1 ELSE 0 END), 0) AS active_residents,
                COALESCE(SUM(CASE WHEN resident_status = 'Inactive' THEN 1 ELSE 0 END), 0) AS inactive_residents,
                COALESCE(SUM(CASE WHEN user_role = 'unit owner' THEN 1 ELSE 0 END), 0) AS unit_owners,
                COALESCE(SUM(CASE WHEN user_role = 'tenant' THEN 1 ELSE 0 END), 0) AS tenants
            FROM users_table
            WHERE user_role IN ('unit owner', 'tenant')
        ";

        $row = $this->fetchOne($sql);
        return $row ?: [
            'total_residents'    => 0,
            'active_residents'   => 0,
            'inactive_residents' => 0,
            'unit_owners'        => 0,
            'tenants'            => 0,
        ];
    }

    /**
     * Retrieve all resident accounts matching optional search query, role, and status filters.
     */
    public function getAll(string $search = '', string $role = '', string $status = ''): array {
        $where = ["user_role IN ('unit owner', 'tenant')"];
        $params = [];

        $cleanSearch = trim($search);
        if ($cleanSearch !== '') {
            $where[] = "(full_name LIKE ? OR email LIKE ? OR contact LIKE ? OR CAST(user_id AS CHAR) LIKE ?)";
            $like = '%' . $cleanSearch . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $cleanRole = strtolower(trim($role));
        if (in_array($cleanRole, self::ALLOWED_ROLES, true)) {
            $where[] = "user_role = ?";
            $params[] = $cleanRole;
        }

        $cleanStatus = trim($status);
        if (in_array($cleanStatus, self::ALLOWED_STATUSES, true)) {
            $where[] = "resident_status = ?";
            $params[] = $cleanStatus;
        }

        $sql = "
            SELECT user_id, full_name, email, contact, user_role, created_at, resident_status
            FROM users_table
            WHERE " . implode(' AND ', $where) . "
            ORDER BY created_at DESC, user_id DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Find a resident account by ID.
     */
    public function findById(int $userId): ?array {
        $sql = "SELECT * FROM users_table WHERE user_id = ? AND user_role IN ('unit owner', 'tenant') LIMIT 1";
        return $this->fetchOne($sql, [$userId]);
    }

    /**
     * Check if an email address is already registered to another user.
     */
    public function emailExists(string $email, int $excludeUserId = 0): bool {
        $sql = "SELECT user_id FROM users_table WHERE email = ? AND user_id <> ? LIMIT 1";
        $row = $this->fetchOne($sql, [trim($email), $excludeUserId]);
        return $row !== null;
    }

    /**
     * Create a new resident account with BCrypt password hashing.
     */
    public function create(array $data): int {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $email = filter_var(trim((string)($data['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $contact = trim((string)($data['contact'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $role = strtolower(trim((string)($data['user_role'] ?? 'tenant')));
        $status = trim((string)($data['resident_status'] ?? 'Active'));

        if ($fullName === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Name, email, and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Invalid resident role.');
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid resident status.');
        }

        if ($this->emailExists($email)) {
            throw new RuntimeException('That email address is already used by another account.');
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "
            INSERT INTO users_table (full_name, email, password, contact, user_role, resident_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ";

        $this->execute($sql, [$fullName, $email, $hashedPassword, $contact, $role, $status]);
        return (int)$this->lastInsertId();
    }

    /**
     * Update an existing resident account.
     */
    public function update(int $userId, array $data): bool {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $email = filter_var(trim((string)($data['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $contact = trim((string)($data['contact'] ?? ''));
        $newPassword = (string)($data['new_password'] ?? '');
        $role = strtolower(trim((string)($data['user_role'] ?? 'tenant')));
        $status = trim((string)($data['resident_status'] ?? 'Active'));

        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid resident account ID.');
        }

        if ($fullName === '' || $email === '') {
            throw new InvalidArgumentException('Name and email are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Invalid resident role.');
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid resident status.');
        }

        if ($this->emailExists($email, $userId)) {
            throw new RuntimeException('That email address is already used by another account.');
        }

        if ($newPassword !== '') {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "
                UPDATE users_table
                SET full_name = ?, email = ?, contact = ?, user_role = ?, resident_status = ?, password = ?
                WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')
            ";
            return $this->execute($sql, [$fullName, $email, $contact, $role, $status, $hashedPassword, $userId]);
        }

        $sql = "
            UPDATE users_table
            SET full_name = ?, email = ?, contact = ?, user_role = ?, resident_status = ?
            WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')
        ";
        return $this->execute($sql, [$fullName, $email, $contact, $role, $status, $userId]);
    }

    /**
     * Toggle or update resident status ('Active' | 'Inactive').
     */
    public function toggleStatus(int $userId, string $status): bool {
        if ($userId <= 0 || !in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status update parameter.');
        }

        $sql = "UPDATE users_table SET resident_status = ? WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')";
        return $this->execute($sql, [$status, $userId]);
    }

    /**
     * Fetch complete resident details including owned units and tenancy history.
     */
    public function getResidentDetails(int $userId): ?array {
        $userSql = "
            SELECT user_id, full_name, email, contact, user_role, resident_status, created_at,
                   date_of_birth, additional_contact, additional_email
            FROM users_table
            WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')
            LIMIT 1
        ";
        $resident = $this->fetchOne($userSql, [$userId]);
        if (!$resident) {
            return null;
        }

        // Fetch owned units
        $ownedSql = "
            SELECT 
                u.unit_id, 
                u.unit_number, 
                u.unit_type, 
                u.floor_number, 
                u.unit_current_status, 
                u.lease_rate, 
                u.unit_owner_id,
                u.created_at,
                'Owned' AS ownership_type,
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
            ORDER BY u.floor_number ASC, u.unit_number ASC
        ";
        $ownedUnits = $this->fetchAll($ownedSql, [$userId]);
        $ownedUnitIds = array_map(fn($u) => (int)$u['unit_id'], $ownedUnits);

        // Fetch rented units
        $leasedSql = "
            SELECT DISTINCT
                u.unit_id, 
                u.unit_number, 
                u.unit_type, 
                u.floor_number, 
                u.unit_current_status, 
                u.lease_rate,
                u.unit_owner_id,
                'Leased' AS ownership_type,
                COALESCE(
                    (
                        SELECT r2.client_name 
                        FROM reservation_table r2 
                        WHERE r2.unit_id = u.unit_id 
                          AND (r2.officially_booked_at IS NOT NULL OR r2.reservation_status IN ('Approved', 'Completed', 'Confirmed', 'Active', 'reserved', 'moved in'))
                        ORDER BY r2.created_at DESC 
                        LIMIT 1
                    ),
                    r.client_name
                ) AS current_tenant_name
            FROM reservation_table r
            JOIN units_table u ON r.unit_id = u.unit_id
            WHERE (r.client_email = ? OR r.client_name = ?)
              AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
            ORDER BY u.unit_number ASC
        ";
        $leasedUnits = $this->fetchAll($leasedSql, [$resident['email'], $resident['full_name']]);

        $units = $ownedUnits;
        foreach ($leasedUnits as $lu) {
            if (!in_array((int)$lu['unit_id'], $ownedUnitIds, true)) {
                $units[] = $lu;
            }
        }

        // Fetch reservations / stays / leases
        $rSql = "
            SELECT r.*, u.unit_number, u.unit_type, u.floor_number 
            FROM reservation_table r 
            LEFT JOIN units_table u ON r.unit_id = u.unit_id 
            WHERE r.client_email = ? OR r.client_name = ? OR u.unit_owner_id = ? 
            ORDER BY r.created_at DESC LIMIT 50
        ";
        $reservations = $this->fetchAll($rSql, [$resident['email'], $resident['full_name'], $userId]);

        // Fetch maintenance requests
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
        $maintenance = $this->fetchAll($mSql, [$userId, $userId, $userId]);

        $resident['units'] = $units;
        $resident['reservations'] = $reservations;
        $resident['maintenance'] = $maintenance;

        return $resident;
    }

    /**
     * Update complete resident profile with optional date of birth and additional contacts.
     */
    public function updateResidentProfile(int $userId, array $data): bool {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $contact = trim((string)($data['contact'] ?? ''));
        $role = strtolower(trim((string)($data['user_role'] ?? 'tenant')));
        $status = trim((string)($data['resident_status'] ?? 'Active'));
        $newPassword = trim((string)($data['new_password'] ?? ''));
        $dob = !empty($data['date_of_birth']) ? trim((string)$data['date_of_birth']) : null;
        $addContact = !empty($data['additional_contact']) ? trim((string)$data['additional_contact']) : null;
        $addEmail = !empty($data['additional_email']) ? trim((string)$data['additional_email']) : null;

        if ($userId <= 0 || $fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($this->emailExists($email, $userId)) {
            return false;
        }

        $fields = [
            "full_name = ?",
            "email = ?",
            "contact = ?",
            "user_role = ?",
            "resident_status = ?",
            "date_of_birth = ?",
            "additional_contact = ?",
            "additional_email = ?"
        ];
        $params = [$fullName, $email, $contact, $role, $status, $dob, $addContact, $addEmail];

        if ($newPassword !== '') {
            $fields[] = "password = ?";
            $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $params[] = $userId;
        $sql = "UPDATE users_table SET " . implode(", ", $fields) . " WHERE user_id = ? AND user_role IN ('unit owner', 'tenant')";

        return $this->execute($sql, $params);
    }
}
