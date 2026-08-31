<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$reservation_id = $data['reservation_id'] ?? null;
$status = $data['status'] ?? null;

if (!$reservation_id || !$status) {
    echo json_encode(["error" => "Missing data"]);
    exit;
}

// VALID STATUSES ONLY
$allowed = ['approved','paid','reserved','occupied','cancelled'];

if (!in_array($status, $allowed)) {
    echo json_encode(["error" => "Invalid status"]);
    exit;
}

$sql = "
    UPDATE reservation_table
    SET status = '$status'
    WHERE reservation_id = '$reservation_id'
";

if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => $conn->error]);
}
?>