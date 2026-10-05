<?php
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'unit owner') {
    echo "<option value=''>Unauthorized</option>";
    return;
}

$owner_id = (int)$_SESSION['user_id'];

$sql = "
    SELECT unit_id, unit_number, unit_type
    FROM units_table
    WHERE unit_owner_id = ?
    ORDER BY unit_number ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<option value=''>Unable to load units</option>";
    return;
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "<option value=''>No units found</option>";
    return;
}

while ($unit = $result->fetch_assoc()) {
    $unitLabel = trim(($unit['unit_number'] ?? '') . ' - ' . ($unit['unit_type'] ?? ''));

    echo "<option value='" . (int)$unit['unit_id'] . "'>" . htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') . "</option>";
}

$stmt->close();
?>