<?php 
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php'; // Add this if needed

// Get user data (safe for login page)
$userData = null;
if (isset($_SESSION['user_id'])) {
    try {
        $userData = requireRole($GLOBALS['conn'], ['admin', 'unit owner', 'tenant'], false);
    } catch (Exception $e) {
        // Session invalid
    }
}

$role = $userData['role'] ?? '';
$userName = $userData['full_name'] ?? '';
$userInitial = $userData['initial'] ?? '';
$isLoggedIn = !empty($userName);

// Redirect if already logged in
if ($isLoggedIn) {
    if ($role === 'admin') {
        header("Location: ../adminPages/homeAdmin.php");
    } elseif ($role === 'unit owner') {
        header("Location: ../unitOwnerPages/overview.php");
    } elseif ($role === 'tenant') {
        header("Location: ../tenantPages/homeTenant.html");
    }
    exit();
}
?>