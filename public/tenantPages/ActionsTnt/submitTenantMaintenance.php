<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'tenant') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../maintenanceTenant.php");
    exit;
}

$tenant_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: ../maintenanceTenant.php");
    exit;
}

$unit_id = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
$subject = trim($_POST['subject'] ?? '');
$category = trim($_POST['category'] ?? '');
$priority = trim($_POST['priority'] ?? 'normal');
$description = trim($_POST['description'] ?? '');

$allowedCategories = ['Plumbing', 'Electrical', 'Cleaning', 'Fixture', 'Structural', 'Other'];
$allowedPriorities = ['low', 'normal', 'urgent'];

if ($unit_id <= 0 || $subject === '' || $category === '' || $description === '') {
    $_SESSION['error_message'] = "Please complete all required fields.";
    header("Location: ../maintenanceTenant.php");
    exit;
}

if (!in_array($category, $allowedCategories)) {
    $_SESSION['error_message'] = "Invalid maintenance category.";
    header("Location: ../maintenanceTenant.php");
    exit;
}

if (!in_array($priority, $allowedPriorities)) {
    $_SESSION['error_message'] = "Invalid priority.";
    header("Location: ../maintenanceTenant.php");
    exit;
}

$conn->begin_transaction();

try {
    // 1. Fetch authenticated tenant's identity (TASK-009)
    $userStmt = $conn->prepare("SELECT email, full_name FROM users_table WHERE user_id = ? LIMIT 1");
    if (!$userStmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $userStmt->bind_param("i", $tenant_id);
    $userStmt->execute();
    $tenantUser = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$tenantUser) {
        throw new Exception("Tenant account not found.");
    }

    $tenantEmail = trim($tenantUser['email'] ?? '');
    $tenantName = trim($tenantUser['full_name'] ?? '');

    // 2. Strict Authorization Check (TASK-009)
    // Confirm that the authenticated tenant has an active or approved lease for the specified unit_id
    $checkSql = "
        SELECT u.unit_id, u.unit_number, u.unit_owner_id 
        FROM units_table u
        INNER JOIN reservation_table r ON r.unit_id = u.unit_id
        WHERE u.unit_id = ? 
          AND (r.client_email = ? OR r.client_name = ?)
          AND LOWER(r.reservation_status) NOT IN ('cancelled', 'rejected')
        LIMIT 1
    ";
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $checkStmt->bind_param("iss", $unit_id, $tenantEmail, $tenantName);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $unit = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!$unit) {
        throw new Exception("Unauthorized: You do not have an active or approved lease for this unit. You can only submit maintenance tickets for your own leased unit.");
    }

    $unit_owner_id = (int)($unit['unit_owner_id'] ?? 0);

    $photoPaths = [];

    if (!empty($_FILES['maintenance_photos']['name'][0])) {
        $uploadDir = __DIR__ . '/../../uploads/maintenance/';
        $dbDir = 'uploads/maintenance/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $maxFiles = 5;
        $maxSize = 5 * 1024 * 1024;

        $fileCount = count($_FILES['maintenance_photos']['name']);

        if ($fileCount > $maxFiles) {
            throw new Exception("You may upload up to 5 photos only.");
        }

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['maintenance_photos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($_FILES['maintenance_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                throw new Exception("One of the uploaded photos failed to process.");
            }

            if ($_FILES['maintenance_photos']['size'][$i] > $maxSize) {
                throw new Exception("Each photo must be 5MB or below.");
            }

            $originalName = $_FILES['maintenance_photos']['name'][$i];
            $tmpName = $_FILES['maintenance_photos']['tmp_name'][$i];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt)) {
                throw new Exception("Only JPG, PNG, and WEBP files are allowed.");
            }

            $newName = 'maintenance_tnt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = $uploadDir . $newName;
            $dbPath = $dbDir . $newName;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                throw new Exception("Failed to upload photo.");
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
        )
        VALUES (?, 'tenant', ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ";

    $stmt = $conn->prepare($insertSql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "iiisssss",
        $tenant_id,
        $unit_owner_id,
        $unit_id,
        $subject,
        $category,
        $description,
        $priority,
        $photoPathsValue
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to submit maintenance request: " . $stmt->error);
    }

    $stmt->close();
    $conn->commit();

    $_SESSION['success_message'] = "Maintenance request submitted successfully.";
    header("Location: ../maintenanceTenant.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../maintenanceTenant.php");
    exit;
}
?>
