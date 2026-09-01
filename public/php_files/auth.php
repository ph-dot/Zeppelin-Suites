<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (!function_exists('normalizeRole')) {
    function normalizeRole($role) {
        return strtolower(trim((string) $role));
    }
}

if (!function_exists('getAuthLoginRedirectUrl')) {
    function getAuthLoginRedirectUrl() {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($scriptName, '/ActionsAP/') !== false || 
            strpos($scriptName, '/ActionsUOP/') !== false || 
            strpos($scriptName, '/ActionsTnt/') !== false || 
            strpos($scriptName, '/ActionsGV/') !== false) {
            return '../../generalViewPages/login.php';
        }
        return '../generalViewPages/login.php';
    }
}

if (!function_exists('requireRole')) {
    function requireRole($conn, array $allowedRoles, bool $redirectOnFail = true) {
        $userId = $_SESSION['user_id'] ?? null;
        $role = normalizeRole($_SESSION['role'] ?? '');

        $allowedRoles = array_map('normalizeRole', $allowedRoles);

        if (!$userId || !in_array($role, $allowedRoles, true)) {
            if ($redirectOnFail) {
                session_unset();
                session_destroy();
                $loginUrl = getAuthLoginRedirectUrl();
                header("Location: " . $loginUrl);
                exit;
            }
            return null;
        }

        $stmt = $conn->prepare("SELECT full_name FROM users_table WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;

        $defaultNames = [
            'admin' => 'Admin User',
            'tenant' => 'Tenant',
            'unit owner' => 'Unit Owner'
        ];

        $fullName = $user['full_name'] ?? ($defaultNames[$role] ?? 'User');
        $initial = strtoupper(substr($fullName, 0, 1));

        $_SESSION['full_name'] = $fullName;
        $GLOBALS['full_name'] = $fullName;
        $GLOBALS['initial'] = $initial;

        $stmt->close();

        return [
            'user_id' => $userId,
            'role' => $role,
            'full_name' => $fullName,
            'initial' => $initial
        ];
    }
}
?>