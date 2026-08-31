<?php
require_once __DIR__ . '/../../php_files/auth.php';
require_once __DIR__ . '/../../php_files/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = requireRole($conn, ['tenant']);
$tenantId = (int)$user['user_id'];

if (!function_exists('clean')) {
    function clean($val) {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_date_nice')) {
    function format_date_nice($date) {
        if (empty($date) || $date === '0000-00-00') return '—';
        $ts = strtotime((string)$date);
        return $ts ? date('F j, Y', $ts) : '—';
    }
}

// Fetch Tenant user info
$stmt = $conn->prepare("SELECT * FROM users_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $tenantId);
$stmt->execute();
$tenantUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

$tenantName = $tenantUser['full_name'] ?? $user['full_name'];
$tenantEmail = $tenantUser['email'] ?? '';
$tenantContact = $tenantUser['contact'] ?? '';
$tenantInitials = strtoupper(substr(trim($tenantName ?: 'T'), 0, 1));

// Fetch Tenant Lease & Unit Information
// Linked through reservation_table matching email or name
$leaseSql = "
    SELECT 
        r.reservation_id,
        r.unit_id,
        r.client_name,
        r.client_email,
        r.client_contact,
        r.move_in_date,
        r.move_out_date,
        r.price_basis,
        r.required_amount,
        r.declared_amount,
        r.payment_status,
        r.reservation_status,
        r.officially_booked_at,
        u.unit_number,
        u.unit_type,
        u.floor_number,
        u.unit_current_status,
        u.lease_rate,
        owner.user_id AS owner_id,
        owner.full_name AS owner_name,
        owner.email AS owner_email,
        owner.contact AS owner_contact
    FROM reservation_table r
    INNER JOIN units_table u ON r.unit_id = u.unit_id
    LEFT JOIN users_table owner ON u.unit_owner_id = owner.user_id
    WHERE (r.client_email = ? OR r.client_name = ?)
    ORDER BY 
        CASE 
            WHEN u.unit_current_status = 'Occupied' THEN 1
            WHEN r.officially_booked_at IS NOT NULL THEN 2
            WHEN r.reservation_status IN ('handover', 'moved in', 'reserved', 'Active', 'Approved') THEN 3
            ELSE 4
        END,
        r.reservation_id DESC
    LIMIT 1
";

$lStmt = $conn->prepare($leaseSql);
$leaseInfo = null;
if ($lStmt) {
    $lStmt->bind_param("ss", $tenantEmail, $tenantName);
    $lStmt->execute();
    $leaseRes = $lStmt->get_result();
    if ($leaseRes && $leaseRes->num_rows > 0) {
        $leaseInfo = $leaseRes->fetch_assoc();
    }
    $lStmt->close();
}

// Fallback: If no reservation matched yet, check if any unit exists or provide defaults
$unitNumber = $leaseInfo['unit_number'] ?? '—';
$unitType = $leaseInfo['unit_type'] ?? 'Standard Suite';
$unitOwnerName = $leaseInfo['owner_name'] ?? 'Zeppelin Suites Management';
$moveInDate = $leaseInfo['move_in_date'] ?? null;
$moveOutDate = $leaseInfo['move_out_date'] ?? null;
$monthlyRate = (float)($leaseInfo['lease_rate'] ?? $leaseInfo['required_amount'] ?? 0);

// Active Maintenance Requests Count for this tenant
$mCountStmt = $conn->prepare("
    SELECT COUNT(*) AS active_count 
    FROM maintenance_requests 
    WHERE (submitted_by_user_id = ? OR (unit_id = ? AND unit_id > 0))
      AND status IN ('pending', 'in progress')
");
$activeMaintenanceCount = 0;
$unitIdForQuery = (int)($leaseInfo['unit_id'] ?? 0);
if ($mCountStmt) {
    $mCountStmt->bind_param("ii", $tenantId, $unitIdForQuery);
    $mCountStmt->execute();
    $mCountRes = $mCountStmt->get_result()->fetch_assoc();
    $activeMaintenanceCount = (int)($mCountRes['active_count'] ?? 0);
    $mCountStmt->close();
}

// Calculate next rent due date
$currentDay = (int)date('j');
$currentMonth = date('F');
$currentYear = date('Y');
$lastDayOfMonth = date('t');
$rentDueDate = "{$currentMonth} {$lastDayOfMonth}, {$currentYear}";
if (!empty($moveInDate) && $moveInDate !== '0000-00-00') {
    $moveInDay = (int)date('j', strtotime($moveInDate));
    // Due on the monthly anniversary of move-in date
    $dueDay = min($moveInDay, (int)$lastDayOfMonth);
    $rentDueDate = date("F", strtotime("now")) . " {$dueDay}, " . date("Y");
}
?>
