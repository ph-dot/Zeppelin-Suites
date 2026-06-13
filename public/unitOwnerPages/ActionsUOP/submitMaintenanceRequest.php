<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'unit owner') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: ../ownersMaintenance.php");
    exit;
}

$owner_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: ../ownersMaintenance.php");
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
    header("Location: ../ownersMaintenance.php");
    exit;
}

if (!in_array($category, $allowedCategories)) {
    $_SESSION['error_message'] = "Invalid maintenance category.";
    header("Location: ../ownersMaintenance.php");
    exit;
}

if (!in_array($priority, $allowedPriorities)) {
    $_SESSION['error_message'] = "Invalid priority.";
    header("Location: ../ownersMaintenance.php");
    exit;
}

$conn->begin_transaction();

try {
    // Make sure the selected unit belongs to this owner
    $checkSql = "
        SELECT unit_id 
        FROM units_table 
        WHERE unit_id = ? 
        AND unit_owner_id = ? 
        LIMIT 1
    ";

    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $unit_id, $owner_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $unit = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!$unit) {
        throw new Exception("Invalid unit selected.");
    }

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

            if (!in_array($ext, $allowedExt)) {
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

    $photoPathsJson = !empty($photoPaths) ? json_encode($photoPaths) : null;

    $insertSql = "
        INSERT INTO maintenance_requests
            (unit_owner_id, unit_id, subject, category, description, priority, status, photo_paths, submitted_at)
        VALUES
            (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ";

    $stmt = $conn->prepare($insertSql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "iisssss",
        $owner_id,
        $unit_id,
        $subject,
        $category,
        $description,
        $priority,
        $photoPathsJson
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to submit maintenance request.");
    }

    $stmt->close();

    $conn->commit();

    $_SESSION['success_message'] = "Maintenance request submitted successfully.";
    header("Location: ../ownersMaintenance.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ../ownersMaintenance.php");
    exit;
}
?>