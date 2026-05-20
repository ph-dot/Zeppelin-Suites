<?php
require_once 'session.php';
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: ../generalViewPages/login.php");
    exit;
}

// Get user details using YOUR column names
$stmt = $conn->prepare("SELECT full_name FROM users_table WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$_SESSION['full_name'] = $user['full_name'] ?? 'Admin User';
$full_name = $_SESSION['full_name'];
$initial = strtoupper(substr($full_name, 0, 1));

$stmt->close();
?>