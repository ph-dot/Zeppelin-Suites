<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Model.php';

/**
 * Zeppelin Suites - User Model
 * Handles all database operations for users_table using PDO prepared statements.
 * Strictly NO HTML output or presentation logic.
 */
class User extends Model {

    /**
     * Find a user record by email address.
     */
    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM users_table WHERE email = ? LIMIT 1";
        return $this->fetchOne($sql, [$email]);
    }

    /**
     * Find a user record by user ID.
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM users_table WHERE user_id = ? LIMIT 1";
        return $this->fetchOne($sql, [$id]);
    }

    /**
     * Authenticate user credentials with support for BCrypt and legacy plaintext password auto-upgrade.
     *
     * @param string $email User email
     * @param string $password User entered plaintext password
     * @return array Result array ['success' => bool, 'user' => ?array, 'error' => ?string]
     */
    public function authenticate(string $email, string $password): array {
        $user = $this->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'user'    => null,
                'error'   => 'No account found with this email.',
            ];
        }

        $storedPassword = (string)($user['password'] ?? '');
        $isPasswordValid = false;

        // 1. Verify standard BCrypt hash
        if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
            $isPasswordValid = true;

            // Auto-rehash if password cost/algorithm settings changed
            if (password_needs_rehash($storedPassword, PASSWORD_BCRYPT)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $this->rehashPassword((int)$user['user_id'], $newHash);
            }
        }
        // 2. Backward compatibility fallback: check for legacy plaintext password and seamlessly upgrade to BCrypt
        elseif ($storedPassword !== '' && hash_equals($storedPassword, $password)) {
            $isPasswordValid = true;
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $this->rehashPassword((int)$user['user_id'], $newHash);
        }

        if (!$isPasswordValid) {
            return [
                'success' => false,
                'user'    => null,
                'error'   => 'Incorrect password. Try again.',
            ];
        }

        return [
            'success' => true,
            'user'    => $user,
            'error'   => null,
        ];
    }

    /**
     * Update user password hash in the database.
     */
    public function rehashPassword(int $userId, string $newHash): bool {
        $sql = "UPDATE users_table SET password = ? WHERE user_id = ?";
        return $this->execute($sql, [$newHash, $userId]);
    }

    /**
     * Update user profile data.
     */
    public function updateProfile(int $userId, array $data): bool {
        $allowedFields = ['full_name', 'contact', 'additional_contact', 'additional_email', 'date_of_birth', 'address'];
        $setClauses = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        $params[] = $userId;
        $sql = "UPDATE users_table SET " . implode(', ', $setClauses) . " WHERE user_id = ?";
        return $this->execute($sql, $params);
    }

    /**
     * Get user display name and initials.
     */
    public function getUserDisplayInfo(int $userId, string $role): array {
        $user = $this->findById($userId);

        $defaultNames = [
            'admin'      => 'Admin User',
            'tenant'     => 'Tenant',
            'unit owner' => 'Unit Owner',
        ];

        $fullName = $user['full_name'] ?? ($defaultNames[strtolower($role)] ?? 'User');
        $initial = strtoupper(substr($fullName, 0, 1));

        return [
            'full_name' => $fullName,
            'initial'   => $initial,
        ];
    }
}
