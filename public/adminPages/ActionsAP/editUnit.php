<?php
require_once __DIR__ . '/../../php_files/auth.php';
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
        // Fetch current owner before updating
        $oldOwnerId = null;
        $stmtOld = $conn->prepare("SELECT unit_owner_id FROM units_table WHERE unit_id = ? LIMIT 1");
        if ($stmtOld) {
            $stmtOld->bind_param("i", $unit_id);
            $stmtOld->execute();
            $resOld = $stmtOld->get_result();
            if ($resOld && $rowOld = $resOld->fetch_assoc()) {
                $oldOwnerId = $rowOld['unit_owner_id'] !== null ? (int)$rowOld['unit_owner_id'] : null;
            }
            $stmtOld->close();
        }

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

        // Track ownership change in history table
        if ($oldOwnerId !== $unit_owner_id) {
            $today = date('Y-m-d');
            if ($oldOwnerId !== null && $oldOwnerId > 0) {
                $stmtClose = $conn->prepare("UPDATE unit_ownership_history SET end_date = ?, ownership_status = 'transferred' WHERE unit_id = ? AND owner_id = ? AND ownership_status = 'active'");
                if ($stmtClose) {
                    $stmtClose->bind_param("sii", $today, $unit_id, $oldOwnerId);
                    $stmtClose->execute();
                    $stmtClose->close();
                }
            }
            if ($unit_owner_id !== null && $unit_owner_id > 0) {
                $stmtHist = $conn->prepare("INSERT INTO unit_ownership_history (unit_id, owner_id, start_date, end_date, ownership_status, transfer_type, remarks) VALUES (?, ?, ?, NULL, 'active', 'Admin Reassignment', 'Updated via edit unit form')");
                if ($stmtHist) {
                    $stmtHist->bind_param("iis", $unit_id, $unit_owner_id, $today);
                    $stmtHist->execute();
                    $stmtHist->close();
                }
            }
        }

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