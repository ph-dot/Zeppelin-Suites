<?php
require_once __DIR__ . '/../../php_files/admin_auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $unit_id = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
    if ($unit_id <= 0) die("Invalid unit ID");

    // Detect action via hidden input or button name
    $action = $_POST['action_type'] ?? (isset($_POST['update_unit']) ? 'update' : (isset($_POST['delete_unit']) ? 'delete' : 'update'));

    $floor_number = isset($_POST['floor_number']) ? max(1, min(10, (int)$_POST['floor_number'])) : 1;
    $unit_type = $_POST['unit_type'] ?? '';
    $lease_rate = $_POST['lease_rate'] !== '' ? (float) $_POST['lease_rate'] : 0;
    $unit_current_status = trim($_POST['unit_current_status'] ?? 'Ready for Occupancy');

    // =========================
    // HANDLE UNIT OWNER LOGIC
    // =========================
    $raw_owner = $_POST['unit_owner_id'] ?? '';
    $unit_owner_id = null;

    if ($raw_owner === 'new') {
        // Create new unit owner first
        $new_name = trim($_POST['new_owner_name'] ?? '');
        $new_email = trim($_POST['new_owner_email'] ?? '');
        $new_contact = trim($_POST['new_owner_contact'] ?? '');

        if ($new_name && $new_email) {
            $insertUser = $conn->prepare("INSERT INTO users_table (full_name, email, contact, user_role, created_at) VALUES (?, ?, ?, 'unit owner', NOW())");
            if (!$insertUser) die("Prepare failed (new user): ".$conn->error);
            $insertUser->bind_param("sss", $new_name, $new_email, $new_contact);
            if (!$insertUser->execute()) die("Create new user failed: ".$insertUser->error);
            $unit_owner_id = $insertUser->insert_id; // get new user's ID
            $insertUser->close();
        }
    } elseif ($raw_owner !== '') {
        // Existing user selected
        $unit_owner_id = (int)$raw_owner;
    } else {
        // No owner selected
        $unit_owner_id = null;
    }

    // =========================
    // UPDATE UNIT
    // =========================
    if ($action === 'update') {
        $sql = "UPDATE units_table SET unit_type=?, floor_number=?, lease_rate=?, unit_current_status=?, unit_owner_id=? WHERE unit_id=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("Prepare failed (update unit): ".$conn->error);

        // Bind parameters
        $stmt->bind_param(
            "sidsii",
            $unit_type,           // s = string
            $floor_number,        // i = integer
            $lease_rate,          // d = double/decimal
            $unit_current_status, // s = string (ENUM)
            $unit_owner_id,       // i = integer
            $unit_id              // i = integer
        );

        if (!$stmt->execute()) die("Execute failed (update unit): ".$stmt->error);
        $stmt->close();

        header("Location: ../units.php");
        exit;
    }

    // =========================
    // DELETE UNIT
    // =========================
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM units_table WHERE unit_id=?");
        if (!$stmt) die("Prepare failed (delete unit): ".$conn->error);
        $stmt->bind_param("i", $unit_id);
        if (!$stmt->execute()) die("Delete failed: ".$stmt->error);
        $stmt->close();

        header("Location: ../units.php?deleted=1");
        exit;
    }
}
?>