<?php
require_once '../../php_files/session.php';
require_once '../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$storedPassword = (string)($user['password'] ?? '');
$isPasswordValid = false;

// 1. Verify BCrypt / password_hash hashed password
if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
    $isPasswordValid = true;

    // Auto-rehash if algorithm or cost options changed
    if (password_needs_rehash($storedPassword, PASSWORD_BCRYPT)) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $rehashStmt = $conn->prepare("UPDATE users_table SET password = ? WHERE user_id = ?");
        if ($rehashStmt) {
            $rehashStmt->bind_param("si", $newHash, $user['user_id']);
            $rehashStmt->execute();
            $rehashStmt->close();
        }
    }
}
// 2. Backward compatibility fallback: check for legacy plaintext password
elseif ($storedPassword !== '' && $password === $storedPassword) {
    $isPasswordValid = true;

    // Seamlessly upgrade legacy plaintext password to secure BCrypt hash in DB
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    $upgradeStmt = $conn->prepare("UPDATE users_table SET password = ? WHERE user_id = ?");
    if ($upgradeStmt) {
        $upgradeStmt->bind_param("si", $newHash, $user['user_id']);
        $upgradeStmt->execute();
        $upgradeStmt->close();
    }
}

if (!$isPasswordValid) {
    error_log("Password mismatch for email: $email");
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
        header("Location: ../../tenantPages/homeTenant.php");
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