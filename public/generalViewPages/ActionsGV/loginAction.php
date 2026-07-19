<?php
require_once '../../php_files/session.php';
require_once '../../php_files/db.php';

session_start(); // Ensure session is started

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: ../login.php");
    exit();
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error_message'] = "Please enter both email and password.";
    header("Location: ../login.php");
    exit();
}

// Debug: Log what we're receiving (REMOVE IN PRODUCTION)
error_log("Login attempt - Email: $email");

$sql = "SELECT * FROM users_table WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $_SESSION['error_message'] = "Database error. Please try again.";
    header("Location: ../login.php");
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("No user found for email: $email");
    $_SESSION['error_message'] = "No account found with this email.";
    header("Location: ../login.php");
    exit();
}

$user = $result->fetch_assoc();
error_log("User found: " . print_r($user, true));

// Plain text password check (CHANGE TO HASHED IN PRODUCTION!)
if ($password !== $user['password']) {
    error_log("Password mismatch. Input: '$password', DB: '{$user['password']}'");
    $_SESSION['error_message'] = "Incorrect password. Try again.";
    header("Location: ../login.php");
    exit();
}

// SUCCESS! Set session variables
session_regenerate_id(true);
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['role'] = strtolower(trim($user['user_role']));

// Clear any old errors
unset($_SESSION['error_message']);

error_log("Login successful for user_id: " . $user['user_id']);

// Redirect based on role
switch ($_SESSION['role']) {
    case 'admin':
        header("Location: ../../adminPages/homeAdmin.php");
        break;
    case 'unit owner':
        header("Location: ../../unitOwnerPages/overview.php");
        break;
    case 'tenant':
        header("Location: ../../tenantPages/homeTenant.html");
        break;
    default:
        error_log("Invalid role: " . $_SESSION['role']);
        $_SESSION['error_message'] = "Invalid user role: " . $_SESSION['role'];
        header("Location: ../login.php");
        break;
}

$stmt->close();
$conn->close();
exit();
?>