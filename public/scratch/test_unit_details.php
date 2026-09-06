<?php
require_once __DIR__ . '/../php_files/db.php';

// Test 1: Check unit_ownership_history table records
$res = $conn->query("SELECT * FROM unit_ownership_history LIMIT 5");
echo "Ownership History Records Count: " . $res->num_rows . "\n";
while ($r = $res->fetch_assoc()) {
    echo "Unit ID: {$r['unit_id']} | Owner ID: {$r['owner_id']} | Status: {$r['ownership_status']} | Start: {$r['start_date']} | End: " . ($r['end_date'] ?? 'Current') . "\n";
}

// Test 2: Check all tenants for Unit 1
$stmt = $conn->prepare("
    SELECT r.reservation_id, r.client_name, r.move_in_date, r.move_out_date,
        (SELECT uo.full_name FROM unit_ownership_history h JOIN users_table uo ON h.owner_id = uo.user_id WHERE h.unit_id = r.unit_id AND (h.start_date <= r.move_in_date AND (h.end_date IS NULL OR h.end_date >= r.move_in_date)) LIMIT 1) as owner_during_stay
    FROM reservation_table r
    WHERE r.unit_id = 1
");
$stmt->execute();
$resTenants = $stmt->get_result();
echo "\nTenants for Unit 1: " . $resTenants->num_rows . " found.\n";
while ($t = $resTenants->fetch_assoc()) {
    echo "Reservation #{$t['reservation_id']} | Tenant: {$t['client_name']} | Owner during stay: " . ($t['owner_during_stay'] ?? 'Current Owner') . " | In: {$t['move_in_date']} | Out: {$t['move_out_date']}\n";
}

// Test 3: Verify View button in getunits.php
ob_start();
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;
include __DIR__ . '/../adminPages/ActionsAP/getunits.php';
$output = ob_get_clean();

if (strpos($output, 'href="unitDetails.php?unit_id=') !== false) {
    echo "\nSUCCESS: getunits.php links to admin unitDetails.php\n";
} else {
    echo "\nERROR: getunits.php does NOT link to admin unitDetails.php\n";
}

if (strpos($output, '../unitOwnerPages/unitDetails.php') === false) {
    echo "SUCCESS: No stale links to unitOwnerPages/unitDetails.php\n";
} else {
    echo "ERROR: Found stale link to unitOwnerPages/unitDetails.php\n";
}
