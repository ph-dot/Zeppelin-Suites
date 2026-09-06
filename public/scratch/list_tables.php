<?php
require_once __DIR__ . '/../php_files/db.php';
$res = $conn->query("SELECT unit_id, unit_number, unit_owner_id FROM units_table LIMIT 10");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

$res2 = $conn->query("SELECT * FROM owner_approval_requests LIMIT 5");
while ($r = $res2->fetch_assoc()) {
    print_r($r);
}
