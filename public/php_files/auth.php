<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

function normalizeRole($role) {
    return strtolower(trim((string) $role));
}

function requireRole($conn, array $allowedRoles) {
    $userId = $_SESSION['user_id'] ?? null;
    $role = normalizeRole($_SESSION['role'] ?? '');

    $allowedRoles = array_map('normalizeRole', $allowedRoles);

    if (!$userId || !in_array($role, $allowedRoles, true)) {
        session_unset();
        session_destroy();
        header("Location: ../generalViewPages/login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT full_name FROM users_table WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $defaultNames = [
        'admin' => 'Admin User',
        'tenant' => 'Tenant',
        'unit owner' => 'Unit Owner'
    ];

    $fullName = $user['full_name'] ?? ($defaultNames[$role] ?? 'User');

    $_SESSION['full_name'] = $fullName;

    $stmt->close();

    return [
        'user_id' => $userId,
        'role' => $role,
        'full_name' => $fullName,
        'initial' => strtoupper(substr($fullName, 0, 1))
    ];
}
?>