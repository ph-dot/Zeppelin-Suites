<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

$unitMap = [
    'Studio Type A' => [
        'prefix' => 'A',
        'start' => 101
    ],
    'Studio Type B' => [
        'prefix' => 'B',
        'start' => 201
    ],
    'One Bedroom' => [
        'prefix' => 'C',
        'start' => 201
    ],
    'Two Bedroom' => [
        'prefix' => 'D',
        'start' => 301
    ]
];

function getNextUnitNumber($conn, $unit_type, $unitMap) {
    if (!array_key_exists($unit_type, $unitMap)) {
        return false;
    }

    $prefix = $unitMap[$unit_type]['prefix'];
    $startNumber = $unitMap[$unit_type]['start'];

    $sql = "SELECT unit_number 
            FROM units_table 
            WHERE unit_type = ? 
            AND unit_number LIKE CONCAT(?, '%')
            ORDER BY CAST(SUBSTRING(unit_number, 2) AS UNSIGNED) DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("ss", $unit_type, $prefix);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $latestNumber = (int) substr($row['unit_number'], 1);
        $nextNumber = $latestNumber + 1;
    } else {
        $nextNumber = $startNumber;
    }

    $stmt->close();
    return $prefix . $nextNumber;
}

// AJAX preview for generated unit number
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_next') {
    header('Content-Type: application/json');
    $unit_type = $_GET['unit_type'] ?? '';

    $nextUnitNumber = getNextUnitNumber($conn, $unit_type, $unitMap);

    if ($nextUnitNumber === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid unit type.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'unit_number' => $nextUnitNumber
    ]);

    $conn->close();
    exit;
}

// Actual Add Unit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_type = $_POST['unit_type'] ?? '';
    $unit_current_status = $_POST['unit_current_status'] ?? 'Ready for Occupancy';
    $owner_assignment = $_POST['owner_assignment'] ?? 'none';
    $unit_owner_id = null;

    $floor_number = isset($_POST['floor_number']) ? max(1, min(10, (int)$_POST['floor_number'])) : 1;

    $unit_number = getNextUnitNumber($conn, $unit_type, $unitMap);
    if ($unit_number === false) die("Invalid unit type.");

    // Lease rate is set by the unit owner afterward, not at creation time.
    $lease_rate = null;

    $conn->begin_transaction();

    try {
        // Existing owner selection
        if ($owner_assignment === 'existing') {
            $existing_owner_id = isset($_POST['existing_owner_id']) ? (int)$_POST['existing_owner_id'] : 0;
            if ($existing_owner_id <= 0) throw new Exception("Please select an existing unit owner.");

            $checkOwnerSql = "SELECT user_id FROM users_table WHERE user_id = ? AND user_role = 'unit owner' LIMIT 1";
            $checkOwnerStmt = $conn->prepare($checkOwnerSql);
            $checkOwnerStmt->bind_param("i", $existing_owner_id);
            $checkOwnerStmt->execute();
            $checkOwnerResult = $checkOwnerStmt->get_result();
            if (!$checkOwnerResult || $checkOwnerResult->num_rows === 0) {
                throw new Exception("Selected unit owner is invalid.");
            }
            $unit_owner_id = $existing_owner_id;
            $checkOwnerStmt->close();
        }

        // New owner creation
        if ($owner_assignment === 'new') {
            $new_owner_name = trim($_POST['new_owner_name'] ?? '');
            $new_owner_email = trim($_POST['new_owner_email'] ?? '');
            $new_owner_contact = trim($_POST['new_owner_contact'] ?? '');

            if ($new_owner_name === '' || $new_owner_email === '' || $new_owner_contact === '') {
                throw new Exception("Please complete all new owner fields.");
            }

            // Check duplicate email
            $checkEmailSql = "SELECT user_id FROM users_table WHERE email = ? LIMIT 1";
            $checkEmailStmt = $conn->prepare($checkEmailSql);
            $checkEmailStmt->bind_param("s", $new_owner_email);
            $checkEmailStmt->execute();
            $checkEmailResult = $checkEmailStmt->get_result();
            if ($checkEmailResult && $checkEmailResult->num_rows > 0) {
                throw new Exception("Email already exists. Please select the existing user instead.");
            }
            $checkEmailStmt->close();

            // Insert new unit owner without password
            $insertOwnerSql = "INSERT INTO users_table 
                                (full_name, email, contact, user_role, created_at, resident_status)
                               VALUES (?, ?, ?, 'unit owner', NOW(), 'Active')";
            $insertOwnerStmt = $conn->prepare($insertOwnerSql);
            $insertOwnerStmt->bind_param("sss", $new_owner_name, $new_owner_email, $new_owner_contact);
            if (!$insertOwnerStmt->execute()) throw new Exception("Owner Insert Error: " . $insertOwnerStmt->error);
            $unit_owner_id = $conn->insert_id;
            $insertOwnerStmt->close();
        }

        // Insert unit
       $insertUnitSql = "INSERT INTO units_table 
                    (unit_type, unit_number, floor_number, lease_rate, unit_owner_id, unit_current_status, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $insertUnitStmt = $conn->prepare($insertUnitSql);
        $insertUnitStmt->bind_param(
            "ssidss",
            $unit_type,
            $unit_number,
            $floor_number,
            $lease_rate,
            $unit_owner_id,
            $unit_current_status
        );
        if (!$insertUnitStmt->execute()) throw new Exception("Unit Insert Error: " . $insertUnitStmt->error);
        $insertUnitStmt->close();

        $conn->commit();
        header("Location: ../units.php?added=success");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "Add Unit Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
}

header("Location: ../units.php");
exit;
?>