<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "zepellin_test"; // CHANGE THIS

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-ensure resellling_price (and reselling_price) columns exist on units_table
if (!isset($GLOBALS['units_reselling_price_checked']) && $conn instanceof mysqli) {
    $GLOBALS['units_reselling_price_checked'] = true;
    $cols = [];
    $chkCol = @$conn->query("SHOW COLUMNS FROM units_table LIKE 'resell%price'");
    if ($chkCol) {
        while ($r = $chkCol->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
    }
    if (!in_array('resellling_price', $cols, true)) {
        @$conn->query("ALTER TABLE units_table ADD COLUMN resellling_price DECIMAL(15,2) NULL DEFAULT NULL AFTER lease_rate");
    }
    if (!in_array('reselling_price', $cols, true)) {
        @$conn->query("ALTER TABLE units_table ADD COLUMN reselling_price DECIMAL(15,2) NULL DEFAULT NULL AFTER lease_rate");
    }
}
?>