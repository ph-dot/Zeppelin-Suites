<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
$custom_password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation ID.'
    ]);
    exit;
}

$admin_id = (int)($_SESSION['user_id'] ?? 0);
$admin_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'admin';

$conn->begin_transaction();

try {
    // 1. Lock and fetch reservation details
    $sql = "
        SELECT 
            r.reservation_id,
            r.inq_id,
            r.unit_id,
            r.client_name,
            r.client_email,
            r.client_contact,
            r.payment_status,
            r.reservation_status,
            r.move_in_date,
            r.move_out_date,
            u.unit_number,
            u.unit_type,
            u.unit_current_status
        FROM reservation_table r
        INNER JOIN units_table u ON r.unit_id = u.unit_id
        WHERE r.reservation_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        throw new Exception("Reservation record not found.");
    }

    $unit_id = (int)$res['unit_id'];
    $client_name = trim($res['client_name'] ?? '');
    $client_email = trim($res['client_email'] ?? '');
    $client_contact = trim($res['client_contact'] ?? '');

    if (empty($client_email)) {
        throw new Exception("Client email is missing in the reservation record.");
    }

    // 2. Update reservation_table status to 'handover'
    $conn->query("ALTER TABLE reservation_table MODIFY COLUMN reservation_status VARCHAR(50) NOT NULL DEFAULT 'submitted'");

    $updateResSql = "
        UPDATE reservation_table
        SET reservation_status = 'handover',
            officially_booked_at = COALESCE(officially_booked_at, NOW())
        WHERE reservation_id = ?
    ";
    $stmt = $conn->prepare($updateResSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare reservation update: " . $conn->error);
    }
    $stmt->bind_param("i", $reservation_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update reservation status: " . $stmt->error);
    }
    $stmt->close();

    // 3. Update units_table status to 'Occupied'
    $updateUnitSql = "
        UPDATE units_table
        SET unit_current_status = 'Occupied'
        WHERE unit_id = ?
    ";
    $stmt = $conn->prepare($updateUnitSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare unit update: " . $conn->error);
    }
    $stmt->bind_param("i", $unit_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update unit status: " . $stmt->error);
    }
    $stmt->close();

    // 4. Update inquiry_table if linked
    if (!empty($res['inq_id'])) {
        $updateInqSql = "UPDATE inquiry_table SET status = 'officially booked' WHERE inq_id = ?";
        $inqStmt = $conn->prepare($updateInqSql);
        if ($inqStmt) {
            $inqStmt->bind_param("i", $res['inq_id']);
            $inqStmt->execute();
            $inqStmt->close();
        }
    }

    // 5. Check if user already exists in users_table by email
    $checkUserSql = "SELECT user_id, full_name, user_role, resident_status, password FROM users_table WHERE email = ? LIMIT 1";
    $userStmt = $conn->prepare($checkUserSql);
    if (!$userStmt) {
        throw new Exception("Failed to check user account: " . $conn->error);
    }
    $userStmt->bind_param("s", $client_email);
    $userStmt->execute();
    $existingUser = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    $defaultPassword = $custom_password !== '' ? $custom_password : 'password123';
    $accountAction = 'created';
    $tenantUserId = 0;

    if ($existingUser) {
        $tenantUserId = (int)$existingUser['user_id'];
        $accountAction = 'updated';

        // Update role and resident status to Active
        // If current role is not admin, ensure user_role is 'tenant'
        $newRole = strtolower($existingUser['user_role']) === 'admin' ? $existingUser['user_role'] : 'tenant';
        
        $updateUserSql = "
            UPDATE users_table 
            SET resident_status = 'Active',
                user_role = ?,
                full_name = COALESCE(NULLIF(full_name, ''), ?),
                contact = COALESCE(NULLIF(contact, ''), ?)
            WHERE user_id = ?
        ";
        $upStmt = $conn->prepare($updateUserSql);
        if ($upStmt) {
            $upStmt->bind_param("sssi", $newRole, $client_name, $client_contact, $tenantUserId);
            $upStmt->execute();
            $upStmt->close();
        }
    } else {
        // Provision new user in users_table
        $insertUserSql = "
            INSERT INTO users_table (full_name, email, password, contact, user_role, resident_status, created_at)
            VALUES (?, ?, ?, ?, 'tenant', 'Active', NOW())
        ";
        $insStmt = $conn->prepare($insertUserSql);
        if (!$insStmt) {
            throw new Exception("Failed to prepare tenant provisioning: " . $conn->error);
        }
        $insStmt->bind_param("ssss", $client_name, $client_email, $defaultPassword, $client_contact);
        if (!$insStmt->execute()) {
            throw new Exception("Failed to create tenant account: " . $insStmt->error);
        }
        $tenantUserId = $insStmt->insert_id;
        $insStmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Handover completed! Reservation is now Moved In, unit status is Occupied, and tenant account is active.',
        'account_action' => $accountAction,
        'tenant' => [
            'user_id' => $tenantUserId,
            'name' => $client_name,
            'email' => $client_email,
            'role' => 'tenant',
            'status' => 'Active',
            'password' => $defaultPassword
        ]
    ]);
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
