<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_id = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: ../maintenance.php");
    exit;
}

$unit_id = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
$subject = trim($_POST['subject'] ?? '');
$category = trim($_POST['category'] ?? 'Other');
$priority = strtolower(trim($_POST['priority'] ?? 'normal'));
$description = trim($_POST['description'] ?? '');

$allowedCategories = ['Plumbing', 'Electrical', 'Cleaning', 'Fixture', 'Structural', 'Other'];
$allowedPriorities = ['low', 'normal', 'medium', 'urgent', 'high'];

if ($unit_id <= 0 || $subject === '' || $description === '') {
    $_SESSION['error_message'] = "Please complete all required fields.";
    header("Location: ../maintenance.php");
    exit;
}

if (!in_array($category, $allowedCategories, true)) {
    $category = 'Other';
}

if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'normal';
}

// Normalize priority to standard values
if ($priority === 'medium') $priority = 'normal';
if ($priority === 'high') $priority = 'urgent';

$conn->begin_transaction();

try {
    // Find unit and its owner
    $unitSql = "SELECT unit_id, unit_owner_id FROM units_table WHERE unit_id = ? LIMIT 1";
    $unitStmt = $conn->prepare($unitSql);
    $unitStmt->bind_param("i", $unit_id);
    $unitStmt->execute();
    $unitRes = $unitStmt->get_result();
    $unitRow = $unitRes->fetch_assoc();
    $unitStmt->close();

    if (!$unitRow) {
        throw new Exception("Invalid unit selected.");
    }

    $owner_id = (int)($unitRow['unit_owner_id'] ?? 0);

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
                throw new Exception("One of the uploaded photos failed.");
            }

            if ($_FILES['maintenance_photos']['size'][$i] > $maxSize) {
                throw new Exception("Each photo must be 5MB or below.");
            }

            $originalName = $_FILES['maintenance_photos']['name'][$i];
            $tmpName = $_FILES['maintenance_photos']['tmp_name'][$i];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                throw new Exception("Only JPG, PNG, and WEBP files are allowed.");
            }

            $newName = 'maintenance_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
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
        VALUES (?, 'admin', ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ";

    $stmt = $conn->prepare($insertSql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "iiisssss",
        $admin_id,
        $owner_id,
        $unit_id,
        $subject,
        $category,
        $description,
        $priority,
        $photoPathsValue
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create maintenance ticket: " . $stmt->error);
    }

    $stmt->close();
    $conn->commit();

    $_SESSION['success_message'] = "Maintenance ticket created successfully.";
    header("Location: ../maintenance.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../maintenance.php");
    exit;
}
