<?php
require_once __DIR__ . '/db.php';

// Create unit_ownership_history table if it doesn't exist
$tableSql = "
CREATE TABLE IF NOT EXISTS `unit_ownership_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `ownership_status` enum('active','transferred','past') NOT NULL DEFAULT 'active',
  `transfer_type` varchar(100) DEFAULT 'Initial Assignment',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `unit_id` (`unit_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (!$conn->query($tableSql)) {
    echo "Error creating table: " . $conn->error . "\n";
    exit(1);
}
echo "Table unit_ownership_history checked/created successfully.\n";

// 1. Backfill current unit owners from units_table
$currentUnitsSql = "
    SELECT u.unit_id, u.unit_owner_id, DATE(u.created_at) AS unit_created
    FROM units_table u
    WHERE u.unit_owner_id IS NOT NULL AND u.unit_owner_id > 0
";
$resCurrent = $conn->query($currentUnitsSql);
if ($resCurrent) {
    while ($row = $resCurrent->fetch_assoc()) {
        $uId = (int)$row['unit_id'];
        $oId = (int)$row['unit_owner_id'];
        $startDate = !empty($row['unit_created']) ? $row['unit_created'] : date('Y-m-d');

        // Check if an active record already exists for this unit
        $stmtCheck = $conn->prepare("SELECT history_id FROM unit_ownership_history WHERE unit_id = ? AND owner_id = ? AND ownership_status = 'active' LIMIT 1");
        $stmtCheck->bind_param('ii', $uId, $oId);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows === 0) {
            $stmtInsert = $conn->prepare("
                INSERT INTO unit_ownership_history (unit_id, owner_id, start_date, end_date, ownership_status, transfer_type, remarks)
                VALUES (?, ?, ?, NULL, 'active', 'Current Owner', 'Backfilled active unit owner')
            ");
            $stmtInsert->bind_param('iis', $uId, $oId, $startDate);
            $stmtInsert->execute();
            $stmtInsert->close();
            echo "Backfilled active owner {$oId} for unit {$uId}\n";
        }
        $stmtCheck->close();
    }
}

// 2. Backfill historical owners from owner_approval_requests if any were associated with this unit
$historyRequestsSql = "
    SELECT r.unit_id, r.unit_owner_id, DATE(r.requested_at) AS req_date, DATE(r.responded_at) AS resp_date, u.unit_owner_id AS current_owner_id
    FROM owner_approval_requests r
    JOIN units_table u ON r.unit_id = u.unit_id
    WHERE r.unit_owner_id != u.unit_owner_id
    ORDER BY r.request_id ASC
";
$resHist = $conn->query($historyRequestsSql);
if ($resHist && $resHist->num_rows > 0) {
    while ($h = $resHist->fetch_assoc()) {
        $uId = (int)$h['unit_id'];
        $oId = (int)$h['unit_owner_id'];
        $startDate = !empty($h['req_date']) ? $h['req_date'] : '2026-01-01';
        $endDate = !empty($h['resp_date']) ? $h['resp_date'] : $startDate;

        $stmtCheck = $conn->prepare("SELECT history_id FROM unit_ownership_history WHERE unit_id = ? AND owner_id = ? LIMIT 1");
        $stmtCheck->bind_param('ii', $uId, $oId);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows === 0) {
            $stmtInsert = $conn->prepare("
                INSERT INTO unit_ownership_history (unit_id, owner_id, start_date, end_date, ownership_status, transfer_type, remarks)
                VALUES (?, ?, ?, ?, 'transferred', 'Previous Owner', 'Historical ownership from approval requests')
            ");
            $stmtInsert->bind_param('iiss', $uId, $oId, $startDate, $endDate);
            $stmtInsert->execute();
            $stmtInsert->close();
            echo "Backfilled historical owner {$oId} for unit {$uId}\n";
        }
        $stmtCheck->close();
    }
}

echo "Migration complete.\n";
